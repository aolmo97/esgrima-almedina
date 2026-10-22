<?php
/**
 * Plugin Name: Almedina Club Manager
 * Description: Backoffice privado del Club de Esgrima Almedina: calendario, tiradores, torneos (poules y eliminación directa), marcador y resultados.
 * Version: 0.3.1
 * Requires PHP: 7.4
 * Text Domain: almedina-club-manager
 */
if (!defined('ABSPATH')) exit;
final class Almedina_Club_Manager {
 const VERSION='0.3.1'; const DB_VERSION='2'; const DB_OPTION='almedina_club_manager_db_version'; const CAP='manage_almedina_calendar'; const ROUTE='gestion';
 static $load_error=null;
 static function load_modules(){
  try{
   require_once __DIR__.'/includes/class-schema.php';
   require_once __DIR__.'/includes/class-rest.php';
   Almedina_Rest::init();
  }catch(Throwable $e){
   self::$load_error=$e->getMessage();
   add_action('admin_notices',function(){echo '<div class="notice notice-error"><p>Almedina Club Manager: no se pudieron cargar los módulos nuevos ('.esc_html(Almedina_Club_Manager::$load_error).'). El calendario sigue funcionando.</p></div>';});
  }
 }
 static function init(){
  self::load_modules();
  add_action('init',[__CLASS__,'rewrite']);
  add_filter('query_vars',function($vars){$vars[]='almedina_gestion';$vars[]='almedina_results';return $vars;});
  add_action('template_redirect',[__CLASS__,'render_results'],5);
  add_action('template_redirect',[__CLASS__,'render']);
  add_action('rest_api_init',[__CLASS__,'api']);
  add_action('admin_menu',[__CLASS__,'menu']);
  add_action('init',[__CLASS__,'maybe_upgrade'],20);
  add_filter('rest_post_dispatch',[__CLASS__,'no_store'],10,3);
  add_filter('robots_txt',function($output){return rtrim($output)."

# Panel privado del club: no rastrear
User-agent: *
Disallow: /gestion/
Disallow: /wp-json/almedina/
";},20);
 }
 /** Marca del club: usa los mismos ajustes que la web (Ajustes del sitio: logo, nombre, eslogan y color principal). */
 static function brand(){
  $b=['name'=>'Sala de Esgrima Almedina','tagline'=>'Córdoba · S.E.A.C.','color'=>'#16a34a','logo'=>''];
  try{
   if(function_exists('get_field')){
    $n=get_field('nombre_marca','option');if(is_string($n)&&$n!=='')$b['name']=wp_strip_all_tags($n);
    $t=get_field('eslogan_marca','option');if(is_string($t)&&$t!=='')$b['tagline']=wp_strip_all_tags($t);
    $c=get_field('color_primario','option');if(is_string($c)&&preg_match('/^#[0-9a-fA-F]{6}$/',$c))$b['color']=$c;
    $id=get_field('logo_sitio','option');if($id){$u=wp_get_attachment_image_url((int)$id,'medium');if($u)$b['logo']=esc_url_raw($u);}
   }
  }catch(Throwable $e){}
  // Siglas del club (para el menú del panel): las del eslogan (p. ej. «S.E.A.C.») o, si no hay, las iniciales del nombre.
  if(preg_match('/(?<![A-ZÁÉÍÓÚa-z])[A-ZÁÉÍÓÚ](?:\.[A-ZÁÉÍÓÚ])+\.?/u',$b['tagline'],$m)){$b['short']=$m[0];}
  else{$ini='';foreach(preg_split('/\s+/u',$b['name']) as $w){if(mb_strlen($w)>2)$ini.=mb_strtoupper(mb_substr($w,0,1)).'.';}$b['short']=$ini?:$b['name'];}
  return $b;
 }
 static function brand_css($b){
  $c=preg_match('/^#[0-9a-fA-F]{6}$/',$b['color'])?$b['color']:'#16a34a';
  return '<style>:root{--brand:'.$c.';--brand-dark:color-mix(in srgb,'.$c.' 78%,black);--brand-tint:color-mix(in srgb,'.$c.' 10%,white);--brand-line:color-mix(in srgb,'.$c.' 30%,white)}</style>';
 }
 static function table(){global $wpdb;return $wpdb->prefix.'almedina_events';}
 static function activate(){
  if(class_exists('Almedina_Schema')){Almedina_Schema::install();}
  $role=get_role('administrator');if($role)$role->add_cap(self::CAP);
  self::rewrite();flush_rewrite_rules();
 }
 static function maybe_upgrade(){if(get_option(self::DB_OPTION)!==self::VERSION){self::activate();update_option(self::DB_OPTION,self::VERSION);}}
 static function no_store($response,$server,$request){if(strpos($request->get_route(),'/almedina/v1/')===0){$response->header('Cache-Control','no-store, private');$response->header('X-Robots-Tag','noindex, nofollow');}return $response;}
 static function deactivate(){flush_rewrite_rules();}
 static function rewrite(){add_rewrite_rule('^gestion/?$','index.php?almedina_gestion=1','top');add_rewrite_rule('^resultados/([a-f0-9]{32})/?$','index.php?almedina_results=$matches[1]','top');}
 static function menu(){add_management_page('Almedina Club Manager','Almedina Club Manager',self::CAP,'almedina-club-manager',function(){echo '<div class="wrap"><h1>Almedina Club Manager</h1><p>Panel privado: <a href="'.esc_url(home_url('/gestion/')).'">Abrir /gestion</a></p><p>El acceso requiere una cuenta con el permiso <code>'.esc_html(self::CAP).'</code>.</p></div>';});}
 static function render(){
  if(!get_query_var('almedina_gestion'))return;
  if(!is_user_logged_in()){wp_safe_redirect(wp_login_url(home_url('/gestion/')));exit;}
  if(!current_user_can(self::CAP)){status_header(403);nocache_headers();wp_die('No tienes permisos para gestionar el calendario.','Acceso denegado',['response'=>403]);}
  nocache_headers();header('X-Robots-Tag: noindex, nofollow',true);
  $css=plugins_url('assets/app.css',__FILE__);$base=plugins_url('assets/',__FILE__);$brand=self::brand();
  $cfg=['api'=>esc_url_raw(rest_url('almedina/v1/events')),'root'=>esc_url_raw(rest_url('almedina/v1')),'nonce'=>wp_create_nonce('wp_rest'),'logout'=>wp_logout_url(home_url('/')),'user'=>wp_get_current_user()->display_name,'version'=>self::VERSION,'results_base'=>esc_url_raw(home_url('/resultados/')),'brand'=>$brand];
  $scripts=['engine.js','shared.js','core.js','view-calendar.js','view-dashboard.js','view-fencers.js','view-tournaments.js','view-scoreboard.js','view-results.js','view-print.js','view-settings.js','app.js'];
  ?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Almedina · Gestión</title><link rel="stylesheet" href="<?php echo esc_url($css.'?v='.self::VERSION); ?>"><?php echo self::brand_css($brand); ?></head><body><div id="app"><p>Cargando panel…</p></div><script>window.ALMEDINA_CONFIG=<?php echo wp_json_encode($cfg,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;</script><?php foreach($scripts as $f){echo '<script src="'.esc_url($base.$f.'?v='.self::VERSION).'" defer></script>';} ?></body></html><?php exit;
 }
 static function render_results(){
  $token=get_query_var('almedina_results');
  if(!$token)return;
  if(!preg_match('/^[a-f0-9]{32}$/',$token)){status_header(404);nocache_headers();wp_die('Enlace no válido.','No encontrado',['response'=>404]);}
  nocache_headers();header('X-Robots-Tag: noindex, nofollow',true);
  $base=plugins_url('assets/',__FILE__);
  $brand=self::brand();
  $cfg=['root'=>esc_url_raw(rest_url('almedina/v1')),'token'=>$token,'brand'=>$brand];
  ?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Resultados · Club de Esgrima Almedina</title><link rel="stylesheet" href="<?php echo esc_url($base.'app.css?v='.self::VERSION); ?>"><?php echo self::brand_css($brand); ?></head><body class="public"><div id="app"><p>Cargando resultados…</p></div><script>window.ALMEDINA_PUBLIC=<?php echo wp_json_encode($cfg,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;</script><script src="<?php echo esc_url($base.'engine.js?v='.self::VERSION); ?>" defer></script><script src="<?php echo esc_url($base.'shared.js?v='.self::VERSION); ?>" defer></script><script src="<?php echo esc_url($base.'public.js?v='.self::VERSION); ?>" defer></script></body></html><?php exit;
 }
 static function api(){register_rest_route('almedina/v1','/events',[
  ['methods'=>'GET','callback'=>[__CLASS__,'list_events'],'permission_callback'=>[__CLASS__,'allowed']],
  ['methods'=>'POST','callback'=>[__CLASS__,'create_event'],'permission_callback'=>[__CLASS__,'allowed']]
 ]);register_rest_route('almedina/v1','/events/(?P<id>\d+)',[
  ['methods'=>'PUT','callback'=>[__CLASS__,'update_event'],'permission_callback'=>[__CLASS__,'allowed']],
  ['methods'=>'DELETE','callback'=>[__CLASS__,'delete_event'],'permission_callback'=>[__CLASS__,'allowed']]
 ]);}
 static function allowed(){return current_user_can(self::CAP);}
 static function list_events($request){global $wpdb;$table=self::table();$rows=$wpdb->get_results("SELECT * FROM $table ORDER BY start_date ASC, id ASC",ARRAY_A);return rest_ensure_response($rows);}
 static function validate($request){
  $p=$request->get_json_params();if(!is_array($p))return new WP_Error('invalid_body','Se esperaba JSON',['status'=>400]);
  foreach(['title','start_date','end_date','city','organizer','weapon','category','format','status','source_url','notes'] as $k){if(isset($p[$k])&&!is_string($p[$k]))return new WP_Error('invalid_'.$k,'Tipo de dato no válido: '.$k,['status'=>400]);}
  $title=sanitize_text_field($p['title']??'');if($title===''||mb_strlen($title)>220)return new WP_Error('invalid_title','Nombre obligatorio (máximo 220 caracteres)',['status'=>400]);
  $start=self::date($p['start_date']??'');$end=self::date($p['end_date']??($p['start_date']??''));
  if(!$start||!$end||$end<$start)return new WP_Error('invalid_dates','Fechas inválidas',['status'=>400]);
  $choices=['organizer'=>['FAE','FADE','RFEE','OTRA'],'weapon'=>['espada','florete','sable','mixta'],'format'=>['individual','equipos','ambas'],'status'=>['provisional','confirmado','cancelado']];
  $city=sanitize_text_field($p['city']??'');if(mb_strlen($city)>150)return new WP_Error('invalid_city','Ciudad demasiado larga (máximo 150)',['status'=>400]);
  $category=sanitize_text_field($p['category']??'absoluta');if(mb_strlen($category)>80)return new WP_Error('invalid_category','Categoría demasiado larga (máximo 80)',['status'=>400]);
  $notes=sanitize_textarea_field($p['notes']??'');if(mb_strlen($notes)>5000)return new WP_Error('invalid_notes','Notas demasiado largas (máximo 5000)',['status'=>400]);
  $out=['title'=>$title,'start_date'=>$start,'end_date'=>$end,'city'=>$city,'category'=>$category,'notes'=>$notes];
  foreach($choices as $key=>$values){$v=sanitize_text_field($p[$key]??$values[0]);if(!in_array($v,$values,true))return new WP_Error('invalid_'.$key,'Valor no válido: '.$key,['status'=>400]);$out[$key]=$v;}
  $raw_url=trim($p['source_url']??'');$url=$raw_url===''?'':esc_url_raw($raw_url,['http','https']);if($raw_url!==''&&($url===''||mb_strlen($url)>500||!wp_http_validate_url($url)))return new WP_Error('invalid_url','URL de origen no válida (solo http o https, máximo 500 caracteres)',['status'=>400]);$out['source_url']=$url;
  return $out;
 }
 static function date($raw){$raw=is_string($raw)?$raw:'';if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$raw))return false;$dt=DateTimeImmutable::createFromFormat('!Y-m-d',$raw);return $dt&&$dt->format('Y-m-d')===$raw?$raw:false;}
 static function create_event($request){global $wpdb;$data=self::validate($request);if(is_wp_error($data))return $data;$data['created_at']=current_time('mysql');$data['updated_at']=current_time('mysql');if(!$wpdb->insert(self::table(),$data))return new WP_Error('db_error','No se pudo guardar',['status'=>500]);return new WP_REST_Response(['id'=>(int)$wpdb->insert_id],201);}
 static function update_event($request){global $wpdb;$id=(int)$request['id'];$data=self::validate($request);if(is_wp_error($data))return $data;$data['updated_at']=current_time('mysql');$res=$wpdb->update(self::table(),$data,['id'=>$id]);if($res===false)return new WP_Error('db_error','No se pudo actualizar',['status'=>500]);if($res===0&&!$wpdb->get_var($wpdb->prepare('SELECT id FROM '.self::table().' WHERE id=%d',$id)))return new WP_Error('not_found','No existe',['status'=>404]);return rest_ensure_response(['id'=>$id]);}
 static function delete_event($request){global $wpdb;$res=$wpdb->delete(self::table(),['id'=>(int)$request['id']]);if($res===false)return new WP_Error('db_error','No se pudo eliminar',['status'=>500]);if(!$res)return new WP_Error('not_found','No existe',['status'=>404]);return rest_ensure_response(['deleted'=>true]);}
}
register_activation_hook(__FILE__,['Almedina_Club_Manager','activate']);register_deactivation_hook(__FILE__,['Almedina_Club_Manager','deactivate']);Almedina_Club_Manager::init();
