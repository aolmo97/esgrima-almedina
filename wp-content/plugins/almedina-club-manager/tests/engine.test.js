// Ejecutar: node --test wp-content/plugins/almedina-club-manager/tests/
const test = require('node:test');
const assert = require('node:assert/strict');
const E = require('../assets/engine.js');

const mk = (n, clubOf) => Array.from({ length: n }, (_, i) => ({ id: i + 1, seed: i + 1, club: clubOf ? clubOf(i) : '' }));
const done = (a, b, sa, sb, w) => ({ a, b, score_a: sa, score_b: sb, winner: w == null ? (sa > sb ? a : b) : w, status: 'done' });

test('pouleCount: poules de hasta 7', () => {
  assert.equal(E.pouleCount(0), 0);
  assert.equal(E.pouleCount(2), 1);
  assert.equal(E.pouleCount(7), 1);
  assert.equal(E.pouleCount(8), 2);
  assert.equal(E.pouleCount(14), 2);
  assert.equal(E.pouleCount(15), 3);
  assert.equal(E.pouleCount(22), 4);
});

test('pouleBoutOrder: todos contra todos, cada pareja una vez', () => {
  for (let n = 2; n <= 10; n++) {
    const order = E.pouleBoutOrder(n);
    assert.equal(order.length, n * (n - 1) / 2, 'nº de asaltos n=' + n);
    const seen = new Set(order.map(p => p.join('-')));
    assert.equal(seen.size, order.length, 'sin repetidos n=' + n);
    order.forEach(([a, b]) => { assert.ok(a < b && a >= 0 && b < n); });
  }
});

test('pouleBoutOrder: nadie combate dos asaltos seguidos (n entre 5 y 8)', () => {
  for (let n = 5; n <= 8; n++) {
    const o = E.pouleBoutOrder(n);
    for (let i = 1; i < o.length; i++) {
      const shared = o[i].filter(x => o[i - 1].includes(x));
      assert.equal(shared.length, 0, `n=${n} asaltos ${i - 1} y ${i} comparten tirador`);
    }
  }
});

test('pouleBoutOrder: es determinista', () => {
  assert.deepEqual(E.pouleBoutOrder(6), E.pouleBoutOrder(6));
});

test('distribute: serpiente por cabeza de serie, tamaños equilibrados', () => {
  const g = E.distribute(mk(12), 3);
  assert.deepEqual(g.map(p => p.length), [4, 4, 4]);
  assert.deepEqual(g.map(p => p.map(f => f.id)), [[1, 6, 7, 12], [2, 5, 8, 11], [3, 4, 9, 10]]);
});

test('distribute: tamaños con resto (10 en 3 poules)', () => {
  const sizes = E.distribute(mk(10), 3).map(p => p.length).sort();
  assert.deepEqual(sizes, [3, 3, 4]);
});

test('distribute: ordena por seed aunque la entrada esté desordenada y respeta seed nulo al final', () => {
  const f = [{ id: 1, seed: 3 }, { id: 2, seed: null }, { id: 3, seed: 1 }, { id: 4, seed: 2 }];
  const g = E.distribute(f, 2);
  assert.deepEqual(g.map(p => p.map(x => x.id)), [[3, 2], [4, 1]]);
});

test('distribute: separa tiradores del mismo club', () => {
  const f = mk(12, i => (i < 6 ? 'A' : 'B' + i));
  const g = E.distribute(f, 3);
  const perPoule = g.map(p => p.filter(x => x.club === 'A').length);
  assert.deepEqual(perPoule.sort(), [2, 2, 2]);
  assert.equal(g.reduce((s, p) => s + p.length, 0), 12);
});

test('distribute: no pierde ni duplica tiradores', () => {
  const f = mk(23, i => 'C' + (i % 3));
  const g = E.distribute(f, 4);
  const ids = g.flat().map(x => x.id).sort((a, b) => a - b);
  assert.deepEqual(ids, f.map(x => x.id));
});

test('generatePoules: estructura completa', () => {
  const p = E.generatePoules(mk(9));
  assert.equal(p.length, 2);
  assert.equal(p[0].no, 1);
  p.forEach(x => assert.equal(x.bouts.length, x.fencers.length * (x.fencers.length - 1) / 2));
  const total = p.reduce((s, x) => s + x.fencers.length, 0);
  assert.equal(total, 9);
});

test('validateResult', () => {
  const ok = { a: 1, b: 2, score_a: 5, score_b: 3, winner: 1 };
  assert.equal(E.validateResult(ok, 5), null);
  assert.match(E.validateResult({ ...ok, score_a: 6 }, 5), /Máximo/);
  assert.match(E.validateResult({ ...ok, score_a: -1 }, 5), /negativos/);
  assert.match(E.validateResult({ ...ok, score_a: 2.5 }, 5), /enteros/);
  assert.match(E.validateResult({ ...ok, winner: 9 }, 5), /uno de los dos/);
  assert.match(E.validateResult({ ...ok, winner: 2 }, 5), /no coincide/);
  assert.equal(E.validateResult({ ...ok, score_a: 4, score_b: 4, winner: 2 }, 5), null, 'igualados con prioridad');
});

