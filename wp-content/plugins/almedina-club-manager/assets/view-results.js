/* Resultados: clasificaciones, poules y cuadros de los torneos (solo lectura, imprimible). */
(function () {
  'use strict';
  var A = window.Almedina, esc = A.esc, S = A.S, E = A.E;

  function list(root) {
    root.innerHTML = '<h1>Resultados</h1><p class="muted">Cargando…</p>';
    return A.request('/tournaments').then(function (rows) {
      var shown = rows.filter(function (t) { return t.status !== 'setup'; });
      root.innerHTML = '<h1>Resultados</h1><p class="muted">Torneos con poules generadas</p>' +
        (shown.length ? '<div class="tablewrap"><table class="grid"><thead><tr><th>Torneo</th><th>Fecha</th><th>Arma</th><th>Estado</th></tr></thead><tbody>' + shown.map(function (t) {
          return '<tr><td><a href="#/resultados/' + t.id + '"><strong>' + esc(t.name) + '</strong></a></td><td>' + esc(S.fmtDate(t.tdate)) + '</td><td>' + esc(S.WEAPON[t.weapon] || t.weapon) + '</td><td>' + A.statusChip(t.status) + '</td></tr>';
        }).join('') + '</tbody></table></div>' : '<p class="empty">Todavía no hay resultados. Genera las poules de un torneo en el gestor.</p>');
    }).catch(function (e) { root.innerHTML = '<h1>Resultados</h1>' + A.errorBox(e); });
  }

  function detail(root, id) {
    root.innerHTML = '<p class="muted">Cargando…</p>';
    return A.request('/tournaments/' + id).then(function (d) {
      var t = d.tournament, map = S.fencerMap(d.participants), n = S.nameOf(map);
      var groups = S.pouleGroups(d.bouts);
      var seedOf = function (fid) { var p = d.participants.find(function (x) { return x.fencer_id === fid; }); return p ? p.seed : null; };
      var de = d.bouts.filter(function (b) { return b.phase === 'de'; });
      var html = '<p class="no-print"><a href="#/resultados">← Todos los resultados</a></p><div class="heading"><div><h1>' + esc(t.name) + '</h1><p class="muted">' + esc(S.fmtDate(t.tdate)) + ' · ' + esc(S.WEAPON[t.weapon]) + ' · ' + esc(t.category) + ' · ' + A.statusChip(t.status) + '</p></div><div class="btnrow no-print"><button class="btn" id="printr">🖨 Imprimir / PDF</button><a class="btn" href="#/torneos/' + t.id + '">Gestionar</a></div></div>';
      if (de.length && t.status === 'finished') {
        html += '<section class="card"><h2>Clasificación final</h2>' + S.placingsHtml(E.bracketPlacings(de.map(S.toEngine)), n) + '</section>';
      }
      if (groups.length) {
        html += '<section class="card"><h2>Clasificación tras las poules</h2>' + S.rankingHtml(E.pouleRanking(groups, seedOf), n) + '</section>' +
          '<section class="card"><h2>Poules</h2>' + groups.map(function (g) { return S.pouleMatrixHtml(g, n, E); }).join('') + '</section>';
      } else html += '<p class="empty">Las poules aún no se han generado.</p>';
      if (de.length) html += '<section class="card"><h2>Cuadro de eliminación directa</h2>' + S.bracketHtml(de, n) + '</section>';
      root.innerHTML = html;
      A.$('#printr', root).onclick = function () { window.print(); };
    }).catch(function (e) { root.innerHTML = '<p><a href="#/resultados">← Resultados</a></p>' + A.errorBox(e); });
  }

  A.views.resultados = { title: 'Resultados', render: function (root, params) { return params[0] ? detail(root, parseInt(params[0], 10)) : list(root); } };
})();
