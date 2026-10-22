/* Marcador móvil: tocados, cronómetro por periodos, tarjetas, prioridad y guardado en los asaltos del torneo. */
(function () {
  'use strict';
  var A = window.Almedina, esc = A.esc, S = A.S;
  var root, st = null, tick = null, saveTimer = null, wake = null;

  var PRESETS = {
    poule: { label: 'Poule', periods: 1, periodSec: 180, breakSec: 0, target: 5 },
    de: { label: 'Eliminación directa', periods: 3, periodSec: 180, breakSec: 60, target: 15 }
  };

  /* ---------- Selector ---------- */

  function chooser(container) {
    root = container;
    root.innerHTML = '<h1>Marcador</h1><p class="muted">Elige un asalto de un torneo o usa el marcador libre.</p><p class="muted">Cargando…</p>';
    return A.request('/tournaments').then(function (list) {
      var active = list.filter(function (t) { return t.status === 'poules' || t.status === 'de'; });
      return Promise.all(active.map(function (t) { return A.request('/tournaments/' + t.id); }));
    }).then(function (details) {
      var html = '<h1>Marcador</h1><p class="muted">Elige un asalto de un torneo o usa el marcador libre.</p><div class="btnrow"><a class="btn" href="#/marcador/libre-poule">Marcador libre · Poule (1×3 min a 5)</a><a class="btn" href="#/marcador/libre-de">Marcador libre · Eliminación (3×3 min a 15)</a></div>';
      if (!details.length) html += '<p class="empty">No hay torneos en curso con asaltos pendientes.</p>';
      details.forEach(function (d) {
        var map = S.fencerMap(d.participants), n = S.nameOf(map);
        var bouts = d.bouts.filter(function (b) { return b.status !== 'done' && b.fencer_a != null && b.fencer_b != null; });
        html += '<section class="card"><h2>' + esc(d.tournament.name) + ' ' + A.statusChip(d.tournament.status) + '</h2>' +
          (bouts.length ? bouts.map(function (b) {
            return '<a class="pick" href="#/marcador/' + b.id + '"><span>' + (b.phase === 'poule' ? 'Poule ' + b.poule_no : S.roundName(b.round)) + '</span><strong>' + esc(n(b.fencer_a)) + ' vs ' + esc(n(b.fencer_b)) + '</strong>' + (b.status === 'live' ? '<span class="chip live">En juego</span>' : '') + '</a>';
          }).join('') : '<p class="empty">Sin asaltos pendientes.</p>') + '</section>';
      });
      root.innerHTML = html;
    }).catch(function (e) { root.innerHTML = '<h1>Marcador</h1>' + A.errorBox(e); });
  }

  /* ---------- Estado ---------- */

  function fresh(preset, names, bout) {
    var p = Object.assign({}, preset);
    return { cfg: p, period: 1, remaining: p.periodSec, running: false, endAt: null, onBreak: false, overtime: false,
      a: { name: names[0], score: 0, cards: [] }, b: { name: names[1], score: 0, cards: [] },
      prio: null, excluded: null, undo: [], bout: bout || null, done: false, saved: null };
  }

  function snapshot() { return JSON.stringify({ a: st.a, b: st.b, prio: st.prio, excluded: st.excluded }); }
  function pushUndo() { st.undo.push(snapshot()); if (st.undo.length > 100) st.undo.shift(); }
  function undo() {
    if (!st.undo.length) return;
    var s = JSON.parse(st.undo.pop());
    st.a = s.a; st.b = s.b; st.prio = s.prio; st.excluded = s.excluded;
    changed();
  }

  function fmt(sec) { sec = Math.max(0, Math.ceil(sec)); return Math.floor(sec / 60) + ':' + String(sec % 60).padStart(2, '0'); }

  function currentRemaining() {
    return st.running ? Math.max(0, (st.endAt - Date.now()) / 1000) : st.remaining;
  }

  /* ---------- Guardado ---------- */

  function stateForServer() {
    return { period: st.period, remaining: Math.round(currentRemaining()), onBreak: st.onBreak, overtime: st.overtime, prio: st.prio, excluded: st.excluded, cards: { a: st.a.cards, b: st.b.cards } };
  }

  function scheduleSave() {
    if (!st.bout || st.done) return;
    clearTimeout(saveTimer);
    saveTimer = setTimeout(function () {
      A.request('/bouts/' + st.bout.id, 'PUT', { score_a: st.a.score, score_b: st.b.score, status: 'live', state: stateForServer() })
        .then(function () { st.saved = 'Guardado ' + new Date().toLocaleTimeString('es-ES'); paintStatus(); })
        .catch(function (e) { st.saved = 'Sin guardar: ' + e.message; paintStatus(); });
    }, 600);
  }

  function changed() { draw(); scheduleSave(); }

  /* ---------- Acciones ---------- */

  function addScore(side, delta) {
    if (st.done) return;
    var s = st[side];
    var next = s.score + delta;
    if (next < 0 || next > st.cfg.target) return;
    pushUndo();
    s.score = next;
    changed();
    if (delta > 0 && next >= st.cfg.target) beep(); // alcanzado el máximo de tocados
  }

  function card(side, kind) {
    if (st.done) return;
    pushUndo();
    st[side].cards.push(kind);
    var other = side === 'a' ? 'b' : 'a';
    if (kind === 'red' && st[other].score < st.cfg.target) st[other].score += 1; // tarjeta roja = tocado de penalización al rival
    if (kind === 'black') st.excluded = side;
    changed();
  }

  function drawPriority() {
    if (st.done) return;
    pushUndo();
    st.prio = Math.random() < 0.5 ? 'a' : 'b';
    changed();
  }

  function startStop() {
    if (st.done) return;
    if (st.running) {
      st.remaining = currentRemaining(); st.running = false; st.endAt = null;
      releaseWake();
    } else {
      if (st.remaining <= 0) return;
      st.running = true; st.endAt = Date.now() + st.remaining * 1000;
      requestWake();
    }
    changed();
  }

  function nextPeriod() {
    if (st.done) return;
    st.running = false; st.endAt = null;
    if (st.onBreak) { st.onBreak = false; st.period += 1; st.remaining = st.cfg.periodSec; }
    else if (st.period < st.cfg.periods) { if (st.cfg.breakSec) { st.onBreak = true; st.remaining = st.cfg.breakSec; } else { st.period += 1; st.remaining = st.cfg.periodSec; } }
    changed();
  }

  function extraMinute() {
    if (st.done) return;
    st.running = false; st.endAt = null; st.overtime = true; st.remaining = 60; st.onBreak = false;
    if (!st.prio) { pushUndo(); st.prio = Math.random() < 0.5 ? 'a' : 'b'; }
    changed();
  }

  function finish() {
    if (st.done) return;
    var winner;
    if (st.excluded) winner = st.excluded === 'a' ? 'b' : 'a';
    else if (st.a.score > st.b.score) winner = 'a';
    else if (st.b.score > st.a.score) winner = 'b';
    else if (st.prio) winner = st.prio;
    else { A.toast('Empate: sortea la prioridad o juega el minuto extra', 'error'); return; }
    if (!A.confirm('¿Terminar el asalto? Gana ' + st[winner].name + ' (' + st.a.score + '–' + st.b.score + ').')) return;
    st.remaining = currentRemaining(); st.running = false; st.endAt = null; releaseWake();
    if (!st.bout) { st.done = true; st.winner = winner; draw(); return; }
    var wid = winner === 'a' ? st.bout.fencer_a : st.bout.fencer_b;
    clearTimeout(saveTimer);
    A.request('/bouts/' + st.bout.id, 'PUT', { score_a: st.a.score, score_b: st.b.score, winner: wid, status: 'done', state: stateForServer() }).then(function () {
      st.done = true; st.winner = winner; st.saved = 'Resultado guardado en el torneo'; draw();
    }).catch(function (e) { A.toast(e.message, 'error'); });
  }

  function requestWake() { try { if (navigator.wakeLock) navigator.wakeLock.request('screen').then(function (l) { wake = l; }, function () {}); } catch (e) {} }
  function releaseWake() { try { if (wake) { wake.release(); wake = null; } } catch (e) {} }
  function beep() {
    try {
      var C = window.AudioContext || window.webkitAudioContext, c = new C(), o = c.createOscillator(), g = c.createGain();
      o.frequency.value = 880; o.connect(g); g.connect(c.destination); g.gain.value = 0.2; o.start(); o.stop(c.currentTime + 0.35);
    } catch (e) {}
    try { if (navigator.vibrate) navigator.vibrate(300); } catch (e) {}
  }

  /* ---------- Pintado ---------- */

  function cardsHtml(side) {
    return st[side].cards.map(function (k) { return '<span class="card-ico ' + k + '" title="' + k + '"></span>'; }).join('');
  }

  function panel(side, label) {
    var s = st[side], win = st.done && st.winner === side;
    return '<div class="sb-side ' + side + (win ? ' win' : '') + '"><div class="sb-name">' + esc(s.name) + (st.prio === side ? ' <span class="chip prio">PRIORIDAD</span>' : '') + (st.excluded === side ? ' <span class="chip danger">EXCLUIDO</span>' : '') + '</div>' +
      '<div class="sb-score" data-score="' + side + '">' + s.score + '</div>' +
      '<div class="sb-btns"><button class="sb-plus" data-add="' + side + '" ' + (st.done ? 'disabled' : '') + '>+1</button><button class="sb-minus" data-sub="' + side + '" ' + (st.done ? 'disabled' : '') + '>−1</button></div>' +
      '<div class="sb-cards">' + cardsHtml(side) + '</div>' +
      '<div class="sb-cardbtns"><button data-card="' + side + ':yellow" class="cy" ' + (st.done ? 'disabled' : '') + '>Amarilla</button><button data-card="' + side + ':red" class="cr" ' + (st.done ? 'disabled' : '') + '>Roja</button><button data-card="' + side + ':black" class="cb" ' + (st.done ? 'disabled' : '') + '>Negra</button></div></div>';
  }

  function periodLabel() {
    if (st.onBreak) return 'Descanso';
    if (st.overtime) return 'Minuto extra';
    return 'Periodo ' + st.period + '/' + st.cfg.periods;
  }

  function draw() {
    var back = st.bout ? '<a class="btn" href="#/torneos/' + st.bout.tournament_id + '">← Torneo</a>' : '<a class="btn" href="#/marcador">← Marcador</a>';
    root.innerHTML = '<div class="sb">' +
      '<div class="sb-top">' + back + '<span class="muted" id="sb-status">' + esc(st.saved || '') + '</span></div>' +
      '<div class="sb-board">' + panel('a') + '<div class="sb-center"><div class="sb-period">' + esc(periodLabel()) + '</div><div class="sb-time" id="sb-time">' + fmt(currentRemaining()) + '</div>' +
      '<button class="sb-go ' + (st.running ? 'on' : '') + '" id="sb-go" ' + (st.done ? 'disabled' : '') + '>' + (st.running ? 'Pausa' : 'Iniciar') + '</button>' +
      '<div class="sb-meta">a ' + st.cfg.target + ' tocados</div></div>' + panel('b') + '</div>' +
      '<div class="sb-tools"><button class="btn" id="sb-undo" ' + (st.undo.length && !st.done ? '' : 'disabled') + '>↶ Deshacer</button>' +
      '<button class="btn" id="sb-next" ' + (st.done ? 'disabled' : '') + '>' + (st.onBreak ? 'Empezar periodo' : 'Siguiente periodo') + '</button>' +
      '<button class="btn" id="sb-prio" ' + (st.done ? 'disabled' : '') + '>🎲 Sortear prioridad</button>' +
      '<button class="btn" id="sb-extra" ' + (st.done ? 'disabled' : '') + '>+ Minuto extra</button>' +
      '<button class="btn primary" id="sb-finish" ' + (st.done ? 'disabled' : '') + '>Terminar asalto</button></div>' +
      (st.done ? '<p class="success center">Asalto terminado · Gana ' + esc(st[st.winner].name) + ' (' + st.a.score + '–' + st.b.score + ')</p>' : '') + '</div>';
    A.$$('[data-add]', root).forEach(function (b) { b.onclick = function () { addScore(b.dataset.add, 1); }; });
    A.$$('[data-sub]', root).forEach(function (b) { b.onclick = function () { addScore(b.dataset.sub, -1); }; });
    A.$$('[data-card]', root).forEach(function (b) { b.onclick = function () { var p = b.dataset.card.split(':'); card(p[0], p[1]); }; });
    A.$('#sb-go', root).onclick = startStop;
    A.$('#sb-undo', root).onclick = undo;
    A.$('#sb-next', root).onclick = nextPeriod;
    A.$('#sb-prio', root).onclick = drawPriority;
    A.$('#sb-extra', root).onclick = extraMinute;
    A.$('#sb-finish', root).onclick = finish;
  }

  function paintStatus() { var el = A.$('#sb-status', root); if (el) el.textContent = st.saved || ''; }

  function loop() {
    clearInterval(tick);
    tick = setInterval(function () {
      if (!st || !A.$('#sb-time', root)) { clearInterval(tick); return; }
      var t = A.$('#sb-time', root);
      var r = currentRemaining();
      t.textContent = fmt(r);
      if (st.running && r <= 0) {
        st.running = false; st.endAt = null; st.remaining = 0; beep(); releaseWake(); changed();
      }
    }, 200);
  }

  /* ---------- Entrada ---------- */

  function open(container, key) {
    root = container;
    clearInterval(tick);
    if (key === 'libre-poule' || key === 'libre-de') {
      st = fresh(PRESETS[key === 'libre-poule' ? 'poule' : 'de'], ['Tirador A', 'Tirador B'], null);
      draw(); loop(); return Promise.resolve();
    }
    root.innerHTML = '<p class="muted">Cargando asalto…</p>';
    return A.request('/tournaments').then(function (list) {
      // Localiza el torneo que contiene el asalto
      var active = list.filter(function (t) { return t.status !== 'setup'; });
      return Promise.all(active.map(function (t) { return A.request('/tournaments/' + t.id); }));
    }).then(function (all) {
      var found = null, d = null;
      all.forEach(function (x) { var b = x.bouts.find(function (o) { return String(o.id) === key; }); if (b) { found = b; d = x; } });
      if (!found) throw new Error('No se encuentra el asalto');
      if (found.fencer_a == null || found.fencer_b == null) throw new Error('El asalto aún no tiene los dos tiradores');
      var n = S.nameOf(S.fencerMap(d.participants));
      var preset = found.phase === 'poule' ? PRESETS.poule : PRESETS.de;
      var cfg = Object.assign({}, preset, { target: found.phase === 'poule' ? d.tournament.poule_max_score : d.tournament.de_max_score });
      st = fresh(cfg, [n(found.fencer_a), n(found.fencer_b)], found);
      st.a.score = found.score_a || 0; st.b.score = found.score_b || 0;
      var sv = found.state;
      if (sv) {
        st.period = sv.period || 1; st.remaining = sv.remaining != null ? sv.remaining : st.remaining; st.onBreak = !!sv.onBreak; st.overtime = !!sv.overtime;
        st.prio = sv.prio || null; st.excluded = sv.excluded || null;
        if (sv.cards) { st.a.cards = sv.cards.a || []; st.b.cards = sv.cards.b || []; }
      }
      if (found.status === 'done') { st.done = true; st.winner = found.winner === found.fencer_a ? 'a' : 'b'; st.saved = 'Asalto ya terminado'; }
      draw(); loop();
    }).catch(function (e) { root.innerHTML = '<p><a href="#/marcador">← Marcador</a></p>' + A.errorBox(e); });
  }

  A.views.marcador = {
    title: 'Marcador',
    render: function (container, params) {
      if (st && !params[0]) { clearInterval(tick); st = null; }
      return params[0] ? open(container, params[0]) : chooser(container);
    },
    leave: function () { clearInterval(tick); clearTimeout(saveTimer); releaseWake(); st = null; }
  };
})();