test('stats: victorias, tocados dados/recibidos, índice y V/M', () => {
  const bouts = [done(1, 2, 5, 3), done(1, 3, 2, 5), done(2, 3, 5, 4), { a: 1, b: 4, status: 'pending' }];
  const s = Object.fromEntries(E.stats([1, 2, 3, 4], bouts).map(x => [x.id, x]));
  assert.deepEqual([s[1].v, s[1].m, s[1].td, s[1].tr, s[1].ind], [1, 2, 7, 8, -1]);
  assert.deepEqual([s[2].v, s[2].m, s[2].td, s[2].tr, s[2].ind], [1, 2, 8, 9, -1]);
  assert.deepEqual([s[3].v, s[3].m, s[3].td, s[3].tr, s[3].ind], [1, 2, 9, 7, 2]);
  assert.deepEqual([s[4].v, s[4].m], [0, 0], 'asalto pendiente no cuenta');
  assert.equal(s[1].vm, 0.5);
  assert.equal(s[4].vm, 0);
});

test('rank: V/M, luego índice, luego TD, luego seed', () => {
  const list = [
    { id: 1, v: 3, m: 5, td: 20, tr: 20, ind: 0, vm: 0.6 },
    { id: 2, v: 3, m: 5, td: 22, tr: 18, ind: 4, vm: 0.6 },
    { id: 3, v: 4, m: 5, td: 21, tr: 21, ind: 0, vm: 0.8 },
    { id: 4, v: 3, m: 5, td: 24, tr: 20, ind: 4, vm: 0.6 }
  ];
  const r = E.rank(list, id => id);
  assert.deepEqual(r.map(x => x.id), [3, 4, 2, 1]);
  assert.deepEqual(r.map(x => x.place), [1, 2, 3, 4]);
  assert.equal(r[0].tied, false);
});

test('rank: marca empates reales y desempata por seed', () => {
  const list = [
    { id: 7, v: 2, m: 4, td: 10, tr: 10, ind: 0, vm: 0.5 },
    { id: 3, v: 2, m: 4, td: 10, tr: 10, ind: 0, vm: 0.5 },
    { id: 5, v: 1, m: 4, td: 8, tr: 12, ind: -4, vm: 0.25 }
  ];
  const r = E.rank(list, id => id);
  assert.deepEqual(r.map(x => x.id), [3, 7, 5]);
  assert.equal(r[0].tied, true);
  assert.equal(r[1].tied, true);
  assert.equal(r[2].tied, false);
});

test('pouleRanking: poule completa de 4, resultado conocido', () => {
  // 1 gana a todos 5-0; 2 gana a 3 y 4; 3 gana a 4.
  const bouts = [done(1, 2, 5, 2), done(1, 3, 5, 3), done(1, 4, 5, 1), done(2, 3, 5, 4), done(2, 4, 5, 2), done(3, 4, 5, 0)];
  const r = E.pouleRanking([{ fencers: [1, 2, 3, 4], bouts }]);
  assert.deepEqual(r.map(x => x.id), [1, 2, 3, 4]);
  assert.deepEqual(r.map(x => x.v), [3, 2, 1, 0]);
  assert.equal(r[0].td, 15);
  assert.equal(r[0].tr, 6);
  assert.equal(r[0].ind, 9);
});

test('seedOrder: orden estándar del cuadro', () => {
  assert.deepEqual(E.seedOrder(2), [1, 2]);
  assert.deepEqual(E.seedOrder(4), [1, 4, 2, 3]);
  assert.deepEqual(E.seedOrder(8), [1, 8, 4, 5, 2, 7, 3, 6]);
});

test('generateBracket: 8 tiradores, 7 asaltos y 1 contra 8', () => {
  const b = E.generateBracket([1, 2, 3, 4, 5, 6, 7, 8]);
  assert.equal(b.length, 7);
  const r8 = b.filter(x => x.round === 8);
  assert.equal(r8.length, 4);
  assert.deepEqual([r8[0].a, r8[0].b], [1, 8]);
  assert.deepEqual([r8[1].a, r8[1].b], [4, 5]);
  assert.deepEqual([r8[2].a, r8[2].b], [2, 7]);
  assert.deepEqual([r8[3].a, r8[3].b], [3, 6]);
});

test('generateBracket: byes para los mejores y avance automático (6 tiradores)', () => {
  const b = E.generateBracket([1, 2, 3, 4, 5, 6]);
  const get = (r, s) => b.find(x => x.round === r && x.slot === s);
  // tabla de 8: 1 y 2 pasan en bye
  assert.deepEqual([get(8, 1).a, get(8, 1).b], [1, null]);
  assert.deepEqual([get(8, 3).a, get(8, 3).b], [2, null]);
  // en cuartos: 1 ya colocado, 4 vs 5 abierto
  assert.equal(get(4, 1).a, 1);
  assert.equal(get(4, 2).a, 2);
  assert.equal(get(4, 1).b, null);
});

