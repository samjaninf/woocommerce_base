<?php
defined( 'ABSPATH' ) or die( 'You cannot access this script directly' );

// Don't resize images
function getbowtied_filter_image_sizes( $sizes ) 
{
	return array();
}

// Set blog and homepage title
function getbowtied_get_reading_settings( $demo_type )
{
	$homepage_title = '';
	$blog_title = '';
	if (THEME_SLUG == 'theretailer') 
	{
		$homepage_title = 'Home V1 - Ecommerce';
		$blog_title 	= 'Blog';
	} 
	else if (THEME_SLUG == 'shopkeeper') 
	{
		$homepage_title = 'Home V1';
		$blog_title 	= 'Blog';
	} 
	else if (THEME_SLUG == 'merchandiser') 
	{
		$homepage_title = 'Home V1';
		$blog_title 	= 'Design Journal';
	} 
	else if (THEME_SLUG == 'mrtailor') 
	{
		switch($demo_type) 
		{
			case 'default':
				$homepage_title = 'Home V1 / About V1';
				$blog_title 	= 'Blog';
				break;
			case 'indie-store':
				$homepage_title = 'Home Page';
				$blog_title 	= 'Blog';
				break;
			case 'startup':
				$homepage_title = 'Home';
				$blog_title 	= 'Blog';
				break;
			default:
				$homepage_title = 'Home';
				$blog_title 	= 'Blog';
		}
	}

	$rsp['homepage'] = $homepage_title;
	$rsp['blog']	 = $blog_title;


	return $rsp; 
}

// Determine which demo we are importing
function getbowtied_demo_file_url( $demo_type )
{
	global $getbowtied_settings;

	$version = '';
	switch($demo_type) 
	{
		case 'default':
			$version = '';
			break;
		case 'indie-store':
			$version = '_indie';
			break;
		case 'startup':
			$version = '_startup';
			break;
		default:
			$version = '';
	}
	return $getbowtied_settings['demo_xml_file_url'.$version];
}

// Determine theme options file we are importing
function getbowtied_options_file_url( $demo_type )
{
	global $getbowtied_settings;

	$version = '';
	switch($demo_type) 
	{
		case 'default':
			$version = '';
			break;
		case 'indie-store':
			$version = '_indie';
			break;
		case 'startup':
			$version = '_startup';
			break;
		default:
			$version = '';
	}
	return $getbowtied_settings['options_file_url'.$version];
}

