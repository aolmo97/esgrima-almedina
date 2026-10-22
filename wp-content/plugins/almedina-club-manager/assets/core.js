/* Núcleo del panel: configuración, llamadas a la API, utilidades de interfaz. */
(function () {
  'use strict';
  var cfg = window.ALMEDINA_CONFIG;
  var S = window.AlmedinaShared;
  var A = window.Almedina = { cfg: cfg, E: window.AlmedinaEngine, S: S, views: {} };

  A.esc = S.esc;
  A.$ = function (sel, parent) { return (parent || document).querySelector(sel); };
  A.$$ = function (sel, parent) { return Array.prototype.slice.call((parent || document).querySelectorAll(sel)); };

  // Llamada autenticada (cookie + nonce). `path` relativo a /almedina/v1 o URL completa.
  A.request = function (path, method, body) {
    method = method || 'GET';
    var url = /^https?:/.test(path) ? path : cfg.root + path;
    var opts = { method: method, credentials: 'same-origin', headers: { 'X-WP-Nonce': cfg.nonce } };
    if (body !== undefined) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
    return fetch(url, opts).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (data) {
        if (!r.ok) { var e = new Error(data.message || ('Error de conexión (' + r.status + ')')); e.status = r.status; e.code = data.code; throw e; }
        return data;
      });
    });
  };

  A.toast = function (msg, kind) {
    var host = A.$('#toasts');
    if (!host) return;
    var el = document.createElement('div');
    el.className = 'toast ' + (kind || '');
    el.textContent = msg;
    host.appendChild(el);
    setTimeout(function () { el.remove(); }, 4000);
  };

  // Diálogo modal genérico. Devuelve {close, el}.
  A.modal = function (html, label) {
    var host = A.$('#modalhost');
    host.innerHTML = '<div class="modal"><div class="dialog" role="dialog" aria-modal="true" aria-label="' + A.esc(label || 'Diálogo') + '">' + html + '</div></div>';
    var close = function () { host.innerHTML = ''; };
    A.$('.modal', host).addEventListener('click', function (e) { if (e.target.classList.contains('modal')) close(); });
    return { close: close, el: A.$('.dialog', host) };
  };

  A.confirm = function (msg) { return window.confirm(msg); };

  A.weaponOptions = function (selected, withMixta) {
    var keys = ['espada', 'florete', 'sable'].concat(withMixta ? ['mixta'] : []);
    return keys.map(function (k) { return '<option value="' + k + '"' + (k === selected ? ' selected' : '') + '>' + S.WEAPON[k] + '</option>'; }).join('');
  };

  A.statusChip = function (st) { return '<span class="chip st-' + A.esc(st) + '">' + A.esc(S.STATUS[st] || st) + '</span>'; };

  A.errorBox = function (e) { return '<p class="error" role="alert">' + A.esc(e.message || e) + '</p>'; };

  A.download = function (filename, text, type) {
    var blob = new Blob([text], { type: type || 'application/json' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url; a.download = filename;
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
  };

  A.publicUrl = function (token) { return cfg.results_base + token + '/'; };
})();
