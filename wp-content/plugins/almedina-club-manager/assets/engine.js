/*
 * Almedina Club Manager — motor de torneos (poules + eliminación directa).
 * Funciones puras, sin DOM ni red: se prueban con Node (tests/engine.test.js)
 * y se usan en el navegador como window.AlmedinaEngine.
 *
 * Convenciones:
 *  - Un tirador es {id, club?, seed?}. "seed" menor = mejor cabeza de serie.
 *  - Un asalto de poule es {a, b, score_a, score_b, winner, status}; solo cuentan los status === 'done'.
 */
(function (root, factory) {
  if (typeof module === 'object' && module.exports) module.exports = factory();
  else root.AlmedinaEngine = factory();
})(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  /* ---------- Poules: número y reparto ---------- */

  // Nº de poules para n tiradores: poules de hasta `maxSize` (FIE prefiere 7, 6 y 5).
  function pouleCount(n, maxSize) {
    maxSize = maxSize || 7;
    if (n <= 0) return 0;
    return Math.max(1, Math.ceil(n / maxSize));
  }

  function sortBySeed(fencers) {
    return fencers
      .map(function (f, i) { return { f: f, i: i }; })
      .sort(function (x, y) {
        var sx = x.f.seed == null ? Infinity : x.f.seed;
        var sy = y.f.seed == null ? Infinity : y.f.seed;
        if (sx !== sy) return sx - sy;
        return x.i - y.i; // estable
      })
      .map(function (x) { return x.f; });
  }

  function clubConflicts(poules) {
    var total = 0;
    poules.forEach(function (p) {
      var seen = {};
      p.forEach(function (f) {
        if (!f.club) return;
        var k = String(f.club).trim().toLowerCase();
        if (!k) return;
        total += seen[k] || 0; // cada tirador suma tantos conflictos como compañeros previos
        seen[k] = (seen[k] || 0) + 1;
      });
    });
    return total;
  }

  // Reparto en serpiente por cabeza de serie + mejora local para separar clubes.
  function distribute(fencers, nPoules) {
    var ordered = sortBySeed(fencers);
    var k = nPoules || pouleCount(ordered.length);
    if (k < 1) return [];
    if (k > ordered.length) k = ordered.length;
    var poules = [];
    for (var i = 0; i < k; i++) poules.push([]);
    ordered.forEach(function (f, idx) {
      var row = Math.floor(idx / k);
      var col = idx % k;
      if (row % 2 === 1) col = k - 1 - col;
      poules[col].push(f);
    });
    // Mejora: intercambios entre poules solo si reducen conflictos de club (tamaños intactos).
    var improved = true, guard = 0;
    while (improved && guard++ < 50) {
      improved = false;
      var current = clubConflicts(poules);
      for (var a = 0; a < k && !improved; a++) {
        for (var b = a + 1; b < k && !improved; b++) {
          for (var x = 0; x < poules[a].length && !improved; x++) {
            for (var y = 0; y < poules[b].length && !improved; y++) {
              var t = poules[a][x]; poules[a][x] = poules[b][y]; poules[b][y] = t;
              if (clubConflicts(poules) < current) { improved = true; }
              else { t = poules[a][x]; poules[a][x] = poules[b][y]; poules[b][y] = t; }
            }
          }
        }
      }
    }
    return poules;
  }

  /* ---------- Orden de asaltos de una poule ---------- */

  // Pares [i,j] (índices 0..n-1) de una poule de n tiradores, todos contra todos.
  // Método de rotación (circle) para la base y búsqueda con retroceso para que ningún
  // tirador combata dos asaltos seguidos cuando es posible (n >= 5). No es la tabla oficial FIE.
  function pouleBoutOrder(n) {
    if (n < 2) return [];
    var players = [];
    for (var i = 0; i < n; i++) players.push(i);
    if (n % 2 === 1) players.push(-1); // descanso
    var m = players.length, base = [];
    for (var r = 0; r < m - 1; r++) {
      for (var j = 0; j < m / 2; j++) {
        var p = players[j], q = players[m - 1 - j];
        if (p !== -1 && q !== -1) base.push(p < q ? [p, q] : [q, p]);
      }
      players.splice(1, 0, players.pop()); // rota dejando fijo el primero
    }
    var total = base.length;
    var used = new Array(total).fill(false);
    var seq = [];
    var nodes = 0, LIMIT = 200000;
    var best = null, bestOverlaps = Infinity;

    function overlaps(x, y) { return x[0] === y[0] || x[0] === y[1] || x[1] === y[0] || x[1] === y[1]; }

    function dfs(overlapCount) {
      if (nodes++ > LIMIT) return;
      if (overlapCount >= bestOverlaps) return;
      if (seq.length === total) { best = seq.slice(); bestOverlaps = overlapCount; return; }
      // candidatos ordenados: sin solape primero, luego por posición base
      var cands = [];
      for (var i = 0; i < total; i++) {
        if (used[i]) continue;
        var o = seq.length && overlaps(seq[seq.length - 1], base[i]) ? 1 : 0;
        cands.push({ i: i, o: o });
      }
      cands.sort(function (x, y) { return x.o - y.o || x.i - y.i; });
      for (var c = 0; c < cands.length; c++) {
        used[cands[c].i] = true; seq.push(base[cands[c].i]);
        dfs(overlapCount + cands[c].o);
        seq.pop(); used[cands[c].i] = false;
        if (bestOverlaps === 0 || nodes > LIMIT) return;
      }
    }
    dfs(0);
    return best || base;
  }

  // Genera las poules: [{no, fencers:[ids], bouts:[[idA,idB],...]}]
  function generatePoules(fencers, opts) {
    opts = opts || {};
    var groups = distribute(fencers, opts.poules || pouleCount(fencers.length, opts.maxSize));
    return groups.map(function (g, i) {
      return {
        no: i + 1,
        fencers: g.map(function (f) { return f.id; }),
        bouts: pouleBoutOrder(g.length).map(function (pr) { return [g[pr[0]].id, g[pr[1]].id]; })
      };
    });
  }

  /* ---------- Resultados y clasificación ---------- */

  // Valida un resultado. Devuelve null si es válido, o un texto con el error.
  function validateResult(r, maxScore) {
    var sa = r.score_a, sb = r.score_b;
    if (!Number.isInteger(sa) || !Number.isInteger(sb)) return 'Los tocados deben ser números enteros';
    if (sa < 0 || sb < 0) return 'Los tocados no pueden ser negativos';
    if (maxScore && (sa > maxScore || sb > maxScore)) return 'Máximo ' + maxScore + ' tocados';
    if (r.winner !== r.a && r.winner !== r.b) return 'El ganador debe ser uno de los dos tiradores';
    if (sa > sb && r.winner !== r.a) return 'El ganador no coincide con el marcador';
    if (sb > sa && r.winner !== r.b) return 'El ganador no coincide con el marcador';
    return null; // igualados + ganador explícito = prioridad
  }

  function emptyStat(id) { return { id: id, v: 0, m: 0, td: 0, tr: 0, ind: 0, vm: 0 }; }

  // Estadísticas de una lista de tiradores a partir de asaltos terminados.
  function stats(fencerIds, bouts) {
    var map = {};
    fencerIds.forEach(function (id) { map[id] = emptyStat(id); });
    bouts.forEach(function (b) {
      if (b.status !== 'done') return;
      var A = map[b.a], B = map[b.b];
      if (A) { A.m++; A.td += b.score_a; A.tr += b.score_b; if (b.winner === b.a) A.v++; }
      if (B) { B.m++; B.td += b.score_b; B.tr += b.score_a; if (b.winner === b.b) B.v++; }
    });
    return fencerIds.map(function (id) {
      var s = map[id];
      s.ind = s.td - s.tr;
      s.vm = s.m ? s.v / s.m : 0;
      return s;
    });
  }

  // Orden: V/M desc, índice desc, TD desc, cabeza de serie asc. `tied` marca empates reales
  // (mismo V/M, índice y TD): en torneo oficial se desempatan por sorteo.
  function rank(statList, seedOf) {
    seedOf = seedOf || function () { return 0; };
    var sorted = statList.slice().sort(function (x, y) {
      if (y.vm !== x.vm) return y.vm - x.vm;
      if (y.ind !== x.ind) return y.ind - x.ind;
      if (y.td !== x.td) return y.td - x.td;
      var sx = seedOf(x.id), sy = seedOf(y.id);
      return (sx == null ? Infinity : sx) - (sy == null ? Infinity : sy);
    });
    return sorted.map(function (s, i) {
      var prev = sorted[i - 1], next = sorted[i + 1];
      function same(p) { return p && p.vm === s.vm && p.ind === s.ind && p.td === s.td; }
      return Object.assign({}, s, { place: i + 1, tied: !!(same(prev) || same(next)) });
    });
  }

  // Clasificación general tras las poules (todas las poules juntas).
  function pouleRanking(poules, seedOf) {
    var all = [];
    poules.forEach(function (p) { all = all.concat(stats(p.fencers, p.bouts)); });
    return rank(all, seedOf);
  }

  /* ---------- Eliminación directa ---------- */

  function nextPow2(n) { var s = 2; while (s < n) s *= 2; return s; }

  // Orden estándar de cabezas de serie en el cuadro: [1, size, ...] por posición.
  function seedOrder(size) {
    var o = [1];
    while (o.length < size) {
      var n = o.length * 2;
      o = o.reduce(function (acc, s) { acc.push(s, n + 1 - s); return acc; }, []);
    }
    return o;
  }

  // ranked: ids en orden de clasificación (1º primero). Devuelve asaltos de TODAS las rondas.
  // round = tamaño de la tabla (16, 8, 4, 2=final). Los "byes" quedan con un lado null.
  function generateBracket(ranked) {
    var n = ranked.length;
    if (n < 2) return [];
    var size = nextPow2(n);
    var order = seedOrder(size);
    var bouts = [];
    for (var s = 1; s <= size / 2; s++) {
      var sa = order[2 * s - 2], sb = order[2 * s - 1];
      bouts.push({ round: size, slot: s, a: sa <= n ? ranked[sa - 1] : null, b: sb <= n ? ranked[sb - 1] : null });
    }
    for (var r = size / 2; r >= 2; r /= 2) {
      for (var t = 1; t <= r / 2; t++) bouts.push({ round: r, slot: t, a: null, b: null });
    }
    return advance(bouts);
  }

  // Propaga ganadores (y byes) a la ronda siguiente. Devuelve una copia nueva.
  function advance(bouts) {
    var list = bouts.map(function (b) { return Object.assign({}, b); });
    var by = {};
    list.forEach(function (b) { by[b.round + ':' + b.slot] = b; });
    var rounds = Array.from(new Set(list.map(function (b) { return b.round; }))).sort(function (x, y) { return y - x; });
    rounds.forEach(function (r) {
      if (r === 2) return;
      list.filter(function (b) { return b.round === r; }).forEach(function (b) {
        var w = null;
        if (b.status === 'done' && b.winner != null) w = b.winner;
        else if (b.a != null && b.b == null && r === rounds[0]) w = b.a; // bye (solo 1ª ronda)
        else if (b.b != null && b.a == null && r === rounds[0]) w = b.b;
        var nxt = by[(r / 2) + ':' + Math.ceil(b.slot / 2)];
        if (!nxt) return;
        if (b.slot % 2 === 1) nxt.a = w; else nxt.b = w;
      });
    });
    return list;
  }

  // Clasificación final del cuadro: [{id, place}] (los perdedores de una misma ronda empatan).
  function bracketPlacings(bouts) {
    var out = [];
    bouts.forEach(function (b) {
      if (b.status !== 'done' || b.winner == null || b.a == null || b.b == null) return;
      var loser = b.winner === b.a ? b.b : b.a;
      out.push({ id: loser, place: b.round / 2 + 1 });
      if (b.round === 2) out.push({ id: b.winner, place: 1 });
    });
    return out.sort(function (x, y) { return x.place - y.place; });
  }

  return {
    pouleCount: pouleCount, distribute: distribute, clubConflicts: clubConflicts,
    pouleBoutOrder: pouleBoutOrder, generatePoules: generatePoules,
    validateResult: validateResult, stats: stats, rank: rank, pouleRanking: pouleRanking,
    seedOrder: seedOrder, generateBracket: generateBracket, advance: advance, bracketPlacings: bracketPlacings
  };
});
