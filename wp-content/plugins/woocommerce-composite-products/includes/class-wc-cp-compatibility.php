<?php
/**
 * 3rd-party Extensions Compatibility.
 *
 * @class    WC_CP_Compatibility
 * @version  3.3.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_CP_Compatibility {

	public function __construct() {

		// Addons and NYP support.
		require_once( 'compatibility/class-wc-addons-compatibility.php' );

		// Points and Rewards support.
		if ( class_exists( 'WC_Points_Rewards_Product' ) ) {
			require_once( 'compatibility/class-wc-pnr-compatibility.php' );
		}

		// Pre-orders support.
		if ( class_exists( 'WC_Pre_Orders' ) ) {
			require_once( 'compatibility/class-wc-po-compatibility.php' );
		}

		// Product Bundles support.
		if ( class_exists( 'WC_Bundles' ) ) {
			require_once( 'compatibility/class-wc-pb-compatibility.php' );
		}

		// One Page Checkout support.
		if ( function_exists( 'is_wcopc_checkout' ) ) {
			require_once( 'compatibility/class-wc-opc-compatibility.php' );
		}

		// Cost of Goods support.
		if ( class_exists( 'WC_COG' ) ) {
			require_once( 'compatibility/class-wc-cog-compatibility.php' );
		}

		// Shipstation integration.
		require_once( 'compatibility/class-wc-shipstation-compatibility.php' );

		// WC Quantity Increment support.
		if ( class_exists( 'WooCommerce_Quantity_Increment' ) ) {
			require_once( 'compatibility/class-wc-qi-compatibility.php' );
		}
	}

	/**
	 * Tells if a product is a Name Your Price product, provided that the extension is installed.
	 *
	 * @param  mixed    $product      product or id to check
	 * @return boolean                true if NYP exists and product is a NYP
	 */
	public function is_nyp( $product ) {

		if ( ! class_exists( 'WC_Name_Your_Price_Helpers' ) ) {
			return false;
		}

		if ( WC_Name_Your_Price_Helpers::is_nyp( $product ) ) {
			return true;
		}

		return false;
	}
}
