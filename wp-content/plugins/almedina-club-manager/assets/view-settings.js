/* Configuración: versión, copia de seguridad en JSON y recomendaciones de despliegue. */
(function () {
  'use strict';
  var A = window.Almedina, esc = A.esc;

  function render(root) {
    root.innerHTML = '<h1>Configuración</h1>' +
      '<section class="card"><h2>Estado</h2><p>Almedina Club Manager <strong>v' + esc(A.cfg.version) + '</strong> · Usuario: ' + esc(A.cfg.user) + '</p>' +
      '<p class="muted">El acceso está limitado a cuentas de WordPress con el permiso <code>manage_almedina_calendar</code> (administradores por defecto).</p></section>' +
      '<section class="card"><h2>Copia de seguridad</h2><p>Descarga todos los datos del plugin (calendario, tiradores, torneos, inscripciones y asaltos) en un fichero JSON. Hazlo antes de cualquier actualización.</p>' +
      '<div class="btnrow"><button class="btn primary" id="backup">⬇ Descargar copia (JSON)</button></div><p id="bkmsg" class="muted"></p></section>' +
      '<section class="card"><h2>Notas</h2><ul><li>Los datos se guardan en tablas propias de la base de datos de WordPress (<code>{prefijo}almedina_*</code>).</li><li>Desactivar el plugin no borra datos.</li><li>El enlace público de cada torneo es de solo lectura y no es adivinable; quien lo tenga puede ver nombres, club y resultados de ese torneo.</li></ul></section>';
    A.$('#backup', root).onclick = function () {
      A.request('/backup').then(function (data) {
        var counts = Object.keys(data.tables).map(function (k) { return k + ': ' + data.tables[k].length; }).join(' · ');
        A.download('almedina-backup-' + new Date().toISOString().slice(0, 10) + '.json', JSON.stringify(data, null, 1));
        A.$('#bkmsg', root).textContent = 'Copia descargada (' + counts + ')';
      }).catch(function (e) { A.$('#bkmsg', root).textContent = 'Error: ' + e.message; });
    };
  }

  A.views.configuracion = { title: 'Configuración', render: render };
})();
