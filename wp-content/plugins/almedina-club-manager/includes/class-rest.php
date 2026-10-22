<?php
/**
 * API REST de tiradores, torneos, asaltos, panel, copia de seguridad y resultados públicos.
 * Todas las rutas (salvo /public/{token}) exigen la capacidad manage_almedina_calendar
 * y, con sesión por cookie, el nonce de WordPress (lo comprueba el propio core).
 */
if (!defined('ABSPATH')) exit;

final class Almedina_Rest {

    const NS = 'almedina/v1';
    const WEAPONS = array('espada', 'florete', 'sable');

    static function init() {
        add_action('rest_api_init', array(__CLASS__, 'routes'));
    }

    static function routes() {
        $can = array('Almedina_Club_Manager', 'allowed');
        $r = function ($path, $methods) {
            register_rest_route(Almedina_Rest::NS, $path, $methods);
        };

        $r('/dashboard', array(array('methods' => 'GET', 'callback' => array(__CLASS__, 'dashboard'), 'permission_callback' => $can)));
        $r('/backup', array(array('methods' => 'GET', 'callback' => array(__CLASS__, 'backup'), 'permission_callback' => $can)));

        $r('/fencers', array(
            array('methods' => 'GET', 'callback' => array(__CLASS__, 'fencers_list'), 'permission_callback' => $can),
            array('methods' => 'POST', 'callback' => array(__CLASS__, 'fencer_create'), 'permission_callback' => $can),
        ));
        $r('/fencers/(?P<id>\d+)', array(
            array('methods' => 'PUT', 'callback' => array(__CLASS__, 'fencer_update'), 'permission_callback' => $can),
            array('methods' => 'DELETE', 'callback' => array(__CLASS__, 'fencer_delete'), 'permission_callback' => $can),
        ));

        $r('/tournaments', array(
            array('methods' => 'GET', 'callback' => array(__CLASS__, 'tournaments_list'), 'permission_callback' => $can),
            array('methods' => 'POST', 'callback' => array(__CLASS__, 'tournament_create'), 'permission_callback' => $can),
        ));
        $r('/tournaments/(?P<id>\d+)', array(
            array('methods' => 'GET', 'callback' => array(__CLASS__, 'tournament_get'), 'permission_callback' => $can),
            array('methods' => 'PUT', 'callback' => array(__CLASS__, 'tournament_update'), 'permission_callback' => $can),
            array('methods' => 'DELETE', 'callback' => array(__CLASS__, 'tournament_delete'), 'permission_callback' => $can),
        ));
        $r('/tournaments/(?P<id>\d+)/participants', array(array('methods' => 'PUT', 'callback' => array(__CLASS__, 'participants_set'), 'permission_callback' => $can)));
        $r('/tournaments/(?P<id>\d+)/poules', array(array('methods' => 'POST', 'callback' => array(__CLASS__, 'poules_create'), 'permission_callback' => $can)));
        $r('/tournaments/(?P<id>\d+)/de', array(array('methods' => 'POST', 'callback' => array(__CLASS__, 'de_create'), 'permission_callback' => $can)));
        $r('/tournaments/(?P<id>\d+)/finish', array(array('methods' => 'POST', 'callback' => array(__CLASS__, 'tournament_finish'), 'permission_callback' => $can)));
        $r('/bouts/(?P<id>\d+)', array(array('methods' => 'PUT', 'callback' => array(__CLASS__, 'bout_update'), 'permission_callback' => $can)));

        // Lectura pública con enlace secreto (32 hex). Solo nombres, club y resultados.
        $r('/public/(?P<token>[a-f0-9]{32})', array(array('methods' => 'GET', 'callback' => array(__CLASS__, 'public_view'), 'permission_callback' => '__return_true')));
    }

    /* ---------- Utilidades ---------- */

    static function err($code, $msg, $status = 400) {
        return new WP_Error($code, $msg, array('status' => $status));
    }

    static function body($request) {
        $p = $request->get_json_params();
        return is_array($p) ? $p : null;
    }

    /** Texto obligatorio u opcional con longitud máxima. Devuelve string o WP_Error. */
    static function text($p, $key, $max, $required = false, $default = '') {
        if (isset($p[$key]) && !is_string($p[$key])) return self::err('invalid_' . $key, "Tipo de dato no válido: $key");
        $v = sanitize_text_field(isset($p[$key]) ? $p[$key] : $default);
        if ($required && $v === '') return self::err('invalid_' . $key, "Campo obligatorio: $key");
        if (mb_strlen($v) > $max) return self::err('invalid_' . $key, "Demasiado largo: $key (máximo $max)");
        return $v;
    }

