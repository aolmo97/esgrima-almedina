/* Documentos imprimibles / PDF de un torneo: lista de inscritos, hojas de poule y cuadro en blanco (para hacer a mano) y resultados. */
(function () {
  'use strict';
  var A = window.Almedina, esc = A.esc, S = A.S, E = A.E;

  var TITLES = {
    lista: 'Lista de inscritos',
    poules: 'Hojas de poule (para rellenar a mano)',
    cuadro: 'Cuadro de eliminación directa (para rellenar a mano)',
    resultados: 'Resultados'
  };

  function head(t, title) {
    return '<div class="pdoc-head">' + S.brandHtml(A.cfg.brand, 'print') +
      '<div class="pdoc-title"><h1>' + esc(title) + '</h1><p>' + esc(t.name) + ' · ' + esc(S.fmtDate(t.tdate)) + ' · ' + esc(S.WEAPON[t.weapon] || t.weapon) + ' · ' + esc(t.category) + '</p></div></div>';
  }

  /* ---- Lista de inscritos ---- */
  function lista(d, n) {
    var rows = d.participants.map(function (p, i) {
      return '<tr><td>' + (p.seed != null ? p.seed : i + 1) + '</td><td class="left">' + esc(n(p.fencer_id)) + '</td><td class="left">' + esc(p.club || '') + '</td><td class="w"></td><td class="w wide"></td></tr>';
    });
    // Líneas vacías para altas de última hora
    for (var k = 0; k < 4; k++) rows.push('<tr><td></td><td class="left w"></td><td class="w"></td><td class="w"></td><td class="w"></td></tr>');
    return '<div class="pdoc-page"><table class="grid pdoc-table"><thead><tr><th>Serie</th><th>Tirador</th><th>Club</th><th>Presente</th><th>Observaciones</th></tr></thead><tbody>' + rows.join('') + '</tbody></table></div>';
  }

  /* ---- Hojas de poule en blanco ---- */
  function pouleSheet(g, n, fenMap) {
    var ids = g.fencers, idx = {};
    ids.forEach(function (id, i) { idx[id] = i + 1; });
    var label = function (id) { var f = fenMap[id]; return n(id) + (f && f.club ? ' (' + f.club + ')' : ''); };
    var matrix = '<table class="grid pdoc-table matrix"><thead><tr><th class="left">Poule ' + g.no + '</th>' + ids.map(function (_, i) { return '<th>' + (i + 1) + '</th>'; }).join('') + '<th>V</th><th>A</th><th>TD</th><th>TR</th><th>Ind</th><th>Puesto</th></tr></thead><tbody>' +
      ids.map(function (id, i) {
        return '<tr><th class="left">' + (i + 1) + '. ' + esc(label(id)) + '</th>' + ids.map(function (_, j) { return i === j ? '<td class="diag"></td>' : '<td class="w"></td>'; }).join('') + '<td class="w"></td><td class="w"></td><td class="w"></td><td class="w"></td><td class="w"></td><td class="w"></td></tr>';
      }).join('') + '</tbody></table>';
    var order = '<h3>Orden de asaltos</h3><table class="grid pdoc-table"><thead><tr><th>Asalto</th><th class="left">Tirador A</th><th class="left">Tirador B</th><th>Resultado</th><th>Ganador</th></tr></thead><tbody>' +
      g.bouts.map(function (b, i) {
        return '<tr><td>' + (i + 1) + '</td><td class="left">' + idx[b.a] + '. ' + esc(n(b.a)) + '</td><td class="left">' + idx[b.b] + '. ' + esc(n(b.b)) + '</td><td class="w">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;–</td><td class="w"></td></tr>';
      }).join('') + '</tbody></table>';
    return '<div class="pdoc-page"><div class="pdoc-sub"><span>Pista nº ______</span><span>Árbitro ______________________</span><span>Hora ________</span></div>' + matrix + order +
      '<p class="muted small">V = victorias · A = asaltos · TD = tocados dados · TR = tocados recibidos · Ind = TD − TR. En asalto empatado, anotar prioridad (P).</p></div>';
  }

  function poules(d, n) {
    var groups = S.pouleGroups(d.bouts), fenMap = S.fencerMap(d.participants);
    if (!groups.length) return '<p class="empty">Genera primero las poules del torneo para poder imprimir sus hojas.</p>';
    return groups.map(function (g) { return pouleSheet(g, n, fenMap); }).join('');
  }

  /* ---- Cuadro en blanco ---- */
  function cuadro(d, n) {
    var count = d.participants.length;
    if (count < 2) return '<p class="empty">Inscribe al menos 2 tiradores para imprimir el cuadro.</p>';
    var groups = S.pouleGroups(d.bouts);
    var allDone = groups.length && groups.every(function (g) { return g.bouts.every(function (b) { return b.status === 'done'; }); });
    var ids, nm, note;
    if (allDone) {
      var seedOf = function (id) { var p = d.participants.find(function (x) { return x.fencer_id === id; }); return p ? p.seed : null; };
      ids = E.pouleRanking(groups, seedOf).map(function (r) { return r.id; });
      nm = n;
      note = 'Cuadro según la clasificación final de las poules.';
    } else {
      ids = []; for (var k = 1; k <= count; k++) ids.push(-k);
      nm = function (id) { return 'Clasificado nº ' + (-id); };
      note = 'Las poules aún no han terminado: los puestos se rellenan a mano con la clasificación de las poules.';
    }
    var bouts = E.generateBracket(ids).map(function (b) { return { round: b.round, slot: b.slot, fencer_a: b.a, fencer_b: b.b, status: 'pending' }; });
    return '<div class="pdoc-page land"><p class="muted small">' + esc(note) + '</p>' + S.bracketHtml(bouts, nm, null, { blank: true }) + '</div>';
  }

  /* ---- Resultados con datos ---- */
  function resultados(d, n) {
    var groups = S.pouleGroups(d.bouts);
    var seedOf = function (id) { var p = d.participants.find(function (x) { return x.fencer_id === id; }); return p ? p.seed : null; };
    var de = d.bouts.filter(function (b) { return b.phase === 'de'; });
    var html = '';
    if (de.length && d.tournament.status === 'finished') html += '<div class="pdoc-page"><h2>Clasificación final</h2>' + S.placingsHtml(E.bracketPlacings(de.map(S.toEngine)), n) + '</div>';
    if (groups.length) html += '<div class="pdoc-page"><h2>Clasificación tras las poules</h2>' + S.rankingHtml(E.pouleRanking(groups, seedOf), n) + '<h2>Poules</h2>' + groups.map(function (g) { return S.pouleMatrixHtml(g, n, E); }).join('') + '</div>';
    if (de.length) html += '<div class="pdoc-page land"><h2>Cuadro de eliminación directa</h2>' + S.bracketHtml(de, n) + '</div>';
    return html || '<p class="empty">Todavía no hay resultados.</p>';
  }

  function render(root, params) {
    var id = parseInt(params[0], 10), tipo = TITLES[params[1]] ? params[1] : 'resultados';
    root.innerHTML = '<p class="muted">Preparando documento…</p>';
    return A.request('/tournaments/' + id).then(function (d) {
      var n = S.nameOf(S.fencerMap(d.participants));
      var body = { lista: lista, poules: poules, cuadro: cuadro, resultados: resultados }[tipo](d, n);
      root.innerHTML = '<div class="no-print printbar"><a href="#/torneos/' + id + '">← Volver al torneo</a><div class="btnrow"><button class="btn primary" id="doprint">🖨 Imprimir / Guardar como PDF</button></div>' +
        '<p class="muted small">En el diálogo de impresión elige «Guardar como PDF» como destino. Tamaño A4; el cuadro se imprime apaisado.</p></div>' +
        '<div class="pdoc">' + head(d.tournament, TITLES[tipo]) + body + '</div>';
      A.$('#doprint', root).onclick = function () { window.print(); };
    }).catch(function (e) { root.innerHTML = '<p><a href="#/torneos">← Torneos</a></p>' + A.errorBox(e); });
  }

  A.views.imprimir = { title: 'Imprimir', hidden: true, render: render };
})();
