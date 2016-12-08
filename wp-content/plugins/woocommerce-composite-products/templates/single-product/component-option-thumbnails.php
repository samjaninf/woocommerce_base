<?php
/**
 * Component Option Thumbnails Template.
 *
 * Override this template by copying it to 'yourtheme/woocommerce/single-product/component-option-thumbnails.php'.
 *
 * On occasion, this template file may need to be updated and you (the theme developer) will need to copy the new files to your theme to maintain compatibility.
 * We try to do this as little as possible, but it does happen.
 * When this occurs the version of the template file will be bumped and the readme will list any important changes.
 *
 * @version 3.1.1
 * @since   2.2.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$thumbnail_columns = $product->get_component_columns( $component_id );

?><div id="component_option_thumbnails_<?php echo $component_id; ?>" class="component_option_thumbnails columns-<?php echo esc_attr( $thumbnail_columns ); ?>" data-columns="<?php echo esc_attr( $thumbnail_columns ); ?>"><?php

	if ( ! empty( $component_options ) ) {

		?><ul class="component_option_thumbnails_container cp_clearfix" style="list-style:none"><?php

			$thumbnail_loop = 0;

			foreach ( $component_options as $product_id ) {

				$thumbnail_class    = '';
				$composited_product = $product->get_composited_product( $component_id, $product_id );

				if ( ! $composited_product ) {
					continue;
				}

				$thumbnail_loop++;

				$title           = $composited_product->get_product()->get_title();
				$quantity_string = $quantity_min == $quantity_max && $quantity_min > 1 ? $quantity_min : '';
				$price_string    = $composited_product->get_price_string();

				// Product loop first/last class
				if ( ( ( $thumbnail_loop - 1 ) % $thumbnail_columns ) == 0 || $thumbnail_columns == 1 ) {
					$thumbnail_class = 'first';
				}

				if ( $thumbnail_loop % $thumbnail_columns == 0 ) {
					$thumbnail_class .= ' last';
				}

				$selected = $selected_option == $product_id ? 'selected' : '';

				?><li class="component_option_thumbnail_container <?php echo $thumbnail_class; ?>">
					<div id="component_option_thumbnail_<?php echo $product_id; ?>" class="cp_clearfix component_option_thumbnail disabled <?php echo $selected; ?>" data-val="<?php echo $product_id; ?>">
						<a class="component_option_thumbnail_tap" href="#" ></a>
						<div class="image thumbnail_image" title="<?php echo esc_attr( $title ); ?>"><?php

							if ( has_post_thumbnail( $product_id ) ) {
								echo get_the_post_thumbnail( $product_id, apply_filters( 'woocommerce_composite_component_option_image_size', 'shop_catalog' ) );
							} else {
								echo apply_filters( 'woocommerce_composite_component_option_image_placeholder', sprintf( '<img src="%s" alt="%s" />', wc_placeholder_img_src(), __( 'Placeholder', 'woocommerce' ) ), $product_id, $component_id, $product->id );
							}

						?></div>
						<div class="thumbnail_description">
							<h5 class="thumbnail_title title">
								<?php echo apply_filters( 'woocommerce_composited_product_thumbnail_title', WC_CP_Product::get_title_string( $title ), $quantity_string, $price_string, $product_id, $component_id, $product ); ?>
							</h5>
							<span class="thumbnail_price price"><?php
								$composited_product->add_filters();
								echo $composited_product->get_product()->get_price_html();
								$composited_product->remove_filters(); ?>
							</span>
						</div>
					</div>

				</li><?php
			}
		?></ul><?php
	} else {

		?><p class="no_query_results"><?php
			echo __( 'No results found.', 'woocommerce-composite-products' );
		?></p><?php
	}

	?><div class="cp_clearfix"></div>
</div>
