/*
 * Pruebas de integración de la API REST (se ejecutan en el navegador, en /gestion/, con sesión de administrador).
 * Uso: pegar el contenido en la consola de /gestion/ (o ejecutarlo con la herramienta de navegador).
 * Crea datos con prefijo "ZZTEST" y los borra al terminar. Devuelve un texto con PASS/FAIL por prueba.
 */
(async () => {
  const cfg = window.ALMEDINA_CONFIG, E = window.AlmedinaEngine;
  const out = [];
  let pass = 0, fail = 0;
  const ok = (name, cond, extra) => { cond ? pass++ : fail++; out.push((cond ? 'PASS ' : 'FAIL ') + name + (cond || extra === undefined ? '' : ' [' + String(extra).replace(/[^\w ,.:-]/g, '') + ']')); };
  const call = async (method, path, body, auth = true) => {
    const r = await fetch(cfg.root + path, { method, credentials: auth ? 'same-origin' : 'omit', headers: { ...(auth ? { 'X-WP-Nonce': cfg.nonce } : {}), ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}) }, ...(body !== undefined ? { body: JSON.stringify(body) } : {}) });
    let j = null; try { j = await r.json(); } catch (e) {}
    return { s: r.status, j };
  };
  const created = { fencers: [], tournaments: [], events: [] };

  try {
    /* ---- Acceso sin sesión ---- */
    for (const p of ['/fencers', '/tournaments', '/backup', '/dashboard', '/events']) {
      const r = await call('GET', p, undefined, false);
      ok('sin sesión GET ' + p + ' -> 401', r.s === 401, r.s);
    }
    ok('sin sesión POST /fencers -> 401', (await call('POST', '/fencers', { first_name: 'a', last_name: 'b' }, false)).s === 401);
    ok('sin sesión PUT /bouts/1 -> 401', (await call('PUT', '/bouts/1', {}, false)).s === 401);
    ok('con cookie y sin nonce POST /tournaments -> 401', (await fetch(cfg.root + '/tournaments', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: '{}' })).status === 401);

    /* ---- Tiradores ---- */
    const base = { first_name: 'ZZTEST', last_name: 'Uno', club: 'Club A', license: 'L-1', birth_year: 1990, nationality: 'ES', weapons: ['espada'], notes: 'nota privada' };
    for (const [n, b] of [['sin apellidos', { ...base, last_name: '' }], ['arma inválida', { ...base, weapons: ['laser'] }], ['armas no array', { ...base, weapons: 'espada' }], ['año 1800', { ...base, birth_year: 1800 }], ['año texto', { ...base, birth_year: '1990' }], ['nombre >100', { ...base, first_name: 'x'.repeat(101) }], ['nombre como array', { ...base, first_name: ['a'] }]]) {
      ok('tirador inválido (' + n + ') -> 400', (await call('POST', '/fencers', b)).s === 400);
    }
    const ids = [];
    const clubs = ['Club A', 'Club A', 'Club B', 'Club B', 'Club C', 'Club C'];
    for (let i = 0; i < 6; i++) {
      const r = await call('POST', '/fencers', { ...base, last_name: 'Tirador' + (i + 1), club: clubs[i], license: 'L-' + (i + 1) });
      if (r.s === 201) { ids.push(r.j.id); created.fencers.push(r.j.id); }
    }
    ok('alta de 6 tiradores -> 201', ids.length === 6);
    let r = await call('PUT', '/fencers/' + ids[0], { ...base, last_name: 'Editado', active: true, weapons: ['espada', 'sable'] });
    ok('editar tirador -> 200', r.s === 200);
    r = await call('GET', '/fencers');
    const f0 = r.j.find(x => x.id === ids[0]);
    ok('edición persistida y armas como lista', f0 && f0.last_name === 'Editado' && f0.weapons.length === 2 && f0.active === true);
    ok('PUT tirador inexistente -> 404', (await call('PUT', '/fencers/99999999', base)).s === 404);
    ok('DELETE tirador inexistente -> 404', (await call('DELETE', '/fencers/99999999')).s === 404);

    /* ---- Torneo: alta y validación ---- */
    const tb = { name: 'ZZTEST Torneo', tdate: '2026-12-12', weapon: 'espada', category: 'absoluta', poule_max_score: 5, de_max_score: 15 };
    for (const [n, b] of [['sin nombre', { ...tb, name: '' }], ['arma inválida', { ...tb, weapon: 'laser' }], ['fecha inválida', { ...tb, tdate: '2026-13-01' }], ['tocados poule 0', { ...tb, poule_max_score: 0 }], ['tocados DE 46', { ...tb, de_max_score: 46 }], ['evento inexistente', { ...tb, event_id: 99999999 }], ['categoría >80', { ...tb, category: 'c'.repeat(81) }]]) {
      ok('torneo inválido (' + n + ') -> 400', (await call('POST', '/tournaments', b)).s === 400);
    }
    const ev = await call('POST', '/events', { title: 'ZZTEST Evento', start_date: '2026-12-12', end_date: '2026-12-12' });
    created.events.push(ev.j.id);
    r = await call('POST', '/tournaments', { ...tb, event_id: ev.j.id });
    ok('alta de torneo vinculado al calendario -> 201', r.s === 201);
    const tid = r.j.id; created.tournaments.push(tid);
    r = await call('GET', '/tournaments/' + tid);
    ok('torneo nuevo en estado setup con token', r.j.tournament.status === 'setup' && /^[a-f0-9]{32}$/.test(r.j.tournament.public_token) && r.j.tournament.event_id === ev.j.id);
    const token = r.j.tournament.public_token;
    r = await call('PUT', '/tournaments/' + tid, { ...tb, name: 'ZZTEST Torneo editado' });
    ok('editar torneo -> 200', r.s === 200);
    ok('GET torneo inexistente -> 404', (await call('GET', '/tournaments/99999999')).s === 404);

    /* ---- Inscripción ---- */
    ok('inscribir con id repetido -> 400', (await call('PUT', '/tournaments/' + tid + '/participants', { fencer_ids: [ids[0], ids[0]] })).s === 400);
    ok('inscribir tirador inexistente -> 400', (await call('PUT', '/tournaments/' + tid + '/participants', { fencer_ids: [ids[0], 99999999] })).s === 400);
    ok('inscribir ids no enteros -> 400', (await call('PUT', '/tournaments/' + tid + '/participants', { fencer_ids: ['1'] })).s === 400);
    ok('inscribir sin fencer_ids -> 400', (await call('PUT', '/tournaments/' + tid + '/participants', {})).s === 400);
    r = await call('PUT', '/tournaments/' + tid + '/participants', { fencer_ids: ids });
    ok('inscribir 6 tiradores -> 200', r.s === 200 && r.j.count === 6);
    ok('borrar tirador inscrito -> 409', (await call('DELETE', '/fencers/' + ids[0])).s === 409);
    r = await call('GET', '/tournaments/' + tid);
    ok('participantes con seed por orden', r.j.participants.length === 6 && r.j.participants[0].seed === 1 && r.j.participants[5].seed === 6);

    /* ---- Poules ---- */
    ok('cuadro antes de las poules -> 409', (await call('POST', '/tournaments/' + tid + '/de', { bracket: [] })).s === 409);
    const list = r.j.participants.map(p => ({ id: p.fencer_id, club: p.club, seed: p.seed }));
    const gen = E.generatePoules(list, { poules: 2 });
    ok('motor: 2 poules de 3 con todos los asaltos', gen.length === 2 && gen.every(p => p.fencers.length === 3 && p.bouts.length === 3));
    const bad = (mut) => { const c = JSON.parse(JSON.stringify(gen)); mut(c); return call('POST', '/tournaments/' + tid + '/poules', { poules: c }); };
    ok('poules sin un tirador -> 400', (await bad(c => c[1].fencers.pop())).s === 400);
    ok('asalto con tirador de otra poule -> 400', (await bad(c => { c[0].bouts[0] = [c[0].fencers[0], c[1].fencers[0]]; })).s === 400);
    ok('asalto repetido -> 400', (await bad(c => { c[0].bouts[1] = c[0].bouts[0]; })).s === 400);
    ok('faltan asaltos -> 400', (await bad(c => c[0].bouts.pop())).s === 400);
    ok('tirador repetido en dos poules -> 400', (await bad(c => { c[1].fencers[0] = c[0].fencers[0]; })).s === 400);
    ok('poules vacías -> 400', (await call('POST', '/tournaments/' + tid + '/poules', { poules: [] })).s === 400);
    ok('poules válidas -> 200', (await call('POST', '/tournaments/' + tid + '/poules', { poules: gen })).s === 200);
    ok('regenerar poules sin resultados -> 200', (await call('POST', '/tournaments/' + tid + '/poules', { poules: gen })).s === 200);
    ok('inscripción bloqueada con poules -> 409', (await call('PUT', '/tournaments/' + tid + '/participants', { fencer_ids: ids })).s === 409);
    r = await call('GET', '/tournaments/' + tid);
    ok('6 asaltos de poule creados y torneo en poules', r.j.bouts.length === 6 && r.j.tournament.status === 'poules');
    ok('cambiar arma con torneo en marcha -> 409', (await call('PUT', '/tournaments/' + tid, { ...tb, weapon: 'sable' })).s === 409);

    /* ---- Resultados de asaltos ---- */
    const b1 = r.j.bouts[0];
    const put = (b, body) => call('PUT', '/bouts/' + b.id, body);
    ok('tocados sobre el máximo -> 400', (await put(b1, { score_a: 6, score_b: 0, winner: b1.fencer_a, status: 'done' })).s === 400);
    ok('tocados negativos -> 400', (await put(b1, { score_a: -1, score_b: 0, winner: b1.fencer_b, status: 'done' })).s === 400);
    ok('tocados decimales -> 400', (await put(b1, { score_a: 2.5, score_b: 0, winner: b1.fencer_a, status: 'done' })).s === 400);
    ok('ganador ajeno al asalto -> 400', (await put(b1, { score_a: 5, score_b: 0, winner: 99999999, status: 'done' })).s === 400);
    ok('ganador distinto al marcador -> 400', (await put(b1, { score_a: 5, score_b: 0, winner: b1.fencer_b, status: 'done' })).s === 400);
    ok('empate sin ganador -> 400', (await put(b1, { score_a: 3, score_b: 3, status: 'done' })).s === 400);
    ok('terminar sin tocados -> 400', (await put(b1, { winner: b1.fencer_a, status: 'done' })).s === 400);
    ok('estado inválido -> 400', (await put(b1, { score_a: 1, score_b: 0, status: 'hack' })).s === 400);
    ok('state no objeto -> 400', (await put(b1, { score_a: 1, score_b: 0, status: 'live', state: 'x' })).s === 400);
    ok('state enorme -> 400', (await put(b1, { score_a: 1, score_b: 0, status: 'live', state: { x: 'y'.repeat(5000) } })).s === 400);
    r = await put(b1, { score_a: 2, score_b: 1, status: 'live', state: { period: 1, remaining: 120, cards: { a: ['yellow'], b: [] } } });
    ok('guardar asalto en juego con estado -> 200', r.s === 200 && r.j.status === 'live' && r.j.state && r.j.state.remaining === 120 && r.j.winner === null);
    ok('PUT asalto inexistente -> 404', (await call('PUT', '/bouts/99999999', { status: 'done' })).s === 404);
    ok('empate con prioridad -> 200', (await put(b1, { score_a: 4, score_b: 4, winner: b1.fencer_b, status: 'done' })).s === 200);
    r = await call('GET', '/tournaments/' + tid);
    const done1 = r.j.bouts.find(x => x.id === b1.id);
    ok('asalto con prioridad: ganador B y marcador 4-4', done1.status === 'done' && done1.winner === b1.fencer_b && done1.score_a === 4 && done1.score_b === 4);
    ok('corregir asalto terminado -> 200', (await put(b1, { score_a: 5, score_b: 3, winner: b1.fencer_a, status: 'done' })).s === 200);
    ok('reabrir asalto (pending) borra marcador', (await put(b1, { status: 'pending' })).s === 200);
    r = await call('GET', '/tournaments/' + tid);
    const reopened = r.j.bouts.find(x => x.id === b1.id);
    ok('asalto reabierto sin tocados ni ganador', reopened.score_a === null && reopened.winner === null && reopened.status === 'pending');
    ok('terminar torneo con asaltos abiertos -> 409', (await call('POST', '/tournaments/' + tid + '/finish', {})).s === 409);
    ok('cuadro con poules abiertas -> 409', (await call('POST', '/tournaments/' + tid + '/de', { bracket: E.generateBracket(ids) })).s === 409);

    // Terminar todas las poules: gana siempre el de menor id, 5 a (id mayor mod 3)
    r = await call('GET', '/tournaments/' + tid);
    for (const b of r.j.bouts) {
      const w = Math.min(b.fencer_a, b.fencer_b);
      const loserPts = (Math.max(b.fencer_a, b.fencer_b) % 3);
      const body = { score_a: b.fencer_a === w ? 5 : loserPts, score_b: b.fencer_b === w ? 5 : loserPts, winner: w, status: 'done' };
      await put(b, body);
    }
    ok('regenerar poules con resultados -> 409', (await call('POST', '/tournaments/' + tid + '/poules', { poules: gen })).s === 409);
    r = await call('GET', '/tournaments/' + tid);
    ok('todas las poules terminadas', r.j.bouts.every(b => b.status === 'done'));

    /* ---- Clasificación (motor sobre datos reales del servidor) ---- */
    const S = window.AlmedinaShared;
    const groups = S.pouleGroups(r.j.bouts);
    const seedOf = id => (r.j.participants.find(p => p.fencer_id === id) || {}).seed;
    const ranking = E.pouleRanking(groups, seedOf);
    ok('clasificación con 6 tiradores y victorias correctas', ranking.length === 6 && ranking[0].v === 2 && ranking[5].v === 0, JSON.stringify(ranking.map(x => x.v)));
    ok('el primero tiene el mejor V/A', ranking[0].vm >= ranking[1].vm);

    /* ---- Eliminación directa ---- */
    const rankedIds = ranking.map(x => x.id);
    const bracket = E.generateBracket(rankedIds);
    ok('motor: cuadro de 8 con 7 asaltos para 6 tiradores', bracket.length === 7);
    const bracketMissing = bracket.map(b => (b.round === 8 && b.slot === 1) ? { ...b, a: null } : b);
    ok('cuadro sin un tirador -> 400', (await call('POST', '/tournaments/' + tid + '/de', { bracket: bracketMissing })).s === 400);
    const bracketDup = bracket.map(b => (b.round === 8 && b.slot === 2) ? { ...b, a: rankedIds[0] } : b);
    ok('cuadro con tirador repetido -> 400', (await call('POST', '/tournaments/' + tid + '/de', { bracket: bracketDup })).s === 400);
    ok('cuadro con tirador ajeno -> 400', (await call('POST', '/tournaments/' + tid + '/de', { bracket: bracket.map(b => (b.round === 8 && b.slot === 2) ? { ...b, b: 99999999 } : b) })).s === 400);
    r = await call('POST', '/tournaments/' + tid + '/de', { bracket });
    ok('generar cuadro -> 200 (tabla de 8)', r.s === 200 && r.j.size === 8 && r.j.bouts === 7, r.s);
    ok('generar cuadro dos veces -> 409', (await call('POST', '/tournaments/' + tid + '/de', { bracket })).s === 409);
    r = await call('GET', '/tournaments/' + tid);
    let de = r.j.bouts.filter(b => b.phase === 'de');
    ok('torneo en fase de y 7 asaltos', r.j.tournament.status === 'de' && de.length === 7);
    const q = (round, slot) => de.find(b => b.round === round && b.slot === slot);
    ok('byes resueltos: 1º y 2º ya están en cuartos', q(4, 1).fencer_a === rankedIds[0] && q(4, 2).fencer_a === rankedIds[1] && q(8, 1).status === 'done' && q(8, 3).status === 'done');
    ok('asalto sin rival todavía -> 409', (await put(q(4, 1), { score_a: 1, score_b: 0, winner: q(4, 1).fencer_a, status: 'done' })).s === 409);
    ok('máximo de tocados de DE es 15', (await put(q(8, 2), { score_a: 16, score_b: 0, winner: q(8, 2).fencer_a, status: 'done' })).s === 400);

    // Jugar el cuadro entero: gana el de mejor posición (menor índice en rankedIds)
    const pos = id => rankedIds.indexOf(id);
    let lockedChecked = false;
    for (const round of [8, 4, 2]) {
      r = await call('GET', '/tournaments/' + tid);
      de = r.j.bouts.filter(b => b.phase === 'de');
      for (const b of de.filter(x => x.round === round && x.status !== 'done')) {
        if (b.fencer_a == null || b.fencer_b == null) continue;
        const w = pos(b.fencer_a) < pos(b.fencer_b) ? b.fencer_a : b.fencer_b;
        const res = await put(b, { score_a: b.fencer_a === w ? 15 : 9, score_b: b.fencer_b === w ? 15 : 9, winner: w, status: 'done' });
        if (res.s !== 200) ok('asalto de ronda ' + round + ' guardado', false, res.s);
      }
      if (round === 8 && !lockedChecked) {
        r = await call('GET', '/tournaments/' + tid);
        de = r.j.bouts.filter(b => b.phase === 'de');
        const qf = de.find(b => b.round === 4 && b.slot === 1 && b.fencer_a != null && b.fencer_b != null);
        if (qf) {
          await put(qf, { score_a: 15, score_b: 0, winner: qf.fencer_a, status: 'done' });
          const feeder = de.find(b => b.round === 8 && b.slot === 2 && b.status === 'done');
          if (feeder) ok('editar asalto cuyo siguiente ya se jugó -> 409', (await put(feeder, { score_a: 15, score_b: 1, winner: feeder.winner, status: 'done' })).s === 409);
          lockedChecked = true;
        }
      }
    }
    r = await call('GET', '/tournaments/' + tid);
    de = r.j.bouts.filter(b => b.phase === 'de');
    const final = de.find(b => b.round === 2);
    ok('final terminada y torneo finalizado automáticamente', final.status === 'done' && r.j.tournament.status === 'finished');
    const placings = E.bracketPlacings(de.map(S.toEngine));
    ok('campeón = mejor clasificado en poules', placings[0].place === 1 && placings[0].id === rankedIds[0]);
    ok('2 en semifinales con puesto 3', placings.filter(p => p.place === 3).length === 2);
    ok('torneo finalizado: no se editan asaltos -> 409', (await put(final, { score_a: 15, score_b: 0, winner: final.winner, status: 'done' })).s === 409);

    /* ---- Vista pública ---- */
    r = await call('GET', '/public/' + token, undefined, false);
    const pub = JSON.stringify(r.j || {});
    ok('público sin sesión -> 200 con datos del torneo', r.s === 200 && r.j.tournament.name === 'ZZTEST Torneo editado' && r.j.fencers.length === 6 && r.j.bouts.length === 13);
    ok('público no expone licencia, año, notas ni token', !/L-1|birth_year|nota privada|license|public_token|notes/.test(pub));
    ok('token desconocido -> 404', (await call('GET', '/public/' + '0'.repeat(32), undefined, false)).s === 404);
    ok('token no hexadecimal -> 404', (await call('GET', '/public/zzzz', undefined, false)).s === 404);
    const pr = await fetch(cfg.root + '/public/' + token, { credentials: 'omit' });
    ok('público con no-store', /no-store/.test(pr.headers.get('cache-control') || ''));
    const page = await fetch(cfg.results_base + token + '/', { credentials: 'omit' });
    const html = await page.text();
    ok('página pública /resultados/{token}/ -> 200 noindex', page.status === 200 && /noindex/.test(html + (page.headers.get('x-robots-tag') || '')) && /public\.js/.test(html));
    ok('página pública con token inválido -> 404', (await fetch(cfg.results_base + 'abc/', { credentials: 'omit' })).status === 404);

    /* ---- Panel y copia de seguridad ---- */
    r = await call('GET', '/dashboard');
    ok('dashboard con contadores', r.s === 200 && typeof r.j.fencers_total === 'number' && r.j.tournaments.finished >= 1 && Array.isArray(r.j.events_upcoming));
    r = await call('GET', '/backup');
    const t = r.j.tables || {};
    ok('backup incluye las 5 tablas', ['events', 'fencers', 'tournaments', 'participants', 'bouts'].every(k => Array.isArray(t[k])) && r.j.version);
    ok('backup contiene los datos creados', t.bouts.filter(b => b.tournament_id == tid).length === 13 && t.participants.filter(p => p.tournament_id == tid).length === 6 && t.events.some(e => e.id == ev.j.id));

    /* ---- Calendario (regresión) ---- */
    r = await call('PUT', '/events/' + ev.j.id, { title: 'ZZTEST Evento editado', start_date: '2026-12-12', end_date: '2026-12-13', organizer: 'FADE', weapon: 'sable', status: 'confirmado' });
    ok('calendario: editar evento -> 200', r.s === 200);
    r = await call('GET', '/events');
    ok('calendario: evento editado persistido', r.j.some(e => String(e.id) === String(ev.j.id) && e.organizer === 'FADE' && e.end_date === '2026-12-13'));
    ok('calendario: fechas inválidas -> 400', (await call('POST', '/events', { title: 'x', start_date: '2026-12-12', end_date: '2026-12-01' })).s === 400);

    /* ---- Borrado en cascada ---- */
    ok('borrar torneo -> 200', (await call('DELETE', '/tournaments/' + tid)).s === 200);
    created.tournaments.length = 0;
    ok('torneo borrado -> 404', (await call('GET', '/tournaments/' + tid)).s === 404);
    r = await call('GET', '/backup');
    ok('borrado en cascada: sin asaltos ni inscritos', r.j.tables.bouts.filter(b => b.tournament_id == tid).length === 0 && r.j.tables.participants.filter(p => p.tournament_id == tid).length === 0);
    ok('el enlace público ya no existe -> 404', (await call('GET', '/public/' + token, undefined, false)).s === 404);
    ok('tirador libre se puede borrar -> 200', (await call('DELETE', '/fencers/' + ids[0])).s === 200);
    created.fencers = created.fencers.filter(x => x !== ids[0]);
  } catch (e) {
    fail++; out.push('FAIL excepción: ' + String(e && e.message).replace(/[^\w ,.:-]/g, ''));
  } finally {
    for (const id of created.tournaments) await call('DELETE', '/tournaments/' + id).catch(() => {});
    for (const id of created.fencers) await call('DELETE', '/fencers/' + id).catch(() => {});
    for (const id of created.events) await call('DELETE', '/events/' + id).catch(() => {});
  }
  const left = (await call('GET', '/fencers')).j.filter(f => f.first_name === 'ZZTEST').length;
  out.push('Limpieza: tiradores ZZTEST restantes = ' + left);
  out.push('TOTAL: ' + pass + ' superadas, ' + fail + ' fallidas');
  window.__apiTestReport = out.join('\n');
  return window.__apiTestReport;
})();
