<?php
	function getbowtied_load_settings($theme_slug)
	{
		$settings = array(); 

		/*
		** 	STANDARD SETTINGS
		*/

		$settings['woocommerce_docs'] 			= 'http://support.getbowtied.com/hc/en-us/categories/200549461';
		$settings['wordpress_docs'] 			= 'http://support.getbowtied.com/hc/en-us/categories/200561452-WordPress-for-Beginners';
		$settings['getbowtied_url']	 			= 'http://my.getbowtied.com/';
		$settings['getbowtied_update_url'] 		= 'http://my.getbowtied.com/api/update_theme.php';
		$settings['getbowtied_validate_url']	= 'http://my.getbowtied.com/api/api_listener.php';

		switch ($theme_slug)
		{
			case 'merchandiser':

				$settings['theme_docs'] 		= 'http://merchandiser-help.getbowtied.com/hc/en-us';
				$settings['release_notes'] 		= 'http://merchandiser-help.getbowtied.com/hc/en-us/articles/208220445-Updates-History';
				$settings['getting_started']	= 'http://merchandiser-help.getbowtied.com/hc/en-us/articles/207098269-Getting-Started';
				$settings['demo_import_faq']	= 'http://merchandiser-help.getbowtied.com/hc/en-us/articles/211343769';
				$settings['product_key_1']		= 'http://merchandiser-help.getbowtied.com/hc/en-us/articles/211396969';
				$settings['product_key_2']		= 'http://merchandiser-help.getbowtied.com/hc/en-us/articles/211396969#change-domain-name';
				$settings['product_key_3']		= 'http://merchandiser-help.getbowtied.com/hc/en-us/articles/211396969#dev-site';

				$settings['purchase'] 			= 'http://themeforest.net/user/getbowtied';
				$settings['dummy_data_preview'] = 'http://import.getbowtied.com/merchandiser/';
				$settings['demo_xml_file_url']  = 'http://my.getbowtied.com/api/merchandiser/demos/demo.gz';
				// $settings['options_file_url']	= 'http://my.getbowtied.com/api/shopkeeper/theme_options/theme_options.json';
				$settings['demo_image']			= GETBOWTIED_TOOLS_URL.'/img/demos/merchandiser.jpg';
				$settings['customize_link']		= admin_url( 'customize.php');

				$settings['stripe_color']		= '#ffc741';

			break;

			case 'shopkeeper':

				$settings['theme_docs'] 		= 'https://shopkeeper-help.zendesk.com/hc/en-us';
				$settings['release_notes'] 		= 'https://shopkeeper-help.zendesk.com/hc/en-us/articles/207365265-Updates-History';
				$settings['getting_started']	= 'https://shopkeeper-help.zendesk.com/hc/en-us/articles/206678019-Getting-Started';
				$settings['demo_import_faq']	= 'https://shopkeeper-help.zendesk.com/hc/en-us/articles/206725179';
				$settings['product_key_1']		= 'https://shopkeeper-help.zendesk.com/hc/en-us/articles/206679669';
				$settings['product_key_2']		= 'https://shopkeeper-help.zendesk.com/hc/en-us/articles/206679669#change-domain-name';
				$settings['product_key_3']		= 'https://shopkeeper-help.zendesk.com/hc/en-us/articles/206679669#dev-site';

				$settings['purchase'] 			= 'http://themeforest.net/item/shopkeeper-responsive-wordpress-theme/9553045?license=regular&open_purchase_for_item_id=9553045&purchasable=source';
				$settings['dummy_data_preview'] = 'http://import.getbowtied.com/shopkeeper-v1.1/';
				$settings['demo_xml_file_url']  = 'http://my.getbowtied.com/api/shopkeeper/demos/demo.gz';
				$settings['options_file_url']	= 'http://my.getbowtied.com/api/shopkeeper/theme_options/theme_options.json';
				$settings['demo_image']			= GETBOWTIED_TOOLS_URL.'/img/demos/shopkeeper.jpg';

				$customize_url = add_query_arg(
			        'return',
			        urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ),
			        'customize.php'
			    );	
				$settings['customize_link']		= esc_url( $customize_url );

				$settings['stripe_color']		= '#EC7A5C';

			break;

			case 'mrtailor':

				$settings['theme_docs'] 		= 'https://mr-tailor-help.zendesk.com/hc/en-us';
				$settings['release_notes'] 		= 'https://mr-tailor-help.zendesk.com/hc/en-us/articles/207382215-Updates-History';
				$settings['getting_started']	= 'http://mr-tailor-help.getbowtied.com/hc/en-us/articles/206694609-Getting-Started';
				$settings['demo_import_faq']	= 'http://mr-tailor-help.getbowtied.com/hc/en-us/articles/207998855-Demo-Import-FAQs';
				$settings['product_key_1']		= 'http://mr-tailor-help.getbowtied.com/hc/en-us/articles/207382845';
				$settings['product_key_2']		= 'http://mr-tailor-help.getbowtied.com/hc/en-us/articles/207382845#change-domain-name';
				$settings['product_key_3']		= 'http://mr-tailor-help.getbowtied.com/hc/en-us/articles/207382845#dev-site';

				$settings['purchase'] 			= 'http://themeforest.net/item/mr-tailor-responsive-woocommerce-theme/7292110?license=regular&open_purchase_for_item_id=7292110&purchasable=source';
				$settings['customize_link']		= admin_url( 'admin.php?page=theme_options');

				// DEFAULT DEMO
				$settings['demo_image']			= GETBOWTIED_TOOLS_URL.'/img/demos/mr-tailor-main.jpg';
				$settings['dummy_data_preview'] = 'http://import.getbowtied.com/mr-tailor2/';
				$settings['demo_xml_file_url']  = 'http://my.getbowtied.com/api/mr_tailor/demos/default/demo.gz';
				$settings['options_file_url']	= 'http://my.getbowtied.com/api/mr_tailor/demos/default/theme_options.json';

				// INDIE STORE
				$settings['demo_image_indie']			= GETBOWTIED_TOOLS_URL.'/img/demos/mr-tailor-indie.jpg';
				$settings['dummy_data_preview_indie'] 	= 'http://import.getbowtied.com/mr-tailor-indie/';
				$settings['demo_xml_file_url_indie']  	= 'http://my.getbowtied.com/api/mr_tailor/demos/indie/demo.gz';
				$settings['options_file_url_indie']		= 'http://my.getbowtied.com/api/mr_tailor/demos/indie/theme_options.json';

				// STARTUP
				$settings['demo_image_startup']			= GETBOWTIED_TOOLS_URL.'/img/demos/mr-tailor-startup.jpg';
				$settings['dummy_data_preview_startup'] = 'http://import.getbowtied.com/mr-tailor-startup/';
				$settings['demo_xml_file_url_startup']  = 'http://my.getbowtied.com/api/mr_tailor/demos/startup/demo.gz';
				$settings['options_file_url_startup']	= 'http://my.getbowtied.com/api/mr_tailor/demos/startup/theme_options.json';

				$settings['stripe_color']				= '#4a5e7a';

			break;

			case 'theretailer':

				$settings['theme_docs'] 		= 'https://the-retailer-help.zendesk.com/hc/en-us';
				$settings['release_notes'] 		= 'https://the-retailer-help.zendesk.com/hc/en-us/articles/206694069-Updates-History';
				$settings['getting_started']	= 'https://the-retailer-help.zendesk.com/hc/en-us/articles/206693659-Getting-Started';
				$settings['demo_import_faq']	= 'https://the-retailer-help.zendesk.com/hc/en-us/articles/207284119';
				$settings['product_key_1']		= 'https://the-retailer-help.zendesk.com/hc/en-us/articles/206694589';
				$settings['product_key_2']		= 'https://the-retailer-help.zendesk.com/hc/en-us/articles/206694589#change-domain-name';
				$settings['product_key_3']		= 'https://the-retailer-help.zendesk.com/hc/en-us/articles/206694589#dev-site';

				$settings['purchase'] 			= 'http://themeforest.net/item/the-retailer-responsive-wordpress-theme/4287447?license=regular&open_purchase_for_item_id=4287447&purchasable=source&ref=getbowtied';
				$settings['dummy_data_preview'] = 'http://import.getbowtied.com/the-retailer-v1.1/';
				$settings['demo_xml_file_url']  = 'http://my.getbowtied.com/api/the_retailer/demos/demo.gz';
				$settings['options_file_url']	= 'http://my.getbowtied.com/api/the_retailer/theme_options/theme_options.txt';
				$settings['demo_image']			= GETBOWTIED_TOOLS_URL.'/img/demos/the-retailer.jpg';
				$settings['customize_link']		= admin_url( 'admin.php?page=optionsframework');

				$settings['#b39964']			= '#b39964';

			break;

			default:
			break;
		}

		return $settings;
	}

	$getbowtied_settings = getbowtied_load_settings(THEME_SLUG);
?>