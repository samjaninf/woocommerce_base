<?php
/**
 * Composite quantity input template.
 *
 * Override this template by copying it to 'yourtheme/woocommerce/single-product/add-to-cart/composite-quantity-input.php'.
 *
 * @version 2.5.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! $product->is_sold_individually() )
	woocommerce_quantity_input( array ( 'min_value' => 1 ) );
else {
	?><input class="qty" type="hidden" name="quantity" value="1" /><?php
}