    static function intval_range($p, $key, $min, $max, $default) {
        if (!isset($p[$key])) return $default;
        if (!is_int($p[$key]) || $p[$key] < $min || $p[$key] > $max) return self::err('invalid_' . $key, "Valor no válido: $key ($min–$max)");
        return $p[$key];
    }

    static function now() {
        return current_time('mysql');
    }

    static function cast_row($row, $ints, $bools = array()) {
        if (!$row) return $row;
        foreach ($ints as $k) if (array_key_exists($k, $row) && $row[$k] !== null) $row[$k] = (int) $row[$k];
        foreach ($bools as $k) if (array_key_exists($k, $row)) $row[$k] = (bool) $row[$k];
        return $row;
    }

    static function bout_row($row) {
        $row = self::cast_row($row, array('id', 'tournament_id', 'poule_no', 'round', 'slot', 'fencer_a', 'fencer_b', 'score_a', 'score_b', 'winner'));
        if (isset($row['state']) && $row['state'] !== null && $row['state'] !== '') {
            $dec = json_decode($row['state'], true);
            $row['state'] = is_array($dec) ? $dec : null;
        } else {
            $row['state'] = null;
        }
        return $row;
    }

    static function tournament_row($row) {
        return self::cast_row($row, array('id', 'event_id', 'poule_max_score', 'de_max_score'));
    }