test('generateBracket: 2, 3 y 5 tiradores', () => {
  assert.equal(E.generateBracket([1, 2]).length, 1);
  assert.equal(E.generateBracket([1, 2, 3]).length, 3);
  assert.equal(E.generateBracket([1, 2, 3, 4, 5]).length, 7);
  assert.deepEqual(E.generateBracket([1]), []);
});

test('advance: el ganador pasa a la posición correcta de la siguiente ronda', () => {
  let b = E.generateBracket([1, 2, 3, 4]);
  const semi1 = b.find(x => x.round === 4 && x.slot === 1);
  const semi2 = b.find(x => x.round === 4 && x.slot === 2);
  assert.deepEqual([semi1.a, semi1.b, semi2.a, semi2.b], [1, 4, 2, 3]);
  semi1.status = 'done'; semi1.winner = 4;
  semi2.status = 'done'; semi2.winner = 2;
  b = E.advance(b);
  const final = b.find(x => x.round === 2);
  assert.deepEqual([final.a, final.b], [4, 2]);
});

test('torneo completo de 8: de las poules al campeón', () => {
  const fencers = mk(8);
  const poules = E.generatePoules(fencers, { poules: 2 });
  // El de menor id siempre gana a quien tenga id mayor, 5-n según la diferencia.
  const withResults = poules.map(p => ({
    fencers: p.fencers,
    bouts: p.bouts.map(([a, b]) => {
      const w = Math.min(a, b);
      return { a, b, score_a: a === w ? 5 : 2, score_b: b === w ? 5 : 2, winner: w, status: 'done' };
    })
  }));
  const ranking = E.pouleRanking(withResults, id => id);
  assert.equal(ranking.length, 8);
  assert.equal(ranking[0].id, 1);
  let bracket = E.generateBracket(ranking.map(r => r.id));
  for (const round of [8, 4, 2]) {
    bracket.filter(x => x.round === round).forEach(x => {
      assert.ok(x.a != null && x.b != null, `ronda ${round} completa antes de jugarse`);
      x.status = 'done'; x.winner = Math.min(x.a, x.b);
    });
    bracket = E.advance(bracket);
  }
  const placings = E.bracketPlacings(bracket);
  assert.deepEqual(placings.find(p => p.place === 1), { id: 1, place: 1 });
  assert.equal(placings.find(p => p.place === 2).id, 2);
  assert.deepEqual(placings.filter(p => p.place === 3).map(p => p.id).sort(), [3, 4]);
  assert.equal(placings.filter(p => p.place === 5).length, 4);
});

test('bracketPlacings: sin asaltos terminados no hay clasificación', () => {
  assert.deepEqual(E.bracketPlacings(E.generateBracket([1, 2, 3, 4])), []);
});

// ---- Helpers de presentación (shared.js) ----
const S = require('../assets/shared.js');

test('shared.esc escapa HTML', () => {
  assert.equal(S.esc('<b>"a" & \'b\'</b>'), '&lt;b&gt;&quot;a&quot; &amp; &#39;b&#39;&lt;/b&gt;');
  assert.equal(S.esc(null), '');
});

test('shared.pouleGroups agrupa asaltos por poule', () => {
  const bouts = [
    { id: 1, phase: 'poule', poule_no: 1, fencer_a: 1, fencer_b: 2, status: 'pending' },
    { id: 2, phase: 'poule', poule_no: 2, fencer_a: 3, fencer_b: 4, status: 'pending' },
    { id: 3, phase: 'poule', poule_no: 1, fencer_a: 1, fencer_b: 5, status: 'pending' },
    { id: 4, phase: 'de', round: 4, slot: 1, fencer_a: 1, fencer_b: 3, status: 'pending' }
  ];
  const g = S.pouleGroups(bouts);
  assert.equal(g.length, 2);
  assert.deepEqual(g[0].fencers, [1, 2, 5]);
  assert.equal(g[0].bouts.length, 2);
  assert.deepEqual(g[1].fencers, [3, 4]);
});

test('shared.rankingHtml y bracketHtml escapan nombres', () => {
  const nameOf = id => '<img src=x onerror=alert(1)>' + id;
  const html = S.rankingHtml([{ id: 1, place: 1, tied: false, v: 1, m: 1, vm: 1, td: 5, tr: 2, ind: 3 }], nameOf);
  assert.ok(!html.includes('<img'));
  assert.ok(html.includes('&lt;img'));
  const de = [{ round: 2, slot: 1, fencer_a: 1, fencer_b: 2, status: 'pending' }];
  const b = S.bracketHtml(de, nameOf);
  assert.ok(!b.includes('<img'));
  assert.ok(b.includes('Final'));
});

test('shared.pouleMatrixHtml muestra V/D con tocados', () => {
  const E2 = require('../assets/engine.js');
  const g = { no: 1, fencers: [1, 2], bouts: [{ a: 1, b: 2, score_a: 5, score_b: 3, winner: 1, status: 'done' }] };
  const html = S.pouleMatrixHtml(g, id => 'T' + id, E2);
  assert.ok(html.includes('V5'));
  assert.ok(html.includes('D3'));
});
