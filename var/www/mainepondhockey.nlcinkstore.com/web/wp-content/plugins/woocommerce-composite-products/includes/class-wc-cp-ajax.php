<?php
/**
 * Composited Products AJAX Handlers.
 *
 * @class 	WC_CP_AJAX
 * @version 3.2.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_CP_AJAX {

	public static function init() {

		// Use WC ajax if available, otherwise fall back to WP ajax.
		if ( WC_CP_Core_Compatibility::use_wc_ajax() ) {

			add_action( 'wc_ajax_woocommerce_show_composited_product', __CLASS__ . '::show_composited_product_ajax' );
			add_action( 'wc_ajax_woocommerce_show_component_options', __CLASS__ . '::show_component_options_ajax' );

		} else {

			add_action( 'wp_ajax_woocommerce_show_composited_product', __CLASS__ . '::show_composited_product_ajax' );
			add_action( 'wp_ajax_woocommerce_show_component_options', __CLASS__ . '::show_component_options_ajax' );

			add_action( 'wp_ajax_nopriv_woocommerce_show_composited_product', __CLASS__ . '::show_composited_product_ajax' );
			add_action( 'wp_ajax_nopriv_woocommerce_show_component_options', __CLASS__ . '::show_component_options_ajax' );
		}
	}

	/**
	 * Display paged component options via ajax. Effective in 'thumbnails' mode only.
	 *
	 * @return void
	 */
	public static function show_component_options_ajax() {

		$data = array();

		header( 'Content-Type: application/json; charset=utf-8' );

		if ( ! check_ajax_referer( 'wc_bto_show_product', 'security', false ) ) {

			echo json_encode( array(
				'result'                  => 'failure',
				'component_scenario_data' => array(),
				'options_markup'          => sprintf( '<div class="woocommerce-error">%s</div>', __( 'Sorry, there are no options to show at the moment. Please refresh the page and try again.', 'woocommerce-composite-products' ) )
			) );

			die();
		}

		if ( isset( $_POST[ 'load_page' ] ) && intval( $_POST[ 'load_page' ] ) > 0 && isset( $_POST[ 'composite_id' ] ) && intval( $_POST[ 'composite_id' ] ) > 0 && ! empty( $_POST[ 'component_id' ] ) ) {

			$component_id    = intval( $_POST[ 'component_id' ] );
			$composite_id    = intval( $_POST[ 'composite_id' ] );
			$selected_option = ! empty( $_POST[ 'selected_option' ] ) ? intval( $_POST[ 'selected_option' ] ) : '';
			$load_page       = intval( $_POST[ 'load_page' ] );

		} else {

			echo json_encode( array(
				'result'                  => 'failure',
				'component_scenario_data' => array(),
				'options_markup'          => sprintf( '<div class="woocommerce-error">%s</div>', __( 'Looks like something went wrong. Please refresh the page and try again.', 'woocommerce-composite-products' ) )
			) );

			die();
		}

		$product = wc_get_product( $composite_id );

		$query_args = array(
			'selected_option' => $selected_option,
			'load_page'       => $load_page,
		);

		// Include orderby argument if posted -- if not, the default ordering method will be used.
		if ( ! empty( $_POST[ 'orderby' ] ) ) {
			$query_args[ 'orderby' ] = $_POST[ 'orderby' ];
		}

		// Include filters argument if posted -- if not, no filters will be applied to the query.
		if ( ! empty( $_POST[ 'filters' ] ) ) {
			$query_args[ 'filters' ] = $_POST[ 'filters' ];
		}

		// Load Component Options.
		$current_options = $product->get_current_component_options( $component_id, $query_args );

		ob_start();

		wc_get_template( 'single-product/component-options.php', array(
			'product'           => $product,
			'component_id'      => $component_id,
			'component_options' => $current_options,
			'component_data'    => $product->get_component_data( $component_id ),
			'selected_option'   => $selected_option,
			'selection_mode'    => $product->get_composite_selections_style()
		), '', WC_CP()->plugin_path() . '/templates/' );

		$component_options_markup = ob_get_clean();

		ob_start();

		wc_get_template( 'single-product/component-options-pagination.php', array(
			'product'             => $product,
			'component_id'        => $component_id,
		), '', WC_CP()->plugin_path() . '/templates/' );

		$component_pagination_markup = ob_get_clean();

		// Calculate scenario data for the displayed component options, including the current selection.
		if ( $selected_option && ! in_array( $selected_option, $current_options ) ) {
			$current_options[] = $selected_option;
		}

		$scenario_data = $product->get_current_component_scenarios( $component_id, $current_options );

		echo json_encode( array(
			'result'                  => 'success',
			'component_scenario_data' => $scenario_data[ 'scenario_data' ][ $component_id ],
			'options_markup'          => $component_options_markup,
			'pagination_markup'       => $component_pagination_markup,
		) );

		die();

	}

	/**
	 * Ajax listener that fetches product markup when a new selection is made.
	 *
	 * @param  mixed    $product_id
	 * @param  mixed    $item_id
	 * @param  mixed    $container_id
	 * @return string
	 */
	public static function show_composited_product_ajax( $product_id = '', $component_id = '', $composite_id = '' ) {

		global $product;

		header( 'Content-Type: application/json; charset=utf-8' );

		if ( ! check_ajax_referer( 'wc_bto_show_product', 'security', false ) ) {

			echo json_encode( array(
				'result' => 'failure',
				'reason' => 'wc_bto_show_product nonce incorrect',
				'markup' => sprintf( '<div class="component_data woocommerce-error" data-component_set="false" data-price="0" data-regular_price="0" data-product_type="invalid-data">%s</div>', __( 'Sorry, the selected item cannot be purchased at the moment. Please refresh the page and try again.', 'woocommerce-composite-products' ) )
			) );

			die();
		}

		if ( isset( $_POST[ 'product_id' ] ) && intval( $_POST[ 'product_id' ] ) > 0 && isset( $_POST[ 'component_id' ] ) && ! empty( $_POST[ 'component_id' ] ) && isset( $_POST[ 'composite_id' ] ) && ! empty( $_POST[ 'composite_id' ] ) ) {

			$product_id   = intval( $_POST[ 'product_id' ] );
			$component_id = intval( $_POST[ 'component_id' ] );
			$composite_id = intval( $_POST[ 'composite_id' ] );

		} else {

			echo json_encode( array(
				'result' => 'failure',
				'reason' => 'required params missing',
				'markup' => sprintf( '<div class="component_data woocommerce-error" data-component_set="false" data-price="0" data-regular_price="0" data-product_type="invalid-data">%s</div>', __( 'Sorry, the selected item cannot be purchased at the moment.', 'woocommerce-composite-products' ) )
			) );

			die();
		}

		$composite          = wc_get_product( $composite_id );

		$composited_product = $composite->get_composited_product( $component_id, $product_id );
		$product            = $composited_product->get_product();

		if ( ! $product || ! $composited_product->is_purchasable() ) {

			echo json_encode( array(
				'result' => 'failure',
				'reason' => 'product does not exist or is not purchasable',
				'markup' => sprintf( '<div class="component_data woocommerce-error" data-component_set="false" data-price="0" data-regular_price="0" data-product_type="invalid-product">%s</div>', __( 'Sorry, the selected item cannot be purchased at the moment.', 'woocommerce-composite-products' ) )
			) );

			die();
		}

		$composite->sync_composite();

		ob_start();

 		WC_CP()->api->apply_composited_product_filters( $product, $component_id, $composite );

		do_action( 'woocommerce_composite_show_composited_product', $product, $component_id, $composite );

		WC_CP()->api->remove_composited_product_filters();

		$output = ob_get_clean();

		echo json_encode( array(
			'result' => 'success',
			'markup' => $output,
		) );

		die();
	}

}

WC_CP_AJAX::init();
