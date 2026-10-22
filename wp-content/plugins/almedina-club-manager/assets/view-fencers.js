/* Tiradores: listado, búsqueda, alta, edición, activar/desactivar y borrado. */
(function () {
  'use strict';
  var A = window.Almedina, esc = A.esc, S = A.S;
  var list = [], q = '', showInactive = false, root;

  A.fetchFencers = function () { return A.request('/fencers').then(function (d) { list = d; return d; }); };

  function matches(f) {
    if (!showInactive && !f.active) return false;
    if (!q) return true;
    var hay = (f.first_name + ' ' + f.last_name + ' ' + f.club + ' ' + f.license).toLowerCase();
    return hay.indexOf(q.toLowerCase()) >= 0;
  }

  function draw() {
    var rows = list.filter(matches);
    root.innerHTML = '<div class="heading"><div><h1>Tiradores</h1><p class="muted">' + list.filter(function (f) { return f.active; }).length + ' activos</p></div><button class="btn primary" id="addf">+ Nuevo tirador</button></div>' +
      '<div class="toolbar"><input id="qf" type="search" placeholder="Buscar por nombre, club o licencia" value="' + esc(q) + '"><label class="inline"><input type="checkbox" id="inactive" ' + (showInactive ? 'checked' : '') + '> Mostrar inactivos</label></div>' +
      (rows.length ? '<div class="tablewrap"><table class="grid"><thead><tr><th>Tirador</th><th>Club</th><th>Armas</th><th>Licencia</th><th></th></tr></thead><tbody>' +
        rows.map(function (f) {
          return '<tr class="' + (f.active ? '' : 'inactive') + '"><td><strong>' + esc(S.fencerName(f)) + '</strong>' + (f.active ? '' : ' <span class="chip">inactivo</span>') + '</td><td>' + esc(f.club) + '</td><td>' + f.weapons.map(function (w) { return esc(S.WEAPON[w] || w); }).join(', ') + '</td><td>' + esc(f.license) + '</td><td class="right"><button class="btn" data-edit="' + f.id + '">Editar</button></td></tr>';
        }).join('') + '</tbody></table></div>' : '<p class="empty">' + (list.length ? 'Ningún tirador coincide con la búsqueda.' : 'Todavía no hay tiradores. Añade el primero.') + '</p>');
    A.$('#addf', root).onclick = function () { form(); };
    var qf = A.$('#qf', root);
    qf.oninput = function () { q = qf.value; var pos = qf.selectionStart; draw(); var n = A.$('#qf', root); n.focus(); n.setSelectionRange(pos, pos); };
    A.$('#inactive', root).onchange = function (e) { showInactive = e.target.checked; draw(); };
    A.$$('[data-edit]', root).forEach(function (b) { b.onclick = function () { form(list.find(function (f) { return String(f.id) === b.dataset.edit; })); }; });
  }

  function form(item) {
    var f = Object.assign({ first_name: '', last_name: '', club: '', license: '', birth_year: '', nationality: '', weapons: [], notes: '', active: true }, item);
    var input = function (k, label, type, req) { return '<label class="field">' + label + '<input name="' + k + '" type="' + (type || 'text') + '" value="' + esc(f[k] == null ? '' : f[k]) + '" ' + (req ? 'required' : '') + '></label>'; };
    var m = A.modal('<h2>' + (item ? 'Editar' : 'Nuevo') + ' tirador</h2><form id="fform"><div class="fields">' + input('first_name', 'Nombre', 'text', true) + input('last_name', 'Apellidos', 'text', true) + input('club', 'Club') + input('license', 'Licencia') + input('birth_year', 'Año de nacimiento', 'number') + input('nationality', 'Nacionalidad') +
      '<fieldset class="field wide"><legend>Armas</legend>' + ['espada', 'florete', 'sable'].map(function (w) { return '<label class="inline"><input type="checkbox" name="w_' + w + '" ' + (f.weapons.indexOf(w) >= 0 ? 'checked' : '') + '> ' + S.WEAPON[w] + '</label>'; }).join(' ') + '</fieldset>' +
      '<label class="field wide">Notas<textarea name="notes" rows="2">' + esc(f.notes) + '</textarea></label><label class="inline wide"><input type="checkbox" name="active" ' + (f.active ? 'checked' : '') + '> Activo</label></div><p id="formerror" class="error" role="alert"></p><div class="actions"><div>' + (item ? '<button class="btn danger" type="button" id="delf">Eliminar</button>' : '') + '</div><div><button class="btn" type="button" id="cancel">Cancelar</button> <button class="btn primary" type="submit">Guardar</button></div></div></form>', 'Tirador');
    A.$('#cancel').onclick = m.close;
    A.$('#fform').onsubmit = function (ev) {
      ev.preventDefault();
      var fd = new FormData(ev.target), by = fd.get('birth_year');
      var body = { first_name: fd.get('first_name'), last_name: fd.get('last_name'), club: fd.get('club'), license: fd.get('license'), nationality: fd.get('nationality'), notes: fd.get('notes'), birth_year: by ? parseInt(by, 10) : null, active: fd.get('active') === 'on', weapons: ['espada', 'florete', 'sable'].filter(function (w) { return fd.get('w_' + w) === 'on'; }) };
      A.request(item ? '/fencers/' + item.id : '/fencers', item ? 'PUT' : 'POST', body).then(function () { m.close(); return A.fetchFencers().then(draw); }).catch(function (e) { A.$('#formerror').textContent = e.message; });
    };
    if (item) A.$('#delf').onclick = function () {
      if (!A.confirm('¿Eliminar a ' + S.fencerName(item) + '?')) return;
      A.request('/fencers/' + item.id, 'DELETE').then(function () { m.close(); return A.fetchFencers().then(draw); }).catch(function (e) { A.$('#formerror').textContent = e.message; });
    };
  }

  A.views.tiradores = {
    title: 'Tiradores',
    render: function (container) {
      root = container;
      root.innerHTML = '<p class="muted">Cargando…</p>';
      return A.fetchFencers().then(draw).catch(function (e) { root.innerHTML = '<h1>Tiradores</h1>' + A.errorBox(e); });
    }
  };
})();
