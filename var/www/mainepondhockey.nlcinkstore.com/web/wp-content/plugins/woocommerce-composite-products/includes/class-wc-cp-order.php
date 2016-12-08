<?php
/**
 * Composite order-related filters and functions.
 *
 * @class 	WC_CP_Order
 * @version 3.3.0
 * @since   2.2.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_CP_Order {

	public function __construct() {

		// Filter price output shown in cart, review-order & order-details templates.
		add_filter( 'woocommerce_order_formatted_line_subtotal', array( $this, 'wc_cp_order_item_subtotal' ), 10, 3 );

		// Composite containers should not affect order status.
		add_filter( 'woocommerce_order_item_needs_processing', array( $this, 'wc_cp_container_items_need_no_processing' ), 10, 3 );

		// Modify order items to include composite meta.
		add_action( 'woocommerce_add_order_item_meta', array( $this, 'wc_cp_add_order_item_meta' ), 10, 3 );

		// Hide composite configuration metadata in order line items.
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'wc_cp_hide_order_item_meta' ) );

		// Filter admin dashboard item count.
		add_filter( 'woocommerce_get_item_count',  array( $this, 'wc_cp_dashboard_recent_orders_item_count' ), 10, 3 );
		add_filter( 'woocommerce_admin_order_item_count',  array( $this, 'wc_cp_order_item_count_string' ), 10, 2 );
		add_filter( 'woocommerce_admin_html_order_item_class',  array( $this, 'wc_cp_html_order_item_class' ), 10, 2 );
		add_filter( 'woocommerce_admin_order_item_class',  array( $this, 'wc_cp_html_order_item_class' ), 10, 2 );

		/*
		 * Order API Modifications.
		 */

		add_filter( 'woocommerce_get_product_from_item', array( $this, 'get_product_from_item' ), 10, 3 );
		add_filter( 'woocommerce_order_get_items', array( $this, 'order_items' ), 10, 2 );
		add_filter( 'woocommerce_order_get_items', array( $this, 'order_items_part_of_meta' ), 10, 2 );
	}

	/**
	 * Find the parent of a composited item in an order.
	 *
	 * @param  array    $item
	 * @param  WC_Order $order
	 * @return array
	 */
	public function get_composited_order_item_container( $item, $order ) {

		$composite_data = $item[ 'composite_data' ];

		remove_filter( 'woocommerce_order_get_items', array( $this, 'order_items_part_of_meta' ), 10, 2 );
		remove_filter( 'woocommerce_order_get_items', array( $this, 'order_items' ), 10, 2 );

		foreach ( $order->get_items( 'line_item' ) as $order_item ) {

			if ( isset( $order_item[ 'composite_cart_key' ] ) ) {
				$is_parent = $item[ 'composite_parent' ] === $order_item[ 'composite_cart_key' ];
			} else {
				$is_parent = isset( $order_item[ 'composite_data' ] ) && $order_item[ 'composite_data' ] === $composite_data && isset( $order_item[ 'composite_children' ] );
			}

			if ( $is_parent ) {
				return $order_item;
			}
		}

		add_filter( 'woocommerce_order_get_items', array( $this, 'order_items_part_of_meta' ), 10, 2 );
		add_filter( 'woocommerce_order_get_items', array( $this, 'order_items' ), 10, 2 );

		return false;
	}

	/**
	 * Modifies the subtotal of order-items (order-details.php) depending on the composite pricing strategy.
	 *
	 * @param  string 	$subtotal
	 * @param  array 	$item
	 * @param  WC_Order $order
	 * @return string
	 */
	public function wc_cp_order_item_subtotal( $subtotal, $item, $order ) {

		// If it's a composited item.
		if ( isset( $item[ 'composite_parent' ] ) ) {

			$composite_data = $item[ 'composite_data' ];

			// find composite parent.
			$parent_item = '';

			foreach ( $order->get_items( 'line_item' ) as $order_item ) {

				if ( isset( $order_item[ 'composite_cart_key' ] ) ) {
					$is_parent = $item[ 'composite_parent' ] === $order_item[ 'composite_cart_key' ];
				} else {
					$is_parent = isset( $order_item[ 'composite_data' ] ) && $order_item[ 'composite_data' ] === $composite_data && isset( $order_item[ 'composite_children' ] );
				}

				if ( $is_parent ) {
					$parent_item = $order_item;
					break;
				}

			}

			if ( function_exists( 'is_account_page' ) && is_account_page() || function_exists( 'is_checkout' ) && is_checkout() ) {
				$wrap_start = '';
				$wrap_end   = '';
			} else {
				$wrap_start = '<small>';
				$wrap_end   = '</small>';
			}

			if ( $parent_item[ 'per_product_pricing' ] === 'no' ) {
				return '';
			} else {
				return  $wrap_start . __( 'Option subtotal', 'woocommerce-composite-products' ) . ': ' . $subtotal . $wrap_end;
			}
		}

		// If it's a parent item.
		if ( isset( $item[ 'composite_children' ] ) ) {

			if ( isset( $item[ 'subtotal_updated' ] ) ) {
				return $subtotal;
			}

			foreach ( $order->get_items( 'line_item' ) as $order_item ) {

				if ( isset( $order_item[ 'composite_cart_key' ] ) ) {
					$is_child = in_array( $order_item[ 'composite_cart_key' ], unserialize( $item[ 'composite_children' ] ) ) ? true : false;
				} else {
					$is_child = isset( $order_item[ 'composite_data' ] ) && $order_item[ 'composite_data' ] == $item[ 'composite_data' ] && isset( $order_item[ 'composite_parent' ] ) ? true : false;
				}

				$is_child = apply_filters( 'woocommerce_order_item_is_child_of_composite', $is_child, $order_item, $item, $order );

				if ( $is_child ) {
					$item[ 'line_subtotal' ]     += $order_item[ 'line_subtotal' ];
					$item[ 'line_subtotal_tax' ] += $order_item[ 'line_subtotal_tax' ];
				}
			}

			$item[ 'subtotal_updated' ] = 'yes';

			return $order->get_formatted_line_subtotal( $item );
		}

		return $subtotal;
	}

	/**
	 * Composite Containers should not affect order status - let it be decided by composited items only.
	 *
	 * @param  bool 		$is_needed
	 * @param  WC_Product 	$product
	 * @param  int 			$order_id
	 * @return bool
	 */
	public function wc_cp_container_items_need_no_processing( $is_needed, $product, $order_id ) {

		if ( $product->is_type( 'composite' ) ) {
			return false;
		}

		return $is_needed;
	}

	/**
	 * Adds composite info to order items.
	 *
	 * @param  int 		$order_item_id
	 * @param  array 	$cart_item_values
	 * @param  string 	$cart_item_key
	 * @return void
	 */
	public function wc_cp_add_order_item_meta( $order_item_id, $cart_item_values, $cart_item_key ) {

		if ( ! empty( $cart_item_values[ 'composite_children' ] ) ) {

			wc_add_order_item_meta( $order_item_id, '_composite_children', $cart_item_values[ 'composite_children' ] );

			if ( $cart_item_values[ 'data' ]->is_priced_per_product() ) {
				wc_add_order_item_meta( $order_item_id, '_per_product_pricing', 'yes' );
			} else {
				wc_add_order_item_meta( $order_item_id, '_per_product_pricing', 'no' );
			}

			if ( $cart_item_values[ 'data' ]->is_shipped_per_product() ) {
				wc_add_order_item_meta( $order_item_id, '_per_product_shipping', 'yes' );
			} else {
				wc_add_order_item_meta( $order_item_id, '_per_product_shipping', 'no' );
			}
		}

		if ( ! empty( $cart_item_values[ 'composite_parent' ] ) ) {
			wc_add_order_item_meta( $order_item_id, '_composite_parent', $cart_item_values[ 'composite_parent' ] );
		}

		if ( ! empty( $cart_item_values[ 'composite_item' ] ) ) {
			wc_add_order_item_meta( $order_item_id, '_composite_item', $cart_item_values[ 'composite_item' ] );
		}

		if ( ! empty( $cart_item_values[ 'composite_data' ] ) ) {

			wc_add_order_item_meta( $order_item_id, '_composite_cart_key', $cart_item_key );

			wc_add_order_item_meta( $order_item_id, '_composite_data', $cart_item_values[ 'composite_data' ] );

			// Store shipping data - useful when exporting order content
			foreach ( WC()->cart->get_shipping_packages() as $package ) {

				foreach ( $package[ 'contents' ] as $pkg_item_id => $pkg_item_values ) {

					if ( $pkg_item_id === $cart_item_key ) {

						$bundled_shipping = $pkg_item_values[ 'data' ]->needs_shipping() ? 'yes' : 'no';
						$bundled_weight   = $pkg_item_values[ 'data' ]->get_weight();

						wc_add_order_item_meta( $order_item_id, '_bundled_shipping', $bundled_shipping );

						if ( $bundled_shipping === 'yes' ) {
							wc_add_order_item_meta( $order_item_id, '_bundled_weight', $bundled_weight );
						}
					}
				}
			}
		}
	}

	/**
	 * Hides composite metadata.
	 *
	 * @param  array $hidden
	 * @return array
	 */
	public function wc_cp_hide_order_item_meta( $hidden ) {
		return array_merge( $hidden, array( '_composite_parent', '_composite_item', '_composite_total', '_composite_cart_key', '_per_product_pricing', '_per_product_shipping', '_bundled_shipping', '_bundled_weight' ) );
	}

	/**
	 * Filters the reported number of admin dashboard recent order items - counts only composite containers.
	 *
	 * @param  int 			$count
	 * @param  string 		$type
	 * @param  WC_Order 	$order
	 * @return int
	 */
	public function wc_cp_dashboard_recent_orders_item_count( $count, $type, $order ) {

		$subtract = 0;

		foreach ( $order->get_items() as $order_item ) {

			if ( isset( $order_item[ 'composite_item' ] ) ) {
				$subtract += $order_item[ 'qty' ];
			}
		}

		return $count - $subtract;
	}

	/**
	 * Filters the string of order item count.
	 * Include bundled items as a suffix.
	 *
	 * @param  int          $count      initial reported count
	 * @param  WC_Order     $order      the order
	 * @return int                      modified count
	 */
	public function wc_cp_order_item_count_string( $count, $order ) {

		$add = 0;

		foreach ( $order->get_items() as $item ) {

			// If it's a bundled item.
			if ( isset( $item[ 'composite_item' ] ) ) {
				$add += $item[ 'qty' ];
			}
		}

		if ( $add > 0 ) {
			return sprintf( __( '%1$s, %2$s composited', 'woocommerce-composite-products' ), $count, $add );
		}

		return $count;
	}

	/**
	 * Filters the order item admin class.
	 *
	 * @param  string       $class     class
	 * @param  array        $item      the order item
	 * @return string                  modified class
	 */
	public function wc_cp_html_order_item_class( $class, $item ) {

		// If it's a bundled item
		if ( isset( $item[ 'composite_item' ] ) ) {
			return $class . ' composited_item';
		}

		return $class;
	}


	/*--------------------------*/
	/* Order API Modifications  */
	/*--------------------------*/

	/**
	 * Order API Modification #1:
	 *
	 * Restore virtual status and weights/dimensions of bundle containers/children depending on the "per-item pricing" and "non-bundled shipping" settings.
	 * Virtual containers/children are assigned a zero weight and tiny dimensions in order to maintain the value of the associated item in shipments (for instance, when a bundle has a static price but is shipped per item).
	 *
	 * @param  WC_Product $product
	 * @param  array      $item
	 * @param  WC_Order   $order
	 * @return WC_Product
	 */
	public function get_product_from_item( $product, $item, $order ) {

		if ( apply_filters( 'woocommerce_composite_filter_product_from_item', false, $order ) ) {

			if ( ! empty( $product ) && isset( $item[ 'composite_data' ] ) && isset( $item[ 'bundled_shipping' ] ) ) {
				if ( $item[ 'bundled_shipping' ] === 'yes' ) {
					if ( isset( $item[ 'bundled_weight' ] ) ) {
						$product->weight = $item[ 'bundled_weight' ];
					}
				} else {

					// Virtual container converted to non-virtual with zero weight and tiny dimensions if it has non-virtual bundled children.
					if ( isset( $item[ 'composite_children' ] ) && isset( $item[ 'composite_cart_key' ] ) ) {

						$bundle_key               = $item[ 'composite_cart_key' ];
						$non_virtual_child_exists = false;

						remove_filter( 'woocommerce_order_get_items', array( $this, 'order_items_part_of_meta' ), 10, 2 );
						remove_filter( 'woocommerce_order_get_items', array( $this, 'order_items' ), 10, 2 );

						foreach ( $order->get_items( 'line_item' ) as $child_item_id => $child_item ) {

							$is_child = apply_filters( 'woocommerce_order_item_is_child_of_composite', isset( $child_item[ 'composite_parent' ] ) && $child_item[ 'composite_parent' ] === $bundle_key, $child_item, $item, $order );

							if ( $is_child && isset( $child_item[ 'bundled_shipping' ] ) && $child_item[ 'bundled_shipping' ] === 'yes' ) {
								$non_virtual_child_exists = true;
								break;
							}
						}

						add_filter( 'woocommerce_order_get_items', array( $this, 'order_items_part_of_meta' ), 10, 2 );
						add_filter( 'woocommerce_order_get_items', array( $this, 'order_items' ), 10, 2 );

						if ( $non_virtual_child_exists ) {
							$product->virtual = 'no';
						}
					}

					$product->weight = 0;
					$product->length = $product->height = $product->width = 0.001;
				}
			}
		}

		return $product;
	}

	/**
	 * Order API Modification #2:
	 *
	 * Add "Part of" meta to bundled order items.
	 *
	 * @param  WC_Product $product
	 * @param  array      $item
	 * @param  WC_Order   $order
	 * @return WC_Product
	 */
	public function order_items_part_of_meta( $items, $order ) {

		if ( apply_filters( 'woocommerce_composite_filter_order_items_part_of_meta', false, $order ) ) {

			foreach ( $items as $item_id => $item ) {

				if ( isset( $item[ 'composite_data' ] ) && ! empty( $item[ 'composite_parent' ] ) ) {
					$parent = $this->get_composited_order_item_container( $item, $order );

					if ( $parent ) {

						if ( WC_CP_Core_Compatibility::is_wc_version_gte_2_4() ) {

							// Terrible hack: add an element in the 'item_meta_array' array (a puppy somewhere just died).
							if ( ! empty( $items[ $item_id ][ 'item_meta_array' ] ) ) {

								$keys         = array_keys( $items[ $item_id ][ 'item_meta_array' ] );
								$last_key     = end( $keys );

								$entry        = new stdClass();
								$entry->key   = __( 'Part of', 'woocommerce-composite-products' );
								$entry->value = $parent[ 'name' ];

								$items[ $item_id ][ 'item_meta_array' ][ $last_key + 1 ] = $entry;
							}
						}

						$items[ $item_id ][ 'item_meta' ][ __( 'Part of', 'woocommerce-composite-products' ) ] = $parent[ 'name' ];
					}
				}
			}
		}

		return $items;
	}

	/**
	 * Order API Modification #3 (unused):
	 *
	 * Exclude/modify order items depending on the "per-item pricing" and "non-bundled shipping" settings.
	 *
	 * @param  array    $items
	 * @param  WC_Order $order
	 * @return array
	 */
	public function order_items( $items, $order ) {

		$return_items = $items;

		if ( apply_filters( 'woocommerce_composite_filter_order_items', false, $order ) ) {

			$return_items = array();

			foreach ( $items as $item_id => $item ) {

				if ( isset( $item[ 'composite_children' ] ) && isset( $item[ 'composite_cart_key' ] ) ) {

					/*
					 * Do not export bundled items that are shipped packaged in the container ("bundled" shipping).
					 * Instead, add their totals into the container and create a container "Contents" meta field to provide a description of the included products.
					 */

					if ( isset( $item[ 'per_product_shipping' ] ) && $item[ 'per_product_shipping' ] === 'no' ) {

						$bundle_key  = $item[ 'composite_cart_key' ];

						// Aggregate contents
						$meta_key    = __( 'Contents', 'woocommerce-composite-products' );
						$meta_values = array();

						// Aggregate prices
						$bundle_totals = array(
							'line_subtotal'     => $item[ 'line_subtotal' ],
							'line_total'        => $item[ 'line_total' ],
							'line_subtotal_tax' => $item[ 'line_subtotal_tax' ],
							'line_tax'          => $item[ 'line_tax' ],
							'line_tax_data'     => maybe_unserialize( $item[ 'line_tax_data' ] )
						);

						foreach ( $items as $child_item_id => $child_item ) {

							if ( isset( $child_item[ 'composite_parent' ] ) && $child_item[ 'composite_parent' ] === $bundle_key && isset( $child_item[ 'bundled_shipping' ] ) && $child_item[ 'bundled_shipping' ] === 'no' ) {

								/*
								 * Aggregate bundled items shipped within the container as "Contents" meta of container.
								 */

								$child = $order->get_product_from_item( $child_item );

								if ( ! $child ) {
									continue;
								}

								$sku = $child->get_sku();

								if ( ! $sku ) {
									$sku = '#' . ( isset( $child->variation_id ) ? $child->variation_id : $child->id );
								}

								$title = WC_CP_Product::get_title_string( $child_item[ 'name' ], $child_item[ 'qty' ] );
								$meta  = '';

								if ( ! empty( $child_item[ 'item_meta' ] ) ) {

									if ( ! empty( $child_item[ 'item_meta' ][ __( 'Part of', 'woocommerce-composite-products' ) ] ) ) {
										unset( $child_item[ 'item_meta' ][ __( 'Part of', 'woocommerce-composite-products' ) ] );
									}

									if ( WC_CP_Core_Compatibility::is_wc_version_gte_2_4() ) {
										$item_meta = new WC_Order_Item_Meta( $child_item );
									} else {
										$item_meta = new WC_Order_Item_Meta( $child_item[ 'item_meta' ] );
									}

									$formatted_meta = $item_meta->display( true, true, '_', ', ' );

									if ( $formatted_meta ) {
										$meta = $formatted_meta;
									}
								}

								$meta_values[] = WC_CP()->api->format_product_title( $title, $sku, $meta, true );

								/*
								 * Aggregate the totals of bundled items shipped within the container into the container price.
								 */

								$bundle_totals[ 'line_subtotal' ]     += $child_item[ 'line_subtotal' ];
								$bundle_totals[ 'line_total' ]        += $child_item[ 'line_total' ];
								$bundle_totals[ 'line_subtotal_tax' ] += $child_item[ 'line_subtotal_tax' ];
								$bundle_totals[ 'line_tax' ]          += $child_item[ 'line_tax' ];

								$child_item_line_tax_data = maybe_unserialize( $child_item[ 'line_tax_data' ] );

								$bundle_totals[ 'line_tax_data' ][ 'total' ] = array_merge( $bundle_totals[ 'line_tax_data' ][ 'total' ], $child_item_line_tax_data[ 'total' ] );
							}
						}

						$items[ $item_id ][ 'line_tax_data' ] = serialize( $bundle_totals[ 'line_tax_data' ] );

						$items[ $item_id ]                    = array_merge( $item, $bundle_totals );


						if ( WC_CP_Core_Compatibility::is_wc_version_gte_2_4() ) {

							// Terrible hack: add an element in the 'item_meta_array' array (a puppy somewhere just died).
							if ( ! empty( $items[ $item_id ][ 'item_meta_array' ] ) ) {

								$keys         = array_keys( $items[ $item_id ][ 'item_meta_array' ] );
								$last_key     = end( $keys );

								$entry        = new stdClass();
								$entry->key   = $meta_key;
								$entry->value = implode( ', ', $meta_values );

								$items[ $item_id ][ 'item_meta_array' ][ $last_key + 1 ] = $entry;
							}
						}

						$items[ $item_id ][ 'item_meta' ][ $meta_key ] = implode( ', ', $meta_values );

						$return_items[ $item_id ] = $items[ $item_id ];

					/*
					 * If the bundled items are shipped individually ("non-bundled" shipping), do not export the container unless it has a non-zero price.
					 * In this case, instead of marking it as virtual, modify its weight and dimensions (tiny values) to avoid any extra shipping costs and ensure that its value is included in the shipment - @see 'get_product_from_item'.
					 */

					} elseif ( $item[ 'line_total' ] > 0 ) {
						$return_items[ $item_id ] = $items[ $item_id ];
					}

				} elseif ( isset( $item[ 'composite_parent' ] ) && isset( $item[ 'composite_cart_key' ] ) ) {

					if ( ! isset( $item[ 'bundled_shipping' ] ) || $item[ 'bundled_shipping' ] === 'yes' ) {
						$return_items[ $item_id ] = $items[ $item_id ];
					}

				} else {
					$return_items[ $item_id ] = $items[ $item_id ];
				}
			}
		}

		return $return_items;
	}
}
