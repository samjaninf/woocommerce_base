<?php
/**
 * Globally accessible functions and filters associated with the Composite type (refactor pending).
 *
 * @class   WC_CP_API
 * @version 3.3.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_CP_API {

	/**
	 * Composited product filter parameters set by 'add_composited_product_filters'.
	 *
	 * @var array
	 */
	public $filter_params;

	/**
	 * General-purpose key/value cache.
	 *
	 * @var array
	 */
	public $cache;

	public function __construct() {
		$this->cache = $this->filter_params = array();
	}

	/**
	 * Simple cache getter.
	 *
	 * @param  string $key
	 * @return mixed
	 */
	public function cache_get( $key ) {
		$value = null;
		if ( ! empty( $this->cache[ $key ] ) ) {
			$value = $this->cache[ $key ];
		}
		return $value;
	}

	/**
	 * Simple cache setter.
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function cache_set( $key, $value ) {
		$this->cache[ $key ] = $value;
	}

	/**
	 * Sets up a WP_Query wrapper object to fetch component options. The query is configured based on the data stored in the 'component_data' array.
	 * Note that the query parameters are filterable - @see WC_CP_Query for details.
	 *
	 * @param  array  $component_data
	 * @param  array  $query_args
	 * @return array
	 */
	public function get_component_options( $component_data, $query_args = array() ) {

		$query = new WC_CP_Query( $component_data, $query_args );

		return $query->get_component_options();
	}

	/**
	 * Price-related filters. Modify composited product prices to take into account component discounts.
	 *
	 * @param  WC_Product           $product
	 * @param  string               $component_id
	 * @param  WC_Product_Composite $composite
	 * @return void
	 */
	public function apply_composited_product_filters( $product, $component_id, $composite ) {

		$component_data = $composite->get_component_data( $component_id );

		$quantity_min   = $component_data[ 'quantity_min' ];
		$quantity_max   = $component_data[ 'quantity_max' ];

		if ( $product->sold_individually === 'yes' ) {
 			$quantity_max = 1;
 			$quantity_min = min( $quantity_min, 1 );
 		}

		$this->filter_params[ 'product' ]             = $product;
 		$this->filter_params[ 'composite' ]           = $composite;
		$this->filter_params[ 'composite_id' ]        = $composite->id;
		$this->filter_params[ 'component_id' ]        = $component_id;
		$this->filter_params[ 'discount' ]            = isset( $component_data[ 'discount' ] ) ? $component_data[ 'discount' ] : 0;
		$this->filter_params[ 'per_product_pricing' ] = $composite->is_priced_per_product();
		$this->filter_params[ 'quantity_min' ]        = $quantity_min;
		$this->filter_params[ 'quantity_max' ]        = $quantity_max;

		add_filter( 'woocommerce_available_variation', array( $this, 'filter_available_variation' ), 10, 3 );
		add_filter( 'woocommerce_get_price', array( $this, 'filter_show_product_get_price' ), 16, 2 );
		add_filter( 'woocommerce_get_regular_price', array( $this, 'filter_show_product_get_regular_price' ), 16, 2 );
		add_filter( 'woocommerce_get_sale_price', array( $this, 'filter_show_product_get_sale_price' ), 16, 2 );
		add_filter( 'woocommerce_get_price_html', array( $this, 'filter_show_product_get_price_html' ), 5, 2 );
		add_filter( 'woocommerce_get_variation_price_html', array( $this, 'filter_show_product_get_price_html' ), 5, 2 );

		add_filter( 'woocommerce_bundles_update_price_meta', array( $this, 'filter_show_product_bundles_update_price_meta' ), 10, 2 );
		add_filter( 'woocommerce_bundle_is_composited', array( $this, 'filter_bundle_is_composited' ), 10, 2 );
		add_filter( 'woocommerce_bundle_is_priced_per_product', array( $this, 'filter_bundle_is_priced_per_product' ), 10, 2 );

		add_filter( 'woocommerce_nyp_html', array( $this, 'filter_show_product_get_nyp_price_html' ), 15, 2 );

		do_action( 'woocommerce_composite_products_apply_product_filters', $product, $component_id, $composite );
	}

	/**
	 * Price-related filters. Modify composited product prices to take into account component discounts.
	 * @deprecated 3.2.0
	 *
	 * @param  array      $args
	 * @param  WC_Product $product
	 * @return void
	 */
	public function add_composited_product_filters( $args, $product = false ) {
		_deprecated_function( 'WC_CP_API::add_composited_product_filters()', '3.2.0', 'WC_CP_API::apply_composited_product_filters()' );

		$composite    = wc_get_product( $args[ 'composite_id' ] );
		$component_id = $args[ 'component_id' ];

		return $this->apply_composited_product_filters( $product, $component_id, $composite );
	}

	/**
	 * Filters variation data in the show_product function.
	 *
	 * @param  mixed                    $variation_data
	 * @param  WC_Product               $bundled_product
	 * @param  WC_Product_Variation     $bundled_variation
	 * @return mixed
	 */
	public function filter_available_variation( $variation_data, $product, $variation ) {

		if ( ! empty ( $this->filter_params ) ) {

			$variation_data[ 'regular_price' ]        = isset( $variation_data[ 'display_regular_price' ] ) ? $variation_data[ 'display_regular_price' ] : $this->get_composited_product_price( $variation, $variation->get_regular_price() );
			$variation_data[ 'price' ]                = isset( $variation_data[ 'display_price' ] ) ? $variation_data[ 'display_price' ] : $this->get_composited_product_price( $variation, $variation->get_price() );

			$variation_data[ 'price_html' ]           = $this->filter_params[ 'per_product_pricing' ] ? ( $variation_data[ 'price_html' ] === '' ? '<span class="price">' . $variation->get_price_html() . '</span>' : $variation_data[ 'price_html' ] ) : '';

			$availability                             = $this->get_composited_item_availability( $variation, $this->filter_params[ 'quantity_min' ] );
			$availability_html                        = empty( $availability[ 'availability' ] ) ? '' : '<p class="stock ' . esc_attr( $availability[ 'class' ] ) . '">' . wp_kses_post( $availability[ 'availability' ] ) . '</p>';

			$variation_data[ 'availability_html' ]    = apply_filters( 'woocommerce_stock_html', $availability_html, $availability[ 'availability' ], $variation );
			$variation_data[ 'is_sold_individually' ] = $variation_data[ 'is_sold_individually' ] === 'yes' && $this->filter_params[ 'quantity_min' ] == 1 ? 'yes' : 'no';

			$variation_data[ 'min_qty' ]              = $this->filter_params[ 'quantity_min' ];
			$variation_data[ 'max_qty' ]              = $this->filter_params[ 'quantity_max' ];
		}

		return $variation_data;
	}

	/**
	 * Filter 'woocommerce_bundle_is_composited'.
	 *
	 * @param  boolean            $is
	 * @param  WC_Product_Bundle  $bundle
	 * @return boolean
	 */
	public function filter_bundle_is_composited( $is, $bundle ) {
		return true;
	}

	/**
	 * Components discounts should not trigger bundle price updates.
	 *
	 * @param  boolean            $is
	 * @param  WC_Product_Bundle  $bundle
	 * @return boolean
	 */
	public function filter_show_product_bundles_update_price_meta( $update, $bundle ) {
		return false;
	}

	/**
	 * Filter 'woocommerce_bundle_is_priced_per_product'. If a composite is not priced per product, this should force composited bundles to revert to static pricing, too, to force bundled items to return a zero price.
	 *
	 * @param  boolean            $is
	 * @param  WC_Product_Bundle  $bundle
	 * @return boolean
	 */
	public function filter_bundle_is_priced_per_product( $is_ppp, $bundle ) {

		if ( ! empty ( $this->filter_params ) ) {

			if ( ! $this->filter_params[ 'per_product_pricing' ] ) {
				return false;
			}
		}

		return $is_ppp;
	}

	/**
	 * Filters get_price_html to include component discounts.
	 *
	 * @param  string     $price_html
	 * @param  WC_Product $product
	 * @return string
	 */
	public function filter_show_product_get_price_html( $price_html, $product ) {

		if ( ! empty ( $this->filter_params ) ) {

			// Tells NYP to back off.
			$product->is_filtered_price_html = 'yes';

			if ( ! $this->filter_params[ 'per_product_pricing' ] ) {

				$price_html = '';

			} else {

				$add_suffix = true;

				// Don't add /pc suffix to products in composited bundles (possibly duplicate).
				if ( isset( $this->filter_params[ 'product' ] ) ) {
					$filtered_product = $this->filter_params[ 'product' ];
					if ( $filtered_product->id != $product->id ) {
						$add_suffix = false;
					}
				}

				if ( $add_suffix ) {
					$suffix     = $this->filter_params[ 'quantity_min' ] > 1 && $product->sold_individually !== 'yes' ? ' ' . __( '/ pc.', 'woocommerce-composite-products' ) : '';
					$price_html = $price_html . $suffix;
				}
			}

			$price_html = apply_filters( 'woocommerce_composited_item_price_html', $price_html, $product, $this->filter_params[ 'component_id' ], $this->filter_params[ 'composite_id' ] );
		}

		return $price_html;
	}

	/**
	 * Filters get_price_html to hide nyp prices in static pricing mode.
	 *
	 * @param  string     $price_html
	 * @param  WC_Product $product
	 * @return string
	 */
	public function filter_show_product_get_nyp_price_html( $price_html, $product ) {

		if ( ! empty ( $this->filter_params ) ) {

			if ( ! $this->filter_params[ 'per_product_pricing' ] ) {

				$price_html = '';

			}
		}

		return $price_html;
	}

	/**
	 * Filters get_price to include component discounts.
	 *
	 * @param  double     $price
	 * @param  WC_Product $product
	 * @return string
	 */
	public function filter_show_product_get_price( $price, $product ) {

		if ( ! empty ( $this->filter_params ) ) {

			if ( $price === '' ) {
				return $price;
			}

			if ( ! $this->filter_params[ 'per_product_pricing' ] ) {
				return ( double ) 0;
			}

			if ( isset( $product->bundled_item_price ) ) {
				$regular_price = $product->bundled_item_price;
			} else {

				if ( apply_filters( 'woocommerce_composited_product_discount_from_regular', true, $this->filter_params[ 'component_id' ], $this->filter_params[ 'composite_id' ] ) ) {
					$regular_price = $product->get_regular_price();
				} else {
					$regular_price = $price;
				}
			}

			$discount = $this->filter_params[ 'discount' ];

			return empty( $discount ) ? $price : round( ( double ) $regular_price * ( 100 - $discount ) / 100, wc_composite_price_num_decimals() );
		}

		return $price;
	}

	/**
	 * Filters get_regular_price to include component discounts.
	 *
	 * @param  double     $price
	 * @param  WC_Product $product
	 * @return string
	 */
	public function filter_show_product_get_regular_price( $price, $product ) {

		if ( ! empty ( $this->filter_params ) ) {

			if ( ! $this->filter_params[ 'per_product_pricing' ] ) {
				return ( double ) 0;
			}

			return empty( $product->regular_price ) ? $product->price : $price;
		}

		return $price;
	}

	/**
	 * Filters get_sale_price to include component discounts.
	 *
	 * @param  double     $price
	 * @param  WC_Product $product
	 * @return string
	 */
	public function filter_show_product_get_sale_price( $price, $product ) {

		if ( ! empty ( $this->filter_params ) ) {

			if ( ! $this->filter_params[ 'per_product_pricing' ] ) {
				return ( double ) 0;
			}

			$discount = $this->filter_params[ 'discount' ];

			return empty( $discount ) ? $price : $this->filter_show_product_get_price( $product->price, $product );
		}

		return $price;
	}

	/**
	 * Remove price filters. @see add_composited_product_filters.
	 *
	 * @return void
	 */
	public function remove_composited_product_filters() {

		do_action( 'woocommerce_composite_products_remove_product_filters', $this->filter_params );

		$this->filter_params = array();

		remove_filter( 'woocommerce_available_variation', array( $this, 'filter_available_variation' ), 10, 3 );
		remove_filter( 'woocommerce_get_price', array( $this, 'filter_show_product_get_price' ), 16, 2 );
		remove_filter( 'woocommerce_get_regular_price', array( $this, 'filter_show_product_get_regular_price' ), 16, 2 );
		remove_filter( 'woocommerce_get_sale_price', array( $this, 'filter_show_product_get_sale_price' ), 16, 2 );
		remove_filter( 'woocommerce_get_price_html', array( $this, 'filter_show_product_get_price_html' ), 5, 2 );
		remove_filter( 'woocommerce_get_variation_price_html', array( $this, 'filter_show_product_get_price_html' ), 5, 2 );

		remove_filter( 'woocommerce_nyp_html', array( $this, 'filter_show_product_get_nyp_price_html' ), 15, 2 );

		remove_filter( 'woocommerce_bundle_is_priced_per_product', array( $this, 'filter_bundle_is_priced_per_product' ), 10, 2 );
		remove_filter( 'woocommerce_bundle_is_composited', array( $this, 'filter_bundle_is_composited' ), 10, 2 );
		remove_filter( 'woocommerce_bundles_update_price_meta', array( $this, 'filter_show_product_bundles_update_price_meta' ), 10, 2 );
	}

	/**
	 * Get the shop price of a product incl or excl tax, depending on the 'woocommerce_tax_display_shop' setting.
	 *
	 * @param  WC_Product $product
	 * @param  double $price
	 * @return double
	 */
	public function get_composited_product_price( $product, $price = '' ) {

		if ( $price === '' ) {
			$price = $product->get_price();
		}

		if ( wc_composite_tax_display_shop() === 'excl' ) {
			$product_price = $product->get_price_excluding_tax( 1, $price );
		} else {
			$product_price = $product->get_price_including_tax( 1, $price );
		}

		return $product_price;
	}

	/**
	 * Used throughout the extension instead of 'wc_price'.
	 *
	 * @param  double $price
	 * @return string
	 */
	public function get_composited_item_price_string_price( $price, $args = array() ) {

		$return          = '';
		$num_decimals    = wc_composite_price_num_decimals();
		$currency        = isset( $args['currency'] ) ? $args['currency'] : '';
		$currency_symbol = get_woocommerce_currency_symbol( $currency );
		$decimal_sep     = wc_composite_price_decimal_sep();
		$thousands_sep   = wc_composite_price_thousand_sep();

		$price = apply_filters( 'raw_woocommerce_price', floatval( $price ) );
		$price = apply_filters( 'formatted_woocommerce_price', number_format( $price, $num_decimals, $decimal_sep, $thousands_sep ), $price, $num_decimals, $decimal_sep, $thousands_sep );

		if ( apply_filters( 'woocommerce_price_trim_zeros', false ) && $num_decimals > 0 ) {
			$price = wc_trim_zeros( $price );
		}

		$return = sprintf( get_woocommerce_price_format(), $currency_symbol, $price );

		return $return;
	}

	/**
	 * Composited product availability function that takes into account min quantity.
	 *
	 * @param  WC_Product $product
	 * @param  int $quantity
	 * @return array
	 */
	public function get_composited_item_availability( $product, $quantity ) {

		$availability = $class = '';

		if ( $product->managing_stock() ) {

			if ( $product->is_in_stock() && $product->get_total_stock() > get_option( 'woocommerce_notify_no_stock_amount' ) && $product->get_total_stock() >= $quantity ) {

				switch ( get_option( 'woocommerce_stock_format' ) ) {

					case 'no_amount' :
						$availability = __( 'In stock', 'woocommerce' );
					break;

					case 'low_amount' :
						if ( $product->get_total_stock() <= get_option( 'woocommerce_notify_low_stock_amount' ) ) {
							$availability = sprintf( __( 'Only %s left in stock', 'woocommerce' ), $product->get_total_stock() );

							if ( $product->backorders_allowed() && $product->backorders_require_notification() ) {
								$availability .= ' ' . __( '(can be backordered)', 'woocommerce' );
							}
						} else {
							$availability = __( 'In stock', 'woocommerce' );
						}
					break;

					default :
						$availability = sprintf( __( '%s in stock', 'woocommerce' ), $product->get_total_stock() );

						if ( $product->backorders_allowed() && $product->backorders_require_notification() ) {
							$availability .= ' ' . __( '(can be backordered)', 'woocommerce' );
						}
					break;
				}

				$class        = 'in-stock';

			} elseif ( $product->backorders_allowed() && $product->backorders_require_notification() ) {

				if ( $product->get_total_stock() >= $quantity || get_option( 'woocommerce_stock_format' ) == 'no_amount' || $product->get_total_stock() <= 0 ) {
					$availability = __( 'Available on backorder', 'woocommerce' );
				} else {
					$availability = __( 'Available on backorder', 'woocommerce' ) . ' ' . sprintf( __( '(only %s left in stock)', 'woocommerce-composite-products' ), $product->get_total_stock() );
				}

				$class        = 'available-on-backorder';

			} elseif ( $product->backorders_allowed() ) {

				$availability = __( 'In stock', 'woocommerce' );
				$class        = 'in-stock';

			} else {

				if ( $product->is_in_stock() && $product->get_total_stock() > get_option( 'woocommerce_notify_no_stock_amount' ) ) {

					if ( get_option( 'woocommerce_stock_format' ) == 'no_amount' ) {
						$availability = __( 'Insufficient stock', 'woocommerce-composite-products' );
					} else {
						$availability = __( 'Insufficient stock', 'woocommerce-composite-products' ) . ' ' . sprintf( __( '(only %s left in stock)', 'woocommerce-composite-products' ), $product->get_total_stock() );
					}

					$class        = 'out-of-stock';

				} else {

					$availability = __( 'Out of stock', 'woocommerce' );
					$class        = 'out-of-stock';
				}
			}

		} elseif ( ! $product->is_in_stock() ) {

			$availability = __( 'Out of stock', 'woocommerce' );
			$class        = 'out-of-stock';
		}

		return apply_filters( 'woocommerce_composited_product_availability', array( 'availability' => $availability, 'class' => $class ), $product );
	}

	/**
	 * Filter scenarios by action type.
	 *
	 * @param  array  $scenarios
	 * @param  string $type
	 * @param  array  $scenario_data
	 * @return array
	 */
	public function filter_scenarios_by_type( $scenarios, $type, $scenario_data ) {

		$filtered = array();

		if ( ! empty( $scenarios ) ) {
			foreach ( $scenarios as $scenario_id ) {

				if ( ! empty( $scenario_data [ 'scenario_settings' ][ 'scenario_actions' ][ $scenario_id ] ) ) {
					$actions = $scenario_data [ 'scenario_settings' ][ 'scenario_actions' ][ $scenario_id ];

					if ( is_array( $actions ) && in_array( $type, $actions ) ) {
						$filtered[] = $scenario_id;
					}
				}
			}
		}

		return $filtered;
	}

	/**
	 * Returns the following arrays:
	 *
	 * 1. $scenarios             - contains all scenario ids.
	 * 2. $scenario_settings     - includes scenario actions and masked components in scenarios.
	 * 3. $scenario_data         - maps every product/variation in a group to the scenarios where it is active.
	 * 4. $defaults_in_scenarios - the scenarios where all default component selections coexist.
	 *
	 * @param  array $bto_scenario_meta     scenarios meta
	 * @param  array $bto_data              component data - values may contain a 'current_component_options' key to generate scenarios for a subset of all component options
	 * @return array
	 */
	public function build_scenarios( $bto_scenario_meta, $bto_data ) {

		$scenarios          = empty( $bto_scenario_meta ) ? array() : array_map( 'strval', array_keys( $bto_scenario_meta ) );
		$common_scenarios   = $scenarios;
		$scenario_data      = array();
		$scenario_settings  = array();

		$compat_group_count = 0;

		// Store the 'actions' associated with every scenario.
		foreach ( $scenarios as $scenario_id ) {

			$scenario_settings[ 'scenario_actions' ][ $scenario_id ] = array();

			if ( isset( $bto_scenario_meta[ $scenario_id ][ 'scenario_actions' ] ) ) {

				$actions = array();

				foreach ( $bto_scenario_meta[ $scenario_id ][ 'scenario_actions' ] as $action_name => $action_data ) {
					if ( isset( $action_data[ 'is_active' ] ) && $action_data[ 'is_active' ] === 'yes' ) {
						$actions[] = $action_name;

						if ( $action_name === 'compat_group' ) {
							$compat_group_count++;
						}
					}
				}

				$scenario_settings[ 'scenario_actions' ][ $scenario_id ] = $actions;

			} else {
				$scenario_settings[ 'scenario_actions' ][ $scenario_id ] = array( 'compat_group' );
				$compat_group_count++;
			}
		}

		$scenario_settings[ 'scenario_actions' ][ '0' ] = array( 'compat_group' );

		// Find which components in every scenario are 'non shaping components' (marked as unrelated).
		if ( ! empty( $bto_scenario_meta ) ) {
			foreach ( $bto_scenario_meta as $scenario_id => $scenario_single_meta ) {

				$scenario_settings[ 'masked_components' ][ $scenario_id ] = array();

				foreach ( $bto_data as $group_id => $group_data ) {

					if ( isset( $scenario_single_meta[ 'modifier' ][ $group_id ] ) && $scenario_single_meta[ 'modifier' ][ $group_id ] === 'masked' ) {
						$scenario_settings[ 'masked_components' ][ $scenario_id ][] = ( string ) $group_id;
					}
				}
			}
		}

		$scenario_settings[ 'masked_components' ][ '0' ] = array();

		// Include the '0' scenario for use when no 'compat_group' scenarios exist.
		if ( $compat_group_count === 0 ) {
			$scenarios[] = '0';
		}

		// Map each product and variation to the scenarios that contain it.
		foreach ( $bto_data as $group_id => $group_data ) {

			$scenario_data[ $group_id ] = array();

			// 'None' option
			if ( $group_data[ 'optional' ] === 'yes' ) {

				$scenarios_for_product = $this->get_scenarios_for_product( $bto_scenario_meta, $group_id, -1, '', 'none' );

				$scenario_data[ $group_id ][ 0 ] = $scenarios_for_product;
			}

			// Component options

			// When indicated, build scenarios only based on a limited set of component options.
			if ( isset( $bto_data[ $group_id ][ 'current_component_options' ] ) ) {

				$component_options = $bto_data[ $group_id ][ 'current_component_options' ];

			// Otherwise run a query to get all component options.
			} else {

				$component_options = $this->get_component_options( $group_data );
			}

			foreach ( $component_options as $product_id ) {

				if ( ! is_numeric( $product_id ) ) {
					continue;
				}

				// Get product type.
				$terms        = get_the_terms( $product_id, 'product_type' );
				$product_type = ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';

				if ( $product_type === 'variable' ) {

					$variations = $this->get_product_variations( $product_id );

					if ( ! empty( $variations ) ) {

						$scenarios_for_product = array();

						foreach ( $variations as $variation_id ) {

							$scenarios_for_variation = $this->get_scenarios_for_product( $bto_scenario_meta, $group_id, $product_id, $variation_id, 'variation' );

							$scenarios_for_product   = array_merge( $scenarios_for_product, $scenarios_for_variation );

							$scenario_data[ $group_id ][ $variation_id ] = $scenarios_for_variation;
						}

						$scenario_data[ $group_id ][ $product_id ] = array_values( array_unique( $scenarios_for_product ) );
					}

				} else {

					$scenarios_for_product = $this->get_scenarios_for_product( $bto_scenario_meta, $group_id, $product_id, '', $product_type );

					$scenario_data[ $group_id ][ $product_id ] = $scenarios_for_product;
				}
			}

			if ( isset( $group_data[ 'default_id' ] ) && $group_data[ 'default_id' ] !== '' ) {

				if ( ! empty ( $scenario_data[ $group_id ][ $group_data[ 'default_id' ] ] ) ) {
					$common_scenarios = array_intersect( $common_scenarios, $scenario_data[ $group_id ][ $group_data[ 'default_id' ] ] );
				} else {
					$common_scenarios = array();
				}
			}
		}

		return array( 'scenarios' => $scenarios, 'scenario_settings' => $scenario_settings, 'scenario_data' => $scenario_data, 'defaults_in_scenarios' => $common_scenarios );
	}

	/**
	 * Returns an array of all scenarios where a particular component option (product/variation) is active.
	 *
	 * @param  array   $scenario_meta
	 * @param  string  $group_id
	 * @param  int     $product_id
	 * @param  int     $variation_id
	 * @param  string  $product_type
	 * @return array
	 */
	public function get_scenarios_for_product( $scenario_meta, $group_id, $product_id, $variation_id, $product_type ) {

		if ( empty( $scenario_meta ) ) {
			return array( '0' );
		}

		$scenarios = array();

		foreach ( $scenario_meta as $scenario_id => $scenario_data ) {

			if ( $this->product_active_in_scenario( $scenario_data, $group_id, $product_id, $variation_id, $product_type ) ) {
				$scenarios[] = ( string ) $scenario_id;
			}
		}

		// All products belong in the '0' scenario.
		$scenarios[] = '0';

		return $scenarios;
	}

	/**
	 * Returns true if a product/variation id of a particular component is present in the scenario meta array. Also @see product_active_in_scenario function.
	 *
	 * @param  array   $scenario_data
	 * @param  string  $group_id
	 * @param  int     $product_id
	 * @return boolean
	 */
	public function scenario_contains_product( $scenario_data, $group_id, $product_id ) {

		if ( isset( $scenario_data[ 'component_data' ] ) && ! empty( $scenario_data[ 'component_data' ][ $group_id ] ) && is_array( $scenario_data[ 'component_data' ][ $group_id ] ) && in_array( $product_id, $scenario_data[ 'component_data' ][ $group_id ] ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns true if a product/variation id of a particular component is present in the scenario meta array. Uses 'scenario_contains_product' but also takes exclusion rules into account.
	 * When checking a variation, also makes sure that the parent product is also tested against the scenario meta array.
	 *
	 * @param  array   $scenario_data
	 * @param  string  $group_id
	 * @param  int     $product_id
	 * @param  int     $variation_id
	 * @param  string  $product_type
	 * @return boolean
	 */
	public function product_active_in_scenario( $scenario_data, $group_id, $product_id, $variation_id, $product_type ) {

		if ( empty( $scenario_data[ 'component_data' ] ) || empty( $scenario_data[ 'component_data' ][ $group_id ] ) ) {
			return true;
		}

		$id = ( $product_type === 'variation' ) ? $variation_id : $product_id;

		if ( $this->scenario_contains_product( $scenario_data, $group_id, 0 ) ) {
			return true;
		}

		$exclude = false;

		if ( isset( $scenario_data[ 'modifier' ][ $group_id ] ) && $scenario_data[ 'modifier' ][ $group_id ] === 'not-in' ) {
			$exclude = true;
		} elseif ( isset( $scenario_data[ 'exclude' ][ $group_id ] ) && $scenario_data[ 'exclude' ][ $group_id ] === 'yes' ) {
			$exclude = true;
		}

		$product_active_in_scenario = false;

		if ( $this->scenario_contains_product( $scenario_data, $group_id, $id ) ) {
			if ( ! $exclude ) {
				$product_active_in_scenario = true;
			} else {
				$product_active_in_scenario = false;
			}
		} else {
			if ( ! $exclude ) {

				if ( $product_type === 'variation' ) {

					if ( $this->scenario_contains_product( $scenario_data, $group_id, $product_id ) ) {
						$product_active_in_scenario = true;
					} else {
						$product_active_in_scenario = false;
					}

				} else {
					$product_active_in_scenario = false;
				}

			} else {

				if ( $product_type === 'variation' ) {

					if ( $this->scenario_contains_product( $scenario_data, $group_id, $product_id ) ) {
						$product_active_in_scenario = false;
					} else {
						$product_active_in_scenario = true;
					}

				} else {
					$product_active_in_scenario = true;
				}
			}
		}

		return $product_active_in_scenario;
	}

	/**
	 * Loads variation ids for a given variable product.
	 *
	 * @param  int    $item_id
	 * @return array
	 */
	public function get_product_variations( $item_id ) {

		$transient_name = 'wc_product_children_ids_' . $item_id;

        if ( false === ( $variations = get_transient( $transient_name ) ) ) {

			$args = array(
				'post_type'   => 'product_variation',
				'post_status' => array( 'publish' ),
				'numberposts' => -1,
				'orderby'     => 'menu_order',
				'order'       => 'asc',
				'post_parent' => $item_id,
				'fields'      => 'ids'
			);

			$variations = get_posts( $args );
		}

		return $variations;

	}

	/**
	 * Loads variation descriptions and ids for a given variable product.
	 *
	 * @param  int $item_id    product id
	 * @return array           array that contains variation ids => descriptions
	 */
	public function get_product_variation_descriptions( $item_id ) {

		$variation_descriptions = array();

		$variations = $this->get_product_variations( $item_id );

		if ( empty( $variations ) ) {
			return $variation_descriptions;
		}

		foreach ( $variations as $variation_id ) {

			$variation_description = $this->get_product_variation_title( $variation_id );

			if ( ! $variation_description ) {
				continue;
			}

			$variation_descriptions[ $variation_id ] = $variation_description;
		}

		return $variation_descriptions;
	}

	/**
	 * Return a formatted product title based on variation id.
	 *
	 * @param  int    $item_id
	 * @return string
	 */
	public function get_product_variation_title( $variation_id ) {

		if ( is_object( $variation_id ) ) {
			$variation = $variation_id;
		} else {
			$variation = wc_get_product( $variation_id );
		}

		if ( ! $variation )
			return false;

		$description = wc_get_formatted_variation( $variation->get_variation_attributes(), true );

		$title = $variation->get_title();
		$sku   = $variation->get_sku();

		if ( $sku ) {
			$identifier = $sku;
		} else {
			$identifier = '#' . $variation->variation_id;
		}

		return $this->format_product_title( $title, $identifier, $description );
	}

	/**
	 * Return a formatted product title based on id.
	 *
	 * @param  int    $product_id
	 * @return string
	 */
	public function get_product_title( $product_id ) {

		if ( is_object( $product_id ) ) {
			$title = $product_id->get_title();
			$sku   = $product_id->get_sku();
			$id    = $product_id->id;
		} else {
			$title = get_the_title( $product_id );
			$sku   = get_post_meta( $product_id, '_sku', true );
			$id    = $product_id;
		}

		if ( ! $title ) {
			return false;
		}

		if ( $sku ) {
			$identifier = $sku;
		} else {
			$identifier = '#' . $id;
		}

		return $this->format_product_title( $title, $identifier );
	}

	/**
	 * Format a product title.
	 *
	 * @param  string $title
	 * @param  string $identifier
	 * @param  string $meta
	 * @param  string $paren
	 * @return string
	 */
	public function format_product_title( $title, $identifier = '', $meta = '', $paren = false ) {

		if ( $identifier && $meta ) {
			if ( $paren ) {
				$title = sprintf( _x( '%1$s &mdash; %2$s (%3$s)', 'product title followed by meta and sku in parenthesis', 'woocommerce-composite-products' ), $title, $meta, $identifier );
			} else {
				$title = sprintf( _x( '%1$s &ndash; %2$s &mdash; %3$s', 'sku followed by product title and meta', 'woocommerce-composite-products' ), $identifier, $title, $meta );
			}
		} elseif ( $identifier ) {
			if ( $paren ) {
				$title = sprintf( _x( '%1$s (%2$s)', 'product title followed by sku in parenthesis', 'woocommerce-composite-products' ), $title, $identifier );
			} else {
				$title = sprintf( _x( '%1$s &ndash; %2$s', 'sku followed by product title', 'woocommerce-composite-products' ), $identifier, $title );
			}
		} elseif ( $meta ) {
			$title = sprintf( _x( '%1$s &mdash; %2$s', 'product title followed by meta', 'woocommerce-composite-products' ), $title, $meta );
		}

		return $title;
	}

	/**
	 * Get composite layout options.
	 * @return array
	 */
	public function get_layout_options() {

		$sanitized_custom_layouts = array();

		$base_layouts = array(
			'single'              => __( 'Stacked', 'woocommerce-composite-products' ),
			'progressive'         => __( 'Progressive', 'woocommerce-composite-products' ),
			'paged'               => __( 'Stepped', 'woocommerce-composite-products' ),
		);

		$custom_layouts = array(
			'paged-componentized' => __( 'Componentized', 'woocommerce-composite-products' ),
		);

		$custom_layouts = apply_filters( 'woocommerce_composite_product_layout_variations', $custom_layouts );

		foreach ( $custom_layouts as $layout_id => $layout_description ) {

			$sanitized_layout_id = esc_attr( sanitize_title( $layout_id ) );

			if ( array_key_exists( $sanitized_layout_id, $base_layouts ) ) {
				continue;
			}

			$sanitized_layout_id_parts = explode( '-', $sanitized_layout_id, 2 );

			if ( ! empty( $sanitized_layout_id_parts[0] ) && array_key_exists( $sanitized_layout_id_parts[0], $base_layouts ) ) {
				$sanitized_custom_layouts[ $sanitized_layout_id ] = $layout_description;
			}
		}

		return array_merge( $base_layouts, $sanitized_custom_layouts );
	}

	/**
	 * Get composite layout tooltips.
	 *
	 * @param  string
	 * @return string
	 */
	public function get_layout_tooltip( $layout_id ) {

		$tooltips = array(
			'single'              => __( 'Components are presented in a stacked, <strong>single-page</strong> layout, with the add-to-cart button located at the bottom. Component Options can be selected in any sequence.', 'woocommerce-composite-products' ),
			'progressive'         => __( 'Similar to the Stacked layout, however, Components must be configured in sequence and can be toggled open/closed.', 'woocommerce-composite-products' ),
			'paged'               => __( 'In this <strong>multi-page</strong> layout, Components are presented as individual steps in the configuration process. Selections are summarized in a final Review step, at which point the Composite can be added to the cart. The Stepped layout allows you to use the <strong>Composite Products Summary Widget</strong> to constantly show a mini version of the Summary on your sidebar.', 'woocommerce-composite-products' ),
			'paged-componentized' => __( 'A <strong>multi-page</strong> layout that begins with a configuration Summary of all Components. The Summary is temporarily hidden from view while inspecting or configuring a Component. The Composite can be added to the cart by returning to the Summary.', 'woocommerce-composite-products' ),
		);

		if ( ! isset( $tooltips[ $layout_id ] ) ) {
			return '';
		}

		$tooltip = '<br/><img class="help_tip" data-tip="' . $tooltips[ $layout_id ] . '" src="' . WC()->plugin_url() . '/assets/images/help.png" />';

		return $tooltip;
	}

	/**
	 * Get selected layout option.
	 *
	 * @param  string $layout
	 * @return string
	 */
	public function get_selected_layout_option( $layout ) {

		if ( ! $layout ) {
			return 'single';
		}

		$layouts         = $this->get_layout_options();
		$layout_id_parts = explode( '-', $layout, 2 );

		if ( array_key_exists( $layout, $layouts ) ) {
			return $layout;
		} elseif ( array_key_exists( $layout_id_parts[0], $layouts ) ) {
			return $layout_id_parts[0];
		}

		return 'single';
	}
}
