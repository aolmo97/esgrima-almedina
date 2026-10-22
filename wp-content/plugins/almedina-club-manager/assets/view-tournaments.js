/* Gestor de torneos: alta, inscripción, poules, resultados, clasificación y cuadro de eliminación directa. */
(function () {
  'use strict';
  var A = window.Almedina, esc = A.esc, S = A.S, E = A.E;
  var root, D = null; // D = {t, parts, bouts, fencers}

  /* ---------- Listado ---------- */

  function renderList(container) {
    root = container;
    root.innerHTML = '<p class="muted">Cargando…</p>';
    return A.request('/tournaments').then(function (rows) {
      root.innerHTML = '<div class="heading"><div><h1>Gestor de torneos</h1><p class="muted">Poules y eliminación directa de espada, florete y sable</p></div><button class="btn primary" id="newt">+ Nuevo torneo</button></div>' +
        (rows.length ? '<div class="tablewrap"><table class="grid"><thead><tr><th>Torneo</th><th>Fecha</th><th>Arma</th><th>Tiradores</th><th>Estado</th></tr></thead><tbody>' +
          rows.map(function (t) {
            return '<tr><td><a href="#/torneos/' + t.id + '"><strong>' + esc(t.name) + '</strong></a><small class="block muted">' + esc(t.category) + '</small></td><td>' + esc(S.fmtDate(t.tdate)) + '</td><td>' + esc(S.WEAPON[t.weapon] || t.weapon) + '</td><td>' + t.participants + '</td><td>' + A.statusChip(t.status) + '</td></tr>';
          }).join('') + '</tbody></table></div>' : '<p class="empty">Todavía no hay torneos. Crea el primero.</p>');
      A.$('#newt', root).onclick = function () { tournamentForm(); };
    }).catch(function (e) { root.innerHTML = '<h1>Gestor de torneos</h1>' + A.errorBox(e); });
  }

  function tournamentForm(t) {
    var cur = Object.assign({ name: '', tdate: '', weapon: 'espada', category: 'absoluta', poule_max_score: 5, de_max_score: 15, event_id: null }, t);
    var locked = t && t.status !== 'setup';
    A.request('/events').then(function (events) { return events; }, function () { return []; }).then(function (events) {
      var m = A.modal('<h2>' + (t ? 'Editar' : 'Nuevo') + ' torneo</h2><form id="tform"><div class="fields">' +
        '<label class="field wide">Nombre<input name="name" required value="' + esc(cur.name) + '"></label>' +
        '<label class="field">Fecha<input name="tdate" type="date" required value="' + esc(cur.tdate) + '"></label>' +
        '<label class="field">Arma<select name="weapon" ' + (locked ? 'disabled' : '') + '>' + A.weaponOptions(cur.weapon) + '</select></label>' +
        '<label class="field">Categoría<input name="category" value="' + esc(cur.category) + '"></label>' +
        '<label class="field">Tocados en poule<input name="poule_max_score" type="number" min="1" max="45" value="' + cur.poule_max_score + '" ' + (locked ? 'disabled' : '') + '></label>' +
        '<label class="field">Tocados en eliminación<input name="de_max_score" type="number" min="1" max="45" value="' + cur.de_max_score + '" ' + (locked ? 'disabled' : '') + '></label>' +
        '<label class="field wide">Competición del calendario (opcional)<select name="event_id"><option value="">— ninguna —</option>' + events.map(function (e) { return '<option value="' + e.id + '" ' + (cur.event_id === e.id ? 'selected' : '') + '>' + esc(S.fmtDate(e.start_date) + ' · ' + e.title) + '</option>'; }).join('') + '</select></label>' +
        '</div><p id="formerror" class="error" role="alert"></p><div class="actions"><div></div><div><button class="btn" type="button" id="cancel">Cancelar</button> <button class="btn primary" type="submit">Guardar</button></div></div></form>', 'Torneo');
      A.$('#cancel').onclick = m.close;
      A.$('#tform').onsubmit = function (ev) {
        ev.preventDefault();
        var fd = new FormData(ev.target);
        var body = { name: fd.get('name'), tdate: fd.get('tdate'), category: fd.get('category') || 'absoluta', event_id: fd.get('event_id') ? parseInt(fd.get('event_id'), 10) : null,
          weapon: locked ? cur.weapon : fd.get('weapon'), poule_max_score: locked ? cur.poule_max_score : parseInt(fd.get('poule_max_score'), 10), de_max_score: locked ? cur.de_max_score : parseInt(fd.get('de_max_score'), 10) };
        A.request(t ? '/tournaments/' + t.id : '/tournaments', t ? 'PUT' : 'POST', body).then(function (r) {
          m.close();
          location.hash = '#/torneos/' + (t ? t.id : r.id);
          if (t) A.router();
        }).catch(function (e) { A.$('#formerror').textContent = e.message; });
      };
    });
  }

  /* ---------- Detalle ---------- */

  function load(id) {
    return Promise.all([A.request('/tournaments/' + id), A.fetchFencers()]).then(function (r) {
      D = { t: r[0].tournament, parts: r[0].participants, bouts: r[0].bouts, fencers: r[1] };
      return D;
    });
  }

  function reload() {
    var y = window.scrollY;
    return load(D.t.id).then(function () { draw(); window.scrollTo(0, y); });
  }

  function nameOf() { return S.nameOf(S.fencerMap(D.parts)); }
  function seedOf(id) { var p = D.parts.find(function (x) { return x.fencer_id === id; }); return p ? p.seed : null; }

  function ranking() { return E.pouleRanking(S.pouleGroups(D.bouts), seedOf); }

  function draw() {
    var t = D.t, n = nameOf();
    var groups = S.pouleGroups(D.bouts);
    var deBouts = D.bouts.filter(function (b) { return b.phase === 'de'; });
    var allPouleDone = groups.length > 0 && groups.every(function (g) { return g.bouts.every(function (b) { return b.status === 'done'; }); });
    var anyPouleDone = groups.some(function (g) { return g.bouts.some(function (b) { return b.status === 'done'; }); });
    var url = A.publicUrl(t.public_token);

    var html = '<p><a href="#/torneos">← Todos los torneos</a></p><div class="heading"><div><h1>' + esc(t.name) + '</h1><p class="muted">' + esc(S.fmtDate(t.tdate)) + ' · ' + esc(S.WEAPON[t.weapon]) + ' · ' + esc(t.category) + ' · ' + A.statusChip(t.status) + '</p></div>' +
      '<div class="btnrow no-print"><button class="btn" id="editt">Editar</button><button class="btn danger" id="delt">Eliminar</button></div></div>' +
      '<div class="card"><label class="field">Enlace público de resultados (solo lectura, para otros dispositivos)<span class="copyrow"><input id="pubUrl" readonly value="' + esc(url) + '"><button class="btn" id="copyUrl" type="button">Copiar</button><a class="btn" href="' + esc(url) + '" target="_blank" rel="noopener">Abrir</a></span></label></div>';

    if (t.status === 'setup') html += setupSection();
    if (groups.length) {
      html += '<section class="card"><h2>Poules</h2>' + groups.map(function (g) { return pouleSection(g, n, t.status === 'poules'); }).join('') + '</section>';
      html += '<section class="card"><h2>Clasificación tras las poules</h2>' + S.rankingHtml(ranking(), n) + '</section>';
    }
    if (t.status === 'poules') {
      html += '<section class="card"><h2>Siguiente fase</h2>' +
        (allPouleDone ? '<p>Todas las poules están terminadas.</p>' : '<p class="muted">Termina todos los asaltos de poule para continuar.</p>') +
        '<div class="btnrow"><button class="btn primary" id="genDE" ' + (allPouleDone ? '' : 'disabled') + '>Generar cuadro de eliminación directa</button><button class="btn" id="finish" ' + (allPouleDone ? '' : 'disabled') + '>Finalizar torneo (solo poules)</button>' +
        (!anyPouleDone ? '<button class="btn" id="regen">Volver a generar poules</button>' : '') + '</div><p id="phaseerror" class="error" role="alert"></p></section>';
    }
    if (deBouts.length) {
      html += '<section class="card"><h2>Eliminación directa</h2>' + S.bracketHtml(deBouts, n, function (b) { return deControls(b); }) + '</section>';
      var pl = E.bracketPlacings(deBouts.map(S.toEngine));
      if (pl.length && t.status === 'finished') html += '<section class="card"><h2>Clasificación final</h2>' + S.placingsHtml(pl, n) + '</section>';
    } else if (t.status === 'finished') {
      html += '<p class="muted">Torneo finalizado (clasificación de poules).</p>';
    }
    root.innerHTML = html;
    bind(allPouleDone);
  }

  /* ---------- Inscripción ---------- */

  function setupSection() {
    var chosen = {};
    D.parts.forEach(function (p) { chosen[p.fencer_id] = p.seed; });
    var active = D.fencers.filter(function (f) { return f.active || chosen[f.id] !== undefined; });
    var n = Object.keys(chosen).length;
    return '<section class="card"><h2>Inscripción de tiradores</h2>' +
      '<p class="muted">Marca a los tiradores y, si quieres, indica su cabeza de serie (1 = mejor). Sin número, se ordenan por apellido tras los que lo tienen.</p>' +
      '<form id="quickf" class="toolbar"><input name="first_name" placeholder="Nombre" required><input name="last_name" placeholder="Apellidos" required><input name="club" placeholder="Club"><button class="btn" type="submit">+ Alta rápida</button></form>' +
      (active.length ? '<div class="enrol">' + active.map(function (f) {
        return '<label class="enrol-row"><input type="checkbox" data-f="' + f.id + '" ' + (chosen[f.id] !== undefined ? 'checked' : '') + '><span class="grow"><strong>' + esc(S.fencerName(f)) + '</strong> <small class="muted">' + esc(f.club) + '</small></span><input class="seed" type="number" min="1" placeholder="serie" data-seed="' + f.id + '" value="' + (chosen[f.id] != null ? chosen[f.id] : '') + '"></label>';
      }).join('') + '</div>' : '<p class="empty">No hay tiradores. Usa el alta rápida o la sección Tiradores.</p>') +
      '<div class="btnrow"><button class="btn" id="saveEnrol">Guardar inscritos</button>' +
      '<label class="inline">Nº de poules <input id="npoules" type="number" min="1" max="50" value="' + Math.max(1, E.pouleCount(n || 2)) + '" class="seed"></label>' +
      '<button class="btn primary" id="genPoules">Guardar y generar poules</button></div><p id="enrolerror" class="error" role="alert"></p></section>';
  }

  function enrolmentFromForm() {
    var rows = A.$$('.enrol-row', root).filter(function (r) { return A.$('input[type=checkbox]', r).checked; }).map(function (r) {
      var id = parseInt(A.$('input[type=checkbox]', r).dataset.f, 10), sv = A.$('input.seed', r).value;
      var f = D.fencers.find(function (x) { return x.id === id; });
      return { id: id, seed: sv ? parseInt(sv, 10) : null, name: S.fencerName(f) };
    });
    rows.sort(function (a, b) {
      var sa = a.seed == null ? Infinity : a.seed, sb = b.seed == null ? Infinity : b.seed;
      return sa !== sb ? sa - sb : a.name.localeCompare(b.name, 'es');
    });
    return rows.map(function (r) { return r.id; });
  }

  function saveEnrolment() {
    return A.request('/tournaments/' + D.t.id + '/participants', 'PUT', { fencer_ids: enrolmentFromForm() });
  }

  /* ---------- Poules ---------- */

  function scoreInputs(b, max) {
    return '<input class="sc" type="number" min="0" max="' + max + '" data-s="a" value="' + (b.score_a == null ? '' : b.score_a) + '" aria-label="Tocados A"> <span>–</span> <input class="sc" type="number" min="0" max="' + max + '" data-s="b" value="' + (b.score_b == null ? '' : b.score_b) + '" aria-label="Tocados B">';
  }

  function pouleSection(g, n, editable) {
    var max = D.t.poule_max_score;
    return '<div class="poule"><h3>Poule ' + g.no + '</h3>' + S.pouleMatrixHtml(g, n, E) +
      '<div class="bouts">' + g.bouts.map(function (b, i) {
        var tie = b.status === 'done' && b.score_a === b.score_b;
        return '<div class="boutrow ' + b.status + '" data-bout="' + b.id + '" data-a="' + b.a + '" data-b="' + b.b + '"><span class="num">' + (i + 1) + '</span><span class="nm r">' + esc(n(b.a)) + '</span>' +
          (editable ? scoreInputs(b, max) : '<b>' + (b.score_a == null ? '' : b.score_a) + ' – ' + (b.score_b == null ? '' : b.score_b) + '</b>') +
          '<span class="nm">' + esc(n(b.b)) + '</span>' +
          (editable ? '<select class="prio" title="Ganador por prioridad si hay empate" ' + (tie ? '' : 'hidden') + '><option value="">Prioridad…</option><option value="a" ' + (b.winner === b.a ? 'selected' : '') + '>Gana A</option><option value="b" ' + (b.winner === b.b ? 'selected' : '') + '>Gana B</option></select>' +
            '<button class="btn save">' + (b.status === 'done' ? 'Corregir' : 'Guardar') + '</button><a class="btn" href="#/marcador/' + b.id + '" title="Abrir en el marcador">⏱</a>' : '') +
          (b.status === 'live' ? '<span class="chip live">En juego</span>' : '') + '</div>';
      }).join('') + '</div></div>';
  }

  function deControls(b) {
    if (b.fencer_a == null || b.fencer_b == null) return '';
    var max = D.t.de_max_score, bo = S.toEngine(b), tie = b.status === 'done' && b.score_a === b.score_b;
    if (D.t.status === 'finished') return '';
    return '<div class="boutrow compact ' + b.status + '" data-bout="' + b.id + '" data-a="' + b.fencer_a + '" data-b="' + b.fencer_b + '">' + scoreInputs(bo, max) +
      '<select class="prio" ' + (tie ? '' : 'hidden') + '><option value="">Prioridad…</option><option value="a" ' + (b.winner === b.fencer_a ? 'selected' : '') + '>Gana A</option><option value="b" ' + (b.winner === b.fencer_b ? 'selected' : '') + '>Gana B</option></select>' +
      '<button class="btn save">' + (b.status === 'done' ? 'Corregir' : 'Guardar') + '</button><a class="btn" href="#/marcador/' + b.id + '" title="Abrir en el marcador">⏱</a></div>';
  }

  function saveBout(row) {
    var id = parseInt(row.dataset.bout, 10), a = parseInt(row.dataset.a, 10), bb = parseInt(row.dataset.b, 10);
    var sa = A.$('input[data-s=a]', row).value, sb = A.$('input[data-s=b]', row).value;
    var err = function (m) { A.toast(m, 'error'); };
    if (sa === '' || sb === '') return err('Introduce los tocados de los dos tiradores');
    sa = parseInt(sa, 10); sb = parseInt(sb, 10);
    var winner = sa > sb ? a : (sb > sa ? bb : null);
    if (winner == null) {
      var pr = A.$('.prio', row).value;
      if (!pr) { A.$('.prio', row).hidden = false; return err('Empate: indica quién gana por prioridad'); }
      winner = pr === 'a' ? a : bb;
    }
    var max = (D.bouts.find(function (x) { return x.id === id; }) || {}).phase === 'de' ? D.t.de_max_score : D.t.poule_max_score;
    var msg = E.validateResult({ a: a, b: bb, score_a: sa, score_b: sb, winner: winner }, max);
    if (msg) return err(msg);
    A.request('/bouts/' + id, 'PUT', { score_a: sa, score_b: sb, winner: winner, status: 'done' }).then(reload).catch(function (e) { err(e.message); });
  }

  /* ---------- Eventos ---------- */

  function bind(allPouleDone) {
    var t = D.t;
    A.$('#editt', root).onclick = function () { tournamentForm(t); };
    A.$('#delt', root).onclick = function () {
      if (!A.confirm('¿Eliminar el torneo "' + t.name + '" con todos sus asaltos? No se puede deshacer.')) return;
      A.request('/tournaments/' + t.id, 'DELETE').then(function () { location.hash = '#/torneos'; }).catch(function (e) { A.toast(e.message, 'error'); });
    };
    A.$('#copyUrl', root).onclick = function () {
      var inp = A.$('#pubUrl', root); inp.select();
      (navigator.clipboard ? navigator.clipboard.writeText(inp.value) : Promise.reject()).then(function () { A.toast('Enlace copiado'); }, function () { document.execCommand('copy'); A.toast('Enlace copiado'); });
    };
    A.$$('.boutrow .save', root).forEach(function (btn) { btn.onclick = function () { saveBout(btn.closest('.boutrow')); }; });
    A.$$('.boutrow input.sc', root).forEach(function (inp) {
      inp.oninput = function () {
        var row = inp.closest('.boutrow'), a = A.$('input[data-s=a]', row).value, b = A.$('input[data-s=b]', row).value;
        A.$('.prio', row).hidden = !(a !== '' && a === b);
      };
    });
    var q = function (id, fn) { var el = A.$(id, root); if (el) el.onclick = fn; };

    q('#saveEnrol', function () {
      saveEnrolment().then(function (r) { A.toast(r.count + ' tiradores inscritos'); return reload(); }).catch(function (e) { A.$('#enrolerror', root).textContent = e.message; });
    });
    var quick = A.$('#quickf', root);
    if (quick) quick.onsubmit = function (ev) {
      ev.preventDefault();
      var fd = new FormData(quick);
      var keep = enrolmentFromForm();
      A.request('/fencers', 'POST', { first_name: fd.get('first_name'), last_name: fd.get('last_name'), club: fd.get('club') || '', weapons: [D.t.weapon] }).then(function (r) {
        return A.request('/tournaments/' + D.t.id + '/participants', 'PUT', { fencer_ids: keep.concat([r.id]) });
      }).then(reload).catch(function (e) { A.$('#enrolerror', root).textContent = e.message; });
    };
    q('#genPoules', function () {
      var k = parseInt(A.$('#npoules', root).value, 10) || 1;
      saveEnrolment().then(function () { return load(D.t.id); }).then(function () {
        var list = D.parts.map(function (p) { return { id: p.fencer_id, club: p.club, seed: p.seed }; });
        if (list.length < 2) throw new Error('Hacen falta al menos 2 tiradores inscritos');
        if (k > Math.floor(list.length / 2)) throw new Error('Demasiadas poules para ' + list.length + ' tiradores (máximo ' + Math.floor(list.length / 2) + ')');
        return A.request('/tournaments/' + D.t.id + '/poules', 'POST', { poules: E.generatePoules(list, { poules: k }) });
      }).then(reload).catch(function (e) { A.$('#enrolerror', root).textContent = e.message; });
    });
    q('#regen', function () {
      if (!A.confirm('Se volverán a sortear las poules con los mismos tiradores (aún no hay resultados). ¿Continuar?')) return;
      A.request('/tournaments/' + D.t.id + '/poules', 'POST', { poules: E.generatePoules(D.parts.map(function (p) { return { id: p.fencer_id, club: p.club, seed: p.seed }; }), { poules: Math.max(1, S.pouleGroups(D.bouts).length) }) }).then(reload).catch(function (e) { A.toast(e.message, 'error'); });
    });
    q('#genDE', function () {
      var ids = ranking().map(function (r) { return r.id; });
      A.request('/tournaments/' + D.t.id + '/de', 'POST', { bracket: E.generateBracket(ids) }).then(reload).catch(function (e) { A.$('#phaseerror', root).textContent = e.message; });
    });
    q('#finish', function () {
      if (!A.confirm('¿Finalizar el torneo con la clasificación de las poules?')) return;
      A.request('/tournaments/' + D.t.id + '/finish', 'POST', {}).then(reload).catch(function (e) { A.$('#phaseerror', root).textContent = e.message; });
    });
  }

  A.views.torneos = {
    title: 'Gestor de torneos',
    render: function (container, params) {
      root = container;
      if (!params[0]) return renderList(container);
      root.innerHTML = '<p class="muted">Cargando…</p>';
      return load(parseInt(params[0], 10)).then(draw).catch(function (e) { root.innerHTML = '<p><a href="#/torneos">← Todos los torneos</a></p>' + A.errorBox(e); });
    }
  };
})();
