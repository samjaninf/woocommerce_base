<?php
/*
Plugin Name: GetBowtied Tools
Plugin URI: http://www.getbowtied.com/
Description: A simple helper that enables you to install the plugins coming with your GetBowtied theme, import demo content and set up automatic updates.
Author: GetBowtied
Author URI: http://www.getbowtied.com
Version: 1.2.2
Text Domain: getbowtied
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/* Main Plugin File */
function getbowtied_plugin_activate() {

  add_option( 'getbowtied_activated_plugin', 'getbowtied-tools' );

  /* activation code here */
}
register_activation_hook( __FILE__, 'getbowtied_plugin_activate' );

function getbowtied_load_plugin() {

    if ( is_admin() && get_option( 'getbowtied_activated_plugin' ) == 'getbowtied-tools' ) {

        delete_option( 'getbowtied_activated_plugin' );

        /* do stuff once right after activation */
        wp_redirect(admin_url("admin.php?page=getbowtied_theme"));
    }
}
add_action( 'admin_init', 'getbowtied_load_plugin' );

if( ! class_exists( 'Getbowtied_Admin_Pages' ) ) {

	$plugin_ver = '1.2.2';
	define( 'GETBOWTIED_TOOLS_PATH', plugin_dir_path( __FILE__ ) );
	require_once GETBOWTIED_TOOLS_PATH.'functions/helpers.php';
	require_once GETBOWTIED_TOOLS_PATH.'functions/importer.php';
	require_once GETBOWTIED_TOOLS_PATH.'functions/getbowtied-tgm/class-tgm-plugin-activation.php';
	require_once GETBOWTIED_TOOLS_PATH.'functions/getbowtied-tgm/plugins.php';
	require_once GETBOWTIED_TOOLS_PATH.'pages/global/settings.php';

	$theme_name = getbowtied_parent_theme_name();

	if ( ('Merchandiser' !== $theme_name) && ('Mr. Tailor' !== $theme_name) && ('Shopkeeper' !== $theme_name) && ('The Retailer' !== $theme_name) ) {
		return;
	}


	class Getbowtied_Admin_Pages {		
		
		protected $settings;

		// =============================================================================
		// Construct
		// =============================================================================

		function __construct() {	

			global $getbowtied_settings;

			$this->settings = $getbowtied_settings;
			add_action( 'register_sidebar', 		array( $this, 'getbowtied_theme_admin_init' ) );
			add_action( 'admin_menu', 				array( $this, 'getbowtied_theme_admin_menu' ) );
			add_action( 'admin_menu', 				array( $this, 'getbowtied_theme_admin_submenu_registration' ) );
			add_action( 'admin_menu', 				array( $this, 'getbowtied_edit_admin_menus' ) );

			if (THEME_SLUG != 'theretailer' && THEME_SLUG != 'mrtailor'):
			add_action( 'admin_menu', 				array( $this, 'getbowtied_customizer_menu' ) );
			endif;

			add_action( 'admin_init', 				array( $this, 'getbowtied_theme_update') );
			add_action( 'after_switch_theme', 		array( $this, 'getbowtied_activation_redirect' ) );
			add_action( 'admin_notices', 			array( $this, 'getbowtied_admin_notices' ) );
			add_action( 'admin_notices', 			array( $this, 'update_notice' ) );
			add_action( 'admin_enqueue_scripts', 	array( $this, 'getbowtied_theme_admin_pages' ) );

			if (THEME_SLUG == 'theretailer')
			{
				if (get_option("getbowtied_the_retailer_license_expired"))
				{
					update_option("getbowtied_".THEME_SLUG."_license_expired", get_option("getbowtied_the_retailer_license_expired"));
					update_option("getbowtied_the_retailer_license_expired", '');
				}

				if (get_option("getbowtied_the_retailer_license"))
				{
					update_option("getbowtied_".THEME_SLUG."_license",  get_option("getbowtied_the_retailer_license"));
					update_option("getbowtied_the_retailer_license", '');
				}
			}

			if (THEME_SLUG == 'mrtailor')
			{
				if (get_option("getbowtied_mr_tailor_license_expired"))
				{
					update_option("getbowtied_".THEME_SLUG."_license_expired", get_option("getbowtied_mr_tailor_license_expired"));
					update_option("getbowtied_mr_tailor_license_expired", '');
				}

				if (get_option("getbowtied_mr_tailor_license"))
				{
					update_option("getbowtied_".THEME_SLUG."_license",  get_option("getbowtied_mr_tailor_license"));
					update_option("getbowtied_mr_tailor_license", '');
				}
			}

			if (!get_option("getbowtied_".THEME_SLUG."_license_expired"))
			{
				update_option("getbowtied_".THEME_SLUG."_license_expired", 0);
			}
		}

		function settings()
		{
			return $this->settings;
		}
	
		// =============================================================================
		// Menus
		// =============================================================================

		function getbowtied_theme_admin_menu() {		
	
			if (!function_exists('current_user_can')):
				require_once(ABSPATH. "wp-includes/pluggable.php");
			endif;

			if (!current_user_can('administrator')):
				return;
			endif;	

			$getbowtied_menu_welcome = add_menu_page(
				THEME_NAME,
				THEME_NAME,
				'administrator',
				'getbowtied_theme',
				array( $this, 'getbowtied_theme_tools_page' ),
				'',
				3
			);
		}

		function getbowtied_theme_admin_submenu_registration() {

			if (!function_exists('current_user_can')):
				require_once(ABSPATH. "wp-includes/pluggable.php");
			endif;

			if (!current_user_can('administrator')):
				return;
			endif;	

			$getbowtied_submenu_welcome = add_submenu_page(
				'getbowtied_theme',
				__( 'Product Key', 'getbowtied' ),
				__( 'Product Key', 'getbowtied' ),
				'administrator',
				'getbowtied_theme_registration',
				array( $this, 'getbowtied_theme_registration_page' )
			);
		}

		function getbowtied_customizer_menu() {		
			$customize_url = add_query_arg(
		        'return',
		        urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ),
		        'customize.php'
		    );	

			add_submenu_page(
				'getbowtied_theme',
		        __( 'Customize' ),
		        __( 'Customize' ),
		        'customize',
		        esc_url( $customize_url ),
		        ''
		    );

		}


		// =============================================================================
		// Pages
		// =============================================================================

		function getbowtied_theme_welcome_page() 
		{
			require_once GETBOWTIED_TOOLS_PATH.'pages/welcome_theme.php';
		}

		function getbowtied_theme_registration_page()
		{
			require_once GETBOWTIED_TOOLS_PATH.'pages/registration.php';
		}

		function getbowtied_theme_tools_page()
		{
			require_once GETBOWTIED_TOOLS_PATH.'pages/tools.php';
		}

		function getbowtied_theme_demos_page()
		{
			require_once GETBOWTIED_TOOLS_PATH.'pages/demos.php';
		}

		function getbowtied_theme_help_center_page()
		{
			require_once GETBOWTIED_TOOLS_PATH.'pages/help-center.php';
		}

		function getbowtied_welcome_page() 
		{
			require_once GETBOWTIED_TOOLS_PATH.'pages/welcome.php';
		}


		// =============================================================================
		// Styles / Scripts
		// =============================================================================

		function getbowtied_theme_admin_pages() {
			wp_enqueue_style(	"getbowtied_theme_admin_css",				GETBOWTIED_TOOLS_URL. "css/styles.css", 	false, '1.1', "all" );
			wp_enqueue_script(	"getbowtied_theme_demos_js", 				GETBOWTIED_TOOLS_URL. "js/scripts.js", 	array(), false, null );			
		}

		// =============================================================================
		// Plug-ins
		// =============================================================================

		function getbowtied_theme_plugin_links( $item ) 
		{
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$installed_plugins = get_plugins();

			$item['sanitized_plugin'] = $item['name'];

			// We have a repo plugin
			if ( ! $item['version'] ) {
				$item['version'] = TGM_Plugin_Activation::$instance->does_plugin_have_update( $item['slug'] );
			}

			/** We need to display the 'Install' hover link */
			if ( ! isset( $installed_plugins[$item['file_path']] ) ) {
				$actions = array(
					'install' => sprintf(
						'<a href="%1$s" class="button" title="Install %2$s">Install Now</a>',
						esc_url( wp_nonce_url(
							add_query_arg(
								array(
									'page'          => urlencode( TGM_Plugin_Activation::$instance->menu ),
									'plugin'        => urlencode( $item['slug'] ),
									'plugin_name'   => urlencode( $item['sanitized_plugin'] ),
									// 'plugin_source' => urlencode( $item['source'] ),
									'tgmpa-install' => 'install-plugin',
									'return_url'    => network_admin_url( 'admin.php?page=getbowtied_theme' )
								),
								TGM_Plugin_Activation::$instance->get_tgmpa_url()
							),
							'tgmpa-install',
							'tgmpa-nonce'
						) ),
						$item['sanitized_plugin']
					),
				);
			}
			/** We need to display the 'Activate' hover link */
			elseif ( is_plugin_inactive( $item['file_path'] ) ) {
				$actions = array(
					'activate' => sprintf(
						'<a href="%1$s" class="button button-primary" title="Activate %2$s">Activate</a>',
						esc_url( add_query_arg(
							array(
								'plugin'               => urlencode( $item['slug'] ),
								'plugin_name'          => urlencode( $item['sanitized_plugin'] ),
								// 'plugin_source'        => urlencode( $item['source'] ),
								'getbowtied-activate'       => 'activate-plugin',
								'getbowtied-activate-nonce' => wp_create_nonce( 'getbowtied-activate' ),
							),
							admin_url( 'admin.php?page=getbowtied_theme' )
						) ),
						$item['sanitized_plugin']
					),
				);
			}
			/** We need to display the 'Update' hover link */
			elseif ( version_compare( $installed_plugins[$item['file_path']]['Version'], $item['version'], '<' ) ) {
				$actions = array(
					'update' => sprintf(
						'<a href="%1$s" class="button button-update" title="Install %2$s"><span class="dashicons dashicons-update"></span> Update</a>',
						wp_nonce_url(
							add_query_arg(
								array(
									'page'          => urlencode( TGM_Plugin_Activation::$instance->menu ),
									'plugin'        => urlencode( $item['slug'] ),

									'tgmpa-update'  => 'update-plugin',
									// 'plugin_source' => urlencode( $item['source'] ),
									'version'       => urlencode( $item['version'] ),
									'return_url'    => network_admin_url( 'admin.php?page=getbowtied_theme' )
								),
								TGM_Plugin_Activation::$instance->get_tgmpa_url()
							),
							'tgmpa-update',
							'tgmpa-nonce'
						),
						$item['sanitized_plugin']
					),
				);
			} elseif ( is_plugin_active( $item['file_path'] ) ) {
				$actions = array(
					'deactivate' => sprintf(
						'<a href="%1$s" class="button" title="Deactivate %2$s">Deactivate</a>',
						esc_url( add_query_arg(
							array(
								'plugin'                 => urlencode( $item['slug'] ),
								'plugin_name'            => urlencode( $item['sanitized_plugin'] ),
								// 'plugin_source'          => urlencode( $item['source'] ),
								'getbowtied-deactivate'       => 'deactivate-plugin',
								'getbowtied-deactivate-nonce' => wp_create_nonce( 'getbowtied-deactivate' ),
							),
							admin_url( 'admin.php?page=getbowtied_theme' )
						) ),
						$item['sanitized_plugin']
					),
				);
			}

			return $actions;
		}

		// =============================================================================
		// Theme Updater
		// =============================================================================

		function getbowtied_theme_update() 
		{
			global $wp_filesystem;

				if (get_option("getbowtied_".THEME_SLUG."_license") && (get_option("getbowtied_".THEME_SLUG."_license_expired") == 0))
				{
					$license_key = get_option("getbowtied_".THEME_SLUG."_license");
				}
				else
				{
					$license_key = '';
				}
				
				require_once( GETBOWTIED_TOOLS_PATH . 'functions/_class-updater.php' );
				
				$theme_update = new GetBowtiedUpdater( $license_key );
		
		}

		function getbowtied_admin_notices() {

			$remote_ver = get_option("getbowtied_".THEME_SLUG."_remote_ver") ? get_option("getbowtied_".THEME_SLUG."_remote_ver") : $this->getbowtied_theme_version();
			$local_ver = $this->getbowtied_theme_version();

			if(!version_compare($local_ver, $remote_ver, '<'))
		    {
				if ( (!get_option("getbowtied_".THEME_SLUG."_license") && ( get_option("getbowtied_".THEME_SLUG."_license_expired") == 0 ) )
					|| (get_option("getbowtied_".THEME_SLUG."_license") && ( get_option("getbowtied_".THEME_SLUG."_license_expired") == 1 )) ){
					
					if ( ! isset($_COOKIE["notice_product_key"]) || $_COOKIE["notice_product_key"] != "1" ) {
						echo '<div class="notice is-dismissible error getbowtied_admin_notices notice_product_key">
						<p><b>' . THEME_NAME . '</b> - Enter your product key to start receiving automatic updates and support. Go to <a href="' . admin_url( 'admin.php?page=getbowtied_theme_registration' ) . '">Product Activation</a>.</p>
						</div>';
					}

				}
			}
		}

		function validate_license($license_key)
		{
			if (empty($license_key))
			{
				return FALSE;
			}
			else
			{
				$api_url = "http://my.getbowtied.com/api/api_listener.php";
				$theme = wp_get_theme();
				$args = array(
								'method' => 'POST',
								'timeout' => 30,
								'body' => array( 'l' => $license_key,  'd' => get_site_url(), 't' => THEME_NAME )
						);
				
				$response = wp_remote_post( $api_url, $args );

				if ( is_wp_error( $response ) ) {
				    $error_message = $response->get_error_message();
				    $request_msg = 'Something went wrong:'.$error_message.'. Please try again!';
				} else {
				  	$rsp = json_decode($response['body']);
				  	$request_msg = '';
				  	
				  	switch ($rsp->status)
				  	{
				  		case '0':
				  		$response['text'] = 'Something went wrong. Please try again!';
				  		break;

				  		case '1':
				  		update_option("getbowtied_".THEME_SLUG."_license", $license_key);
				  		update_option("getbowtied_".THEME_SLUG."_license_expired", 0);
				  		break;

				  		case '2':
				  		$response['text'] = 'The product key you entered is not valid.';
				  		break;

				  		case '3':
				  		$response['domain'] = $rsp->correct_domain;
				  		$response['text'] = 'The product key you entered is not valid.';
				  		break;

				  	}
				}

			 	return $response;

			}
		}
		
		function update_notice()
		{
			$remote_ver = get_option("getbowtied_".THEME_SLUG."_remote_ver") ? get_option("getbowtied_".THEME_SLUG."_remote_ver") : $this->getbowtied_theme_version();
			$local_ver = $this->getbowtied_theme_version();

		    if(version_compare($local_ver, $remote_ver, '<'))
		    {
				if( function_exists('wp_get_theme') ) {
					$theme_name = '<strong>'. wp_get_theme(get_template()) .'</strong>';
				}

				if ( ( !get_option("getbowtied_".THEME_SLUG."_license") && ( get_option("getbowtied_".THEME_SLUG."_license_expired") == 0 ) )
				|| (get_option("getbowtied_".THEME_SLUG."_license") && ( get_option("getbowtied_".THEME_SLUG."_license_expired") == 1 )) ) {

					echo '<div class="notice is-dismissible error getbowtied_admin_notices">
					<p>There is an update available for the ' . $theme_name . ' theme. Go to <a href="' . admin_url( 'admin.php?page=getbowtied_theme_registration' ) . '">Product Activation</a> to enable theme updates.</p>
					</div>';

				}

				if ( get_option("getbowtied_".THEME_SLUG."_license") && ( get_option("getbowtied_".THEME_SLUG."_license_expired") == 0 ) ) {

				echo '<div class="notice is-dismissible error getbowtied_admin_notices">
				<p>There is an update available for the ' . $theme_name . ' theme. <a href="' . admin_url() . 'update-core.php">Update now</a>.</p>
				</div>';

				}
		    }
		}

		// =============================================================================
		// Admin Redirect
		// =============================================================================

		function getbowtied_activation_redirect(){
			if ( current_user_can( 'edit_theme_options' ) ) {
				header('Location:'.admin_url().'admin.php?page=getbowtied_theme');
			}
		}

		// =============================================================================
		// Admin Init
		// =============================================================================

		function getbowtied_theme_admin_init() {

			if ( isset( $_GET['getbowtied-deactivate'] ) && $_GET['getbowtied-deactivate'] == 'deactivate-plugin' ) {
				
				check_admin_referer( 'getbowtied-deactivate', 'getbowtied-deactivate-nonce' );

				if ( ! function_exists( 'get_plugins' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}

				$plugins = get_plugins();

				foreach ( $plugins as $plugin_name => $plugin ) {
					if ( $plugin['Name'] == $_GET['plugin_name'] ) {
							deactivate_plugins( $plugin_name );
					}
				}

			} 

			if ( isset( $_GET['getbowtied-activate'] ) && $_GET['getbowtied-activate'] == 'activate-plugin' ) {
				
				check_admin_referer( 'getbowtied-activate', 'getbowtied-activate-nonce' );

				if ( ! function_exists( 'get_plugins' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}

				$plugins = get_plugins();

				foreach ( $plugins as $plugin_name => $plugin ) {
					if ( $plugin['Name'] == $_GET['plugin_name'] ) {
						activate_plugin( $plugin_name );
					}
				}

			}

		}

		
		// =============================================================================
		// Edit Menus
		// =============================================================================

		function getbowtied_edit_admin_menus() {
			global $submenu;

			if (!function_exists('current_user_can')):
				require_once(ABSPATH. "wp-includes/pluggable.php");
			endif;

			if (!current_user_can('administrator')):
				return;
			endif;	
			$submenu['getbowtied_theme'][0][0] = __( 'Tools', 'getbowtied' );
		}

		
		// =============================================================================
		// Let to num
		// =============================================================================

		function let_to_num( $size ) {
			$l   = substr( $size, -1 );
			$ret = substr( $size, 0, -1 );
			switch ( strtoupper( $l ) ) {
				case 'P':
					$ret *= 1024;
				case 'T':
					$ret *= 1024;
				case 'G':
					$ret *= 1024;
				case 'M':
					$ret *= 1024;
				case 'K':
					$ret *= 1024;
			}
			return $ret;
		}

		function getbowtied_theme_version() {
			$getbowtied_theme = wp_get_theme();
			if($getbowtied_theme->parent()):
				return $getbowtied_theme->parent()->get('Version');
			else:
				return $getbowtied_theme->get('Version');
			endif;
		}

	}
		
		new Getbowtied_Admin_Pages;

	if ( ! class_exists( 'GetbowtiedToolsUpdater') ) {
		require_once GETBOWTIED_TOOLS_PATH.'functions/plugin-updater.php';

		$plugin_update = new GetbowtiedToolsUpdater($plugin_ver, 'https://my.getbowtied.com/getbowtied-tools/update.php', 'getbowtied-tools/getbowtied-tools.php');
	}
}

