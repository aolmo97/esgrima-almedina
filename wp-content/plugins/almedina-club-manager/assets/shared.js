/* Helpers de presentación compartidos por el panel y la página pública de resultados. */
(function (root, factory) {
  if (typeof module === 'object' && module.exports) module.exports = factory();
  else root.AlmedinaShared = factory();
})(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  var S = {};
  S.esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };
  S.WEAPON = { espada: 'Espada', florete: 'Florete', sable: 'Sable', mixta: 'Mixta' };
  S.STATUS = { setup: 'Inscripción', poules: 'Poules', de: 'Eliminación directa', finished: 'Finalizado' };
  S.fmtDate = function (s) {
    if (!s) return '';
    return new Date(s + 'T12:00:00').toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
  };
  S.fencerName = function (f) { return f ? (f.last_name + ', ' + f.first_name) : '—'; };

  // Mapa id -> tirador a partir de la lista de participantes/tiradores del servidor.
  // Acepta {id} o {fencer_id}.
  S.fencerMap = function (list) {
    var m = {};
    (list || []).forEach(function (f) { m[f.id != null ? f.id : f.fencer_id] = f; });
    return m;
  };
  S.nameOf = function (map) {
    return function (id) { return id == null ? '—' : S.fencerName(map[id]); };
  };

  // Asalto del servidor -> formato del motor.
  S.toEngine = function (b) {
    return { id: b.id, a: b.fencer_a, b: b.fencer_b, score_a: b.score_a, score_b: b.score_b, winner: b.winner, status: b.status, poule_no: b.poule_no, round: b.round, slot: b.slot };
  };

  // Agrupa los asaltos de poule: [{no, fencers:[ids], bouts:[engineBout...]}]
  S.pouleGroups = function (bouts) {
    var groups = {};
    bouts.filter(function (b) { return b.phase === 'poule'; }).forEach(function (b) {
      var g = groups[b.poule_no] || (groups[b.poule_no] = { no: b.poule_no, fencers: [], bouts: [] });
      [b.fencer_a, b.fencer_b].forEach(function (id) { if (g.fencers.indexOf(id) < 0) g.fencers.push(id); });
      g.bouts.push(S.toEngine(b));
    });
    return Object.keys(groups).map(Number).sort(function (a, b) { return a - b; }).map(function (k) { return groups[k]; });
  };

  S.rankingHtml = function (ranking, nameOf, opts) {
    opts = opts || {};
    if (!ranking.length) return '<p class="empty">Sin datos todavía.</p>';
    return '<div class="tablewrap"><table class="grid"><thead><tr><th>#</th><th>Tirador</th><th>V</th><th>A</th><th>V/A</th><th>TD</th><th>TR</th><th>Ind</th></tr></thead><tbody>' +
      ranking.map(function (r) {
        return '<tr><td>' + r.place + (r.tied ? '<span class="tie" title="Empate: se desempata por sorteo">*</span>' : '') + '</td><td>' + S.esc(nameOf(r.id)) + '</td><td>' + r.v + '</td><td>' + r.m + '</td><td>' + r.vm.toFixed(2) + '</td><td>' + r.td + '</td><td>' + r.tr + '</td><td>' + (r.ind > 0 ? '+' : '') + r.ind + '</td></tr>';
      }).join('') + '</tbody></table></div>' +
      '<p class="muted small">V = victorias · A = asaltos · TD = tocados dados · TR = tocados recibidos · Ind = TD − TR. * = empate total (sorteo).</p>';
  };

  // Matriz de resultados de una poule.
  S.pouleMatrixHtml = function (g, nameOf, E) {
    var ids = g.fencers;
    var st = {};
    E.stats(ids, g.bouts).forEach(function (s) { st[s.id] = s; });
    var cell = function (x, y) {
      if (x === y) return '<td class="diag"></td>';
      var b = g.bouts.find(function (o) { return (o.a === x && o.b === y) || (o.a === y && o.b === x); });
      if (!b || b.status !== 'done') return '<td class="muted">·</td>';
      var mine = b.a === x ? b.score_a : b.score_b;
      var win = b.winner === x;
      return '<td class="' + (win ? 'win' : 'loss') + '">' + (win ? 'V' : 'D') + mine + '</td>';
    };
    return '<div class="tablewrap"><table class="grid matrix"><thead><tr><th>Poule ' + g.no + '</th>' + ids.map(function (_, i) { return '<th>' + (i + 1) + '</th>'; }).join('') + '<th>V</th><th>TD</th><th>TR</th><th>Ind</th></tr></thead><tbody>' +
      ids.map(function (x, i) {
        var s = st[x];
        return '<tr><th class="left">' + (i + 1) + '. ' + S.esc(nameOf(x)) + '</th>' + ids.map(function (y) { return cell(x, y); }).join('') + '<td>' + s.v + '</td><td>' + s.td + '</td><td>' + s.tr + '</td><td>' + (s.ind > 0 ? '+' : '') + s.ind + '</td></tr>';
      }).join('') + '</tbody></table></div>';
  };

  var ROUND_NAMES = { 2: 'Final', 4: 'Semifinales', 8: 'Cuartos', 16: 'Octavos', 32: 'Dieciseisavos', 64: 'Treintaidosavos' };
  S.roundName = function (r) { return ROUND_NAMES[r] || ('Tabla de ' + r); };

  // Cuadro de eliminación. `extra(bout)` devuelve HTML adicional (controles) para cada asalto.
  S.bracketHtml = function (deBouts, nameOf, extra, opts) {
    opts = opts || {};
    if (!deBouts.length) return '<p class="empty">El cuadro aún no se ha generado.</p>';
    var rounds = Array.from(new Set(deBouts.map(function (b) { return b.round; }))).sort(function (x, y) { return y - x; });
    var first = rounds[0];
    return '<div class="bracket">' + rounds.map(function (r) {
      return '<div class="bracket-col"><h4>' + S.roundName(r) + '</h4><div class="bracket-bouts">' +
        deBouts.filter(function (b) { return b.round === r; }).sort(function (x, y) { return x.slot - y.slot; }).map(function (b) {
          var bye = r === first && (b.fencer_a == null || b.fencer_b == null);
          var side = function (id, score, isWinner) {
            if (opts.blank) return '<div class="side"><span>' + S.esc(id == null ? (bye ? 'Descansa' : '') : nameOf(id)) + '</span><b class="box"></b></div>';
            return '<div class="side ' + (isWinner ? 'winner' : '') + '"><span>' + S.esc(id == null ? (bye ? 'Descansa' : 'Por determinar') : nameOf(id)) + '</span><b>' + (score == null ? '' : score) + '</b></div>';
          };
          var done = !opts.blank && b.status === 'done';
          return '<div class="bout ' + b.status + (bye ? ' bye' : '') + '">' + side(b.fencer_a, b.score_a, done && b.winner === b.fencer_a) + side(b.fencer_b, b.score_b, done && b.winner === b.fencer_b) + (extra && !bye ? extra(b) : '') + '</div>';
        }).join('') + '</div></div>';
    }).join('') + '</div>';
  };

  S.placingsHtml = function (placings, nameOf) {
    if (!placings.length) return '';
    return '<div class="tablewrap"><table class="grid"><thead><tr><th>Puesto</th><th>Tirador</th></tr></thead><tbody>' +
      placings.map(function (p) { return '<tr><td>' + p.place + '</td><td>' + S.esc(nameOf(p.id)) + '</td></tr>'; }).join('') + '</tbody></table></div>';
  };

  // Cabecera de marca (logo + nombre + eslogan) para el panel, la página pública y los documentos impresos.
  S.brandHtml = function (brand, cls) {
    brand = brand || {};
    return '<div class="brandhead ' + (cls || '') + '">' + (brand.logo ? '<img class="brandlogo" src="' + S.esc(brand.logo) + '" alt="">' : '') +
      '<div><div class="brandname">' + S.esc(brand.name || 'Club de Esgrima') + '</div><div class="brandtag">' + S.esc(brand.tagline || '') + '</div></div></div>';
  };

  return S;
});
