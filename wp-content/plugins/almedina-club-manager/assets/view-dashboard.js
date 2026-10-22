/* Dashboard: resumen de próximas competiciones, tiradores y torneos. */
(function () {
  'use strict';
  var A = window.Almedina, esc = A.esc, S = A.S;

  function card(label, value, href) {
    return '<a class="stat" href="' + href + '"><span class="stat-n">' + esc(value) + '</span><span class="stat-l">' + esc(label) + '</span></a>';
  }

  function render(root) {
    root.innerHTML = '<h1>Dashboard</h1><p class="muted">Resumen del club</p><p class="muted">Cargando…</p>';
    return A.request('/dashboard').then(function (d) {
      var t = d.tournaments;
      root.innerHTML = '<h1>Dashboard</h1><p class="muted">Resumen del club</p>' +
        '<div class="stats">' +
        card('Competiciones en calendario', d.events_total, '#/calendario') +
        card('Tiradores activos', d.fencers_total, '#/tiradores') +
        card('Torneos en curso', t.poules + t.de, '#/torneos') +
        card('Torneos finalizados', t.finished, '#/resultados') + '</div>' +
        '<div class="cols"><section class="card"><h2>Próximas competiciones</h2>' +
        (d.events_upcoming.length ? d.events_upcoming.map(function (e) {
          return '<div class="row"><div class="date">' + esc(S.fmtDate(e.start_date)) + '</div><div><strong>' + esc(e.title) + '</strong><small class="block muted">' + esc(e.organizer) + ' · ' + esc(e.weapon) + ' · ' + esc(e.city || 'Sede pendiente') + '</small></div></div>';
        }).join('') : '<p class="empty">No hay competiciones próximas. Añádelas en el calendario.</p>') +
        '<p><a class="btn" href="#/calendario">Ver calendario</a></p></section>' +
        '<section class="card"><h2>Torneos en curso</h2>' +
        (d.tournaments_active.length ? d.tournaments_active.map(function (x) {
          return '<div class="row"><div class="date">' + esc(S.fmtDate(x.tdate)) + '</div><div><a href="#/torneos/' + x.id + '"><strong>' + esc(x.name) + '</strong></a><small class="block muted">' + esc(S.WEAPON[x.weapon] || x.weapon) + ' · ' + A.statusChip(x.status) + '</small></div></div>';
        }).join('') : '<p class="empty">No hay torneos en curso.</p>') +
        '<p><a class="btn primary" href="#/torneos">Gestor de torneos</a> <a class="btn" href="#/marcador">Marcador</a></p></section></div>';
    }).catch(function (e) { root.innerHTML = '<h1>Dashboard</h1>' + A.errorBox(e); });
  }

  A.views.dashboard = { title: 'Dashboard', render: render };
})();
