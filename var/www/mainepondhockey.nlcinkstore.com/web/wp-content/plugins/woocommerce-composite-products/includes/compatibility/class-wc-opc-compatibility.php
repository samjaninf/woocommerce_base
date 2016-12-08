<?php
/**
 * One Page Checkout Compatibility.
 *
 * @version 3.3.0
 * @since   3.3.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_CP_OPC_Compatibility {

	public static function init() {

		// OPC support
		add_action( 'wcopc_composite_add_to_cart', array( __CLASS__, 'opc_single_add_to_cart_composite' ) );
		add_filter( 'wcopc_allow_cart_item_modification', array( __CLASS__, 'opc_disallow_composited_cart_item_modification' ), 10, 4 );
	}

	/**
	 * OPC Single-product bundle-type add-to-cart template
	 *
	 * @param  int  $opc_post_id
	 * @return void
	 */
	public static function opc_single_add_to_cart_composite( $opc_post_id ) {

		global $product;

		// Enqueue scripts
		wp_enqueue_script( 'wc-add-to-cart-composite' );

		// Enqueue styles
		wp_enqueue_style( 'wc-composite-single-css' );

		// Load NYP scripts
		if ( function_exists( 'WC_Name_Your_Price' ) ) {
			WC_Name_Your_Price()->display->nyp_scripts();
		}

		// Enqueue Bundle styles
		if ( class_exists( 'WC_Bundles' ) ) {
			wp_enqueue_style( 'wc-bundle-css' );
		}

		$navigation_style = $product->get_composite_layout_style();
		$components       = $product->get_composite_data();

		ob_start();

		if ( ! empty( $components ) ) {
			wc_get_template( 'single-product/add-to-cart/composite.php', array(
				'navigation_style' => $navigation_style,
				'components'       => $components,
				'product'          => $product
			), '', WC_CP()->plugin_path() . '/templates/' );
		}

		echo str_replace( array( '<form method="post" enctype="multipart/form-data"', '</form>' ), array( '<div', '</div>' ), ob_get_clean() );
	}

	/**
	 * Prevent OPC from managing composited cart items.
	 *
	 * @param  bool   $allow
	 * @param  array  $cart_item
	 * @param  string $cart_item_key
	 * @param  string $opc_id
	 * @return bool
	 */
	public static function opc_disallow_composited_cart_item_modification( $allow, $cart_item, $cart_item_key, $opc_id ) {

		if ( ! empty( $cart_item[ 'composite_parent' ] ) ) {
			return false;
		}

		return $allow;
	}

}

WC_CP_OPC_Compatibility::init();
