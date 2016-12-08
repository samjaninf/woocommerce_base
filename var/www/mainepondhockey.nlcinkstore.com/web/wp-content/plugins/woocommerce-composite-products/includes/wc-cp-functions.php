<?php
/**
 * Composite Products compatibility functions and conditional functions.
 *
 * @version 3.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wc_composite_get_template( $file, $data, $empty, $path ) {

	return wc_get_template( $file, $data, $empty, $path );
}

/**
 * wc_get_product_terms() back-compat wrapper.
 *
 * @return array
 */
function wc_composite_get_product_terms( $product_id, $attribute_name, $args ) {

	if ( WC_CP_Core_Compatibility::is_wc_version_gte_2_3() ) {

		return wc_get_product_terms( $product_id, $attribute_name, $args );

	} else {

		$orderby = wc_attribute_orderby( sanitize_title( $attribute_name ) );

		switch ( $orderby ) {
			case 'name' :
				$args = array( 'orderby' => 'name', 'hide_empty' => false, 'menu_order' => false );
			break;
			case 'id' :
				$args = array( 'orderby' => 'id', 'order' => 'ASC', 'menu_order' => false );
			break;
			case 'menu_order' :
				$args = array( 'menu_order' => 'ASC' );
			break;
		}

		$terms = get_terms( sanitize_title( $attribute_name ), $args );

		return $terms;
	}
}

/**
 * True if the current product page is a composite product.
 *
 * @return boolean
 */
function is_composite_product() {

	global $product;

	return function_exists( 'is_product' ) && is_product() && ! empty( $product ) && $product->product_type === 'composite' ? true : false;
}

/**
 * get_variation_default_attribute() back-compat wrapper.
 *
 * @return string
 */
function wc_composite_get_variation_default_attribute( $product, $attribute_name ) {

	if ( WC_CP_Core_Compatibility::is_wc_version_gte_2_4() ) {

		return $product->get_variation_default_attribute( $attribute_name );

	} else {

		$defaults       = $product->get_variation_default_attributes();
		$attribute_name = sanitize_title( $attribute_name );

		return isset( $defaults[ $attribute_name ] ) ? $defaults[ $attribute_name ] : '';
	}
}

/**
 * wc_dropdown_variation_attribute_options() back-compat wrapper.
 */
function wc_composite_dropdown_variation_attribute_options( $args = array() ) {

	if ( WC_CP_Core_Compatibility::is_wc_version_gte_2_4() ) {

		return wc_dropdown_variation_attribute_options( $args );

	} else {

		$args = wp_parse_args( $args, array(
			'options'          => false,
			'attribute'        => false,
			'product'          => false,
			'selected' 	       => false,
			'name'             => '',
			'id'               => '',
			'show_option_none' => __( 'Choose an option', 'woocommerce' )
		) );

		$options   = $args[ 'options' ];
		$product   = $args[ 'product' ];
		$attribute = $args[ 'attribute' ];
		$name      = $args[ 'name' ] ? $args[ 'name' ] : 'attribute_' . sanitize_title( $attribute );
		$id        = $args[ 'id' ] ? $args[ 'id' ] : sanitize_title( $attribute );

		if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
			$attributes = $product->get_variation_attributes();
			$options    = $attributes[ $attribute ];
		}

		echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '">';

		if ( $args[ 'show_option_none' ] ) {
			echo '<option value="">' . esc_html( $args[ 'show_option_none' ] ) . '</option>';
		}

		if ( ! empty( $options ) ) {
			if ( $product && taxonomy_exists( $attribute ) ) {

				// Get terms if this is a taxonomy - ordered. We need the names too.
				$terms = wc_composite_get_product_terms( $product->id, $attribute, array( 'fields' => 'all' ) );

				foreach ( $terms as $term ) {
					if ( in_array( $term->slug, $options ) ) {
						echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args[ 'selected' ] ), $term->slug, false ) . '>' . apply_filters( 'woocommerce_variation_option_name', $term->name ) . '</option>';
					}
				}
			} else {
				foreach ( $options as $option ) {
					echo '<option value="' . esc_attr( sanitize_title( $option ) ) . '" ' . selected( $args[ 'selected' ], sanitize_title( $option ), false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) . '</option>';
				}
			}
		}

		echo '</select>';
	}
}

/**
 * get_option( 'woocommerce_tax_display_shop' ) cache.
 *
 * @return string
 */
function wc_composite_tax_display_shop() {
	$wc_tax_display_shop = WC_CP()->api->cache_get( 'wc_tax_display_shop' );
	if ( null === $wc_tax_display_shop ) {
		$wc_tax_display_shop = get_option( 'woocommerce_tax_display_shop' );
		WC_CP()->api->cache_set( 'wc_tax_display_shop', $wc_tax_display_shop );
	}
	return $wc_tax_display_shop;
}

/**
 * get_option( 'woocommerce_price_decimal_sep' ) cache.
 *
 * @return string
 */
function wc_composite_price_decimal_sep() {
	$wc_price_decimal_sep = WC_CP()->api->cache_get( 'wc_price_decimal_sep' );
	if ( null === $wc_price_decimal_sep ) {
		$wc_price_decimal_sep = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_decimal_sep' ) ), ENT_QUOTES );
		WC_CP()->api->cache_set( 'wc_price_decimal_sep', $wc_price_decimal_sep );
	}
	return $wc_price_decimal_sep;
}

/**
 * get_option( 'woocommerce_price_thousand_sep' ) cache.
 *
 * @return string
 */
function wc_composite_price_thousand_sep() {
	$wc_price_thousand_sep = WC_CP()->api->cache_get( 'wc_price_thousand_sep' );
	if ( null === $wc_price_thousand_sep ) {
		$wc_price_thousand_sep = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ), ENT_QUOTES );
		WC_CP()->api->cache_set( 'wc_price_thousand_sep', $wc_price_thousand_sep );
	}
	return $wc_price_thousand_sep;
}

/**
 * get_option( 'woocommerce_price_num_decimals' ) cache.
 *
 * @return string
 */
function wc_composite_price_num_decimals() {
	$wc_price_num_decimals = WC_CP()->api->cache_get( 'wc_price_num_decimals' );
	if ( null === $wc_price_num_decimals ) {
		$wc_price_num_decimals = absint( get_option( 'woocommerce_price_num_decimals' ) );
		WC_CP()->api->cache_set( 'wc_price_num_decimals', $wc_price_num_decimals );
	}
	return $wc_price_num_decimals;
}

