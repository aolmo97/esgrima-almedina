<?php
/**
 * Esquema de base de datos del plugin (migraciones aditivas con dbDelta).
 * Nunca se borran ni se alteran columnas existentes: la tabla de competiciones
 * ({prefijo}almedina_events) se mantiene tal cual.
 */
if (!defined('ABSPATH')) exit;

final class Almedina_Schema {

    static function table($name) {
        global $wpdb;
        return $wpdb->prefix . 'almedina_' . $name;
    }

    /** Crea o actualiza todas las tablas. Es idempotente. */
    static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        $events = self::table('events');
        dbDelta("CREATE TABLE $events (
   id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
   title varchar(220) NOT NULL,
   start_date date NOT NULL,
   end_date date NOT NULL,
   city varchar(150) NOT NULL DEFAULT '',
   organizer varchar(12) NOT NULL DEFAULT 'FAE',
   weapon varchar(20) NOT NULL DEFAULT 'espada',
   category varchar(80) NOT NULL DEFAULT 'absoluta',
   format varchar(20) NOT NULL DEFAULT 'individual',
   status varchar(20) NOT NULL DEFAULT 'provisional',
   source_url varchar(500) NOT NULL DEFAULT '',
   notes text NULL,
   created_at datetime NOT NULL,
   updated_at datetime NOT NULL,
   PRIMARY KEY  (id),
   KEY start_date (start_date)
  ) $charset;");

        $fencers = self::table('fencers');
        dbDelta("CREATE TABLE $fencers (
   id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
   first_name varchar(100) NOT NULL,
   last_name varchar(120) NOT NULL,
   club varchar(150) NOT NULL DEFAULT '',
   license varchar(40) NOT NULL DEFAULT '',
   birth_year smallint(5) unsigned NULL,
   nationality varchar(60) NOT NULL DEFAULT '',
   weapons varchar(40) NOT NULL DEFAULT '',
   notes text NULL,
   active tinyint(1) NOT NULL DEFAULT 1,
   created_at datetime NOT NULL,
   updated_at datetime NOT NULL,
   PRIMARY KEY  (id),
   KEY last_name (last_name)
  ) $charset;");

        $tournaments = self::table('tournaments');
        dbDelta("CREATE TABLE $tournaments (
   id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
   name varchar(220) NOT NULL,
   event_id bigint(20) unsigned NULL,
   tdate date NOT NULL,
   weapon varchar(20) NOT NULL DEFAULT 'espada',
   category varchar(80) NOT NULL DEFAULT 'absoluta',
   status varchar(20) NOT NULL DEFAULT 'setup',
   poule_max_score smallint(5) unsigned NOT NULL DEFAULT 5,
   de_max_score smallint(5) unsigned NOT NULL DEFAULT 15,
   public_token char(32) NOT NULL,
   created_at datetime NOT NULL,
   updated_at datetime NOT NULL,
   PRIMARY KEY  (id),
   UNIQUE KEY public_token (public_token),
   KEY tdate (tdate)
  ) $charset;");

        $participants = self::table('participants');
        dbDelta("CREATE TABLE $participants (
   id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
   tournament_id bigint(20) unsigned NOT NULL,
   fencer_id bigint(20) unsigned NOT NULL,
   seed int(11) NULL,
   poule_no smallint(5) unsigned NULL,
   created_at datetime NOT NULL,
   PRIMARY KEY  (id),
   UNIQUE KEY tournament_fencer (tournament_id,fencer_id)
  ) $charset;");

        $bouts = self::table('bouts');
        dbDelta("CREATE TABLE $bouts (
   id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
   tournament_id bigint(20) unsigned NOT NULL,
   phase varchar(10) NOT NULL DEFAULT 'poule',
   poule_no smallint(5) unsigned NULL,
   round smallint(5) unsigned NOT NULL DEFAULT 0,
   slot smallint(5) unsigned NOT NULL DEFAULT 0,
   fencer_a bigint(20) unsigned NULL,
   fencer_b bigint(20) unsigned NULL,
   score_a smallint(5) unsigned NULL,
   score_b smallint(5) unsigned NULL,
   winner bigint(20) unsigned NULL,
   status varchar(12) NOT NULL DEFAULT 'pending',
   state text NULL,
   created_at datetime NOT NULL,
   updated_at datetime NOT NULL,
   PRIMARY KEY  (id),
   KEY tournament_phase (tournament_id,phase)
  ) $charset;");
    }

    /** Nombres de las tablas del plugin (para copias de seguridad). */
    static function all_tables() {
        return array('events', 'fencers', 'tournaments', 'participants', 'bouts');
    }
}
