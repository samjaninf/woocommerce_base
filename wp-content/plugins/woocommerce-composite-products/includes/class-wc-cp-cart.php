<?php
/**
 * Composite products cart functionality.
 *
 * @class   WC_CP_Cart
 * @version 3.3.1
 * @since   2.2.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_CP_Cart {

	public function __construct() {

		// Validate composite add-to-cart.
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'wc_cp_validation' ), 10, 6 );

		// Validate cart quantity updates.
		add_filter( 'woocommerce_update_cart_validation', array( $this, 'wc_cp_cart_validation' ), 10, 4 );

		// Add composite configuration data to all composited items.
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'wc_cp_add_cart_item_data' ), 10, 2 );

		// Add composited items to the cart.
		add_action( 'woocommerce_add_to_cart', array( $this, 'wc_cp_add_items_to_cart' ), 10, 6 );

		// Modify cart item data for composite products.
		add_filter( 'woocommerce_add_cart_item', array( $this, 'wc_cp_add_cart_item_filter' ), 10, 2 );

		// Preserve data in cart.
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'wc_cp_get_cart_data_from_session' ), 10, 2 );

		// Control modification of composited items' quantity.
		add_filter( 'woocommerce_cart_item_quantity', array( $this, 'wc_cp_cart_item_quantity' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_remove_link', array( $this, 'wc_cp_cart_item_remove_link' ), 10, 2 );

		// Sync quantities of bundled items with bundle quantity.
		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'wc_cp_update_quantity_in_cart' ), 1, 2 );
		add_action( 'woocommerce_before_cart_item_quantity_zero', array( $this, 'wc_cp_update_quantity_in_cart' ) );

		// Put back cart item data to allow re-ordering of composites.
		add_filter( 'woocommerce_order_again_cart_item_data', array( $this, 'wc_cp_order_again' ), 10, 3 );

		// Filter cart item price.
		add_filter( 'woocommerce_cart_item_price', array( $this, 'wc_cp_cart_item_price' ), 11, 3 );

		// Modify cart items subtotals according to the pricing strategy used (static / per-product).
		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'wc_cp_item_subtotal' ), 11, 3 );
		add_filter( 'woocommerce_checkout_item_subtotal', array( $this, 'wc_cp_item_subtotal' ), 11, 3 );

		add_action( 'woocommerce_cart_item_removed', array( $this, 'wc_cp_cart_item_removed' ), 10, 2 );
		add_action( 'woocommerce_cart_item_restored', array( $this, 'wc_cp_cart_item_restored' ), 10, 2 );

		// Shipping fix - ensure that non-virtual containers/children, which are shipped, have a valid price that can be used for insurance calculations.
		// Additionally, composited item weights may have to be added in the container.
		add_filter( 'woocommerce_cart_shipping_packages', array( $this, 'wc_cp_shipping_packages_fix' ), 11 );
	}

	/**
	 * Validates that all composited items chosen can be added-to-cart before actually starting to add items.
	 *
	 * @param  bool 	$add
	 * @param  int 		$product_id
	 * @param  int 		$product_quantity
	 * @return bool
	 */
	public function wc_cp_validation( $add, $product_id, $product_quantity, $variation_id = '', $variations = array(), $cart_item_data = array() ) {

		// Get product type
		$terms        = get_the_terms( $product_id, 'product_type' );
		$product_type = ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';

		// Ordering again?
		$order_again  = isset( $_GET[ 'order_again' ] ) && isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( $_GET[ '_wpnonce' ], 'woocommerce-order_again' );

		// prevent composited items from getting validated - they will be added by the container item.
		if ( isset( $cart_item_data[ 'is_composited' ] ) && $order_again )
			return false;

		if ( $product_type == 'composite' ) {

			// Get composite data
			$composite      = wc_get_product( $product_id );
			$composite_data = $composite->get_composite_data();
			$component_ids 	= array_keys( $composite_data );

			// Check request and prepare validation data for stock and scenarios.

			$validate_scenarios = array();

			// If a stock-managed product / variation exists in the bundle multiple times, its stock will be checked only once for the sum of all bundled quantities.
			// The WC_CP_Stock_Manager class does exactly that
			$composited_stock = new WC_CP_Stock_Manager( $composite );

			foreach ( $component_ids as $component_id ) {

				// Check that a product has been selected
				if ( isset( $_REQUEST[ 'wccp_component_selection' ][ $component_id ] ) && $_REQUEST[ 'wccp_component_selection' ][ $component_id ] !== '' ) {

					$composited_product_id = $_REQUEST[ 'wccp_component_selection' ][ $component_id ];

				} elseif ( isset( $cart_item_data[ 'composite_data' ][ $component_id ][ 'product_id' ] ) && $order_again ) {

					$composited_product_id = $cart_item_data[ 'composite_data' ][ $component_id ][ 'product_id' ];

				} else {

					$_REQUEST[ 'wccp_component_selection' ][ $component_id ] = '';

					// Save for later
					$validate_scenarios[ $component_id ]                   = array();
					$validate_scenarios[ $component_id ][ 'product_id' ]   = '0';
					$validate_scenarios[ $component_id ][ 'product_type' ] = 'none';

					continue;
				}

				// Prevent people from fucking around - only valid component options can be added to the cart.
				if ( ! in_array( $composited_product_id, WC_CP()->api->get_component_options( $composite_data[ $component_id ] ) ) ) {
					return false;
				}

				$item_quantity_min = absint( $composite_data[ $component_id ][ 'quantity_min' ] );
				$item_quantity_max = $composite_data[ $component_id ][ 'quantity_max' ] !== '' ? absint( $composite_data[ $component_id ][ 'quantity_max' ] ) : '';

				// Check quantity
				if ( isset( $_REQUEST[ 'wccp_component_quantity' ][ $component_id ] ) && is_numeric( $_REQUEST[ 'wccp_component_quantity' ][ $component_id ] ) ) {

					$item_quantity = absint( $_REQUEST[ 'wccp_component_quantity' ][ $component_id ] );

				} elseif ( isset( $cart_item_data[ 'composite_data' ][ $component_id ][ 'quantity' ] ) && $order_again ) {

					$item_quantity = absint( $cart_item_data[ 'composite_data' ][ $component_id ][ 'quantity' ] );

				} else {
					$item_quantity = $item_quantity_min;
				}

				$quantity = $item_quantity * $product_quantity;

				$item_sold_individually = get_post_meta( $composited_product_id, '_sold_individually', true );

				if ( $item_sold_individually === 'yes' && $quantity > 1 ) {
					$quantity = 1;
				}

				// Save for later.
				$validate_scenarios[ $component_id ]                        = array();
				$validate_scenarios[ $component_id ][ 'quantity' ]          = $item_quantity;
				$validate_scenarios[ $component_id ][ 'quantity_min' ]      = $item_quantity_min;
				$validate_scenarios[ $component_id ][ 'quantity_max' ]      = $item_quantity_max;
				$validate_scenarios[ $component_id ][ 'sold_individually' ] = $item_sold_individually;

				// Get composited product type.
				$composited_product_wrapper = $composite->get_composited_product( $component_id, $composited_product_id );

				if ( ! $composited_product_wrapper ) {
					wc_add_notice( sprintf( __( 'This &quot;%1$s&quot; configuration cannot be added to the cart. Please choose another &quot;%2$s&quot; option&hellip;', 'woocommerce-composite-products' ), get_the_title( $product_id ), apply_filters( 'woocommerce_composite_component_title', $composite_data[ $component_id ][ 'title' ], $component_id, $product_id ) ), 'error' );
					return false;
				}

				$composited_product      = $composited_product_wrapper->get_product();
				$composited_product_type = $composited_product->product_type;

				// Save for later.
				$validate_scenarios[ $component_id ][ 'product_type' ] = $composited_product_type;
				$validate_scenarios[ $component_id ][ 'product_id' ]   = $composited_product_id;

				// Validate attributes.
				if ( $composited_product_type === 'variable' ) {

					$variation_id = '';

					if ( isset( $cart_item_data[ 'composite_data' ][ $component_id ][ 'variation_id' ] ) && $order_again ) {

						$variation_id = $cart_item_data[ 'composite_data' ][ $component_id ][ 'variation_id' ];

					} elseif ( isset( $_REQUEST[ 'wccp_variation_id' ][ $component_id ] ) ) {

						$variation_id = $_REQUEST[ 'wccp_variation_id' ][ $component_id ] ;
					}

					if ( $variation_id && is_numeric( $variation_id ) && $variation_id > 1 ) {

						// Add item for validation.
						$composited_stock->add_item( $composited_product_id, $variation_id, $quantity );

						// Save for later.
						$validate_scenarios[ $component_id ][ 'variation_id' ] = $variation_id;

					}

					// Verify all attributes for the variable product were set.
					$composited_variation = wc_get_product( $variation_id );
					$attributes           = $composited_product->get_attributes();
					$variation_data       = array();
					$missing_attributes   = array();
					$all_set              = true;

					if ( $composited_variation ) {
						$variation_data = $composited_variation->variation_data;
					}

					foreach ( $attributes as $attribute ) {

					    if ( ! $attribute[ 'is_variation' ] ) {
					    	continue;
					    }

					    $taxonomy = 'attribute_' . sanitize_title( $attribute[ 'name' ] );

						if ( isset( $_REQUEST[ 'wccp_' . $taxonomy ][ $component_id ] ) ) {

							if ( WC_CP_Core_Compatibility::is_wc_version_gte_2_4() ) {

								 // Get value from post data.
								if ( $attribute[ 'is_taxonomy' ] ) {
									$value = sanitize_title( stripslashes( $_REQUEST[ 'wccp_' . $taxonomy ][ $component_id ] ) );
								} else {
									$value = wc_clean( stripslashes( $_REQUEST[ 'wccp_' . $taxonomy ][ $component_id ] ) );
								}

							} else {

								// Get value from post data.
								$value = sanitize_title( trim( stripslashes( $_REQUEST[ 'wccp_' . $taxonomy ][ $component_id ] ) ) );
							}

							// Get valid value from variation.
							$valid_value = $variation_data[ $taxonomy ];

							// Allow if valid.
							if ( $valid_value === '' || $valid_value === $value ) {
								continue;
							}

						} elseif ( isset( $cart_item_data[ 'composite_data' ][ $component_id ][ 'attributes' ][ $taxonomy ] ) && isset( $cart_item_data[ 'composite_data' ][ $component_id ][ 'variation_id' ] ) && $order_again ) {

							if ( WC_CP_Core_Compatibility::is_wc_version_gte_2_4() ) {

								 // Get value from post data.
								if ( $attribute[ 'is_taxonomy' ] ) {
									$value = sanitize_title( stripslashes( $cart_item_data[ 'composite_data' ][ $component_id ][ 'attributes' ][ $taxonomy ] ) );
								} else {
									$value = wc_clean( stripslashes( $cart_item_data[ 'composite_data' ][ $component_id ][ 'attributes' ][ $taxonomy ] ) );
								}

							} else {

								// Get value from post data.
								$value = sanitize_title( trim( stripslashes( $cart_item_data[ 'composite_data' ][ $component_id ][ 'attributes' ][ $taxonomy ] ) ) );
							}

							$valid_value = $variation_data[ $taxonomy ];

							if ( $valid_value === '' || $valid_value === $value ) {
								continue;
							}

						} else {

							$missing_attributes[] = wc_attribute_label( $attribute[ 'name' ] );
						}

					    $all_set = false;
					}

					if ( ! $all_set ) {

						if ( $missing_attributes && WC_CP_Core_Compatibility::is_wc_version_gte_2_3() ) {

							$required_fields_notice = sprintf( _n( '%1$s is a required &quot;%2$s&quot; field', '%1$s are required &quot;%2$s&quot; fields', sizeof( $missing_attributes ), 'woocommerce-composite-products' ), wc_format_list_of_items( $missing_attributes ), apply_filters( 'woocommerce_composite_component_title', $composite_data[ $component_id ][ 'title' ], $component_id, $product_id ) );
    						wc_add_notice( sprintf( __( 'This &quot;%1$s&quot; configuration cannot be added to the cart. %2$s.', 'woocommerce-composite-products' ), get_the_title( $product_id ), $required_fields_notice ), 'error' );
    						return false;

						} else {

    						wc_add_notice( sprintf( __( 'This &quot;%1$s&quot; configuration cannot be added to the cart. Please choose &quot;%2$s&quot; options&hellip;', 'woocommerce-composite-products' ), get_the_title( $product_id ), apply_filters( 'woocommerce_composite_component_title', $composite_data[ $component_id ][ 'title' ], $component_id, $product_id ) ), 'error' );
							return false;
						}
					}

				} elseif ( $composited_product_type === 'simple' ) {

					// Add item for validation.
					$composited_stock->add_item( $composited_product_id, false, $quantity );

				} else {

					// Add item for validation.
					$composited_stock->add_item( $composited_product_id, false, $quantity );
				}

				if ( ! apply_filters( 'woocommerce_composite_component_add_to_cart_validation', true, $product_id, $component_id, $composited_product_id, $quantity, $cart_item_data ) ) {
					return false;
				}

				// Allow composited products to add extra items to the stock manager.
				$composited_stock->add_stock( apply_filters( 'woocommerce_composite_component_associated_stock', '', $product_id, $component_id, $composited_product_id, $quantity ) );
			}

			/*
			 * Stock Validation.
			 */

			if ( false === $composited_stock->validate_stock() ) {
				return false;
			}

			/*
			 * Scenarios Validation.
			 */

			$scenario_data = get_post_meta( $product_id, '_bto_scenario_data', true );

			// Build scenarios for the selected combination of options
			foreach ( $composite_data as $component_id => &$modified_component_data ) {

				if ( isset( $validate_scenarios[ $component_id ] ) && $validate_scenarios[ $component_id ][ 'product_type' ] !== 'none' ) {
					$modified_component_data[ 'current_component_options' ] = array( $validate_scenarios[ $component_id ][ 'product_id' ] );
				} else {
					$modified_component_data[ 'current_component_options' ] = array( '' );
				}
			}

			$scenarios_for_products        = apply_filters( 'woocommerce_composite_validation_scenario_data', WC_CP()->api->build_scenarios( $scenario_data, $composite_data ), $composite_data, $scenario_data, $product_id );
			$common_scenarios              = array_values( $scenarios_for_products[ 'scenarios' ] );
			$common_compat_group_scenarios = WC_CP()->api->filter_scenarios_by_type( $common_scenarios, 'compat_group', $scenarios_for_products );

			// Validate Selections.
			foreach ( $composite_data as $component_id => $component_data ) {

				if ( isset( $validate_scenarios[ $component_id ] ) ) {

					$validate_product_id      = isset( $validate_scenarios[ $component_id ][ 'variation_id' ] ) ? $validate_scenarios[ $component_id ][ 'variation_id' ] : $validate_scenarios[ $component_id ][ 'product_id' ];
					$scenarios_for_product    = array();
					$mandatory_override_check = false;

					if ( $validate_product_id === '0' && $component_data[ 'optional' ] === 'no' ) {
						$mandatory_override_check = true;
					}

					if ( ! empty( $scenarios_for_products[ 'scenario_data' ][ $component_id ][ $validate_product_id ] ) ) {
						$scenarios_for_product              = $scenarios_for_products[ 'scenario_data' ][ $component_id ][ $validate_product_id ];
						$compat_group_scenarios_for_product = WC_CP()->api->filter_scenarios_by_type( $scenarios_for_product, 'compat_group', $scenarios_for_products );
					}

					if ( empty( $compat_group_scenarios_for_product ) || $mandatory_override_check ) {

						if ( $validate_product_id === '0' ) {

							// Allow 3rd parties to override notices for empty selections in non-optional components conditionally through scenarios.
							if ( apply_filters( 'woocommerce_composite_validation_component_is_mandatory', true, $component_id, $validate_scenarios[ $component_id ], $common_scenarios, $scenarios_for_products, $product_id ) ) {
								wc_add_notice( sprintf( __( 'Please select a &quot;%s&quot; option.', 'woocommerce-composite-products' ), apply_filters( 'woocommerce_composite_component_title', $component_data[ 'title' ], $component_id, $product_id ) ), 'error' );
								return false;
							}

						} else {
							wc_add_notice( sprintf( __( 'Please select a different &quot;%s&quot; option &mdash; the selected product cannot be purchased at the moment.', 'woocommerce-composite-products' ), apply_filters( 'woocommerce_composite_component_title', $component_data[ 'title' ], $component_id, $product_id ) ), 'error' );
							return false;
						}

					} else {
						$common_scenarios              = array_intersect( $common_scenarios, $scenarios_for_product );
						$common_compat_group_scenarios = array_intersect( $common_compat_group_scenarios, $compat_group_scenarios_for_product );
					}
				}
			}

			if ( empty( $common_compat_group_scenarios ) ) {
				wc_add_notice( __( 'The selected options cannot be purchased together. Please select a different configuration and try again.', 'woocommerce-composite-products' ), 'error' );
				return false;
			}

			// Validate Quantities.
			foreach ( $composite_data as $component_id => $component_data ) {

				if ( ! isset( $validate_scenarios[ $component_id ] ) || $validate_scenarios[ $component_id ][ 'product_type' ] === 'none' ) {
					continue;
				}

				$qty               = $validate_scenarios[ $component_id ][ 'quantity' ];
				// Allow 3rd parties to modify the min/max qty settings of a component conditionally through scenarios.
				$qty_min           = absint( apply_filters( 'woocommerce_composite_validation_component_quantity_min', $component_data[ 'quantity_min' ], $component_id, $validate_scenarios[ $component_id ], $common_scenarios, $scenarios_for_products, $product_id ) );
				$qty_max           = absint( apply_filters( 'woocommerce_composite_validation_component_quantity_max', $component_data[ 'quantity_max' ], $component_id, $validate_scenarios[ $component_id ], $common_scenarios, $scenarios_for_products, $product_id ) );
				$sold_individually = $validate_scenarios[ $component_id ][ 'sold_individually' ];

				if ( $qty < $qty_min && $sold_individually !== 'yes' ) {
					wc_add_notice( sprintf( __( 'This &quot;%1$s&quot; configuration cannot be added to the cart. The quantity of &quot;%2$s&quot; cannot be lower than %3$d.', 'woocommerce-composite-products' ), get_the_title( $product_id ), apply_filters( 'woocommerce_composite_component_title', $composite_data[ $component_id ][ 'title' ], $component_id, $product_id ), $qty_min ), 'error' );
					return false;
				} elseif ( $qty_max && $qty > $qty_max ) {
					wc_add_notice( sprintf( __( 'This &quot;%1$s&quot; configuration cannot be added to the cart. The quantity of &quot;%2$s&quot; cannot be higher than %3$d.', 'woocommerce-composite-products' ), get_the_title( $product_id ), apply_filters( 'woocommerce_composite_component_title', $composite_data[ $component_id ][ 'title' ], $component_id, $product_id ), $qty_max ), 'error' );
					return false;
				}
			}

			$add = apply_filters( 'woocommerce_add_to_cart_composite_validation', $add, $product_id, $composited_stock );
		}

		return $add;
	}

	/**
	 * Add a composited product to the cart. Must be done without updating session data, recalculating totals or calling 'woocommerce_add_to_cart' recursively.
	 * For the recursion issue, see: https://core.trac.wordpress.org/ticket/17817.
	 *
	 * @param int          $composite_id
	 * @param int          $product_id
	 * @param string       $quantity
	 * @param int          $variation_id
	 * @param array        $variation
	 * @param array        $cart_item_data
	 * @return bool
	 */
	public function composited_add_to_cart( $composite_id, $product_id, $quantity = 1, $variation_id = '', $variation = '', $cart_item_data ) {

		if ( $quantity <= 0 ) {
			return false;
		}

		// Load cart item data when adding to cart.
		$cart_item_data = ( array ) apply_filters( 'woocommerce_add_cart_item_data', $cart_item_data, $product_id, $variation_id );

		// Generate a ID based on product ID, variation ID, variation data, and other cart item data.
		$cart_id = WC()->cart->generate_cart_id( $product_id, $variation_id, $variation, $cart_item_data );

		// See if this product and its options is already in the cart.
		$cart_item_key = WC()->cart->find_product_in_cart( $cart_id );

		// Ensure we don't add a variation to the cart directly by variation ID.
		if ( 'product_variation' == get_post_type( $product_id ) ) {
			$variation_id = $product_id;
			$product_id   = wp_get_post_parent_id( $variation_id );
		}

		// Get the product
		$product_data = wc_get_product( $variation_id ? $variation_id : $product_id );

		// If cart_item_key is set, the item is already in the cart and its quantity will be handled by wc_cp_update_quantity_in_cart.
		if ( ! $cart_item_key ) {

			$cart_item_key = $cart_id;

			// Add item after merging with $cart_item_data - allow plugins and wc_cp_add_cart_item_filter to modify cart item.
			WC()->cart->cart_contents[ $cart_item_key ] = apply_filters( 'woocommerce_add_cart_item', array_merge( $cart_item_data, array(
				'product_id'   => $product_id,
				'variation_id' => $variation_id,
				'variation'    => $variation,
				'quantity'     => $quantity,
				'data'         => $product_data
			) ), $cart_item_key );

		}

		do_action( 'woocommerce_composited_add_to_cart', $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data, $composite_id );

		return $cart_item_key;
	}

	/**
	 * Adds configuration-specific cart-item data.
	 *
	 * @param  array 	$cart_item_data
	 * @param  int 		$product_id
	 * @return void
	 */
	public function wc_cp_add_cart_item_data( $cart_item_data, $product_id ) {

		// Get product type.
		$terms        = get_the_terms( $product_id, 'product_type' );
		$product_type = ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';

		if ( $product_type === 'composite' && isset( $_REQUEST[ 'wccp_component_selection' ] ) && is_array( $_REQUEST[ 'wccp_component_selection' ] ) ) {

			// Create a unique array with the composite configuration.
			$composite_config = array();

			// Get composite data.
			$composite        = wc_get_product( $product_id );
			$composite_data   = $composite->get_composite_data();

			foreach ( $_REQUEST[ 'wccp_component_selection' ] as $component_id => $composited_product_id ) {

				$composited_product_quantity = isset( $_REQUEST[ 'wccp_component_quantity' ][ $component_id ] ) ? absint( $_REQUEST[ 'wccp_component_quantity' ][ $component_id ] ) : absint( $composite_data[ $component_id ][ 'quantity_min' ] );

				if ( $composited_product_id ) {

					$composited_product_wrapper = $composite->get_composited_product( $component_id, $composited_product_id );

					if ( ! $composited_product_wrapper ) {
						continue;
					}

					$composited_product      = $composited_product_wrapper->get_product();
					$composited_product_type = $composited_product->product_type;

					if ( $composited_product->sold_individually === 'yes' && $composited_product_quantity > 1 ) {
						$composited_product_quantity = 1;
					}
				}

				$composite_config[ $component_id ][ 'product_id' ]   = $composited_product_id;
				$composite_config[ $component_id ][ 'composite_id' ] = $product_id;
				$composite_config[ $component_id ][ 'quantity' ]     = $composited_product_quantity;
				$composite_config[ $component_id ][ 'title' ]        = $composite_data[ $component_id ][ 'title' ];
				$composite_config[ $component_id ][ 'quantity_min' ] = $composite_data[ $component_id ][ 'quantity_min' ];
				$composite_config[ $component_id ][ 'quantity_max' ] = $composite_data[ $component_id ][ 'quantity_max' ];
				$composite_config[ $component_id ][ 'discount' ]     = isset( $composite_data[ $component_id ][ 'discount' ] ) ? $composite_data[ $component_id ][ 'discount' ] : 0;
				$composite_config[ $component_id ][ 'optional' ]     = $composite_data[ $component_id ][ 'optional' ];

				// Continue when selected product is 'None'.
				if ( ! $composited_product_id || $composited_product_id === '' || $composited_product_quantity === 0 ) {

					$composite_config[ $component_id ][ 'type' ]  = 'none';
					$composite_config[ $component_id ][ 'price' ] = 0;
					continue;

				} else {
					$composite_config[ $component_id ][ 'type' ] = $composited_product_type;
				}

				if ( $composited_product_type === 'variable' ) {

					$attributes_config 	= array();
					$attributes 		= $composited_product->get_attributes();

					foreach ( $attributes as $attribute ) {

						if ( ! $attribute[ 'is_variation' ] ) {
							continue;
						}

						$taxonomy = 'attribute_' . sanitize_title( $attribute[ 'name' ] );

						// Value has already been checked for validity in function 'wc_cp_validation()'.
						if ( WC_CP_Core_Compatibility::is_wc_version_gte_2_4() ) {

							 // Get value from post data
							if ( $attribute[ 'is_taxonomy' ] ) {
								$value = sanitize_title( stripslashes( $_REQUEST[ 'wccp_' . $taxonomy ][ $component_id ] ) );
							} else {
								$value = wc_clean( stripslashes( $_REQUEST[ 'wccp_' . $taxonomy ][ $component_id ] ) );
							}

							$attributes_config[ $taxonomy ] = $value;

						} else {

							// Get value from post data.
							$value = sanitize_title( trim( stripslashes( $_REQUEST[ 'wccp_' . $taxonomy ][ $component_id ] ) ) );

							if ( $attribute[ 'is_taxonomy' ] ) {

								$attributes_config[ $taxonomy ] = $value;

							} else {

								// For custom attributes, get the name from the slug.
								$options = array_map( 'trim', explode( WC_DELIMITER, $attribute[ 'value' ] ) );

								foreach ( $options as $option ) {
									if ( sanitize_title( $option ) == $value ) {
										$value = $option;
										break;
									}
								}

								$attributes_config[ $taxonomy ] = $value;
							}
						}
					}

					$composite_config[ $component_id ][ 'variation_id' ] = $_REQUEST[ 'wccp_variation_id' ][ $component_id ];
					$composite_config[ $component_id ][ 'attributes' ]   = $attributes_config;
				}

				$composited_product_variation_id              = isset( $composite_config[ $component_id ][ 'variation_id' ] ) ? $composite_config[ $component_id ][ 'variation_id' ] : '';
				$composite_config[ $component_id ][ 'price' ] = $this->get_composited_cart_item_price( $composited_product_id, $composited_product_variation_id, $component_id, $composite );

				$composite_config[ $component_id ] = apply_filters( 'woocommerce_composite_component_cart_item_identifier', $composite_config[ $component_id ], $component_id );
			}

			$cart_item_data[ 'composite_data' ] = $composite_config;

			// Prepare additional data for later use
			$cart_item_data[ 'composite_children' ] = array();

			return $cart_item_data;

		} else {

			return $cart_item_data;
		}

	}

	/**
	 * Get composited products prices via their WC_CP_Product wrapper to account for discounts.
	 *
	 * @param  int                  $product_id
	 * @param  mixed                $variation_id
	 * @param  string               $component_id
	 * @param  WC_Product_Composite $composite
	 * @return double
	 */
	private function get_composited_cart_item_price( $product_id, $variation_id, $component_id, $composite ) {

		if ( ! $product_id ) {
			return 0;
		}

		$composited_product_wrapper = $composite->get_composited_product( $component_id, $product_id );

		if ( ! $composited_product_wrapper || ! $composited_product_wrapper->exists() ) {
			return false;
		}

		$composited_product       = $composited_product_wrapper->get_product();
		$composited_product_type  = $composited_product->product_type;
		$composited_product_price = 0;

		$composited_product_wrapper->add_filters();

		if ( $composited_product_type === 'variable' && $variation_id > 0 ) {

			$variation = $composited_product->get_child( $variation_id );

			if ( $variation ) {
				$composited_product_price = $variation->get_price();
			}

		} else {

			$composited_product_price = $composited_product->get_price();
		}

		$composited_product_wrapper->remove_filters();

		return $composited_product_price;
	}

	/**
	 * Adds composited items to the cart.
	 *
	 * @param  string   $item_cart_key
	 * @param  int      $product_id
	 * @param  int      $quantity
	 * @param  int      $variation_id
	 * @param  array    $variation
	 * @param  array    cart_item_data
	 * @return void
	 */
	public function wc_cp_add_items_to_cart( $item_cart_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {

		// Runs when adding container item - adds composited items.
		if ( isset( $cart_item_data[ 'composite_data' ] ) && ! isset( $cart_item_data[ 'composite_parent' ] ) ) {

			// Only attempt to add composited items if they don't already exist.
			foreach ( WC()->cart->cart_contents as $cart_key => $cart_value ) {
				if ( isset( $cart_value[ 'composite_data' ] ) && isset( $cart_value[ 'composite_parent' ] ) && $item_cart_key == $cart_value[ 'composite_parent' ] ) {
					return;
				}
			}

			// This id is unique, so that composited and non-composited versions of the same product will be added separately to the cart.
			$composited_cart_data = array( 'composite_item' => '', 'composite_parent' => $item_cart_key, 'composite_data' => $cart_item_data[ 'composite_data' ] );

			// Now add all items - yay!
			foreach ( $cart_item_data[ 'composite_data' ] as $item_id => $composite_item_data ) {

				$composited_item_cart_data = $composited_cart_data;

				$composited_item_cart_data[ 'composite_item' ] = $item_id;

				$composited_product_id = $composite_item_data[ 'product_id' ];
				$variation_id          = '';
				$variations            = array();

				if ( $composite_item_data[ 'type' ] === 'none' ) {
					continue;
				}

				$item_quantity      = $composite_item_data[ 'quantity' ];
				$composite_quantity = ( isset( $_REQUEST[ 'quantity' ] ) && (int) $_REQUEST[ 'quantity' ] > 0 ) ? (int) $_REQUEST[ 'quantity' ] : 1;
				$quantity           = $item_quantity * $composite_quantity;

				$item_sold_individually = get_post_meta( $composited_product_id, '_sold_individually', true );

				if ( $item_sold_individually === 'yes' && $quantity > 1 ) {
					$quantity = 1;
				}

				if ( $composite_item_data[ 'type' ] === 'variable' ) {

					$variation_id = ( int ) $composite_item_data[ 'variation_id' ];
					$variations   = $composite_item_data[ 'attributes' ];

				} elseif ( $composite_item_data[ 'type' ] === 'bundle' ) {

					$composited_item_cart_data[ 'stamp' ]         = $composite_item_data[ 'stamp' ];
					$composited_item_cart_data[ 'bundled_items' ] = array();
				}

				// Allow loading child cart item data from the parent cart item data array.
				$composited_item_cart_data = apply_filters( 'woocommerce_composited_cart_item_data', $composited_item_cart_data, $cart_item_data );

				// Prepare for adding children to cart.
				do_action( 'woocommerce_composited_product_before_add_to_cart', $composited_product_id, $quantity, $variation_id, $variations, $composited_item_cart_data );

				// Add to cart.
				$composited_item_cart_key = $this->composited_add_to_cart( $product_id, $composited_product_id, $quantity, $variation_id, $variations, $composited_item_cart_data );

				if ( $composited_item_cart_key && ! in_array( $composited_item_cart_key, WC()->cart->cart_contents[ $item_cart_key ][ 'composite_children' ] ) ) {
					WC()->cart->cart_contents[ $item_cart_key ][ 'composite_children' ][] = $composited_item_cart_key;
				}

				// Finish.
				do_action( 'woocommerce_composited_product_after_add_to_cart', $composited_product_id, $quantity, $variation_id, $variations, $composited_item_cart_data );
			}
		}
	}

	/**
	 * Modifies composited cart item virtual status and price depending on composite pricing and shipping strategies.
	 *
	 * @param  array                $cart_item
	 * @param  WC_Product_Composite $composite
	 * @return array
	 */
	private function get_composited_cart_item( $cart_item, $composite ) {

		$component_id = $cart_item[ 'composite_item' ];

		// Pricing.

		$cart_item[ 'data' ]->price = (double) $this->get_composited_cart_item_price( $cart_item[ 'product_id' ], $cart_item[ 'variation_id' ], $component_id, $composite );

		// Shipping.

		if ( $cart_item[ 'data' ]->needs_shipping() ) {

			if ( false === apply_filters( 'woocommerce_composited_product_shipped_individually', $composite->is_shipped_per_product(), $cart_item[ 'data' ], $component_id, $composite ) ) {

				if ( apply_filters( 'woocommerce_composited_product_has_bundled_weight', false, $cart_item[ 'data' ], $component_id, $composite ) ) {
					$cart_item[ 'data' ]->bundled_weight = $cart_item[ 'data' ]->get_weight();
				}

				$cart_item[ 'data' ]->bundled_value = $cart_item[ 'data' ]->price;
				$cart_item[ 'data' ]->virtual       = 'yes';
			}
		}

		return apply_filters( 'woocommerce_composited_cart_item', $cart_item, $composite );
	}

	/**
	 * Modifies cart item data - important for the first calculation of totals only.
	 *
	 * @param  array $cart_item
	 * @param  string $cart_item_key
	 * @return array
	 */
	public function wc_cp_add_cart_item_filter( $cart_item, $cart_item_key ) {

		$cart_contents = WC()->cart->cart_contents;

		if ( ! empty( $cart_item[ 'composite_parent' ] ) && ! empty( $cart_item[ 'composite_item' ] ) ) {

			$composite_cart_key = $cart_item[ 'composite_parent' ];
			$component_id       = $cart_item[ 'composite_item' ];

			if ( isset( $cart_contents[ $composite_cart_key ] ) ) {

				$composite = $cart_contents[ $composite_cart_key ][ 'data' ];
				$cart_item = $this->get_composited_cart_item( $cart_item, $composite );
			}
		}

		return $cart_item;
	}

	/**
	 * Load all composite-related session data.
	 *
	 * @param  array 	$cart_item
	 * @param  array 	$item_session_values
	 * @return void
	 */
	public function wc_cp_get_cart_data_from_session( $cart_item, $item_session_values ) {

		if ( isset( $item_session_values[ 'composite_data' ] ) ) {
			$cart_item[ 'composite_data' ] = $item_session_values[ 'composite_data' ];
		}

		if ( ! empty( $item_session_values[ 'composite_children' ] ) ) {

			if ( $cart_item[ 'data' ]->product_type === 'composite' ) {

				if ( ! isset( $cart_item[ 'composite_children' ] ) ) {
					$cart_item[ 'composite_children' ] = $item_session_values[ 'composite_children' ];
				}

			} else {

				if ( isset( $cart_item[ 'composite_children' ] ) ) {
					unset( $cart_item[ 'composite_children' ] );
				}
			}
		}

		if ( ! empty( $item_session_values[ 'composite_parent' ] ) && ! empty( $item_session_values[ 'composite_item' ] ) ) {

			$composite_cart_key = $item_session_values[ 'composite_parent' ];
			$component_id       = $item_session_values[ 'composite_item' ];

			$cart_contents      = WC()->cart->cart_contents;

			if ( isset( $cart_contents[ $composite_cart_key ] ) && ! empty( $cart_contents[ $composite_cart_key ][ 'composite_children' ] ) ) {

				if ( ! isset( $cart_item[ 'composite_parent' ] ) ) {
					$cart_item[ 'composite_parent' ] = $composite_cart_key;
				}

				if ( ! isset( $cart_item[ 'composite_item' ] ) ) {
					$cart_item[ 'composite_item' ] = $component_id;
				}

				$composite = $cart_contents[ $composite_cart_key ][ 'data' ];
				$cart_item = $this->get_composited_cart_item( $cart_item, $composite );

			} else {

				if ( isset( $cart_item[ 'composite_parent' ] ) ) {
					unset( $cart_item[ 'composite_parent' ] );
				}
			}
		}

		return $cart_item;
	}

	/**
	 * Composited items can't be removed individually from the cart.
	 *
	 * @param  string 	$link
	 * @param  string 	$cart_item_key
	 * @return string
	 */
	public function wc_cp_cart_item_remove_link( $link, $cart_item_key ) {

		if ( isset( WC()->cart->cart_contents[ $cart_item_key ][ 'composite_data' ] ) && ! empty( WC()->cart->cart_contents[ $cart_item_key ][ 'composite_parent' ] ) ) {

			$parent_key = WC()->cart->cart_contents[ $cart_item_key ][ 'composite_parent' ];

			if ( isset( WC()->cart->cart_contents[ $parent_key ] ) ) {
				return '';
			}

		}

		return $link;
	}

	/**
	 * Composited item quantities may be changed between min_q and max_q.
	 *
	 * @param  string 	$quantity
	 * @param  string 	$cart_item_key
	 * @return string
	 */
	public function wc_cp_cart_item_quantity( $quantity, $cart_item_key ) {

		$cart_item = WC()->cart->cart_contents[ $cart_item_key ];

		if ( isset( $cart_item[ 'composite_data' ] ) && ! empty( $cart_item[ 'composite_parent' ] ) ) {

			$component_id = $cart_item[ 'composite_item' ];

			if ( $cart_item[ 'composite_data' ][ $component_id ][ 'quantity_min' ] === $cart_item[ 'composite_data' ][ $component_id ][ 'quantity_max' ] ) {

				$quantity = $cart_item[ 'quantity' ];

			} elseif ( isset( $cart_item[ 'composite_parent' ] ) && isset( WC()->cart->cart_contents[ $cart_item[ 'composite_parent' ] ] ) ) {

				$parent          = WC()->cart->cart_contents[ $cart_item[ 'composite_parent' ] ];
				$parent_quantity = $parent[ 'quantity' ];
				$max_stock       = $cart_item[ 'data' ]->backorders_allowed() ? '' : $cart_item[ 'data' ]->get_stock_quantity();

				if ( $max_stock !== '' ) {
					$max_qty = $cart_item[ 'composite_data' ][ $component_id ][ 'quantity_max' ] !== '' ? min( $max_stock, $parent_quantity * $cart_item[ 'composite_data' ][ $component_id ][ 'quantity_max' ] ) : $max_stock;
				} else {
					$max_qty = $cart_item[ 'composite_data' ][ $component_id ][ 'quantity_max' ] !== '' ? $parent_quantity * $cart_item[ 'composite_data' ][ $component_id ][ 'quantity_max' ] : '';
				}

				$min_qty = $parent_quantity * $cart_item[ 'composite_data' ][ $component_id ][ 'quantity_min' ];

				if ( ( $max_qty > $min_qty || $max_qty === '' ) && ! $cart_item[ 'data' ]->is_sold_individually() ) {

					$component_quantity = woocommerce_quantity_input( array(
						'input_name'  => "cart[{$cart_item_key}][qty]",
						'input_value' => $cart_item[ 'quantity' ],
						'min_value'   => $min_qty,
						'max_value'   => $max_qty,
						'step'        => $parent_quantity
					), $cart_item[ 'data' ], false );

					$quantity = $component_quantity;

				} else {
					$quantity = $cart_item[ 'quantity' ];
				}

			} else {
				$quantity = $cart_item[ 'quantity' ];
			}
		}

		return $quantity;
	}

	/**
	 * Keeps composited items' quantities in sync with container item.
	 *
	 * @param  string  $cart_item_key
	 * @param  int     $quantity
	 * @return void
	 */
	public function wc_cp_update_quantity_in_cart( $cart_item_key, $quantity = 0 ) {

		if ( ! empty( WC()->cart->cart_contents[ $cart_item_key ] ) ) {

			if ( $quantity == 0 || $quantity < 0 ) {
				$quantity = 0;
			} else {
				$quantity = WC()->cart->cart_contents[ $cart_item_key ][ 'quantity' ];
			}

			$composite_children = ! empty( WC()->cart->cart_contents[ $cart_item_key ][ 'composite_children' ] ) ? WC()->cart->cart_contents[ $cart_item_key ][ 'composite_children' ] : '';

			if ( ! empty( $composite_children ) ) {

				$composite_quantity = $quantity;

				// change the quantity of all composited items that belong to the same config.
				foreach ( $composite_children as $child_key ) {

					if ( ! isset( WC()->cart->cart_contents[ $child_key ] ) ) {
						continue;
					}

					$child_item = WC()->cart->cart_contents[ $child_key ];

					if ( ! $child_item ) {
						continue;
					}

					if ( $child_item[ 'data' ]->is_sold_individually() && $quantity > 0 ) {

						WC()->cart->set_quantity( $child_key, 1, false );

					} else {

						$child_item_id  = WC()->cart->cart_contents[ $child_key ][ 'composite_item' ];
						$child_quantity = WC()->cart->cart_contents[ $child_key ][ 'composite_data' ][ $child_item_id ][ 'quantity' ];

						WC()->cart->set_quantity( $child_key, $child_quantity * $composite_quantity, false );
					}
				}
			}
		}
	}

	/**
	 * Validates in-cart component quantity changes.
	 *
	 * @param  bool   $passed
	 * @param  string $cart_item_key
	 * @param  array  $cart_item
	 * @param  int    $quantity
	 * @return bool
	 */
	public function wc_cp_cart_validation( $passed, $cart_item_key, $cart_item, $quantity ) {

		if ( isset( $cart_item[ 'composite_data' ] ) && ! empty( $cart_item[ 'composite_parent' ] ) ) {

			$component_id = $cart_item[ 'composite_item' ];

			$parent_key = $cart_item[ 'composite_parent' ];

			if ( isset( WC()->cart->cart_contents[ $parent_key ] ) ) {

				$parent          = WC()->cart->cart_contents[ $parent_key ];
				$parent_quantity = $parent[ 'quantity' ];
				$min_quantity    = $parent_quantity * $cart_item[ 'composite_data' ][ $component_id ][ 'quantity_min' ];
				$max_quantity    = $cart_item[ 'composite_data' ][ $component_id ][ 'quantity_max' ] ? $parent_quantity * $cart_item[ 'composite_data' ][ $component_id ][ 'quantity_max' ] : '';

				if ( $quantity < $min_quantity ) {

					wc_add_notice( sprintf( __( 'The quantity of &quot;%s&quot; cannot be lower than %d.', 'woocommerce-composite-products' ), $cart_item[ 'data' ]->get_title(), $min_quantity ), 'error' );
					return false;

				} elseif ( $max_quantity && $quantity > $max_quantity ) {

					wc_add_notice( sprintf( __( 'The quantity of &quot;%s&quot; cannot be higher than %d.', 'woocommerce-composite-products' ), $cart_item[ 'data' ]->get_title(), $max_quantity ), 'error' );
					return false;

				} elseif ( $quantity % $parent_quantity != 0 ) {

					wc_add_notice( sprintf( __( 'The quantity of &quot;%s&quot; must be entered in multiples of %d.', 'woocommerce-composite-products' ), $cart_item[ 'data' ]->get_title(), $parent_quantity ), 'error' );
					return false;

				} else {

					// Update new component quantity in container/children composite_data array.
					// Note: updating the composite_data array will have no effect on the generated parent cart_id at this point.

					$parent[ 'composite_data' ][ $component_id ][ 'quantity' ] = $quantity / $parent_quantity;

					foreach ( $parent[ 'composite_children' ] as $composite_child_key ) {
						if ( isset( WC()->cart->cart_contents[ $composite_child_key ] ) ) {
							WC()->cart->cart_contents[ $composite_child_key ][ 'composite_data' ][ $component_id ][ 'quantity' ] = $quantity / $parent_quantity;
						}
					}
				}
			}
		}

		return $passed;
	}

	/**
	 * Reinialize cart item data for re-ordering purchased orders.
	 *
	 * @param  mixed 	$cart_item_data
	 * @param  mixed 	$order_item
	 * @param  WC_Order $order
	 * @return mixed
	 */
	public function wc_cp_order_again( $cart_item_data, $order_item, $order ) {

		if ( isset( $order_item[ 'composite_parent' ] ) && isset( $order_item[ 'composite_data' ] ) )
			$cart_item_data[ 'is_composited' ] = 'yes';

		if ( isset( $order_item[ 'composite_children' ] ) && isset( $order_item[ 'composite_data' ] ) ) {

			$cart_item_data[ 'composite_data' ]     = maybe_unserialize( $order_item[ 'composite_data' ] );
			$cart_item_data[ 'composite_children' ] = array();
		}

		return $cart_item_data;
	}

	/**
	 * Modifies the cart.php & review-order.php templates formatted html prices visibility depending on pricing strategy.
	 *
	 * @param  string 	$price
	 * @param  array 	$values
	 * @param  string 	$cart_item_key
	 * @return string
	 */
	public function wc_cp_cart_item_price( $price, $values, $cart_item_key ) {

		if ( empty( WC()->cart ) ) {
			return $price;
		}

		if ( ! empty( $values[ 'composite_parent' ] ) ) {

			$parent_cart_key = $values[ 'composite_parent' ];

			if ( isset( WC()->cart->cart_contents[ $parent_cart_key ] ) && ! WC()->cart->cart_contents[ $parent_cart_key ][ 'data' ]->is_priced_per_product() && $values[ 'data' ]->price == 0 ) {
				return '';
			}
		}

		if ( ! empty( $values[ 'composite_children' ] ) ) {

			if ( $values[ 'data' ]->is_priced_per_product() && $values[ 'data' ]->get_price() == 0 ) {
				return '';
			}
		}

		return $price;
	}

	/**
	 * Outputs a formatted subtotal.
	 *
	 * @param  WC_Product 	$product
	 * @param  double 		$subtotal
	 * @return string
	 */
	public function format_product_subtotal( $product, $subtotal ) {

		$cart = WC()->cart;

		$taxable = $product->is_taxable();

		// Taxable.
		if ( $taxable ) {

			if ( $cart->tax_display_cart == 'excl' ) {

				$product_subtotal = wc_price( $subtotal );

				if ( $cart->prices_include_tax && $cart->tax_total > 0 )
					$product_subtotal .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';

			} else {

				$product_subtotal = wc_price( $subtotal );

				if ( ! $cart->prices_include_tax && $cart->tax_total > 0 )
					$product_subtotal .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
			}

		// Non-taxable.
		} else {
			$product_subtotal = wc_price( $subtotal );
		}

		return $product_subtotal;
	}

	/**
	 * Modifies the cart.php & review-order.php templates formatted subtotal appearance depending on pricing strategy.
	 *
	 * @param  string 	$price
	 * @param  array 	$values
	 * @param  string 	$cart_item_key
	 * @return string
	 */
	public function wc_cp_item_subtotal( $subtotal, $values, $cart_item_key ) {

		if ( ! empty( $values[ 'composite_parent' ] ) ) {

			$parent_cart_key = $values[ 'composite_parent' ];

			if ( isset( WC()->cart->cart_contents[ $parent_cart_key ] ) ) {

				if ( ! WC()->cart->cart_contents[ $parent_cart_key ][ 'data' ]->is_priced_per_product() ) {
					return '';
				} else {
					return __( 'Option subtotal', 'woocommerce-composite-products' ) . ': ' . $subtotal;
				}
			}
		}

		if ( ! empty( $values[ 'composite_children' ] ) ) {

			$composited_items_price = 0;
			$composite_price        = get_option( 'woocommerce_tax_display_cart' ) == 'excl' ? $values[ 'data' ]->get_price_excluding_tax( $values[ 'quantity' ] ) : $values[ 'data' ]->get_price_including_tax( $values[ 'quantity' ] );

			foreach ( WC()->cart->cart_contents as $cart_key => $cart_data ) {

				if ( apply_filters( 'woocommerce_cart_item_is_child_of_composite', in_array( $cart_key, $values[ 'composite_children' ] ), $cart_key, $cart_data, $cart_item_key, $values ) ) {

					$composite_child = $cart_data;

					$composited_items_price += get_option( 'woocommerce_tax_display_cart' ) == 'excl' ? $composite_child[ 'data' ]->get_price_excluding_tax( $composite_child[ 'quantity' ] ) : $composite_child[ 'data' ]->get_price_including_tax( $composite_child[ 'quantity' ] );
				}
			}

			$subtotal = $composite_price + $composited_items_price;

			return $this->format_product_subtotal( $values[ 'data' ], $subtotal );
		}

		return $subtotal;
	}

	/**
	 * Remove bundled cart items with parent.
	 *
	 * @param  string  $cart_item_key
	 * @param  WC_Cart $cart
	 * @return void
	 */
	public function wc_cp_cart_item_removed( $cart_item_key, $cart ) {

		if ( ! empty( $cart->removed_cart_contents[ $cart_item_key ][ 'composite_children' ] ) ) {

			$bundled_item_cart_keys = $cart->removed_cart_contents[ $cart_item_key ][ 'composite_children' ];

			foreach ( $bundled_item_cart_keys as $bundled_item_cart_key ) {

				if ( ! empty( $cart->cart_contents[ $bundled_item_cart_key ] ) ) {

					$remove = $cart->cart_contents[ $bundled_item_cart_key ];

					$cart->removed_cart_contents[ $bundled_item_cart_key ] = $remove;

					unset( $cart->cart_contents[ $bundled_item_cart_key ] );

					do_action( 'woocommerce_cart_item_removed', $bundled_item_cart_key, $cart );
				}
			}
		}
	}

	/**
	 * Restore bundled cart items with parent.
	 *
	 * @param  string  $cart_item_key
	 * @param  WC_Cart $cart
	 * @return void
	 */
	public function wc_cp_cart_item_restored( $cart_item_key, $cart ) {

		if ( ! empty( $cart->cart_contents[ $cart_item_key ][ 'composite_children' ] ) ) {

			$bundled_item_cart_keys = $cart->cart_contents[ $cart_item_key ][ 'composite_children' ];

			foreach ( $bundled_item_cart_keys as $bundled_item_cart_key ) {

				if ( ! empty( $cart->removed_cart_contents[ $bundled_item_cart_key ] ) ) {

					$remove = $cart->removed_cart_contents[ $bundled_item_cart_key ];

					$cart->cart_contents[ $bundled_item_cart_key ] = $remove;

					unset( $cart->removed_cart_contents[ $bundled_item_cart_key ] );

					do_action( 'woocommerce_cart_item_restored', $bundled_item_cart_key, $cart );
				}
			}
		}
	}

	/**
	 * Shipping fix - ensure that non-virtual containers/children, which are shipped, have a valid price that can be used for insurance calculations.
	 * Additionally, bundled item weights may have to be added in the container.
	 *
	 * Note: If you charge a static price for the composite but ship the contained items individually, the only working solution is to spread the total value among the bundled items.
	 *
	 * @param  array  $packages
	 * @return array
	 */
	public function wc_cp_shipping_packages_fix( $packages ) {

		if ( ! empty( $packages ) ) {

			foreach ( $packages as $package_key => $package ) {

				if ( ! empty( $package[ 'contents' ] ) ) {

					foreach ( $package[ 'contents' ] as $cart_item_key => $cart_item_data ) {

						if ( isset( $cart_item_data[ 'composite_children' ] ) ) {

							$composite     = clone $cart_item_data[ 'data' ];
							$composite_qty = $cart_item_data[ 'quantity' ];

							/*
							 * Physical container (bundled shipping):
							 *
							 * - If the container is priced per-item, sum the prices of the children into the parent.
							 * - Optionally, append the weight of the children into the parent.
							 */

							if ( ! $composite->is_shipped_per_product() ) {

								$bundled_value  = 0;
								$bundled_weight = 0;

								foreach ( $cart_item_data[ 'composite_children' ] as $child_item_key ) {

									if ( isset( $package[ 'contents' ][ $child_item_key ] ) ) {

										$composited_product     = clone $package[ 'contents' ][ $child_item_key ][ 'data' ];
										$composited_product_qty = $package[ 'contents' ][ $child_item_key ][ 'quantity' ];

										if ( isset( $composited_product->bundled_value ) ) {
											$bundled_value += $composited_product->bundled_value * $composited_product_qty;
											$composited_product->price = 0;
											$packages[ $package_key ][ 'contents' ][ $child_item_key ][ 'data' ] = $composited_product;
										}

										if ( isset( $composited_product->bundled_weight ) ) {
											$bundled_weight += $composited_product->bundled_weight * $composited_product_qty;
										}
									}
								}

								$composite->adjust_price( $bundled_value / $composite_qty );
								$composite->weight += $bundled_weight / $composite_qty;
								$packages[ $package_key ][ 'contents' ][ $cart_item_key ][ 'data' ] = $composite;

							/*
							 * Virtual container (non-bundled shipping enabled) that is priced statically:
							 * Distribute the price of the parent uniformly among the children.
							 */

							} elseif ( $composite->is_shipped_per_product() && ! $composite->is_priced_per_product() ) {

								$total_value        = $composite->get_price() * $composite_qty;
								$child_count        = 0;
								$composite_children = array();

								foreach ( $package[ 'contents' ] as $search_item_key => $search_item_data ) {

									if ( apply_filters( 'woocommerce_cart_item_is_child_of_composite', in_array( $search_item_key, $cart_item_data[ 'composite_children' ] ), $search_item_key, $search_item_data, $cart_item_key, $cart_item_data ) ) {

										$composited_product     = $package[ 'contents' ][ $search_item_key ][ 'data' ];
										$composited_product_qty = $package[ 'contents' ][ $search_item_key ][ 'quantity' ];

										if ( $composited_product->needs_shipping() ) {
											$child_count += $composited_product_qty;
											$total_value += $composited_product->get_price() * $composited_product_qty;
											$composite_children[] = $search_item_key;
										}
									}
								}

								foreach ( $composite_children as $child_item_key ) {

									$composited_product        = clone $package[ 'contents' ][ $child_item_key ][ 'data' ];
									$composited_product->price = round( $total_value / $child_count, 2 );

									$packages[ $package_key ][ 'contents' ][ $child_item_key ][ 'data' ] = $composited_product;
								}

								$composite->adjust_price( - $total_value );
								$packages[ $package_key ][ 'contents' ][ $cart_item_key ][ 'data' ] = $composite;
							}
						}
					}
				}
			}
		}

		return $packages;
	}
}