// Hook importer into admin init
add_action( 'wp_ajax_getbowtied_demo_importer', 'getbowtied_demo_importer' );
function getbowtied_demo_importer() {
	global $wpdb, $getbowtied_settings;

	if ( current_user_can( 'manage_options' ) ) {
		
		if ( !defined('WP_LOAD_IMPORTERS') ) define('WP_LOAD_IMPORTERS', true); // we are loading importers

		if ( ! class_exists( 'WP_Importer' ) ) { // if main importer class doesn't exist
			include get_home_path() . 'wp-admin/includes/class-wp-importer.php';
		}

		if ( ! class_exists('WP_Import') ) { // if WP importer doesn't exist
			include GETBOWTIED_TOOLS_PATH . 'functions/getbowtied-importer/wordpress-importer.php';
		}

		if ( class_exists( 'WP_Importer' ) && class_exists( 'WP_Import' ) ) { // check for main import class and wp import class

			$demo_type = $_POST['demo_type'];
			$reading_settings = getbowtied_get_reading_settings($demo_type);
			$version = getbowtied_demo_file_url($demo_type);


			add_filter('intermediate_image_sizes_advanced', 'getbowtied_filter_image_sizes');

			if (!is_dir(get_home_path().'/wp-content/uploads/demos/'))
			{
				mkdir(get_home_path().'/wp-content/uploads/demos/');
			}

			/* 
			** Download and save GZ file
			*/

			$theme_demo_xml_file_url = getbowtied_demo_file_url($demo_type);
			
			if( ini_get('allow_url_fopen') ) 
			{
				$remote = gzopen($theme_demo_xml_file_url, "rb");
			}
			else
			{
				$remote = gzopen(get_template_directory() .'/inc/demo/demo.gz', "rb");
			}
			

			$theme_demo_xml_file = get_home_path().'/wp-content/uploads/demos/demo.gz';
			
			if ( $home = fopen($theme_demo_xml_file, "w") )
			{
				while ($string = gzread($remote, 4096)) {
				    if (! fwrite($home, $string, strlen($string)) )
				    {
				    	echo 'Failed to save file.';
				    	exit();
				    }
				}
			}
			else 
			{
				exit();
			}




			gzclose($remote);
			fclose($home);

			/*
			** Import demo content
			*/

			// echo 'Importing demo content...';
			$importer = new WP_Import();
			$importer->fetch_attachments = true;
			ob_start();
			$importer->import($theme_demo_xml_file);
			ob_end_clean();

			if( class_exists('Woocommerce') ) 
			{
				$woopages = array(
					'woocommerce_shop_page_id' => 'Shop',
					'woocommerce_cart_page_id' => 'Cart',
					'woocommerce_checkout_page_id' => 'Checkout',
					'woocommerce_myaccount_page_id' => 'My Account',
				);
				
				foreach($woopages as $woo_page_name => $woo_page_title) {
					$woopage = get_page_by_title( $woo_page_title );
					if(isset( $woopage ) && $woopage->ID) {
						update_option($woo_page_name, $woopage->ID); // Front Page
					}
				}

				flush_rewrite_rules();
			}

			// Set imported menus to registered theme locations
			$locations = get_theme_mod( 'nav_menu_locations' ); // registered menu locations in theme
			$menus = wp_get_nav_menus(); // registered menus

			if($menus) {
				foreach($menus as $menu) 
				{ // assign menus to theme locations
					// echo THEME_SLUG;
					// die();
					if (THEME_SLUG == 'theretailer') 
					{
						// if( $demo_type == 'default' ) 
						// {
							if ( $menu->name == 'Main Navigation' )	
							{
								$locations['primary'] = $menu->term_id;
							}
							else if ( $menu->name == 'Secondary Navigation' )
							{
								$locations['secondary'] = $menu->term_id;
							}
						// }
					} 
					else if (THEME_SLUG == 'shopkeeper') 
					{
						if( $demo_type == 'default' ) 
						{
							if ( $menu->name == 'Main Navigation' ) $locations['main-navigation'] = $menu->term_id;
						}
					}
					else if (THEME_SLUG == 'mrtailor') 
					{
							if ( $menu->name == 'Main Navigation' ) 	$locations['main-navigation'] 		= $menu->term_id;
							else if ( $menu->name == 'Top Bar Menu' ) 	$locations['top-bar-navigation']	= $menu->term_id;
					} 
					else if (THEME_SLUG == 'merchandiser') 
					{
						if ( $menu->name == 'Main Navigation' ) $locations['primary'] = $menu->term_id;
					}
				}
			}

			// set_theme_mod( 'nav_menu_locations', $locations ); // set menus to locations
			// $locations = get_theme_mod( 'nav_menu_locations' );
			// print_r($locations);
			// die();

			// Set reading options
			$homepage 	= get_page_by_title( html_entity_decode($reading_settings['homepage']));
			$blog 		= get_page_by_title( html_entity_decode($reading_settings['blog']));

			if( isset($homepage) && $homepage->ID) {
				update_option( 'show_on_front', 	'page' );
				update_option( 'page_on_front', 	$homepage->ID ); 	// Front Page
				update_option( 'page_for_posts', 	$blog->ID ); 	// Posts Page
			}

			// set_theme_mod( 'nav_menu_locations', $locations ); // set menus to locations


			/*
			** Download and save Theme Options .txt file
			*/

			if (THEME_SLUG != 'merchandiser'):
				$theme_options_file_url = getbowtied_options_file_url($demo_type);
				if ( !$rsp = wp_remote_get($theme_options_file_url) )
				{
					exit();
				}

				$file = $rsp['body'];
				$theme_options_file = get_home_path().'/wp-content/uploads/demos/theme_options.txt';
				$fp = fopen($theme_options_file, "w");
				if ( !fwrite($fp, $file) )
				{
					exit();
				}
				fclose($fp);

			endif;


			// Import Theme Options
			if (THEME_SLUG == 'theretailer'):
				$theme_options_txt = $theme_options_file; // theme options data file
				$theme_options_txt = file_get_contents( $theme_options_txt );
				$imported_smof_data = unserialize( base64_decode( $theme_options_txt )  );
				of_save_options($imported_smof_data);
			else:
				if (THEME_SLUG != 'merchandiser'):
					$file_contents = file_get_contents( $theme_options_file );
		            $options = json_decode($file_contents, true);

		            if (THEME_SLUG == 'mrtailor'):
		            	$redux = ReduxFrameworkInstances::get_instance('mr_tailor_theme_options');
		        	else: 
		        		$redux = ReduxFrameworkInstances::get_instance(THEME_SLUG.'_theme_options');
		        	endif;
		            $redux->set_options($options);
	            endif;
			endif;

			set_theme_mod( 'nav_menu_locations', $locations );

			unlink($theme_options_file);
			unlink($theme_demo_xml_file);

			echo 'imported';
		}
	}
}