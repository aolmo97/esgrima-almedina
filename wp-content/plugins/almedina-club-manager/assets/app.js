/* Shell del panel: menú lateral responsive y router por hash (#/seccion/parametros). */
(function () {
  'use strict';
  var A = window.Almedina, esc = A.esc, root = document.getElementById('app');

  var NAV = [
    ['dashboard', 'Dashboard', '▦'],
    ['calendario', 'Calendario', '📅'],
    ['torneos', 'Gestor de torneos', '🏆'],
    ['tiradores', 'Tiradores', '🤺'],
    ['marcador', 'Marcador', '⏱'],
    ['resultados', 'Resultados', '📊'],
    ['configuracion', 'Configuración', '⚙']
  ];
  var current = null;

  function parse() {
    var parts = (location.hash || '').replace(/^#\/?/, '').split('/').filter(Boolean);
    var name = parts[0] || 'dashboard';
    if (!A.views[name]) name = 'dashboard';
    return { name: name, params: parts.slice(1) };
  }

  function shell() {
    root.innerHTML = '<div class="layout"><aside class="sidebar" id="sidebar"><div class="brand">⚔ Almedina<small>Club Manager</small></div><nav id="nav">' +
      NAV.map(function (n) { return '<a href="#/' + n[0] + '" data-nav="' + n[0] + '"><span class="ico" aria-hidden="true">' + n[2] + '</span>' + esc(n[1]) + '</a>'; }).join('') +
      '</nav><div class="side-foot"><div class="muted small">' + esc(A.cfg.user) + '</div><a class="btn" href="' + esc(A.cfg.logout) + '">Cerrar sesión</a></div></aside>' +
      '<div class="mainwrap"><header class="topbar"><button class="btn" id="burger" aria-label="Menú" aria-expanded="false">☰</button><strong id="toptitle">Almedina</strong></header><main class="content panel" id="content"></main></div></div>' +
      '<div id="modalhost"></div><div id="toasts" aria-live="polite"></div>';
    A.$('#burger').onclick = function () {
      var open = A.$('#sidebar').classList.toggle('open');
      A.$('#burger').setAttribute('aria-expanded', open ? 'true' : 'false');
    };
    A.$('#nav').addEventListener('click', function () { A.$('#sidebar').classList.remove('open'); });
  }

  A.router = function () {
    var r = parse();
    if (current && A.views[current].leave) A.views[current].leave();
    current = r.name;
    A.$$('#nav a').forEach(function (a) { a.classList.toggle('active', a.dataset.nav === r.name); });
    A.$('#toptitle').textContent = A.views[r.name].title;
    document.title = A.views[r.name].title + ' · Almedina Gestión';
    A.$('#modalhost').innerHTML = '';
    var c = A.$('#content');
    window.scrollTo(0, 0);
    return A.views[r.name].render(c, r.params);
  };

  shell();
  window.addEventListener('hashchange', A.router);
  A.router();
})();
