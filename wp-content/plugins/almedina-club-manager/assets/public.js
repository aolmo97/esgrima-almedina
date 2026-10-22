/* Página pública de resultados de un torneo (solo lectura, se refresca sola). */
(function () {
  'use strict';
  var cfg = window.ALMEDINA_PUBLIC, S = window.AlmedinaShared, E = window.AlmedinaEngine;
  var root = document.getElementById('app');

  function render(d) {
    var t = d.tournament, map = S.fencerMap(d.fencers), n = S.nameOf(map);
    var groups = S.pouleGroups(d.bouts);
    var seedOf = function (id) { var f = map[id]; return f ? f.seed : null; };
    var de = d.bouts.filter(function (b) { return b.phase === 'de'; });
    var html = '<div class="pubwrap"><header class="pubhead">' + S.brandHtml(cfg.brand, 'pub') + '<h1>' + S.esc(t.name) + '</h1><p class="muted">' + S.esc(S.fmtDate(t.tdate)) + ' · ' + S.esc(S.WEAPON[t.weapon] || t.weapon) + ' · ' + S.esc(t.category) + ' · <span class="chip st-' + S.esc(t.status) + '">' + S.esc(S.STATUS[t.status] || t.status) + '</span></p><p class="muted small">Se actualiza automáticamente cada 15 s · <button class="btn" onclick="window.print()">🖨 Imprimir</button></p></header>';
    if (de.length && t.status === 'finished') html += '<section class="card"><h2>Clasificación final</h2>' + S.placingsHtml(E.bracketPlacings(de.map(S.toEngine)), n) + '</section>';
    if (groups.length) {
      html += '<section class="card"><h2>Clasificación tras las poules</h2>' + S.rankingHtml(E.pouleRanking(groups, seedOf), n) + '</section><section class="card"><h2>Poules</h2>' + groups.map(function (g) { return S.pouleMatrixHtml(g, n, E); }).join('') + '</section>';
    } else html += '<p class="empty">Las poules aún no se han generado.</p>';
    if (de.length) html += '<section class="card"><h2>Cuadro de eliminación directa</h2>' + S.bracketHtml(de, n) + '</section>';
    html += '</div>';
    root.innerHTML = html;
  }

  function load() {
    fetch(cfg.root + '/public/' + cfg.token, { cache: 'no-store' }).then(function (r) {
      if (!r.ok) throw new Error(r.status === 404 ? 'Este enlace no existe o ha caducado.' : 'No se pudo cargar (' + r.status + ')');
      return r.json();
    }).then(render).catch(function (e) {
      if (!root.querySelector('.pubwrap')) root.innerHTML = '<div class="pubwrap"><p class="error">' + S.esc(e.message) + '</p></div>';
    });
  }

  load();
  setInterval(load, 15000);
})();
