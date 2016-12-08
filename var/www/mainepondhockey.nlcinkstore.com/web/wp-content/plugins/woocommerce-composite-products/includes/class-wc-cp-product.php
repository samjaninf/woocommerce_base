<?php
/**
 * Composited product wrapper class.
 *
 * @class   WC_CP_Product
 * @version 3.3.0
 * @since   2.6.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_CP_Product {

	private $product;

	private $min_price;
	private $max_price;
	private $min_regular_price;
	private $max_regular_price;

	private $is_nyp;
	private $is_sold_individually;
	private $is_purchasable;

	private $component_data;
	private $component_id;
	private $composite;

	private $per_product_pricing;

	public function __construct( $product_id, $component_id, $parent ) {

		$this->product = wc_get_product( $product_id );

		if ( $this->product ) {

			$this->component_data      = $parent->get_component_data( $component_id );
			$this->component_id        = $component_id;
			$this->per_product_pricing = $parent->is_priced_per_product();
			$this->composite           = $parent;

			$this->add_filters();

			$this->init();

			$this->remove_filters();
		}
	}

	/**
	 * Initialize composited product price data, if needed.
	 *
	 * @return void
	 */
	public function init() {

		// Init prices
		$this->min_price          = 0;
		$this->max_price          = 0;
		$this->min_regular_price  = 0;
		$this->max_regular_price  = 0;

		$this->min_price_incl_tax = 0;
		$this->min_price_excl_tax = 0;

		$id = $this->get_product()->id;

		// Sold individually status.
		$this->is_sold_individually = get_post_meta( $id, '_sold_individually', true );

		// Purchasable status.
		if ( ! $this->per_product_pricing && $this->product->price === '' ) {
			$this->product->price = 0;
		}

		$this->is_purchasable = $this->product->is_purchasable();

		// Calculate product prices.
		if ( $this->per_product_pricing && $this->is_purchasable ) {

			$composited_product = $this->product;
			$product_type       = $composited_product->product_type;

			$this->is_nyp       = false;

			/*-----------------------------------------------------------------------------------*/
			/*  Simple Products and Static Bundles.  */
			/*-----------------------------------------------------------------------------------*/

			if ( $product_type === 'simple' ) {

				$product_price         = $composited_product->get_price();
				$product_regular_price = $composited_product->get_regular_price();

				// Name your price support.
				if ( WC_CP()->compatibility->is_nyp( $composited_product ) ) {
					$product_price = $product_regular_price = WC_Name_Your_Price_Helpers::get_minimum_price( $id ) ? WC_Name_Your_Price_Helpers::get_minimum_price( $id ) : 0;
					$this->is_nyp = true;
				}

				$this->min_price          = $this->max_price         = $product_price;
				$this->min_regular_price  = $this->max_regular_price = $product_regular_price;

			/*-----------------------------------------------------------------------------------*/
			/*  Variable Products.  */
			/*-----------------------------------------------------------------------------------*/

			} elseif ( $product_type === 'variable' ) {

				if ( WC_CP_Core_Compatibility::is_wc_version_gte_2_4() ) {

					$this->remove_filters();
					$variation_prices = $composited_product->get_variation_prices( false );
					$this->add_filters();

					if ( ! empty( $this->component_data[ 'discount' ] ) && apply_filters( 'woocommerce_composited_product_discount_from_regular', true, $this->component_id, $this->composite->id ) ) {
						$variation_price_ids = array_keys( $variation_prices[ 'regular_price' ] );
					} else {
						$variation_price_ids = array_keys( $variation_prices[ 'price' ] );
					}

					$min_variation_price_id = current( $variation_price_ids );
					$max_variation_price_id = end( $variation_price_ids );

				} else {

					if ( ! empty( $this->component_data[ 'discount' ] ) && apply_filters( 'woocommerce_composited_product_discount_from_regular', true, $this->component_id, $this->composite->id ) ) {

						// Product may need to be synced.
						if ( $composited_product->get_variation_regular_price( 'min', false ) === false ) {
							$this->remove_filters();
							$composited_product->variable_product_sync();
							$this->add_filters();
						}

						$min_variation_price_id = get_post_meta( $this->product->id, '_min_regular_price_variation_id', true );
						$max_variation_price_id = get_post_meta( $this->product->id, '_max_regular_price_variation_id', true );

					} else {

						// Product may need to be synced.
						if ( $composited_product->get_variation_price( 'min', false ) === false ) {
							$this->remove_filters();
							$composited_product->variable_product_sync();
							$this->add_filters();
						}

						$min_variation_price_id = get_post_meta( $this->product->id, '_min_price_variation_id', true );
						$max_variation_price_id = get_post_meta( $this->product->id, '_max_price_variation_id', true );
					}
				}

				$min_variation = $composited_product->get_child( $min_variation_price_id );
				$max_variation = $composited_product->get_child( $max_variation_price_id );

				if ( $min_variation && $max_variation ) {
					$this->min_price             = $min_variation->get_price();
					$this->max_price             = $max_variation->get_price();
					$min_variation_regular_price = $min_variation->get_regular_price();
					$max_variation_regular_price = $max_variation->get_regular_price();

					// the variation with the lowest price may have a higher regular price then the variation with the highest price.
					$this->min_regular_price    = min( $min_variation_regular_price, $max_variation_regular_price );
					$this->max_regular_price    = max( $min_variation_regular_price, $max_variation_regular_price );
				}

			/*-----------------------------------------------------------------------------------*/
			/*  Other Product Types.  */
			/*-----------------------------------------------------------------------------------*/

			} else {

				$this->min_price         = apply_filters( 'woocommerce_composited_product_min_price', $this->min_price, $this );
				$this->max_price         = apply_filters( 'woocommerce_composited_product_max_price', $this->max_price, $this );

				$this->min_regular_price = apply_filters( 'woocommerce_composited_product_min_regular_price', $this->min_regular_price, $this );
				$this->max_regular_price = apply_filters( 'woocommerce_composited_product_max_regular_price', $this->max_regular_price, $this );

				$this->is_nyp            = apply_filters( 'woocommerce_composited_product_is_nyp', $this->is_nyp, $this );
			}
		}
	}

	/**
	 * Generated dropdown price string for composited products in per product pricing mode.
	 *
	 * @return string
	 */
	public function get_price_string() {

		if ( ! $this->exists() ) {
			return false;
		}

		$price_string = '';
		$component_id = $this->component_id;
		$product_id   = $this->get_product()->id;

		if ( $this->per_product_pricing && $this->is_purchasable ) {

			$discount = $sale = '';

			$has_multiple = ! $this->is_sold_individually() && $this->component_data[ 'quantity_min' ] > 1;

			$ref_price = $this->get_regular_price( 'min', true );
			$price     = $this->get_price( 'min', true );
			$is_nyp    = $this->is_nyp;
			$is_range  = $price < $this->get_price( 'max', true );

			if ( ! empty( $this->component_data[ 'discount' ] ) && $ref_price > 0 && ! $is_nyp && $this->get_product() && $this->get_product()->product_type !== 'bundle' ) {
				$discount = sprintf( __( '(%s%% off)', 'woocommerce-composite-products' ), round( $this->component_data[ 'discount' ], 1 ) );
			}

			if ( ! $discount && $ref_price > $price && $ref_price > 0 && ! $is_nyp ) {
				$sale = sprintf( __( '(%s%% off)', 'woocommerce-composite-products' ), round( 100 * ( $ref_price - $price ) / $ref_price, 1 ) );
			}

			$pct_off = $discount . $sale;

			$suffix       = apply_filters( 'woocommerce_composited_product_price_suffix', $pct_off, $component_id, $product_id, $price, $ref_price, $is_nyp, $is_range, $this ) ;
			$show_free    = $price == 0 && ! $is_range;
			$price_string = $show_free ? __( 'Free!', 'woocommerce' ) : WC_CP()->api->get_composited_item_price_string_price( $price );
			$qty_suffix   = $has_multiple && ! $show_free ? __( '/ pc.', 'woocommerce-composite-products' ) : '';

			$price_string = apply_filters( 'woocommerce_composited_product_price_string_inner', sprintf( __( '%1$s %2$s %3$s', 'dropdown price followed by per unit suffix and discount suffix', 'woocommerce-composite-products' ), $price_string, $qty_suffix, $suffix ), $price_string, $qty_suffix, $suffix, $price, $is_range, $has_multiple, $product_id, $component_id, $this );

			$price_string = $is_range || $is_nyp ? sprintf( _x( '%1$s%2$s', 'Price range: from', 'woocommerce-composite-products' ), $this->get_product()->get_price_html_from_text(), $price_string ) : $price_string;
		}

		return apply_filters( 'woocommerce_composited_product_price_string', $price_string, $product_id, $component_id, $this );
	}

	/**
	 * Generated title string for composited products.
	 *
	 * @param  string $title
	 * @param  string $qty
	 * @param  string $price
	 * @return string
	 */
	public static function get_title_string( $title, $qty = '', $price = '' ) {

		$quantity_string = '';
		$price_string    = '';

		if ( $qty ) {
			$quantity_string = sprintf( _x( ' &times; %s', 'qty string', 'woocommerce-composite-products' ), $qty );
		}

		if ( $price ) {
			$price_string = sprintf( _x( ' &ndash; %s', 'price suffix', 'woocommerce-composite-products' ), $price );
		}

		$title_string = sprintf( _x( '%1$s%2$s%3$s', 'title quantity price', 'woocommerce-composite-products' ), $title, $quantity_string, $price_string );

		return $title_string;
	}

	/**
	 * Adds price filters to account for component discounts.
	 *
	 * @return void
	 */
	public function add_filters() {

		$product = $this->get_product();

		if ( ! $product ) {
			return false;
		}

		WC_CP()->api->apply_composited_product_filters( $product, $this->component_id, $this->composite );
	}

	/**
	 * Removes attached price filters.
	 *
	 * @return void
	 */
	public function remove_filters() {

		WC_CP()->api->remove_composited_product_filters();
	}

	/**
	 * Get composited product.
	 *
	 * @return WC_Product|false
	 */
	public function get_product() {

		if ( ! $this->exists() ) {
			return false;
		}

		return $this->product;
	}

	/**
	 * Get composite product.
	 *
	 * @return WC_Product_Composite|false
	 */
	public function get_composite() {

		if ( empty( $this->composite ) ) {
			return false;
		}

		return $this->composite;
	}

	/**
	 * Get component id.
	 *
	 * @return string|false
	 */
	public function get_component_id() {

		if ( empty( $this->component_id ) ) {
			return false;
		}

		return $this->component_id;
	}

	/**
	 * True if the composited product is marked as individually-sold item.
	 *
	 * @return boolean
	 */
	public function is_sold_individually() {

		$is_sold_individually = false;

		if ( $this->is_sold_individually === 'yes' ) {
			$is_sold_individually = true;
		}

		return $is_sold_individually;
	}

	/**
	 * True if the composited product is a NYP product.
	 *
	 * @return boolean
	 */
	public function is_nyp() {

		return $this->is_nyp;
	}

	/**
	 * True if the composited product is a valid product.
	 *
	 * @return boolean
	 */
	public function exists() {

		$exists = false;

		if ( ! empty( $this->product ) ) {
			$exists = true;
		}

		return $exists;
	}

	/**
	 * Get bundled item price after discount.
	 *
	 * @param  string  $min_or_max
	 * @param  boolean $display
	 * @return double
	 */
	public function get_price( $min_or_max = 'min', $display = false ) {

		if ( ! $this->exists() ) {
			return false;
		}

		$price = $min_or_max === 'min' ? $this->min_price : $this->max_price;

		return apply_filters( 'woocommerce_composited_product_get_price', $display ? WC_CP()->api->get_composited_product_price( $this->product, $price ) : $price, $min_or_max, $display, $this );
	}

	/**
	 * Get bundled item regular price after discount.
	 *
	 * @param  string  $min_or_max
	 * @param  boolean $display
	 * @return double
	 */
	public function get_regular_price( $min_or_max = 'min', $display = false ) {

		if ( ! $this->exists() ) {
			return false;
		}

		$price = $min_or_max === 'min' ? $this->min_regular_price : $this->max_regular_price;

		return apply_filters( 'woocommerce_composited_product_get_regular_price', $display ? WC_CP()->api->get_composited_product_price( $this->product, $price ) : $price, $min_or_max, $display, $this );
	}

	/**
	 * Min bundled item price incl tax.
	 *
	 * @return double
	 */
	public function get_price_including_tax( $min_or_max = 'min' ) {

		if ( ! $this->exists() ) {
			return false;
		}

		$price = $min_or_max === 'min' ? $this->min_price : $this->max_price;

		if ( $price && get_option( 'woocommerce_calc_taxes' ) === 'yes' ) {
			if ( get_option( 'woocommerce_prices_include_tax' ) !== 'yes' ) {
				$price = $this->product->get_price_including_tax( 1, $price );
			}
		}

		return apply_filters( 'woocommerce_composited_product_get_price_including_tax', $price, $min_or_max, $this );
	}

	/**
	 * Min bundled item price excl tax.
	 *
	 * @return double
	 */
	public function get_price_excluding_tax( $min_or_max = 'min' ) {

		if ( ! $this->exists() ) {
			return false;
		}

		$price = $min_or_max === 'min' ? $this->min_price : $this->max_price;

		if ( $price && get_option( 'woocommerce_calc_taxes' ) === 'yes' ) {
			if ( get_option( 'woocommerce_prices_include_tax' ) === 'yes' ) {
				$price = $this->product->get_price_excluding_tax( 1, $price );
			}
		}

		return apply_filters( 'woocommerce_composited_product_get_price_excluding_tax', $price, $min_or_max, $this );
	}

	/**
	 * Returns false if the product cannot be bought.
	 *
	 * @return bool
	 */
	public function is_purchasable() {

		return $this->is_purchasable;
	}

	/**
	 * Deprecated price methods.
	 *
	 * @deprecated
	 */
	public function get_min_price() {
		_deprecated_function( 'get_min_price()', '3.2.3', 'get_price()' );
		return $this->min_price;
	}

	public function get_min_regular_price() {
		_deprecated_function( 'get_min_regular_price()', '3.2.3', 'get_regular_price()' );
		return $this->min_regular_price;
	}

	public function get_max_price() {
		_deprecated_function( 'get_max_price()', '3.2.3', 'get_price()' );
		return $this->max_price;
	}

	public function get_max_regular_price() {
		_deprecated_function( 'get_max_regular_price()', '3.2.3', 'get_regular_price()' );
		return $this->max_regular_price;
	}

	public function get_min_price_incl_tax() {
		_deprecated_function( 'get_min_price_incl_tax()', '3.2.3', 'get_price_including_tax()' );
		return $this->get_price_including_tax( 'min' );
	}

	public function get_min_price_excl_tax() {
		_deprecated_function( 'get_min_price_excl_tax()', '3.2.3', 'get_price_excluding_tax()' );
		return $this->get_price_excluding_tax( 'min' );
	}
}
