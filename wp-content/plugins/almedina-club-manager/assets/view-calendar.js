/* Vista Calendario (la funcionalidad de la v0.1, conservada): listado/mensual, filtros, alta/edición/baja, impresión. */
(function () {
  'use strict';
  var A = window.Almedina, esc = A.esc;
  var events = [], org = 'TODAS', view = 'list', month = new Date().toISOString().slice(0, 7), editing = null, root;

  var date = function (s) { return new Date(s + 'T12:00:00').toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' }); };
  var visible = function () {
    return events.filter(function (e) { return org === 'TODAS' || e.organizer === org; })
      .sort(function (a, b) { return a.start_date.localeCompare(b.start_date); });
  };
  var api = function (path, method, body) { return A.request(path ? '/events' + path : '/events', method, body); };

  function template() {
    return '<div class="heading"><div><h1>Calendario de competiciones</h1><p class="muted">Temporada 2026–2027 · Eventos guardados en WordPress</p></div><button class="btn primary no-print" id="add">+ Nueva competición</button></div>' +
      '<div class="toolbar"><select id="org"><option value="TODAS">Todas las entidades</option>' +
      ['FAE', 'FADE', 'RFEE', 'OTRA'].map(function (x) { return '<option ' + (x === org ? 'selected' : '') + '>' + x + '</option>'; }).join('') +
      '</select><select id="view"><option value="list" ' + (view === 'list' ? 'selected' : '') + '>Vista listado</option><option value="month" ' + (view === 'month' ? 'selected' : '') + '>Vista mensual</option></select><span id="monthcontrols"></span><button class="btn" id="print">🖨 Imprimir / PDF</button></div>' +
      '<div class="printhead"><h1>Club de Esgrima Almedina · Competiciones</h1><p>Filtro: ' + esc(org) + '</p></div><div id="results"></div>';
  }

  function draw() {
    root.innerHTML = template();
    A.$('#org', root).onchange = function (e) { org = e.target.value; draw(); };
    A.$('#view', root).onchange = function (e) { view = e.target.value; draw(); };
    A.$('#add', root).onclick = function () { form(); };
    A.$('#print', root).onclick = function () { window.print(); };
    var mc = A.$('#monthcontrols', root);
    if (view === 'month') {
      mc.innerHTML = '<button class="btn" id="prev">‹</button> <strong>' + esc(new Date(month + '-01T12:00:00').toLocaleDateString('es-ES', { month: 'long', year: 'numeric' })) + '</strong> <button class="btn" id="next">›</button>';
      A.$('#prev', root).onclick = function () { move(-1); };
      A.$('#next', root).onclick = function () { move(1); };
    }
    renderResults();
  }

  function move(n) {
    var d = new Date(month + '-01T12:00:00');
    d.setMonth(d.getMonth() + n);
    month = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
    draw();
  }

  function renderResults() {
    var v = visible(), target = A.$('#results', root);
    if (view === 'list') {
      target.innerHTML = v.length ? v.map(function (e) {
        return '<article class="item"><div class="date">' + esc(date(e.start_date)) + '</div><div class="item-body"><strong>' + esc(e.title) + '</strong><span class="chip">' + esc(e.organizer) + '</span><span class="chip">' + esc(e.weapon) + ' · ' + esc(e.category) + '</span><small>' + esc(e.city || 'Sede pendiente') + ' · ' + esc(e.format) + ' · ' + esc(e.status) + (e.end_date !== e.start_date ? ' · Hasta ' + esc(date(e.end_date)) : '') + '</small>' +
          (e.source_url ? '<small><a href="' + esc(e.source_url) + '" target="_blank" rel="noopener noreferrer">Convocatoria / fuente ↗</a></small>' : '') +
          '</div><button class="btn no-print" data-edit="' + e.id + '">Editar</button></article>';
      }).join('') : '<p class="empty">Todavía no hay competiciones. Añade la primera con el botón superior.</p>';
    } else {
      var first = new Date(month + '-01T12:00:00'), days = new Date(first.getFullYear(), first.getMonth() + 1, 0).getDate(), pad = (first.getDay() + 6) % 7;
      var cells = ['L', 'M', 'X', 'J', 'V', 'S', 'D'].map(function (x) { return '<div class="weekday">' + x + '</div>'; }).join('') +
        new Array(pad + 1).join('<div></div>') +
        Array.apply(null, { length: days }).map(function (_, i) {
          var key = month + '-' + String(i + 1).padStart(2, '0');
          var items = v.filter(function (e) { return e.start_date <= key && e.end_date >= key; });
          return '<div class="day"><b>' + (i + 1) + '</b>' + items.map(function (e) {
            return '<button class="dot no-print" data-edit="' + e.id + '" title="Editar">' + esc(e.title) + '</button><span class="dot printhead">' + esc(e.title) + '</span>';
          }).join('') + '</div>';
        }).join('');
      target.innerHTML = '<div class="calendar">' + cells + '</div>';
    }
    A.$$('[data-edit]', target).forEach(function (b) {
      b.onclick = function () { form(events.find(function (e) { return String(e.id) === b.dataset.edit; })); };
    });
  }

  var defaults = { title: '', start_date: '', end_date: '', city: '', organizer: 'FAE', weapon: 'espada', category: 'absoluta', format: 'individual', status: 'provisional', source_url: '', notes: '' };

  function form(item) {
    editing = item || null;
    var e = Object.assign({}, defaults, item);
    var options = function (key, vals) { return '<select name="' + key + '">' + vals.map(function (v) { return '<option value="' + v + '" ' + (e[key] === v ? 'selected' : '') + '>' + v + '</option>'; }).join('') + '</select>'; };
    var input = function (key, type, required) { return '<input name="' + key + '" type="' + (type || 'text') + '" value="' + esc(e[key]) + '" ' + (required ? 'required' : '') + '>'; };
    var m = A.modal('<h2>' + (item ? 'Editar' : 'Nueva') + ' competición</h2><form id="eventform"><div class="fields"><label class="field wide">Nombre ' + input('title', 'text', true) + '</label><label class="field">Fecha de inicio ' + input('start_date', 'date', true) + '</label><label class="field">Fecha de fin ' + input('end_date', 'date') + '</label><label class="field">Ciudad ' + input('city') + '</label><label class="field">Entidad ' + options('organizer', ['FAE', 'FADE', 'RFEE', 'OTRA']) + '</label><label class="field">Arma ' + options('weapon', ['espada', 'florete', 'sable', 'mixta']) + '</label><label class="field">Categoría ' + input('category') + '</label><label class="field">Modalidad ' + options('format', ['individual', 'equipos', 'ambas']) + '</label><label class="field">Estado ' + options('status', ['provisional', 'confirmado', 'cancelado']) + '</label><label class="field wide">Enlace oficial ' + input('source_url', 'url') + '</label><label class="field wide">Notas <textarea name="notes" rows="3">' + esc(e.notes) + '</textarea></label></div><p id="formerror" class="error" role="alert"></p><div class="actions"><div>' + (item ? '<button class="btn danger" type="button" id="delete">Eliminar</button>' : '') + '</div><div><button class="btn" type="button" id="cancel">Cancelar</button> <button class="btn primary" type="submit">Guardar</button></div></div></form>', 'Editar competición');
    A.$('#cancel').onclick = m.close;
    A.$('#eventform').onsubmit = function (ev) { save(ev, m); };
    if (item) A.$('#delete').onclick = function () { remove(m); };
  }

  function refresh() {
    return api('').then(function (data) { events = data; draw(); }).catch(function (e) {
      root.innerHTML = '<h1>No se pudo cargar el calendario</h1>' + A.errorBox(e) + '<button class="btn" id="retry">Reintentar</button>';
      A.$('#retry', root).onclick = refresh;
    });
  }

  function save(ev, m) {
    ev.preventDefault();
    var data = Object.fromEntries(new FormData(ev.target).entries());
    data.end_date = data.end_date || data.start_date;
    var btn = ev.target.querySelector('[type=submit]');
    btn.disabled = true;
    api(editing ? '/' + editing.id : '', editing ? 'PUT' : 'POST', data).then(function () { m.close(); return refresh(); }).catch(function (e) {
      A.$('#formerror').textContent = e.message;
      btn.disabled = false;
    });
  }

  function remove(m) {
    if (!A.confirm('¿Eliminar esta competición?')) return;
    api('/' + editing.id, 'DELETE').then(function () { m.close(); return refresh(); }).catch(function (e) { A.$('#formerror').textContent = e.message; });
  }

  A.views.calendario = { title: 'Calendario', render: function (container) { root = container; root.innerHTML = '<p class="muted">Cargando…</p>'; return refresh(); } };
  A.cachedEvents = function () { return events; };
})();
