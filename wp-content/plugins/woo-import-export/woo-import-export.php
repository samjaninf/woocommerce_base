<?php
/*
Plugin Name: Woo Import Export
Description: Advanced WooCommerce Store Products, Orders, Users, Product Categories, Coupons data Import Export with Multiple Filter, Export Management, Field Management, Scheduled Management.
Version: 1.7.2
Author: VJInfotech
Author URI: http://www.vjinfotech.com

*/
if (!defined('ABSPATH'))
    die("Can't load this file directly");

global $wpdb;

// Plugin version
if ( ! defined( 'WPIE_PLUGIN_VERSION' ) ) {
	define( 'WPIE_PLUGIN_VERSION', '1.7.2' );
}

// Plugin Folder Path
if ( ! defined( 'WPIE_PLUGIN_DIR' ) ) {
	define( 'WPIE_PLUGIN_DIR', WP_PLUGIN_DIR.'/'.dirname(plugin_basename( __FILE__ )) );
}

if(is_ssl()){
    define('WPIE_PLUGIN_URL', str_replace('http://', 'https://', WP_PLUGIN_URL.'/woo-import-export'));
	define('WPIE_CURRENT_PLUGIN_URL', str_replace('http://', 'https://', WP_PLUGIN_URL));
}else{
    define('WPIE_PLUGIN_URL', WP_PLUGIN_URL.'/woo-import-export');
	define('WPIE_CURRENT_PLUGIN_URL', WP_PLUGIN_URL);
}
	
global $WPIE_AJAXURL;

$WPIE_AJAXURL = admin_url('admin-ajax.php');
	
if ( ! defined( 'WPIE_CSS_URL' ) )
	define( 'WPIE_CSS_URL', WPIE_PLUGIN_URL.'/css' );

if ( ! defined( 'WPIE_JS_URL' ) )
	define( 'WPIE_JS_URL', WPIE_PLUGIN_URL.'/js' );
	
if ( ! defined( 'WPIE_IMAGES_URL' ) )
	define( 'WPIE_IMAGES_URL', WPIE_PLUGIN_URL.'/images' );
	
if ( ! defined( 'WPIE_CORE_DIR' ) )
	define( 'WPIE_CORE_DIR', WPIE_PLUGIN_DIR.'/core' );

if ( ! defined( 'WPIE_CLASSES_DIR' ) )
	define( 'WPIE_CLASSES_DIR', WPIE_CORE_DIR.'/classes' );
		
if ( ! defined( 'WPIE_VIEW_DIR' ) )
	define( 'WPIE_VIEW_DIR', WPIE_CORE_DIR.'/views' );
	
if ( ! defined( 'WPIE_TEXTDOMAIN' ) )
	define( 'WPIE_TEXTDOMAIN', 'woo-import-export' );
	
// Plugin site path
if ( ! defined( 'WPIE_PLUGIN_SITE' ) ) 
{
	define( 'WPIE_PLUGIN_SITE', 'http://www.vjinfotech.com' );	
}	
$wpupload_dir 	= wp_upload_dir();
$wpie_upload_dir = $wpupload_dir['basedir'].'/woo-import-export';
$wpie_upload_url = $wpupload_dir['baseurl'].'/woo-import-export';

define('WPIE_UPLOAD_DIR', $wpie_upload_dir);

define('WPIE_UPLOAD_URL', $wpie_upload_url);

wp_mkdir_p($wpie_upload_dir);

global $wpie_import_export, $wpie_product, $wpie_order, $wpie_scheduled, $wpie_user, $wpie_auto_update, $wpie_product_cat, $wpie_coupon;
		
if(file_exists(WPIE_CLASSES_DIR . '/wpie_import_export.class.php'))
{
	require_once( WPIE_CLASSES_DIR . '/wpie_import_export.class.php' );

	$wpie_import_export = new wpie_import_export();	
}

if(file_exists(WPIE_CLASSES_DIR . '/wpie_product.class.php'))
{
	require_once( WPIE_CLASSES_DIR . '/wpie_product.class.php' );

	$wpie_product = new wpie_product();	
}

if(file_exists(WPIE_CLASSES_DIR . '/wpie_order.class.php'))
{
	require_once( WPIE_CLASSES_DIR . '/wpie_order.class.php' );

	$wpie_order = new wpie_order();	
}

if(file_exists(WPIE_CLASSES_DIR . '/wpie_scheduled.class.php'))
{
	require_once( WPIE_CLASSES_DIR . '/wpie_scheduled.class.php' );

	$wpie_scheduled = new wpie_scheduled();	
}

if(file_exists(WPIE_CLASSES_DIR . '/wpie_user.class.php'))
{
	require_once( WPIE_CLASSES_DIR . '/wpie_user.class.php' );

	$wpie_user = new wpie_user();	
}
if(file_exists(WPIE_CLASSES_DIR . '/wpie_product_cat.class.php'))
{
	require_once( WPIE_CLASSES_DIR . '/wpie_product_cat.class.php' );

	$wpie_product_cat = new wpie_product_cat();	
}
if(file_exists(WPIE_CLASSES_DIR . '/wpie_coupon.class.php'))
{
	require_once( WPIE_CLASSES_DIR . '/wpie_coupon.class.php' );

	$wpie_coupon = new wpie_coupon();	
}
if(file_exists(WPIE_CORE_DIR . '/wpie_auto_update/wpie_auto_update.class.php'))
{
	require_once( WPIE_CORE_DIR . '/wpie_auto_update/wpie_auto_update.class.php' );

	$wpie_auto_update = new wpie_auto_update();	
}
?>