    static function get_tournament($id) {
        global $wpdb;
        $t = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . Almedina_Schema::table('tournaments') . ' WHERE id=%d', $id), ARRAY_A);
        return $t ? self::tournament_row($t) : null;
    }

    /* ---------- Panel ---------- */

    static function dashboard() {
        global $wpdb;
        $ev = Almedina_Schema::table('events');
        $fe = Almedina_Schema::table('fencers');
        $to = Almedina_Schema::table('tournaments');
        $today = current_time('Y-m-d');
        $upcoming = $wpdb->get_results($wpdb->prepare("SELECT * FROM $ev WHERE end_date >= %s AND status <> 'cancelado' ORDER BY start_date ASC, id ASC LIMIT 5", $today), ARRAY_A);
        $by_status = $wpdb->get_results("SELECT status, COUNT(*) AS n FROM $to GROUP BY status", ARRAY_A);
        $counts = array('setup' => 0, 'poules' => 0, 'de' => 0, 'finished' => 0);
        foreach ($by_status as $s) $counts[$s['status']] = (int) $s['n'];
        $active = $wpdb->get_results("SELECT id, name, tdate, weapon, status FROM $to WHERE status IN ('poules','de') ORDER BY tdate DESC LIMIT 10", ARRAY_A);
        return rest_ensure_response(array(
            'events_total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $ev"),
            'events_upcoming' => $upcoming,
            'fencers_total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $fe WHERE active=1"),
            'tournaments' => $counts,
            'tournaments_active' => array_map(function ($r) { return Almedina_Rest::cast_row($r, array('id')); }, $active),
        ));
    }

    /** Copia de seguridad en JSON de todas las tablas del plugin. */
    static function backup() {
        global $wpdb;
        $out = array('plugin' => 'almedina-club-manager', 'version' => Almedina_Club_Manager::VERSION, 'created_at' => self::now(), 'tables' => array());
        foreach (Almedina_Schema::all_tables() as $name) {
            $out['tables'][$name] = $wpdb->get_results('SELECT * FROM ' . Almedina_Schema::table($name) . ' ORDER BY id ASC', ARRAY_A);
        }
        return rest_ensure_response($out);
    }

    /* ---------- Tiradores ---------- */

    static function fencer_validate($request) {
        $p = self::body($request);
        if ($p === null) return self::err('invalid_body', 'Se esperaba JSON');
        $out = array();
        foreach (array(array('first_name', 100, true), array('last_name', 120, true), array('club', 150, false), array('license', 40, false), array('nationality', 60, false)) as $f) {
            $v = self::text($p, $f[0], $f[1], $f[2]);
            if (is_wp_error($v)) return $v;
            $out[$f[0]] = $v;
        }
        if (isset($p['notes']) && !is_string($p['notes'])) return self::err('invalid_notes', 'Tipo de dato no válido: notes');
        $notes = sanitize_textarea_field(isset($p['notes']) ? $p['notes'] : '');
        if (mb_strlen($notes) > 2000) return self::err('invalid_notes', 'Notas demasiado largas (máximo 2000)');
        $out['notes'] = $notes;
        $year = null;
        if (isset($p['birth_year']) && $p['birth_year'] !== null && $p['birth_year'] !== '') {
            if (!is_int($p['birth_year']) || $p['birth_year'] < 1900 || $p['birth_year'] > (int) current_time('Y')) return self::err('invalid_birth_year', 'Año de nacimiento no válido');
            $year = $p['birth_year'];
        }
        $out['birth_year'] = $year;
        $weapons = array();
        if (isset($p['weapons'])) {
            if (!is_array($p['weapons'])) return self::err('invalid_weapons', 'Armas no válidas');
            foreach ($p['weapons'] as $w) {
                if (!is_string($w) || !in_array($w, self::WEAPONS, true)) return self::err('invalid_weapons', 'Arma no válida');
                $weapons[$w] = true;
            }
        }
        $out['weapons'] = implode(',', array_keys($weapons));
        $out['active'] = isset($p['active']) ? ($p['active'] ? 1 : 0) : 1;
        return $out;
    }

    static function fencer_out($row) {
        $row = self::cast_row($row, array('id', 'birth_year'), array('active'));
        $row['weapons'] = $row['weapons'] === '' ? array() : explode(',', $row['weapons']);
        return $row;
    }

    static function fencers_list() {
        global $wpdb;
        $rows = $wpdb->get_results('SELECT * FROM ' . Almedina_Schema::table('fencers') . ' ORDER BY last_name ASC, first_name ASC, id ASC', ARRAY_A);
        return rest_ensure_response(array_map(array(__CLASS__, 'fencer_out'), $rows));
    }

    static function fencer_create($request) {
        global $wpdb;
        $d = self::fencer_validate($request);
        if (is_wp_error($d)) return $d;
        $d['created_at'] = self::now();
        $d['updated_at'] = self::now();
        if (!$wpdb->insert(Almedina_Schema::table('fencers'), $d)) return self::err('db_error', 'No se pudo guardar', 500);
        return new WP_REST_Response(array('id' => (int) $wpdb->insert_id), 201);
    }

    static function fencer_update($request) {
        global $wpdb;
        $id = (int) $request['id'];
        $t = Almedina_Schema::table('fencers');
        if (!$wpdb->get_var($wpdb->prepare("SELECT id FROM $t WHERE id=%d", $id))) return self::err('not_found', 'No existe', 404);
        $d = self::fencer_validate($request);
        if (is_wp_error($d)) return $d;
        $d['updated_at'] = self::now();
        if ($wpdb->update($t, $d, array('id' => $id)) === false) return self::err('db_error', 'No se pudo actualizar', 500);
        return rest_ensure_response(array('id' => $id));
    }

    static function fencer_delete($request) {
        global $wpdb;
        $id = (int) $request['id'];
        $t = Almedina_Schema::table('fencers');
        if (!$wpdb->get_var($wpdb->prepare("SELECT id FROM $t WHERE id=%d", $id))) return self::err('not_found', 'No existe', 404);
        if ($wpdb->get_var($wpdb->prepare('SELECT id FROM ' . Almedina_Schema::table('participants') . ' WHERE fencer_id=%d LIMIT 1', $id))) {
            return self::err('in_use', 'El tirador participa en algún torneo; márcalo como inactivo en lugar de borrarlo', 409);
        }
        $wpdb->delete($t, array('id' => $id));
        return rest_ensure_response(array('deleted' => true));
    }

    /* ---------- Torneos ---------- */

    static function tournament_validate($request) {
        $p = self::body($request);
        if ($p === null) return self::err('invalid_body', 'Se esperaba JSON');
        $name = self::text($p, 'name', 220, true);
        if (is_wp_error($name)) return $name;
        $date = Almedina_Club_Manager::date(isset($p['tdate']) && is_string($p['tdate']) ? $p['tdate'] : '');
        if (!$date) return self::err('invalid_tdate', 'Fecha inválida');
        $weapon = isset($p['weapon']) ? $p['weapon'] : 'espada';
        if (!is_string($weapon) || !in_array($weapon, self::WEAPONS, true)) return self::err('invalid_weapon', 'Arma no válida');
        $category = self::text($p, 'category', 80, false, 'absoluta');
        if (is_wp_error($category)) return $category;
        $pm = self::intval_range($p, 'poule_max_score', 1, 45, 5);
        if (is_wp_error($pm)) return $pm;
        $dm = self::intval_range($p, 'de_max_score', 1, 45, 15);
        if (is_wp_error($dm)) return $dm;
        $event = null;
        if (isset($p['event_id']) && $p['event_id'] !== null) {
            if (!is_int($p['event_id'])) return self::err('invalid_event_id', 'Competición no válida');
            global $wpdb;
            if (!$wpdb->get_var($wpdb->prepare('SELECT id FROM ' . Almedina_Schema::table('events') . ' WHERE id=%d', $p['event_id']))) return self::err('invalid_event_id', 'La competición del calendario no existe');
            $event = $p['event_id'];
        }
        return array('name' => $name, 'tdate' => $date, 'weapon' => $weapon, 'category' => $category, 'poule_max_score' => $pm, 'de_max_score' => $dm, 'event_id' => $event);
    }

    static function tournaments_list() {
        global $wpdb;
        $t = Almedina_Schema::table('tournaments');
        $p = Almedina_Schema::table('participants');
        $rows = $wpdb->get_results("SELECT t.*, (SELECT COUNT(*) FROM $p WHERE tournament_id=t.id) AS participants FROM $t t ORDER BY t.tdate DESC, t.id DESC", ARRAY_A);
        return rest_ensure_response(array_map(function ($r) {
            $r = Almedina_Rest::tournament_row($r);
            $r['participants'] = (int) $r['participants'];
            return $r;
        }, $rows));
    }

    static function tournament_create($request) {
        global $wpdb;
        $d = self::tournament_validate($request);
        if (is_wp_error($d)) return $d;
        $d['status'] = 'setup';
        $d['public_token'] = bin2hex(random_bytes(16));
        $d['created_at'] = self::now();
        $d['updated_at'] = self::now();
        $format = array('%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s');
        $ok = $wpdb->insert(Almedina_Schema::table('tournaments'), $d);
        if (!$ok) return self::err('db_error', 'No se pudo guardar', 500);
        return new WP_REST_Response(array('id' => (int) $wpdb->insert_id), 201);
    }

    static function tournament_get($request) {
        global $wpdb;
        $id = (int) $request['id'];
        $t = self::get_tournament($id);
        if (!$t) return self::err('not_found', 'No existe', 404);
        $p = Almedina_Schema::table('participants');
        $f = Almedina_Schema::table('fencers');
        $parts = $wpdb->get_results($wpdb->prepare("SELECT p.fencer_id, p.seed, p.poule_no, f.first_name, f.last_name, f.club FROM $p p JOIN $f f ON f.id=p.fencer_id WHERE p.tournament_id=%d ORDER BY (p.seed IS NULL), p.seed ASC, f.last_name ASC", $id), ARRAY_A);
        $parts = array_map(function ($r) { return Almedina_Rest::cast_row($r, array('fencer_id', 'seed', 'poule_no')); }, $parts);
        $bouts = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . Almedina_Schema::table('bouts') . ' WHERE tournament_id=%d ORDER BY phase ASC, poule_no ASC, round DESC, slot ASC, id ASC', $id), ARRAY_A);
        return rest_ensure_response(array('tournament' => $t, 'participants' => $parts, 'bouts' => array_map(array(__CLASS__, 'bout_row'), $bouts)));
    }

    static function tournament_update($request) {
        global $wpdb;
        $id = (int) $request['id'];
        $t = self::get_tournament($id);
        if (!$t) return self::err('not_found', 'No existe', 404);
        $d = self::tournament_validate($request);
        if (is_wp_error($d)) return $d;
        if ($t['status'] !== 'setup') {
            // Una vez generadas las poules no se cambian reglas que afectan a resultados ya introducidos.
            if ($d['weapon'] !== $t['weapon'] || $d['poule_max_score'] !== $t['poule_max_score'] || $d['de_max_score'] !== $t['de_max_score']) {
                return self::err('locked', 'No se puede cambiar el arma ni los tocados máximos con el torneo en marcha', 409);
            }
        }
        $d['updated_at'] = self::now();
        if ($wpdb->update(Almedina_Schema::table('tournaments'), $d, array('id' => $id)) === false) return self::err('db_error', 'No se pudo actualizar', 500);
        return rest_ensure_response(array('id' => $id));
    }

    static function tournament_delete($request) {
        global $wpdb;
        $id = (int) $request['id'];
        if (!self::get_tournament($id)) return self::err('not_found', 'No existe', 404);
        $wpdb->delete(Almedina_Schema::table('bouts'), array('tournament_id' => $id));
        $wpdb->delete(Almedina_Schema::table('participants'), array('tournament_id' => $id));
        $wpdb->delete(Almedina_Schema::table('tournaments'), array('id' => $id));
        return rest_ensure_response(array('deleted' => true));
    }

    static function participants_set($request) {
        global $wpdb;
        $id = (int) $request['id'];
        $t = self::get_tournament($id);
        if (!$t) return self::err('not_found', 'No existe', 404);
        if ($t['status'] !== 'setup') return self::err('locked', 'Las inscripciones solo se editan antes de generar las poules', 409);
        $p = self::body($request);
        if ($p === null || !isset($p['fencer_ids']) || !is_array($p['fencer_ids'])) return self::err('invalid_body', 'Se esperaba fencer_ids');
        $ids = array();
        foreach ($p['fencer_ids'] as $fid) {
            if (!is_int($fid) || $fid < 1) return self::err('invalid_fencer', 'Identificador de tirador no válido');
            if (isset($ids[$fid])) return self::err('invalid_fencer', 'Tirador repetido');
            $ids[$fid] = true;
        }
        $ids = array_keys($ids);
        if (count($ids) > 200) return self::err('too_many', 'Máximo 200 tiradores por torneo');
        if ($ids) {
            $in = implode(',', array_map('intval', $ids));
            $found = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . Almedina_Schema::table('fencers') . " WHERE id IN ($in)");
            if ($found !== count($ids)) return self::err('invalid_fencer', 'Algún tirador no existe');
        }
        $pt = Almedina_Schema::table('participants');
        $wpdb->query('START TRANSACTION');
        $wpdb->delete($pt, array('tournament_id' => $id));
        foreach ($ids as $i => $fid) {
            $ok = $wpdb->insert($pt, array('tournament_id' => $id, 'fencer_id' => $fid, 'seed' => $i + 1, 'created_at' => self::now()));
            if (!$ok) { $wpdb->query('ROLLBACK'); return self::err('db_error', 'No se pudo guardar', 500); }
        }
        $wpdb->query('COMMIT');
        return rest_ensure_response(array('count' => count($ids)));
    }

    /** Participantes del torneo como [fencer_id => true]. */
    static function participant_ids($tid) {
        global $wpdb;
        $rows = $wpdb->get_col($wpdb->prepare('SELECT fencer_id FROM ' . Almedina_Schema::table('participants') . ' WHERE tournament_id=%d', $tid));
        $out = array();
        foreach ($rows as $r) $out[(int) $r] = true;
        return $out;
    }

    static function poules_create($request) {
        global $wpdb;
        $tid = (int) $request['id'];
        $t = self::get_tournament($tid);
        if (!$t) return self::err('not_found', 'No existe', 404);
        if (!in_array($t['status'], array('setup', 'poules'), true)) return self::err('locked', 'Las poules ya están cerradas', 409);
        $bt = Almedina_Schema::table('bouts');
        if ((int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $bt WHERE tournament_id=%d AND phase='poule' AND status='done'", $tid)) > 0) {
            return self::err('locked', 'Ya hay asaltos de poule terminados; no se pueden regenerar', 409);
        }
        $p = self::body($request);
        if ($p === null || !isset($p['poules']) || !is_array($p['poules']) || !$p['poules']) return self::err('invalid_body', 'Se esperaba poules');

        $part = self::participant_ids($tid);
        if (count($part) < 2) return self::err('few_fencers', 'Hacen falta al menos 2 tiradores inscritos');
        $seen = array();
        $nos = array();
        $clean = array();
        foreach ($p['poules'] as $poule) {
            if (!is_array($poule) || !isset($poule['no'], $poule['fencers'], $poule['bouts']) || !is_int($poule['no']) || !is_array($poule['fencers']) || !is_array($poule['bouts'])) return self::err('invalid_poule', 'Poule mal formada');
            if ($poule['no'] < 1 || $poule['no'] > 100 || isset($nos[$poule['no']])) return self::err('invalid_poule', 'Número de poule no válido');
            $nos[$poule['no']] = true;
            $inpoule = array();
            foreach ($poule['fencers'] as $fid) {
                if (!is_int($fid) || !isset($part[$fid]) || isset($seen[$fid])) return self::err('invalid_poule', 'Tirador no inscrito o repetido en las poules');
                $seen[$fid] = true;
                $inpoule[$fid] = true;
            }
            $n = count($inpoule);
            if ($n < 2) return self::err('invalid_poule', 'Cada poule necesita al menos 2 tiradores');
            if (count($poule['bouts']) !== $n * ($n - 1) / 2) return self::err('invalid_poule', 'La poule debe tener todos los asaltos');
            $pairs = array();
            $list = array();
            foreach ($poule['bouts'] as $b) {
                if (!is_array($b) || count($b) !== 2 || !is_int($b[0]) || !is_int($b[1]) || $b[0] === $b[1] || !isset($inpoule[$b[0]], $inpoule[$b[1]])) return self::err('invalid_poule', 'Asalto no válido');
                $key = min($b[0], $b[1]) . '-' . max($b[0], $b[1]);
                if (isset($pairs[$key])) return self::err('invalid_poule', 'Asalto repetido');
                $pairs[$key] = true;
                $list[] = array($b[0], $b[1]);
            }
            $clean[] = array('no' => $poule['no'], 'fencers' => array_keys($inpoule), 'bouts' => $list);
        }
        if (count($seen) !== count($part)) return self::err('invalid_poule', 'Todos los inscritos deben estar en una poule');

        $wpdb->query('START TRANSACTION');
        $wpdb->delete($bt, array('tournament_id' => $tid, 'phase' => 'poule'));
        $pt = Almedina_Schema::table('participants');
        foreach ($clean as $poule) {
            foreach ($poule['fencers'] as $fid) {
                $wpdb->update($pt, array('poule_no' => $poule['no']), array('tournament_id' => $tid, 'fencer_id' => $fid));
            }
            foreach ($poule['bouts'] as $i => $b) {
                $ok = $wpdb->insert($bt, array('tournament_id' => $tid, 'phase' => 'poule', 'poule_no' => $poule['no'], 'round' => 0, 'slot' => $i + 1, 'fencer_a' => $b[0], 'fencer_b' => $b[1], 'status' => 'pending', 'created_at' => self::now(), 'updated_at' => self::now()));
                if (!$ok) { $wpdb->query('ROLLBACK'); return self::err('db_error', 'No se pudo guardar', 500); }
            }
        }
        $wpdb->update(Almedina_Schema::table('tournaments'), array('status' => 'poules', 'updated_at' => self::now()), array('id' => $tid));
        $wpdb->query('COMMIT');
        return rest_ensure_response(array('poules' => count($clean)));
    }

    static function next_pow2($n) {
        $s = 2;
        while ($s < $n) $s *= 2;
        return $s;
    }

    static function de_create($request) {
        global $wpdb;
        $tid = (int) $request['id'];
        $t = self::get_tournament($tid);
        if (!$t) return self::err('not_found', 'No existe', 404);
        if ($t['status'] !== 'poules') return self::err('locked', 'El cuadro solo se genera desde la fase de poules', 409);
        $bt = Almedina_Schema::table('bouts');
        $pending = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $bt WHERE tournament_id=%d AND phase='poule' AND status<>'done'", $tid));
        if ($pending > 0) return self::err('poules_open', "Quedan $pending asaltos de poule sin terminar", 409);
        $p = self::body($request);
        if ($p === null || !isset($p['bracket']) || !is_array($p['bracket'])) return self::err('invalid_body', 'Se esperaba bracket');

        $part = self::participant_ids($tid);
        $n = count($part);
        if ($n < 2) return self::err('few_fencers', 'Hacen falta al menos 2 tiradores');
        $size = self::next_pow2($n);
        $first = array();
        $used = array();
        foreach ($p['bracket'] as $b) {
            if (!is_array($b) || !isset($b['round'], $b['slot']) || !is_int($b['round']) || !is_int($b['slot'])) return self::err('invalid_bracket', 'Cuadro mal formado');
            if ($b['round'] !== $size) continue; // las rondas siguientes las construye el servidor
            if ($b['slot'] < 1 || $b['slot'] > $size / 2 || isset($first[$b['slot']])) return self::err('invalid_bracket', 'Posición de cuadro no válida');
            $a = isset($b['a']) ? $b['a'] : null;
            $c = isset($b['b']) ? $b['b'] : null;
            foreach (array($a, $c) as $x) {
                if ($x === null) continue;
                if (!is_int($x) || !isset($part[$x]) || isset($used[$x])) return self::err('invalid_bracket', 'Tirador no inscrito o repetido en el cuadro');
                $used[$x] = true;
            }
            if ($a === null && $c === null) return self::err('invalid_bracket', 'Asalto vacío en la primera ronda');
            $first[$b['slot']] = array($a, $c);
        }
        if (count($first) !== $size / 2 || count($used) !== $n) return self::err('invalid_bracket', 'El cuadro debe incluir a todos los tiradores');

        $wpdb->query('START TRANSACTION');
        $wpdb->delete($bt, array('tournament_id' => $tid, 'phase' => 'de'));
        for ($r = $size; $r >= 2; $r = (int) ($r / 2)) {
            for ($s = 1; $s <= $r / 2; $s++) {
                $row = array('tournament_id' => $tid, 'phase' => 'de', 'round' => $r, 'slot' => $s, 'status' => 'pending', 'created_at' => self::now(), 'updated_at' => self::now());
                if ($r === $size) { $row['fencer_a'] = $first[$s][0]; $row['fencer_b'] = $first[$s][1]; }
                if (!$wpdb->insert($bt, $row)) { $wpdb->query('ROLLBACK'); return self::err('db_error', 'No se pudo guardar', 500); }
            }
        }
        // Byes: el que no tiene rival pasa directamente a la ronda siguiente.
        $byes = $wpdb->get_results($wpdb->prepare("SELECT * FROM $bt WHERE tournament_id=%d AND phase='de' AND round=%d AND (fencer_a IS NULL OR fencer_b IS NULL)", $tid, $size), ARRAY_A);
        foreach ($byes as $bye) {
            $w = $bye['fencer_a'] !== null ? (int) $bye['fencer_a'] : (int) $bye['fencer_b'];
            $wpdb->update($bt, array('winner' => $w, 'status' => 'done', 'updated_at' => self::now()), array('id' => (int) $bye['id']));
            self::propagate($tid, (int) $bye['round'], (int) $bye['slot'], $w);
        }
        $wpdb->update(Almedina_Schema::table('tournaments'), array('status' => 'de', 'updated_at' => self::now()), array('id' => $tid));
        $wpdb->query('COMMIT');
        return rest_ensure_response(array('size' => $size, 'bouts' => $size - 1));
    }

    /** Coloca al ganador (o null) de un asalto de eliminación en la ronda siguiente. */
    static function propagate($tid, $round, $slot, $winner) {
        global $wpdb;
        if ($round <= 2) return;
        $bt = Almedina_Schema::table('bouts');
        $col = ($slot % 2 === 1) ? 'fencer_a' : 'fencer_b';
        $next = $wpdb->get_row($wpdb->prepare("SELECT id FROM $bt WHERE tournament_id=%d AND phase='de' AND round=%d AND slot=%d", $tid, (int) ($round / 2), (int) ceil($slot / 2)), ARRAY_A);
        if (!$next) return;
        $wpdb->update($bt, array($col => $winner, 'updated_at' => self::now()), array('id' => (int) $next['id']));
    }

    static function tournament_finish($request) {
        global $wpdb;
        $tid = (int) $request['id'];
        $t = self::get_tournament($tid);
        if (!$t) return self::err('not_found', 'No existe', 404);
        if ($t['status'] !== 'poules' && $t['status'] !== 'de') return self::err('locked', 'El torneo no está en curso', 409);
        $bt = Almedina_Schema::table('bouts');
        $open = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $bt WHERE tournament_id=%d AND status<>'done'", $tid));
        if ($open > 0) return self::err('open_bouts', "Quedan $open asaltos sin terminar", 409);
        $wpdb->update(Almedina_Schema::table('tournaments'), array('status' => 'finished', 'updated_at' => self::now()), array('id' => $tid));
        return rest_ensure_response(array('status' => 'finished'));
    }

    /* ---------- Asaltos ---------- */

    static function bout_update($request) {
        global $wpdb;
        $id = (int) $request['id'];
        $bt = Almedina_Schema::table('bouts');
        $b = $wpdb->get_row($wpdb->prepare("SELECT * FROM $bt WHERE id=%d", $id), ARRAY_A);
        if (!$b) return self::err('not_found', 'No existe', 404);
        $b = self::bout_row($b);
        $t = self::get_tournament($b['tournament_id']);
        if (!$t) return self::err('not_found', 'Torneo inexistente', 404);
        $p = self::body($request);
        if ($p === null) return self::err('invalid_body', 'Se esperaba JSON');

        if ($t['status'] === 'finished') return self::err('locked', 'El torneo está finalizado', 409);
        if ($b['phase'] === 'poule' && $t['status'] !== 'poules') return self::err('locked', 'Las poules ya están cerradas', 409);
        if ($b['fencer_a'] === null || $b['fencer_b'] === null) return self::err('not_ready', 'El asalto aún no tiene los dos tiradores', 409);

        $status = isset($p['status']) ? $p['status'] : 'done';
        if (!is_string($status) || !in_array($status, array('pending', 'live', 'done'), true)) return self::err('invalid_status', 'Estado no válido');
        $max = $b['phase'] === 'poule' ? $t['poule_max_score'] : $t['de_max_score'];

        $sa = array_key_exists('score_a', $p) ? $p['score_a'] : null;
        $sb = array_key_exists('score_b', $p) ? $p['score_b'] : null;
        if ($status === 'pending') { $sa = null; $sb = null; }
        foreach (array($sa, $sb) as $s) {
            if ($s === null) continue;
            if (!is_int($s) || $s < 0 || $s > $max) return self::err('invalid_score', "Los tocados deben ser enteros entre 0 y $max");
        }
        $winner = isset($p['winner']) ? $p['winner'] : null;
        if ($status === 'done') {
            if ($sa === null || $sb === null) return self::err('invalid_score', 'Faltan los tocados');
            if ($winner !== $b['fencer_a'] && $winner !== $b['fencer_b']) return self::err('invalid_winner', 'El ganador debe ser uno de los dos tiradores');
            if ($sa > $sb && $winner !== $b['fencer_a']) return self::err('invalid_winner', 'El ganador no coincide con el marcador');
            if ($sb > $sa && $winner !== $b['fencer_b']) return self::err('invalid_winner', 'El ganador no coincide con el marcador');
        } else {
            $winner = null;
        }

        $state = null;
        if (isset($p['state'])) {
            if (!is_array($p['state'])) return self::err('invalid_state', 'Estado de marcador no válido');
            $state = wp_json_encode($p['state']);
            if (!$state || strlen($state) > 4000) return self::err('invalid_state', 'Estado de marcador demasiado grande');
        }

        // Eliminación directa: no se puede tocar un asalto cuyo siguiente ya ha empezado.
        $was_done = $b['status'] === 'done';
        if ($b['phase'] === 'de' && $b['round'] > 2 && ($was_done || $status === 'done')) {
            $nx = $wpdb->get_row($wpdb->prepare("SELECT status FROM $bt WHERE tournament_id=%d AND phase='de' AND round=%d AND slot=%d", $b['tournament_id'], (int) ($b['round'] / 2), (int) ceil($b['slot'] / 2)), ARRAY_A);
            if ($nx && $nx['status'] !== 'pending') return self::err('locked', 'La ronda siguiente ya ha empezado; no se puede modificar este asalto', 409);
        }

        $data = array('score_a' => $sa, 'score_b' => $sb, 'winner' => $winner, 'status' => $status, 'updated_at' => self::now());
        if ($state !== null) $data['state'] = $state;
        if ($wpdb->update($bt, $data, array('id' => $id)) === false) return self::err('db_error', 'No se pudo guardar', 500);

        if ($b['phase'] === 'de') {
            self::propagate($b['tournament_id'], $b['round'], $b['slot'], $status === 'done' ? $winner : null);
            if ($b['round'] === 2) {
                $wpdb->update(Almedina_Schema::table('tournaments'), array('status' => $status === 'done' ? 'finished' : 'de', 'updated_at' => self::now()), array('id' => $b['tournament_id']));
            }
        }
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $bt WHERE id=%d", $id), ARRAY_A);
        return rest_ensure_response(self::bout_row($row));
    }

    /* ---------- Vista pública (solo lectura) ---------- */

    static function public_view($request) {
        global $wpdb;
        $token = (string) $request['token'];
        $t = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . Almedina_Schema::table('tournaments') . ' WHERE public_token=%s', $token), ARRAY_A);
        if (!$t) return self::err('not_found', 'No existe', 404);
        $t = self::tournament_row($t);
        $tid = $t['id'];
        $p = Almedina_Schema::table('participants');
        $f = Almedina_Schema::table('fencers');
        $fen = $wpdb->get_results($wpdb->prepare("SELECT f.id, f.first_name, f.last_name, f.club, p.seed, p.poule_no FROM $p p JOIN $f f ON f.id=p.fencer_id WHERE p.tournament_id=%d", $tid), ARRAY_A);
        $fen = array_map(function ($r) { return Almedina_Rest::cast_row($r, array('id', 'seed', 'poule_no')); }, $fen);
        $bouts = $wpdb->get_results($wpdb->prepare('SELECT id, phase, poule_no, round, slot, fencer_a, fencer_b, score_a, score_b, winner, status FROM ' . Almedina_Schema::table('bouts') . ' WHERE tournament_id=%d ORDER BY phase ASC, poule_no ASC, round DESC, slot ASC', $tid), ARRAY_A);
        $bouts = array_map(function ($r) { return Almedina_Rest::cast_row($r, array('id', 'poule_no', 'round', 'slot', 'fencer_a', 'fencer_b', 'score_a', 'score_b', 'winner')); }, $bouts);
        return rest_ensure_response(array(
            'tournament' => array('name' => $t['name'], 'tdate' => $t['tdate'], 'weapon' => $t['weapon'], 'category' => $t['category'], 'status' => $t['status'], 'poule_max_score' => $t['poule_max_score'], 'de_max_score' => $t['de_max_score']),
            'fencers' => $fen,
            'bouts' => $bouts,
        ));
    }
}
