<?php
if( ! class_exists( 'GetBowtiedUpdater' ) ) {
	
	class GetBowtiedUpdater {
		
		var $api_url = "http://my.getbowtied.com/api/update_theme.php";
	
		function __construct( $license_key ) {
	
			$this->license_key = $license_key;

			add_filter( 'pre_set_site_transient_update_themes', array( &$this, 'check_for_update' ) );
			add_filter( 'upgrader_pre_download', array( $this, 'upgradeFilter' ), 10, 4 );

			//set_site_transient('update_themes', null);
		}

		function getbowtied_theme_version() {
			$getbowtied_theme = wp_get_theme();
			if($getbowtied_theme->parent()):
				return $getbowtied_theme->parent()->get('Version');
			else:
				return $getbowtied_theme->get('Version');
			endif;
		}
		
		function check_for_update( $transient ) {
			
			global $wp_filesystem;

			if( empty( $transient->checked ) )  {
				return $transient;
			}

			$curr_theme = wp_get_theme();

			$curr_ver =  $this->getbowtied_theme_version();
			// die();

			$url = $this->api_url;

			// $args  = array("k" => $this->license_key,
			// 			   "t" => $curr_theme->name,
			// 			   "d" => get_site_url() 
			// 		);

			$args = array(
						'method' => 'POST',
						'timeout' => 30,
						'body' => array( "k" => $this->license_key,  "t" => THEME_NAME, "d" => get_site_url()  )
			);

			// echo $url;
			// die();

			$request = wp_remote_post( $url, $args);

			// echo $url;

			if ( is_wp_error( $request ) ) 
			{
		    	return $transient;
		    }	    

		    if ( $request['response']['code'] == 200 ) 
		    {
		    	$data = json_decode( $request['body'] );

		    	if (!empty($data->error) && $data->error == 1)
		    	{
		    		update_option("getbowtied_".THEME_SLUG."_license_expired", 1);
		    		// add_action( 'admin_notices', array(&$this, 'expired_notice') );
   		
		    	}
				
				if(version_compare($curr_ver, $data->version, '<'))
				{

					$transient->response[$curr_theme->get_template()] = array(
						"new_version"	=> 		$data->version,
						"package"		=>	    $data->download_url,
						"url"			=>		'http://www.getbowtied.com'		
					);

					// add_action( 'admin_notices', array(&$this, 'update_notice') );
					update_option("getbowtied_".THEME_SLUG."_remote_ver", $data->version);

				}
				else 
				{
					// update_option("getbowtied_".THEME_SLUG."_update_available", 0);
				}

			}

	
			return $transient;
		}

		function update_notice() {
			update_option("getbowtied_".THEME_SLUG."_update_available", 1);
		}

		// function expired_notice() {
			
		// 	echo '<div class="error getbowtied_admin_notices">
		// 	<p>This site will no longer receive automatic theme updates. Theme\'s <a href="' . admin_url( 'admin.php?page=getbowtied_theme_registration' ) . '">Product Key</a> is no longer active on this domain.</p>
		// 	</div>';
		// }

		public function upgradeFilter( $reply, $package, $updater ) {
			global $wp_filesystem;

			// return new WP_Error ('test', $updater->skin->theme_info['Name'] );

			// Update GetBowtied Theme
			$theme = wp_get_theme();

			$condition = isset( $updater->skin->theme_info ) && $updater->skin->theme_info['Name'] === $theme['Name'];
			if (  $condition ) 
			{
				if ( !get_option("getbowtied_".THEME_SLUG."_license") || (get_option("getbowtied_".THEME_SLUG."_license_expired") == 1) ) 
				{
					return new WP_Error( 'no_credentials', sprintf( __( 'To receive automatic updates license activation is required. Please visit <a href="%1$s" target="_blank">Product Activation</a> to activate your theme.', 'getbowtied' ), admin_url( 'admin.php?page=getbowtied_theme_registration' ) ) );
				}
			}

			// Update GetBowtied distributed VisualComposer
			$condition = isset( $updater->skin->plugin ) && $updater->skin->plugin === 'js_composer/js_composer.php';
			if ( (isset( $updater->skin->plugin )) && ( $updater->skin->plugin === 'js_composer/js_composer.php') );
			{
				// $updater->strings['downloading_package_url'] = __( 'Getting download link...', 'GetBowtied' );
				// $updater->skin->feedback( 'downloading_package_url' );

				$updater->strings['dummy_string'] = __( 'Getting the package...', 'js_composer' );
				$updater->skin->feedback( 'dummy_string' );


				$updater->strings['downloading_package_url'] = __( '', 'js_composer' );
				$updater->skin->feedback( 'downloading_package_url' );

				$updater->strings['downloading_package'] = __( '', 'js_composer' );
				$updater->skin->feedback( 'downloading_package_url' );

				// echo '<pre>';
				// print_r($updater);
				// die();
				// echo '</pre>';
			}

			return $reply;
		}

	}

}