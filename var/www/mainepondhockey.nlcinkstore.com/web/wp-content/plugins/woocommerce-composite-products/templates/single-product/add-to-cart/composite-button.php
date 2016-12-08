<?php
/**
 * Composite add-to-cart button template.
 *
 * Override this template by copying it to 'yourtheme/woocommerce/single-product/add-to-cart/composite-button.php'.
 *
 * @version 2.5.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

?>
<button type="submit" class="single_add_to_cart_button composite_add_to_cart_button button alt"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>
