/* jshint -W069 */
/* jshint -W041 */
/* jshint -W018 */
/* global wc_composite_params */
/* global woocommerce_params */
/* global wc_pb_bundle_scripts */

/**
 * Composite scripts, accessible to the outside world.
 */
var wc_cp_composite_scripts = {};

jQuery( document ).ready( function($) {

	$( 'body' ).on( 'quick-view-displayed', function() {

		$( '.composite_form .composite_data' ).each( function() {
			$( this ).wc_composite_form();
		} );
	} );

	/**
	 * Responsive form CSS (we can't rely on media queries since we must work with the .composite_form width, not screen width).
	 */
	$( window ).resize( function() {

		$.each( wc_cp_composite_scripts, function( container_id, composite ) {

			clearTimeout( composite.on_resize_timer );

			composite.on_resize_timer = setTimeout( function() {
				composite.on_resize_handler();
			}, 50 );
		} );

	} );

	/**
	 * BlockUI background params.
	 */
	var wc_cp_block_params = {};

	if ( wc_composite_params.is_wc_version_gte_2_3 === 'yes' ) {

		wc_cp_block_params = {
			message:    null,
			overlayCSS: {
				background: 'rgba( 255, 255, 255, 0 )',
				opacity:    0.6
			}
		};
	} else {

		wc_cp_block_params = {
			message:    null,
			overlayCSS: {
				background: 'rgba( 255, 255, 255, 0 ) url(' + woocommerce_params.ajax_loader_url + ') no-repeat center',
				backgroundSize: '20px 20px',
				opacity:    0.6
			}
		};
	}

	/**
 	* Populate composite scripts.
 	*/
	$.fn.wc_composite_form = function() {

		if ( ! $( this ).hasClass( 'composite_data' ) ) {
			return true;
		}

		var composite_data = $( this );
		var container_id   = $( this ).data( 'container_id' );

		if ( typeof( wc_cp_composite_scripts[ container_id ] ) !== 'undefined' ) {
			return true;
		}

		var composite_form          = composite_data.closest( '.composite_form' );
		var composite_form_settings = composite_data.data( 'composite_settings' );

		wc_cp_composite_scripts[ container_id ] = {

			$composite_data:                    composite_data,
			$composite_form:                    composite_form,
			$composite_add_to_cart_button:      composite_form.find( '.composite_add_to_cart_button' ),
			$composite_navigation:              composite_form.find( '.composite_navigation' ),
			$composite_navigation_top:          composite_form.find( '.composite_navigation.top' ),
			$composite_navigation_bottom:       composite_form.find( '.composite_navigation.bottom' ),
			$composite_navigation_movable:      composite_form.find( '.composite_navigation.movable' ),
			$composite_pagination:              composite_form.find( '.composite_pagination' ),
			$composite_summary:                 composite_form.find( '.composite_summary' ),
			$composite_summary_widget:          $( '.widget_composite_summary' ),
			$components:                        composite_form.find( '.component' ),
			$steps:                             {},

			$composite_price:                   composite_data.find( '.composite_price' ),
			$composite_message:                 composite_data.find( '.composite_message' ),
			$composite_message_content:         composite_data.find( '.composite_message ul.msg' ),
			$composite_button:                  composite_data.find( '.composite_button' ),

			$composite_stock_status:            false,

			on_resize_timer:                    false,

			ajax_url:                           '',

			composite_id:                       container_id,
			composite_settings:                 composite_data.data( 'composite_settings' ),
			composite_layout:                   composite_form_settings.layout,
			composite_layout_variation:         composite_form_settings.layout_variation,
			composite_selection_mode:           composite_form_settings.selection_mode,
			composite_button_behaviour:         composite_form_settings.button_behaviour,
			composite_sequential_comp_progress: composite_form_settings.sequential_componentized_progress,
			actions_nesting:                    0,
			last_active_step_index:             0,
			append_results_nesting:             0,
			append_results_nesting_count:       0,

			composite_components:               {},

			composite_steps:                    {},

			composite_initialized:              false,
			has_update_lock:                    false,
			has_ui_update_lock:                 false,
			has_transition_lock:                false,
			has_update_nav_delay:               false,
			has_scenarios_update_lock:          false,
			current_transition_delay:           200,
			active_scenarios:                   {},

			composite_summary_widget:           {},

			_validation_messages:               [],

			/**
			 * Insertion point.
			 */

			init: function() {

				/**
				 * Bind composite event handlers.
				 */

				this.bind_event_handlers();

				/**
				 * Init components.
				 */

				this.init_components();

				/**
				 * Init steps.
				 */

				this.init_steps();

				/**
				 * Init widget.
				 */

				this.init_widget();

				/**
				 * Initial states and loading.
				 */

				var composite      = this;
				var composite_data = composite.$composite_data;

				if ( wc_composite_params.use_wc_ajax === 'yes' ) {
					composite.ajax_url = woocommerce_params.wc_ajax_url;
				} else {
					composite.ajax_url = woocommerce_params.ajax_url;
				}

				if ( typeof( composite.composite_sequential_comp_progress ) === 'undefined' ) {
					composite.composite_sequential_comp_progress = 'yes';
				}

				// Trigger resize to add responsive CSS classes to form.
				composite.on_resize_handler();

				// Save composite stock status.
				if ( composite_data.find( '.composite_wrap p.stock' ).length > 0 ) {
					composite.$composite_stock_status = composite_data.find( '.composite_wrap p.stock' ).clone();
				}

				// Ensure composite msg div exists (template back-compat).
				if ( composite.$composite_message_content.length == 0 ) {
					if ( composite.$composite_message.length > 0 ) {
						composite.$composite_message.remove();
					}
					composite.$composite_price.after( '<div class="composite_message" style="display:none"><ul class="msg woocommerce-error"></ul></div>' );
					composite.$composite_message         = composite_data.find( '.composite_message' );
					composite.$composite_message_content = composite.$composite_message.find( 'ul.msg' );
				}
				// Treat as info in paged layouts.
				if ( composite.composite_layout === 'paged' ) {
					composite.$composite_message_content.removeClass( 'woocommerce-error' ).addClass( 'woocommerce-info' );
				}

				// Add-ons support - move totals container.
				var addons_totals = composite_data.find( '#product-addons-total' );
				composite.$composite_price.after( addons_totals );

				// NYP support.
				composite_data.find( '.nyp' ).trigger( 'woocommerce-nyp-updated-item' );

				// Init toggle boxes.
				if ( composite.composite_layout === 'progressive' && ! composite.$composite_data.hasClass( 'composite_added_to_cart' ) ) {
					composite.$composite_form.find( '.toggled:not(.active) .component_title' ).addClass( 'inactive' );
				}

				// Trigger pre-init event.
				composite_data.trigger( 'wc-composite-initializing', [ composite ] );


				// Initialize component selection states and quantities for all modes.

				composite.has_scenarios_update_lock = true;

				$.each( composite.composite_components, function( index, component ) {

					var component_options_select = component.$component_options.find( 'select.component_options_select' );
					component.set_selection_id( component_options_select.val() );

					// Load main component scripts.
					component.init_scripts();

					// Load 3rd party scripts.
					component.$self.trigger( 'wc-composite-component-loaded', [ component, composite ] );

				} );

				composite.has_scenarios_update_lock = false;

				// Remove js notice.
				composite.$composite_form.find( '.cp-no-js-msg' ).remove();

				// Activate initial step.
				var current_step = composite.get_current_step();

				composite.last_active_step_index = current_step.step_index;

				if ( composite.composite_layout === 'paged' || composite.composite_layout === 'progressive' ) {

					current_step.show_step();

				} else {

					current_step.set_active();

					current_step.fire_scenario_actions();

					composite.update_ui();
				}

				// Set the form as initialized and validate/update it.
				composite.composite_initialized = true;

				composite.update_composite();

				// Let 3rd party scripts know that all component options are loaded.
				$.each( composite.composite_components, function( index, component ) {
					component.$self.trigger( 'wc-composite-component-options-loaded', [ component, composite ] );
				} );

				// Trigger post-init event.
				composite_data.trigger( 'wc-composite-initialized', [ composite ] );

			},

			/**
			 * Attach composite-level event handlers.
			 */

			bind_event_handlers: function() {

				var composite = this;

				/**
				 * Activate all fields.
				 */
				this.$composite_add_to_cart_button

					.on( 'click', function() {

						$.each( composite.composite_components, function( index, component ) {
							component.$self.find( 'select, input' ).each( function() {
								$( this ).prop( 'disabled', false );
							} );
						} );
					} );


				/**
				 * Update composite totals when a new NYP price is entered at composite level.
				 */
				this.$composite_data

					.on( 'woocommerce-nyp-updated-item', function() {

						var nyp = $( this ).find( '.nyp' );

						if ( nyp.length > 0 ) {

							var price_data = $( this ).data( 'price_data' );

							price_data[ 'base_price' ] = nyp.data( 'price' );

							composite.update_composite();
						}
					} );


				/**
				 * On clicking the Next / Previous navigation buttons.
				 */
				this.$composite_navigation

					.on( 'click', '.page_button', function() {

						if ( $( this ).hasClass( 'inactive' ) ) {
							return false;
						}

						if ( composite.has_transition_lock ) {
							return false;
						}

						if ( $( this ).hasClass( 'next' ) ) {

							if ( composite.get_next_step() ) {

								composite.show_next_step();

							} else {

								wc_cp_scroll_viewport( composite.$composite_form.find( '.scroll_final_step' ), { partial: false, duration: 250, queue: false } );
							}

						} else {

							composite.show_previous_step();
						}

						$( this ).blur();

						return false;

					} );


				/**
				 * On clicking a composite pagination link.
				 */
				this.$composite_pagination

					.on( 'click', '.pagination_element a', function() {

						if ( composite.has_transition_lock ) {
							return false;
						}

						if ( $( this ).hasClass( 'inactive' ) ) {
							return false;
						}

						var step_id = $( this ).closest( '.pagination_element' ).data( 'item_id' );

						composite.show_step( step_id );

						$( this ).blur();

						return false;

					} );


				/**
				 * On clicking a composite summary link (review section).
				 */
				this.$composite_summary

					.on( 'click', '.summary_element_link', function() {

						if ( composite.has_transition_lock ) {
							return false;
						}

						var form = composite.$composite_form;

						if ( $( this ).hasClass( 'disabled' ) ) {
							return false;
						}

						var step_id = $( this ).closest( '.summary_element' ).data( 'item_id' );

						if ( typeof( step_id ) === 'undefined' ) {
							var composite_summary = composite.$composite_summary;
							var element_index     = composite_summary.find( '.summary_element' ).index( $( this ).closest( '.summary_element' ) );
							step_id               = form.find( '.multistep.component:eq(' + element_index + ')' ).data( 'item_id' );
						}

						var step = composite.get_step( step_id );

						if ( step === false ) {
							return false;
						}

						if ( step.get_element().hasClass( 'progressive' ) ) {
							step.block_next_steps();
						}

						if ( ! step.is_current() || composite.composite_layout === 'single' ) {
							step.show_step();
						}

						return false;

					} )

					.on( 'click', 'a.summary_element_tap', function() {
						$( this ).closest( '.summary_element_link' ).trigger( 'click' );
						return false;
					} );

			},

			/**
			 * Initialize component objects.
			 */

			init_components: function() {

				var composite = this;

				composite.$components.each( function( index ) {

					composite.composite_components[ index ] = new WC_CP_Component( composite, $( this ), index );

					composite.bind_component_event_handlers( composite.composite_components[ index ] );

				} );

			},

			/**
			 * Attach component-level event handlers.
			 */

			bind_component_event_handlers: function( component ) {

				var composite = this;

				component.$self

					.on( 'wc-composite-component-loaded', function() {

						if ( $.isFunction( $.fn.prettyPhoto ) ) {

							$( this ).find( 'a[data-rel^="prettyPhoto"]' ).prettyPhoto( {
								hook: 'data-rel',
								social_tools: false,
								theme: 'pp_woocommerce',
								horizontal_padding: 20,
								opacity: 0.8,
								deeplinking: false
							} );
						}
					} )

					/**
					 * Update composite totals when a new Add-on is selected.
					 */
					.on( 'woocommerce-product-addons-update', function() {

						var addons = $( this ).find( '.addon' );

						if ( addons.length == 0 ) {
							return false;
						}

						composite.update_composite();
					} )

					/**
					 * Update composite totals when a new NYP price is entered.
					 */
					.on( 'woocommerce-nyp-updated-item', function() {

						var nyp = $( this ).find( '.cart .nyp' );

						if ( nyp.length > 0 && component.get_selected_product_type() !== 'variable' ) {

							component.$component_data.data( 'price', nyp.data( 'price' ) );
							component.$component_data.data( 'regular_price', nyp.data( 'price' ) );

							composite.update_composite();
						}
					} )

					/**
					 * Reset composite totals and form inputs when a new variation selection is initiated.
					 */
					.on( 'woocommerce_variation_select_change', function( event ) {

						var summary = component.$component_summary;

						// Mark component as not set.
						component.$component_data.data( 'component_set', false );

						// Add images class to composited_product_images div ( required by the variations script to flip images ).
						summary.find( '.composited_product_images' ).addClass( 'images' );

						$( this ).find( '.variations .attribute-options select' ).each( function() {

							if ( $( this ).val() === '' ) {
								summary.find( '.component_wrap .single_variation input.variation_id' ).val( '' );
								summary.find( '.component_wrap .stock' ).addClass( 'inactive' );

								var step = component.get_step();

								step.fire_scenario_actions();

								composite.update_ui();
								composite.update_composite();
								return false;
							}
						} );
					} )

					.on( 'woocommerce_variation_select_focusin', function( event ) {

						component.get_step().fire_scenario_actions( true );
					} )

					.on( 'reset_image', function( event ) {

						var summary = component.$component_summary;

						// Remove images class from composited_product_images div in order to avoid styling issues.
						summary.find( '.composited_product_images' ).removeClass( 'images' );
					} )

					/**
					 * Update composite totals and form inputs when a new variation is selected.
					 */
					.on( 'found_variation', function( event, variation ) {

						var summary = component.$component_summary;

						// Copy variation price data.
						var price_data = composite.$composite_data.data( 'price_data' );

						if ( price_data[ 'per_product_pricing' ] === 'yes' ) {
							component.$component_data.data( 'price', variation.price );
							component.$component_data.data( 'regular_price', variation.regular_price );
						}

						// Mark component as set.
						component.$component_data.data( 'component_set', true );

						// Remove images class from composited_product_images div in order to avoid styling issues.
						summary.find( '.composited_product_images' ).removeClass( 'images' );

						// Handle sold_individually variations qty.
						if ( variation.is_sold_individually === 'yes' ) {
							$( this ).find( '.component_wrap input.qty' ).val( '1' ).change();
						}

						component.get_step().fire_scenario_actions();

						composite.update_ui();
						composite.update_composite();
					} )

					/**
					 * Event triggered by custom product types to indicate that the state of the component selection has changed.
					 */
					.on ( 'woocommerce-composited-product-update', function( event ) {

						var price_data = composite.$composite_data.data( 'price_data' );

						if ( price_data[ 'per_product_pricing' ] === 'yes' ) {

							var bundle_price         = component.$component_data.data( 'price' );
							var bundle_regular_price = component.$component_data.data( 'regular_price' );

							component.$component_data.data( 'price', bundle_price );
							component.$component_data.data( 'regular_price', bundle_regular_price );
						}

						composite.update_ui();
						composite.update_composite();
					} )

					/**
					 * On clicking the clear options button.
					 */
					.on( 'click', '.clear_component_options', function( event ) {

						if ( $( this ).hasClass( 'reset_component_options' ) ) {
							return false;
						}

						var selection = component.$component_options.find( 'select.component_options_select' );

						component.get_step().unblock_step_inputs();

						component.$self.find( '.component_option_thumbnails .selected' ).removeClass( 'selected' );

						selection.val( '' ).change();

						return false;
					} )

					/**
					 * On clicking the reset options button.
					 */
					.on( 'click', '.reset_component_options', function( event ) {

						var step      = component.get_step();
						var selection = component.$component_options.find( 'select.component_options_select' );

						step.unblock_step_inputs();

						component.$self.find( '.component_option_thumbnails .selected' ).removeClass( 'selected' );

						step.set_active();

						selection.val( '' ).change();

						step.block_next_steps();

						return false;
					} )

					/**
					 * On clicking the blocked area in progressive mode.
					 */
					.on( 'click', '.block_component_selections_inner', function( event ) {

						var step = component.get_step();

						step.block_next_steps();
						step.show_step();

						return false;
					} )

					/**
					 * On clicking a thumbnail.
					 */
					.on( 'click', '.component_option_thumbnail', function( event ) {

						var item = component.$self;

						if ( item.hasClass( 'disabled' ) || $( this ).hasClass( 'disabled' ) ) {
							return true;
						}

						$( this ).blur();

						if ( ! $( this ).hasClass( 'selected' ) ) {
							var value = $( this ).data( 'val' );
							component.$component_options.find( 'select.component_options_select' ).val( value ).change();
						}

					} )

					.on( 'click', 'a.component_option_thumbnail_tap', function( event ) {
						$( this ).closest( '.component_option_thumbnail' ).trigger( 'click' );
						return false;
					} )

					.on( 'focusin touchstart', '.component_options select.component_options_select', function( event ) {

						component.get_step().fire_scenario_actions( true );

					} )

					/**
					 * On changing a component option.
					 */
					.on( 'change', '.component_options select.component_options_select', function( event ) {

						var summary_content     = component.$self.find( '.component_summary > .content' );
						var selected_product_id = $( this ).val();

						// Exit if triggering 'change' for the same selection.
						if ( component.get_selection_id() === selected_product_id ) {
							return false;
						}

						$( this ).blur();

						// Select thumbnail.
						component.$component_options.find( '.component_option_thumbnails .selected' ).removeClass( 'selected disabled' );
						component.$component_options.find( '#component_option_thumbnail_' + $( this ).val() ).addClass( 'selected' );

						var data = {
							action: 		'woocommerce_show_composited_product',
							product_id: 	selected_product_id,
							component_id: 	component.component_id,
							composite_id: 	composite.composite_id,
							security: 		wc_composite_params.show_product_nonce
						};

						// Remove all event listeners.
						summary_content.removeClass( 'variations_form bundle_form cart' );
						summary_content.off().find( '*' ).off();

						component.select_component_option( data );

						// Finito.
						return false;
					} )

					/**
					 * Append component options upon clicking the "Load more" button.
					 */
					.on( 'click', '.component_pagination a.component_options_load_more', function( event ) {

						var item                    = component.$self;
						var item_id                 = component.component_id;
						var component_ordering      = item.find( '.component_ordering select' );

						// Variables to post.
						var page                    = parseInt( $( this ).data( 'pages_loaded' ) ) + 1;
						var pages                   = parseInt( $( this ).data( 'pages' ) );
						var selected_option         = component.get_selected_product_id();
						var container_id            = composite.composite_id;
						var filters                 = component.get_active_filters();

						if ( page > pages ) {
							return false;
						}

						var data = {
							action: 			'woocommerce_show_component_options',
							load_page: 			page,
							component_id: 		item_id,
							composite_id: 		container_id,
							selected_option: 	selected_option,
							filters:            filters,
							security: 			wc_composite_params.show_product_nonce
						};

						// Current 'orderby' setting.
						if ( component_ordering.length > 0 ) {
							data.orderby = component_ordering.val();
						}

						// Update component options.
						if ( data.load_page > 0 ) {
							$( this ).blur();
							component.append_component_options( data );
						}

						// Finito.
						return false;

					} )

					/**
					 * Refresh component options upon clicking on a component options page.
					 */
					.on( 'click', '.component_pagination a.component_pagination_element', function( event ) {

						var item                    = component.$self;
						var item_id                 = component.component_id;
						var component_ordering      = item.find( '.component_ordering select' );

						// Variables to post.
						var page                    = parseInt( $( this ).data( 'page_num' ) );
						var selected_option         = component.get_selected_product_id();
						var container_id            = composite.composite_id;
						var filters                 = component.get_active_filters();

						var data = {
							action: 			'woocommerce_show_component_options',
							load_page: 			page,
							component_id: 		item_id,
							composite_id: 		container_id,
							selected_option: 	selected_option,
							filters:            filters,
							security: 			wc_composite_params.show_product_nonce
						};

						// Current 'orderby' setting.
						if ( component_ordering.length > 0 ) {
							data.orderby = component_ordering.val();
						}

						// Update component options.
						if ( data.load_page > 0 ) {
							$( this ).blur();
							component.reload_component_options( data );
						}

						// Finito.
						return false;

					} )

					/**
					 * Refresh component options upon reordering.
					 */
					.on( 'change', '.component_ordering select', function( event ) {

						var item_id                 = component.component_id;

						// Variables to post
						var selected_option         = component.get_selected_product_id();
						var container_id            = composite.composite_id;
						var orderby                 = $( this ).val();
						var filters                 = component.get_active_filters();

						var data = {
							action: 			'woocommerce_show_component_options',
							load_page: 			1,
							component_id: 		item_id,
							composite_id: 		container_id,
							selected_option: 	selected_option,
							orderby: 			orderby,
							filters:            filters,
							security: 			wc_composite_params.show_product_nonce
						};

						$( this ).blur();

						// Update component options.
						component.reload_component_options( data );

						// Finito.
						return false;

					} )

					/**
					 * Refresh component options upon activating a filter.
					 */
					.on( 'click', '.component_filter_option a', function( event ) {

						var item                    = component.$self;
						var item_id                 = component.component_id;
						var component_ordering      = item.find( '.component_ordering select' );

						var component_filter_option = $( this ).closest( '.component_filter_option' );

						// Variables to post.
						var selected_option         = component.get_selected_product_id() > 0 ? component.get_selected_product_id() : '';
						var container_id            = composite.composite_id;
						var filters                 = {};

						if ( ! component_filter_option.hasClass( 'selected' ) ) {
							component_filter_option.addClass( 'selected' );
						} else {
							component_filter_option.removeClass( 'selected' );
						}

						// add / remove 'active' classes.
						component.update_filters_ui();

						// get active filters.
						filters = component.get_active_filters();

						var data = {
							action: 			'woocommerce_show_component_options',
							load_page: 			1,
							component_id: 		item_id,
							composite_id: 		container_id,
							selected_option: 	selected_option,
							filters: 			filters,
							security: 			wc_composite_params.show_product_nonce
						};

						// Current 'orderby' setting.
						if ( component_ordering.length > 0 ) {
							data.orderby = component_ordering.val();
						}

						$( this ).blur();

						// Update component options.
						component.reload_component_options( data );

						// Finito
						return false;

					} )

					/**
					 * Refresh component options upon resetting all filters.
					 */
					.on( 'click', '.component_filters a.reset_component_filters', function( event ) {

						var item                    = component.$self;
						var item_id                 = component.component_id;
						var component_ordering      = item.find( '.component_ordering select' );

						// Get active filters.
						var component_filter_options = item.find( '.component_filters .component_filter_option.selected' );

						if ( component_filter_options.length == 0 ) {
							return false;
						}

						// Variables to post.
						var selected_option         = component.get_selected_product_id() > 0 ? component.get_selected_product_id() : '';
						var container_id            = composite.composite_id;
						var filters                 = {};

						component_filter_options.removeClass( 'selected' );

						// add / remove 'active' classes.
						component.update_filters_ui();

						var data = {
							action: 			'woocommerce_show_component_options',
							load_page: 			1,
							component_id: 		item_id,
							composite_id: 		container_id,
							selected_option: 	selected_option,
							filters: 			filters,
							security: 			wc_composite_params.show_product_nonce
						};

						// Current 'orderby' setting.
						if ( component_ordering.length > 0 ) {
							data.orderby = component_ordering.val();
						}

						$( this ).blur();

						// Update component options.
						component.reload_component_options( data );

						// Finito
						return false;

					} )

					/**
					 * Refresh component options upon resetting a filter.
					 */
					.on( 'click', '.component_filters a.reset_component_filter', function( event ) {

						var item                     = component.$self;
						var item_id                  = component.component_id;
						var component_ordering       = item.find( '.component_ordering select' );

						// Get active filters.
						var component_filter_options = $( this ).closest( '.component_filter' ).find( '.component_filter_option.selected' );

						if ( component_filter_options.length == 0 ) {
							return false;
						}

						// Variables to post.
						var selected_option         = component.get_selected_product_id() > 0 ? component.get_selected_product_id() : '';
						var container_id            = composite.composite_id;
						var filters                 = {};

						component_filter_options.removeClass( 'selected' );

						// Add / remove 'active' classes.
						component.update_filters_ui();

						// Get active filters
						filters = component.get_active_filters();

						var data = {
							action: 			'woocommerce_show_component_options',
							load_page: 			1,
							component_id: 		item_id,
							composite_id: 		container_id,
							selected_option: 	selected_option,
							filters: 			filters,
							security: 			wc_composite_params.show_product_nonce
						};

						// Current 'orderby' setting.
						if ( component_ordering.length > 0 ) {
							data.orderby = component_ordering.val();
						}

						$( this ).blur();

						// Update component options.
						component.reload_component_options( data );

						// Finito.
						return false;

					} )

					/**
					 * Expand / Collapse filters.
					 */
					.on( 'click', '.component_filter_title label', function( event ) {

						var component_filter         = $( this ).closest( '.component_filter' );
						var component_filter_content = component_filter.find( '.component_filter_content' );

						wc_cp_toggle_element( component_filter, component_filter_content );

						$( this ).blur();

						// Finito
						return false;

					} )

					/**
					 * Expand / Collapse components.
					 */
					.on( 'click', '.component_title', function( event ) {

						var item = component.$self;
						var form = composite.$composite_form;

						if ( ! item.hasClass( 'toggled' ) || $( this ).hasClass( 'inactive' ) ) {
							return false;
						}

						if ( item.hasClass( 'progressive' ) && item.hasClass( 'active' ) ) {
							return false;
						}

						var component_inner = component.$component_inner;

						if ( wc_cp_toggle_element( item, component_inner ) ) {

							if ( item.hasClass( 'progressive' ) ) {

								if ( item.hasClass( 'blocked' ) ) {

									form.find( '.page_button.next' ).click();

								} else if ( item.hasClass( 'disabled' ) ) {

									var step = component.get_step();

									step.block_next_steps();
									step.show_step();
								}
							}
						}

						$( this ).blur();

						// Finito.
						return false;

					} )

					/**
					 * Update composite totals upon changing quantities.
					 */
					.on( 'change', '.component_wrap input.qty', function( event ) {

						var min = parseFloat( $( this ).attr( 'min' ) );
						var max = parseFloat( $( this ).attr( 'max' ) );

						if ( min >= 0 && parseFloat( $( this ).val() ) < min ) {
							$( this ).val( min );
						}

						if ( max > 0 && parseFloat( $( this ).val() ) > max ) {
							$( this ).val( max );
						}

						composite.update_composite();
					} );

			},

			/**
			 * Initialize composite step objects.
			 */

			init_steps: function() {

				var composite = this;

				/*
				 * Prepare markup for "Review" step, if needed.
				 */

				if ( composite.composite_layout === 'paged' ) {

					// Componentized layout: replace the step-based process with a summary-based process.
					if ( composite.composite_layout_variation === 'componentized' ) {

						composite.$composite_form.find( '.multistep.active' ).removeClass( 'active' );
						composite.$composite_data.addClass( 'multistep active' );

						// No review step in the pagination template.
						composite.$composite_pagination.find( '.pagination_element_review' ).remove();

						// No summary widget.
						composite.$composite_summary_widget.hide();

					// If the composite-add-to-cart.php template is added right after the component divs, it will be used as the final step of the step-based configuration process.
					} else if ( composite.$composite_data.prev().hasClass( 'multistep' ) ) {

						composite.$composite_data.addClass( 'multistep' );
						composite.$composite_data.hide();

						// If the composite was just added to the cart, make the review/summary step active.
						if ( composite.$composite_data.hasClass( 'composite_added_to_cart' ) ) {
							composite.$composite_form.find( '.multistep.active' ).removeClass( 'active' );
							composite.$composite_data.addClass( 'active' );
						}

					} else {
						composite.$composite_data.show();
						composite.$composite_data.find( '.component_title' ).hide();
						composite.$composite_pagination.find( '.pagination_element_review' ).remove();
					}

				} else if ( composite.composite_layout === 'progressive' ) {

					composite.$components.show();
					composite.$composite_data.show();

					// If the composite was just added to the cart, make the last step active.
					if ( composite.$composite_data.hasClass( 'composite_added_to_cart' ) ) {
						composite.$components.removeClass( 'blocked open' ).addClass( 'closed disabled' );
						composite.$components.filter( '.toggled' ).find( '.component_inner' ).hide();
						composite.$components.filter( '.toggled' ).find( '.component_title' ).removeClass( 'inactive' ).addClass( 'active' );
						composite.$components.filter( '.multistep.active' ).removeClass( 'active' );
						composite.$components.filter( '.multistep.last' ).addClass( 'active' );
					}
				} else if ( composite.composite_layout === 'single' ) {

					composite.$components.show();
					composite.$composite_data.show();
				}

				/*
				 * Initialize steps.
				 */

				composite.$steps = composite.$composite_form.find( '.multistep' );

				composite.$composite_form.children( '.component, .multistep' ).each( function( index ) {

					composite.composite_steps[ index ] = new WC_CP_Step( composite, $( this ), index );

				} );

				composite.$composite_navigation.removeAttr( 'style' );

			},

			/**
			 * Shows a step when its id is known.
			 */

			show_step: function( step_id ) {

				var composite = this;

				$.each( composite.composite_steps, function( step_index, step ) {

					if ( step.step_id == step_id ) {
						step.show_step();
						return false;
					}

				} );

			},

			/**
			 * Shows the step marked as previous from the current one.
			 */

			show_previous_step: function() {

				var composite = this;

				$.each( composite.composite_steps, function( step_index, step ) {

					if ( step.is_previous() ) {
						step.show_step();
						return false;
					}

				} );

			},

			/**
			 * Shows the step marked as next from the current one.
			 */

			show_next_step: function() {

				var composite = this;

				$.each( composite.composite_steps, function( step_index, step ) {

					if ( step.is_next() ) {
						step.show_step();
						return false;
					}

				} );

			},

			/**
			 * Returns a step object by id.
			 */

			get_step: function( step_id ) {

				var composite = this;
				var found     = false;

				$.each( composite.composite_steps, function( step_index, step ) {

					if ( step.step_id == step_id ) {
						found = step;
						return false;
					}

				} );

				return found;

			},

			/**
			 * Returns the current step object.
			 */

			get_current_step: function() {

				var composite = this;
				var current   = false;

				$.each( composite.composite_steps, function( step_index, step ) {

					if ( step.is_current() ) {
						current = step;
						return false;
					}

				} );

				return current;

			},

			/**
			 * Returns the previous step object.
			 */

			get_previous_step: function() {

				var composite  = this;
				var previous   = false;

				$.each( composite.composite_steps, function( step_index, step ) {

					if ( step.is_previous() ) {
						previous = step;
						return false;
					}

				} );

				return previous;

			},

			/**
			 * Returns the next step object.
			 */

			get_next_step: function() {

				var composite = this;
				var next      = false;

				$.each( composite.composite_steps, function( step_index, step ) {

					if ( step.is_next() ) {
						next = step;
						return false;
					}

				} );

				return next;

			},

			/**
			 * Return stored scenario data.
			 */

			get_scenario_data: function() {

				var composite = this;
				var scenarios = composite.$composite_data.data( 'scenario_data' );

				return scenarios;

			},

			/**
			 * Extract active scenarios from current selections.
			 */

			update_active_scenarios: function( firing_step_id, return_result ) {

				var composite         = this;
				var style             = composite.composite_layout;

				var fired_by_step;

				if ( style !== 'single' ) {
					fired_by_step  = composite.get_current_step();
					firing_step_id = fired_by_step.step_id;
				} else {
					fired_by_step = composite.get_step( firing_step_id );
				}

				var firing_step_index = fired_by_step.step_index;
				var tabs              = '';

				for ( var i = composite.actions_nesting - 1; i > 0; i-- ) {
					tabs = tabs + '	';
				}

				if ( typeof( return_result ) === 'undefined' ) {
					return_result = false;
				}

				if ( fired_by_step.is_review() ) {
					firing_step_index = 1000;
				}

				// Initialize by getting all scenarios.
				var scenarios = composite.get_scenario_data().scenarios;

				var compat_group_scenarios = composite.get_scenarios_by_type( scenarios, 'compat_group' );

				if ( compat_group_scenarios.length === 0 ) {
					scenarios.push( '0' );
				}

				// Active scenarios including current component.
				var active_scenarios_incl_current            = scenarios;

				// Active scenarios excluding current component.
				var active_scenarios_excl_current            = scenarios;

				var scenario_shaping_components_incl_current = [];
				var scenario_shaping_components_excl_current = [];

				if ( wc_composite_params.script_debug === 'yes' ) {

					if ( return_result ) {
						tabs = tabs + '	';
						wc_cp_log( '\n' + tabs + 'Scenarios requested by ' + fired_by_step.get_title() + ' at ' + new Date().getTime().toString() + '...' );
					} else {
						wc_cp_log( '\n' + tabs + 'Selections update triggered by ' + fired_by_step.get_title() + ' at ' + new Date().getTime().toString() + '...' );
					}

					wc_cp_log( '\n' + tabs + 'Calculating active scenarios...' );
				}

				// Incl current.
				$.each( composite.composite_components, function( index, component ) {

					var component_id = component.component_id;

					if ( style === 'progressive' || style === 'paged' ) {

						if ( index > firing_step_index ) {
							return false;
						}

						active_scenarios_excl_current            = active_scenarios_incl_current.slice();
						scenario_shaping_components_excl_current = scenario_shaping_components_incl_current.slice();

					}

					var product_id   = component.get_selected_product_id();
					var product_type = component.get_selected_product_type();

					if ( product_id !== null && product_id >= 0 ) {

						var scenario_data      = composite.get_scenario_data().scenario_data;
						var item_scenario_data = scenario_data[ component_id ];

						// Treat '' optional component selections as 'None' if the component is optional.
						if ( product_id === '' ) {
							if ( 0 in item_scenario_data ) {
								product_id = '0';
							} else {
								return true;
							}
						}

						var product_in_scenarios = item_scenario_data[ product_id ];

						if ( wc_composite_params.script_debug === 'yes' ) {
							wc_cp_log( tabs + 'Selection #' + product_id + ' of ' + component.get_title() + ' in scenarios: ' + product_in_scenarios.toString() );
						}

						var product_intersection    = wc_cp_intersect_safe( active_scenarios_incl_current, product_in_scenarios );
						var product_is_compatible   = product_intersection.length > 0;

						var variation_is_compatible = true;

						if ( product_is_compatible ) {

							if ( product_type === 'variable' ) {

								var variation_id = component.$self.find( '.single_variation_wrap .variations_button input.variation_id' ).val();

								if ( variation_id > 0 ) {

									var variation_in_scenarios = item_scenario_data[ variation_id ];

									if ( wc_composite_params.script_debug === 'yes' ) {
										wc_cp_log( tabs + 'Variation selection #' + variation_id + ' of ' + component.get_title() + ' in scenarios: ' + variation_in_scenarios.toString() );
									}

									product_intersection    = wc_cp_intersect_safe( product_intersection, variation_in_scenarios );
									variation_is_compatible = product_intersection.length > 0;
								}
							}
						}

						var is_compatible = product_is_compatible && variation_is_compatible;

						if ( is_compatible ) {

							scenario_shaping_components_incl_current.push( component_id );
							active_scenarios_incl_current = product_intersection;

							if ( wc_composite_params.script_debug === 'yes' ) {
								wc_cp_log( tabs + '	Active scenarios: ' + active_scenarios_incl_current.toString() );
							}

						} else {

							// The chosen product was found incompatible.
							if ( ! product_is_compatible ) {

								if ( wc_composite_params.script_debug === 'yes' ) {
									wc_cp_log( tabs + '	Selection not found in any scenario - breaking out...' );
								}

								if ( product_id !== '0' && ( style === 'single' || firing_step_id == component_id ) ) {
									component.$self.addClass( 'reset' );
								}

							} else {

								if ( wc_composite_params.script_debug === 'yes' ) {
									wc_cp_log( tabs + '	Variation selection not found in any scenario - breaking out and resetting...' );
								}

								if ( style === 'single' || firing_step_id == component_id ) {
									component.$self.addClass( 'reset_variation' );
								}
							}
						}
					}

				} );

				if ( style === 'single' ) {

					// Excl current.
					$.each( composite.composite_components, function( index, component ) {

						var component_id = component.component_id;

						if ( index == firing_step_index || component.$self.hasClass( 'reset' ) || component.$self.hasClass( 'reset_variation' ) ) {
							return true;
						}

						var product_id   = component.get_selected_product_id();
						var product_type = component.get_selected_product_type();

						if ( product_id !== null && product_id >= 0 ) {

							var scenario_data      = composite.get_scenario_data().scenario_data;
							var item_scenario_data = scenario_data[ component_id ];

							// Treat '' optional component selections as 'None' if the component is optional.
							if ( product_id === '' ) {
								if ( 0 in item_scenario_data ) {
									product_id = '0';
								} else {
									return true;
								}
							}

							var product_in_scenarios    = item_scenario_data[ product_id ];
							var product_intersection    = wc_cp_intersect_safe( active_scenarios_excl_current, product_in_scenarios );
							var product_is_compatible   = product_intersection.length > 0;

							var variation_is_compatible = true;

							if ( product_is_compatible ) {

								if ( product_type === 'variable' ) {

									var variation_id = component.$self.find( '.single_variation_wrap .variations_button input.variation_id' ).val();

									if ( variation_id > 0 ) {

										var variation_in_scenarios = item_scenario_data[ variation_id ];

										product_intersection    = wc_cp_intersect_safe( product_intersection, variation_in_scenarios );
										variation_is_compatible = product_intersection.length > 0;
									}
								}
							}

							var is_compatible = product_is_compatible && variation_is_compatible;

							if ( is_compatible ) {
								scenario_shaping_components_excl_current.push( component_id );
								active_scenarios_excl_current = product_intersection;
							}
						}

					} );
				}


				if ( wc_composite_params.script_debug === 'yes' ) {
					wc_cp_log( tabs + 'Removing active scenarios where all scenario shaping components are masked...' );
				}

				if ( wc_composite_params.script_debug === 'yes' ) {
					wc_cp_log( tabs + '	Scenario shaping components: ' + scenario_shaping_components_incl_current.toString() );
				}

				if ( return_result ) {

					var result = {
						incl_current: composite.get_binding_scenarios( active_scenarios_incl_current, scenario_shaping_components_incl_current ),
						excl_current: composite.get_binding_scenarios( active_scenarios_excl_current, scenario_shaping_components_excl_current )
					};

					if ( wc_composite_params.script_debug === 'yes' ) {
						wc_cp_log( '\n' + tabs + 'Final active scenarios incl.: ' + result.incl_current.toString() + '\n' + tabs + 'Final active scenarios excl.: ' + result.excl_current.toString() );
					}

					return result;

				} else {

					composite.active_scenarios = {
						incl_current: composite.get_binding_scenarios( active_scenarios_incl_current, scenario_shaping_components_incl_current ),
						excl_current: composite.get_binding_scenarios( active_scenarios_excl_current, scenario_shaping_components_excl_current )
					};

					composite.$composite_data.data( 'active_scenarios', composite.active_scenarios.incl_current );

					if ( wc_composite_params.script_debug === 'yes' ) {
						wc_cp_log( '\n' + tabs + 'Final active scenarios incl.: ' + composite.active_scenarios.incl_current.toString() + '\n' + tabs + 'Final active scenarios excl.: ' + composite.active_scenarios.excl_current.toString() );
					}
				}

			},

			/**
			 * Filters out unbinding scenarios.
			 */

			get_binding_scenarios: function( scenarios, scenario_shaping_components ) {

				var composite = this;

				var masked    = composite.get_scenario_data().scenario_settings.masked_components;
				var clean     = [];

				if ( scenario_shaping_components.length > 0 ) {

					if ( scenarios.length > 0 ) {
						$.each( scenarios, function( i, scenario_id ) {

							// If all scenario shaping components are masked, filter out the scenario.
							var all_components_masked_in_scenario = true;

							$.each( scenario_shaping_components, function( i, component_id ) {

								if ( $.inArray( component_id.toString(), masked[ scenario_id ] ) == -1 ) {
									all_components_masked_in_scenario = false;
									return false;
								}
							} );

							if ( ! all_components_masked_in_scenario ) {
								clean.push( scenario_id );
							}
						} );
					}

				} else {
					clean = scenarios;
				}

				if ( clean.length === 0 && scenarios.length > 0 ) {
					clean = scenarios;
				}

				return clean;

			},

			/**
			 * Filters active scenarios by type.
			 */

			get_active_scenarios_by_type: function( type ) {

				var composite = this;

				var incl = composite.get_scenarios_by_type( composite.active_scenarios.incl_current, type );
				var excl = composite.get_scenarios_by_type( composite.active_scenarios.excl_current, type );

				return {
					incl_current: incl,
					excl_current: excl
				};

			},

			/**
			 * Filters scenarios by type.
			 */

			get_scenarios_by_type: function( scenarios, type ) {

				var composite   = this;

				var filtered    = [];
				var scenario_id = '';

				if ( scenarios.length > 0 ) {
					for ( var i in scenarios ) {

						scenario_id = scenarios[ i ];

						if ( $.inArray( type, composite.get_scenario_data().scenario_settings.scenario_actions[ scenario_id ] ) > -1 ) {
							filtered.push( scenario_id );
						}
					}
				}

				return filtered;

			},

			/**
			 * Filters out masked scenarios in 'update_selections'.
			 */

			get_binding_scenarios_for: function( scenarios, component_id ) {

				var composite = this;

				var masked    = composite.get_scenario_data().scenario_settings.masked_components;
				var clean     = [];

				if ( scenarios.length > 0 ) {
					$.each( scenarios, function( i, scenario_id ) {

						if ( $.inArray( component_id.toString(), masked[ scenario_id ] ) == -1 ) {
							clean.push( scenario_id );
						}

					} );
				}

				return clean;

			},

			/**
			 * Activates or deactivates products and variations based on scenarios.
			 */

			update_selections: function( firing_step_id, excl_firing ) {

				var composite        = this;

				var style            = composite.composite_layout;
				var selection_mode   = composite.composite_selection_mode;

				var fired_by_step;

				if ( style !== 'single' ) {
					fired_by_step  = composite.get_current_step();
					firing_step_id = fired_by_step.step_id;
				} else {
					fired_by_step = composite.get_step( firing_step_id );
				}

				var active_scenarios = [];
				var tabs             = '';

				for ( var i = composite.actions_nesting-1; i > 0; i-- ) {
					tabs = tabs + '	';
				}

				if ( typeof( excl_firing ) === 'undefined' ) {
					excl_firing = false;
				}

				if ( style === 'progressive' || style === 'paged' ) {
					excl_firing = true;
				}

				if ( wc_composite_params.script_debug === 'yes' ) {
					wc_cp_log( '\n' + tabs + 'Updating selections...' );
				}

				/**
				 * 	1. Clear resets.
				 */

				if ( wc_composite_params.script_debug === 'yes' ) {
					wc_cp_log( '\n' + tabs + 'Clearing incompatible selections...' );
				}

				$.each( composite.composite_components, function( index, component ) {

					var component_id             = component.component_id;
					var component_options_select = component.$component_options.find( 'select.component_options_select' );

					var product_id               = component.get_selected_product_id();

					if ( style !== 'single' && firing_step_id != component_id ) {
						return true;
					}

					// If a disabled option is still selected, val will be null - use this fact to reset options before moving on.
					if ( product_id === null ) {
						component.$self.addClass( 'reset' );

						if ( wc_composite_params.script_debug === 'yes' ) {
							wc_cp_log( tabs + component.get_title() + ' selection was found disabled.' );
						}
					}

					// Verify and reset active product selections that were found incompatible.
					if ( component.$self.hasClass( 'reset' ) ) {

						if ( wc_composite_params.script_debug === 'yes' ) {
							wc_cp_log( tabs + 'Resetting ' + component.get_title() + '...\n' );
						}

						component.$self.addClass( 'resetting' );

						composite.has_scenarios_update_lock = true;
						composite.has_ui_update_lock        = true;
						component_options_select.val( '' ).change();
						composite.has_scenarios_update_lock = false;
						composite.has_ui_update_lock        = false;

						component.$self.removeClass( 'reset' );
						component.$self.removeClass( 'resetting' );

						if ( wc_composite_params.script_debug === 'yes' ) {
							wc_cp_log( tabs + 'Reset ' + component.get_title() + ' complete...\n' );
						}

					}

					// Verify and reset active variation selections that were found incompatible.
					if ( component.$self.hasClass( 'reset_variation' ) ) {

						if ( wc_composite_params.script_debug === 'yes' ) {
							wc_cp_log( tabs + 'Resetting variation selections of ' + component.get_title() + '...\n' );
						}

						component.$self.addClass( 'resetting' );

						composite.has_scenarios_update_lock = true;
						composite.has_ui_update_lock        = true;
						component.$component_summary.find( '.reset_variations' ).trigger( 'click' );
						composite.has_scenarios_update_lock = false;
						composite.has_ui_update_lock        = false;

						component.$self.removeClass( 'reset_variation' );
						component.$self.removeClass( 'resetting' );

						setTimeout( function() {
							component.$self.find( '.reset_variations' ).css( 'visibility', 'hidden' );
						}, 10 );

						if ( wc_composite_params.script_debug === 'yes' ) {
							wc_cp_log( tabs + 'Reset variation selections of ' + component.get_title() + ' complete...\n' );
						}

					}

				} );

				/**
				 * 	2. Disable or enable product and variation selections.
				 */

				var firing_step_index = fired_by_step.step_index;

				if ( fired_by_step.is_review() ) {
					firing_step_index = 1000;
				}

				// Get active scenarios filtered by action = 'compat_group'.
				var scenarios = composite.get_active_scenarios_by_type( 'compat_group' );

				// Do the work.
				$.each( composite.composite_components, function( index, component ) {

					var component_id    = component.component_id;
					var summary_content = component.$self.find( '.component_summary > .content' );

					if ( wc_composite_params.script_debug === 'yes' ) {
						wc_cp_log( tabs + 'Updating selections of ' + component.get_title() + '...' );
					}

					if ( style === 'single' && selection_mode === 'thumbnails' ) {

						// The constraints of the working item must not be taken into account when refreshing thumbnails using the 'single' layout, in order to be able to switch the selection.
						var component_scenarios = composite.update_active_scenarios( component_id, true );
						active_scenarios        = composite.get_scenarios_by_type( component_scenarios.excl_current, 'compat_group' );

					} else {

						// The constraints of the firing item must not be taken into account when the update action is triggered by a dropdown, in order to be able to switch the selection.
						if ( excl_firing && firing_step_index == index ) {
							active_scenarios = scenarios.excl_current;
						} else {
							active_scenarios = scenarios.incl_current;
						}
					}

					if ( wc_composite_params.script_debug === 'yes' ) {
						wc_cp_log( tabs + '	Reference scenarios: ' + active_scenarios.toString() );
						wc_cp_log( tabs + '	Removing any scenarios where the current component is masked...' );
					}

					active_scenarios = composite.get_binding_scenarios_for( active_scenarios, component_id );

					// Enable all if all active scenarios ignore this component.
					if ( active_scenarios.length === 0 ) {
						active_scenarios.push( '0' );
					}

					if ( wc_composite_params.script_debug === 'yes' ) {
						wc_cp_log( tabs + '	Active scenarios: ' + active_scenarios.slice().toString() );
					}

					var scenario_data      = composite.get_scenario_data().scenario_data;
					var item_scenario_data = scenario_data[ component_id ];

					// Set optional status.

					var is_optional = false;

					if ( 0 in item_scenario_data ) {

						var optional_in_scenarios = item_scenario_data[ 0 ];

						for ( var s in optional_in_scenarios ) {

							var optional_in_scenario_id = optional_in_scenarios[ s ];

							if ( $.inArray( optional_in_scenario_id, active_scenarios ) > -1 ) {
								is_optional = true;
								break;
							}
						}

					} else {
						is_optional = false;
					}

					if ( is_optional ) {
						if ( wc_composite_params.script_debug === 'yes' ) {
							wc_cp_log( tabs + '	Component set as optional.' );
						}
						component.set_optional( true );
					} else {
						component.set_optional( false );
					}

					/*
					 * Disable incompatible products.
					 */

					var thumbnails;
					var thumbnail_loop     = 0;
					var thumbnail_columns  = 1;

					if ( composite.composite_selection_mode === 'thumbnails' ) {
						thumbnails        = component.$component_options.find( '.component_option_thumbnails' );
						thumbnail_columns = parseInt( thumbnails.data( 'columns' ) );
					}

					var component_options_select       = component.$component_options.find( 'select.component_options_select' );
					var component_options_select_value = component.get_selection_id();

					if ( ! component_options_select_value ) {
						component_options_select_value = component_options_select.val();
					}

					// Reset options.
					if ( ! component_options_select.data( 'select_options' ) ) {
						component_options_select.data( 'select_options', component_options_select.find( 'option:gt(0)' ).get() );
					}

					component_options_select.find( 'option:gt(0)' ).remove();
					component_options_select.append( component_options_select.data( 'select_options' ) );
					component_options_select.find( 'option:gt(0)' ).removeClass( 'disabled' );
					component_options_select.find( 'option:gt(0)' ).removeAttr( 'disabled' );
					component.set_selection_invalid( false );

					if ( composite.composite_selection_mode === 'thumbnails' ) {
						thumbnails.find( '.no_compat_results' ).remove();
					}

					// Enable or disable options.
					component_options_select.find( 'option:gt(0)' ).each( function() {

						var product_id           = $( this ).val();
						var product_in_scenarios = item_scenario_data[ product_id ];
						var is_compatible        = false;

						var thumbnail;
						var thumbnail_container;

						if ( wc_composite_params.script_debug === 'yes' ) {
							wc_cp_log( tabs + '	Updating selection #' + product_id + ':' );
							wc_cp_log( tabs + '		Selection in scenarios: ' + product_in_scenarios.toString() );
						}

						for ( var i in product_in_scenarios ) {

							var scenario_id = product_in_scenarios[ i ];

							if ( $.inArray( scenario_id, active_scenarios ) > -1 ) {
								is_compatible = true;
								break;
							}
						}

						if ( composite.composite_selection_mode === 'thumbnails' ) {
							thumbnail           = component.$component_options.find( '#component_option_thumbnail_' + $( this ).val() );
							thumbnail_container = thumbnail.closest( '.component_option_thumbnail_container' );
						}

						if ( ! is_compatible ) {

							if ( wc_composite_params.script_debug === 'yes' ) {
								wc_cp_log( tabs + '		Selection disabled.' );
							}

							if ( component_options_select_value != product_id ) {
								$( this ).addClass( 'disabled' );
							} else {
								component.set_selection_invalid( true );
								$( this ).prop( 'disabled', 'disabled' );
							}

							if ( composite.composite_selection_mode === 'thumbnails' ) {
								thumbnail.addClass( 'disabled' );
							}

						} else {

							if ( wc_composite_params.script_debug === 'yes' ) {
								wc_cp_log( tabs + '		Selection enabled.' );
							}

							if ( composite.composite_selection_mode === 'thumbnails' ) {
								thumbnail.removeClass( 'disabled' );
							}
						}

						// Update first/last/hidden class state when appending results.
						if ( composite.composite_selection_mode === 'thumbnails' ) {

							if ( component.append_results() ) {

								if ( style === 'single' || index >= fired_by_step.step_index ) {

									var thumbnail_container_class = '';

									if ( component.hide_disabled_products() && ! is_compatible ) {

										thumbnail_container_class = 'hidden';

									} else {

										thumbnail_loop++;

										// Add first/last class to compatible li elements.
										if ( ( ( thumbnail_loop - 1 ) % thumbnail_columns ) == 0 || thumbnail_columns == 1 ) {
											thumbnail_container_class = 'first';
										}

										if ( thumbnail_loop % thumbnail_columns == 0 ) {
											thumbnail_container_class += ' last';
										}
									}

									thumbnail_container.removeClass( 'first last hidden' );

									if ( thumbnail_container_class ) {
										thumbnail_container.addClass( thumbnail_container_class );
									}
								}
							}
						}

					} );

					// Hide or grey-out disabled options.
					if ( component.hide_disabled_products() ) {
						component_options_select.find( 'option.disabled' ).remove();

						if ( composite.composite_selection_mode === 'thumbnails' ) {

							var thumbnail_elements         = thumbnails.find( '.component_option_thumbnail_container' );
							var visible_thumbnail_elements = thumbnail_elements.not( '.hidden' );

							if ( thumbnail_elements.length > 0 && visible_thumbnail_elements.length == 0 ) {
								thumbnails.find( '.component_option_thumbnails_container' ).after( '<p class="no_compat_results">' + wc_composite_params.i18n_no_compat_options + '</p>' );
								component.has_compat_results   = false;
								component.compat_results_count = 0;
							} else {
								component.has_compat_results   = true;
								component.compat_results_count = visible_thumbnail_elements.length;
							}
						}

					} else {
						component_options_select.find( 'option.disabled' ).prop( 'disabled', 'disabled' );
					}

					/*
					 * Disable incompatible variations.
					 */

					var product_type = component.get_selected_product_type();

					if ( product_type === 'variable' ) {

						// Note the variation id.
						var variation_input    = summary_content.find( '.single_variation_wrap .variations_button input.variation_id' );
						var variation_input_id = variation_input.val();
						var variation_valid    = variation_input_id > 0 ? false : true;

						if ( wc_composite_params.script_debug === 'yes' ) {
							wc_cp_log( tabs + '		Checking variations...' );
						}

						if ( variation_input_id > 0 ) {
							if ( wc_composite_params.script_debug === 'yes' ) {
								wc_cp_log( tabs + '			--- Stored variation is #' + variation_input_id );
							}
						}

						// Get all variations.
						var product_variations = component.$component_data.data( 'product_variations' );

						var product_variations_in_scenario = [];

						for ( var i in product_variations ) {

							var variation_id           = product_variations[ i ].variation_id;
							var variation_in_scenarios = item_scenario_data[ variation_id ];
							var is_compatible          = false;

							if ( wc_composite_params.script_debug === 'yes' ) {
								wc_cp_log( tabs + '			Checking variation #' + variation_id + ':' );
								wc_cp_log( tabs + '			Selection in scenarios: ' + variation_in_scenarios.toString() );
							}

							for ( var k in variation_in_scenarios ) {

								var scenario_id = variation_in_scenarios[ k ];

								if ( $.inArray( scenario_id, active_scenarios ) > -1 ) {
									is_compatible = true;
									break;
								}
							}

							// Copy all variation objects but set the variation_is_active property to false in order to disable the attributes of incompatible variations.
							// Only if WC v2.3 and disabled variations are set to be visible.
							if ( wc_composite_params.is_wc_version_gte_2_3 === 'yes' && ! component.hide_disabled_variations() ) {

								var variation = $.extend( true, {}, product_variations[ i ] );

								var variation_has_empty_attributes = false;

								if ( ! is_compatible ) {

									variation.variation_is_active = false;

									// Do not include incompatible variations with empty attributes - they can break stuff when prioritized.
									for ( var attr_name in variation.attributes ) {
										if ( variation.attributes[ attr_name ] === '' ) {
											variation_has_empty_attributes = true;
											break;
										}
									}

									if ( wc_composite_params.script_debug === 'yes' ) {
										wc_cp_log( tabs + '			Variation disabled.' );
									}
								} else {

									if ( wc_composite_params.script_debug === 'yes' ) {
										wc_cp_log( tabs + '			Variation enabled.' );
									}

									if ( parseInt( variation_id ) === parseInt( variation_input_id ) ) {
										variation_valid = true;
										if ( wc_composite_params.script_debug === 'yes' ) {
											wc_cp_log( tabs + '			--- Stored variation is valid.' );
										}
									}
								}

								if ( ! variation_has_empty_attributes ) {

									product_variations_in_scenario.push( variation );
								}

							// Copy only compatible variations.
							// Only if WC v2.1/2.2 or disabled variations are set to be hidden.
							} else {

								if ( is_compatible ) {

									product_variations_in_scenario.push( product_variations[ i ] );

									if ( wc_composite_params.script_debug === 'yes' ) {
										wc_cp_log( tabs + '			Variation enabled.' );
									}

									if ( parseInt( variation_id ) === parseInt( variation_input_id ) ) {
										variation_valid = true;
										if ( wc_composite_params.script_debug === 'yes' ) {
											wc_cp_log( tabs + '			--- Stored variation is valid.' );
										}
									}

								} else {
									if ( wc_composite_params.script_debug === 'yes' ) {
										wc_cp_log( tabs + '			Variation disabled.' );
									}
								}
							}
						}

						// Put filtered variations in place.
						summary_content.data( 'product_variations', product_variations_in_scenario );

						if ( ! variation_valid ) {

							if ( wc_composite_params.script_debug === 'yes' ) {
								wc_cp_log( tabs + '			--- Stored variation was NOT found.' );
							}

							composite.has_scenarios_update_lock = true;
							composite.has_ui_update_lock        = true;
							summary_content.find( '.reset_variations' ).trigger( 'click' );
							composite.has_scenarios_update_lock = false;
							composite.has_ui_update_lock        = false;
						}

						summary_content.triggerHandler( 'reload_product_variations' );
					}

					component.$self.trigger( 'wc-composite-component-selections-updated', [ component, composite ] );

				} );

				if ( wc_composite_params.script_debug === 'yes' ) {
					wc_cp_log( tabs + 'Finished updating component selections.\n\n' );
				}

			},

			/**
			 * Uses a dumb scheduler to update all pagination/navigation ui elements.
			 */

			update_ui: function( delay_nav_update ) {

				var composite    = this;
				var current_step = composite.get_current_step();

				if ( typeof( delay_nav_update ) === 'undefined' ) {
					delay_nav_update = false;
				}

				// Update nav previous/next button immediately before init.
				if ( composite.composite_initialized === false ) {
					delay_nav_update = false;
				}

				// Dumb task scheduler.
				if ( composite.has_ui_update_lock === true ) {
					return false;
				}

				if ( delay_nav_update ) {
					composite.has_update_nav_delay = true;
				}

				composite.has_ui_update_lock = true;

				setTimeout( function() {

					composite.update_ui_task( composite.has_update_nav_delay );
					composite.has_ui_update_lock   = false;
					composite.has_update_nav_delay = false;

					current_step.get_element().trigger( 'wc-composite-ui-updated', [ composite ] );

				}, 10 );

			},

			/**
			 * Updates all pagination/navigation ui elements.
			 */

			update_ui_task: function() {

				var composite           = this;

				var current_step        = composite.get_current_step();
				var current_component   = current_step.get_component();
				var step_markup         = current_step.get_element();


				/*
				 * Validation.
				 */

				composite._validation_messages = [];

				$.each( composite.composite_steps, function( step_index, step ) {
					step.validate();
				} );


				/*
				 * Update navigation (next/previous buttons) and show/hide component message.
				 */

				var form                = composite.$composite_form;
				var navigation          = composite.$composite_navigation;
				var style               = composite.composite_layout;
				var style_variation     = composite.composite_layout_variation;
				var show_next           = false;

				var next_step           = composite.get_next_step();
				var prev_step           = composite.get_previous_step();

				var button_next         = navigation.find( '.next' );
				var button_prev         = navigation.find( '.prev' );

				var $movable_navi_cl    = false;

				delay_nav_update        = composite.has_update_nav_delay && style !== 'progressive';


				var update_nav          = function() {

					if ( wc_composite_params.transition_type === 'slide' ) {
						$movable_navi_cl = composite.$composite_navigation_movable.clone().addClass( 'cloned' );
					}

					// Hide navigation.
					button_next.addClass( 'invisible inactive' );
					button_prev.addClass( 'invisible' );

					if ( current_component ) {

						// Initialize notices container as inactive = nothing to display.
						current_component.$component_message.addClass( 'inactive' );

						// Selectively show next/previous navigation buttons.
						if ( next_step && style_variation !== 'componentized' ) {

							button_next.html( wc_composite_params.i18n_next_step.replace( '%s', next_step.get_title() ) );
							button_next.removeClass( 'invisible' );

							if ( next_step.get_element().hasClass( 'toggled' ) ) {
								next_step.get_element().find( '.component_title' ).removeClass( 'inactive' );
							}

						} else if ( style === 'paged' ) {
							button_next.html( wc_composite_params.i18n_final_step );
							button_next.removeClass( 'invisible' );
						}
					}

					// Paged previous / next.
					if ( current_step.passes_validation() || ( style_variation === 'componentized' && current_component ) ) {

						if ( next_step ) {
							button_next.removeClass( 'inactive' );
						}

						if ( prev_step && style === 'paged' && prev_step.is_component() ) {
							button_prev.html( wc_composite_params.i18n_previous_step.replace( '%s', prev_step.get_title() ) );
							button_prev.removeClass( 'invisible' );
						} else {
							button_prev.html( '' );
						}

						show_next = true;

					} else {

						if ( next_step && next_step.get_element().hasClass( 'toggled' ) ) {
							next_step.get_element().find( '.component_title' ).addClass( 'inactive' );
						}

						if ( prev_step && prev_step.is_component() ) {

							var product_id = prev_step.get_component().get_selected_product_id();

							if ( product_id > 0 || product_id === '0' || product_id === '' && prev_step.get_component().is_optional() ) {

								if ( style === 'paged' ) {
									button_prev.html( wc_composite_params.i18n_previous_step.replace( '%s', prev_step.get_title() ) );
									button_prev.removeClass( 'invisible' );
								}
							}
						}

						if ( current_component ) {

							// Don't show the prompt if it's the last component of the progressive layout.
							if ( ! step_markup.hasClass( 'last' ) || ! step_markup.hasClass( 'progressive' ) ) {

								var messages           = $( '<ul/>' );
								var validation_messages = current_step.get_validation_messages();

								if ( validation_messages.length > 0 ) {
									$.each( validation_messages, function( i, message ) {
										messages.append( $( '<li/>' ).html( message ) );
									} );
								}

								current_component.$component_message_content.html( messages.html() );

								// We actually have something to display here.
								current_component.$component_message.removeClass( 'inactive' );
							}
						}
					}

					/*
					 * Move navigation and component message container into the next component when using the progressive layout without toggles.
					 */

					if ( style === 'progressive' ) {

						var navi              = form.find( '.composite_navigation.progressive' );
						var navi_in_component = step_markup.find( '.composite_navigation.progressive' ).length > 0;
						var next_step_markup  = form.find( '.component.next' );

						if ( ! navi_in_component ) {

							navi.css( { visibility: 'hidden' } );

							navi.slideUp( 200 );

							setTimeout( function() {

								navi.appendTo( step_markup.find( '.component_inner' ) ).css( { visibility: 'visible' } );

								setTimeout( function() {

									if ( current_component && ! current_component.$component_message.hasClass( 'inactive' ) ) {
										current_component.$component_message.slideDown( 200 );
									}

									var show_navi = false;

									if ( ! step_markup.hasClass( 'last' ) ) {
										if ( show_next && ! next_step_markup.hasClass( 'toggled' ) ) {
											show_navi = true;
										}
									}

									if ( show_navi ) {
										navi.slideDown( { duration: 200, queue: false } );
									}

								}, 200 );

							}, 200 );

						} else {

							if ( current_component ) {
								if ( current_component.$component_message.hasClass( 'inactive' ) ) {
									current_component.$component_message.slideUp( 200 );
								} else {
									current_component.$component_message.slideDown( 200 );
								}
							}

							var show_navi = false;

							if ( ! step_markup.hasClass( 'last' ) ) {
								if ( show_next && ! next_step_markup.hasClass( 'toggled' ) ) {
									show_navi = true;
								}
							}

							if ( show_navi ) {
								navi.slideDown( 200 );
							} else {
								navi.slideUp( 200 );
							}
						}

					/*
					 * Move navigation and component message container when using a paged layout with thumbnails.
					 */

					} else if ( style === 'paged' && composite.composite_selection_mode === 'thumbnails' && step_markup.hasClass( 'options-style-thumbnails' ) ) {

						var component_message_delay_thumbnails = 0;

						// Component message handling.

						if ( current_component ) {

							// Add a delay when loading a new component option with notices, in order to display the message after the animation has finished.
							if ( current_component.$component_selections.hasClass( 'blocked_content' ) && ! current_component.$component_message.hasClass( 'inactive' ) ) {
								component_message_delay_thumbnails = 600;
							}

							// Hide the message container when moving into a relocating summary and add a delay.
							if ( current_component.$component_content.hasClass( 'relocating' ) ) {
								current_component.$component_message.hide();
								component_message_delay_thumbnails = 600;
							}

							if ( delay_nav_update ) {
								component_message_delay_thumbnails = wc_composite_params.transition_type === 'slide' ? composite.current_transition_delay + 100 : 200;
							}

							setTimeout( function() {
								if ( current_component.$component_message.hasClass( 'inactive' ) ) {
									current_component.$component_message.slideUp( 200 );
								} else {
									current_component.$component_message.slideDown( 200 );
								}
							}, component_message_delay_thumbnails );
						}

						// Navi handling.

						if ( current_component ) {

							if ( current_component.get_selection_id() > 0 ) {

								// Measure distance from bottom navi and only append navi in content if far enough.
								var navi_in_content    = current_component.$component_content.find( '.composite_navigation' ).not( '.cloned' ).length > 0;
								var bottom_navi_nearby = false;

								if ( current_component.append_results() && ! current_component.$component_pagination.find( '.component_options_load_more' ).is( ':visible' ) ) {
									var distance_from_navi = composite.$composite_navigation_bottom.offset().top - current_component.$component_content.offset().top - current_component.$component_content.outerHeight( true );
									if ( distance_from_navi < 100 ) {
										bottom_navi_nearby = true;
									}
								}

								if ( ! navi_in_content && ! bottom_navi_nearby ) {
									// For a smooth sliding transition, before moving the movable navi replace it with a dummy one whose buttons state hasn't been updated.
									if ( wc_composite_params.transition_type === 'slide' ) {
										composite.$composite_navigation_movable.after( $movable_navi_cl );
									}
									composite.$composite_navigation_movable.appendTo( current_component.$component_summary ).removeClass( 'hidden' );
								} else if ( navi_in_content && bottom_navi_nearby ) {
									composite.$composite_navigation_movable.addClass( 'hidden' );
								} else if ( navi_in_content ) {
									composite.$composite_navigation_movable.removeClass( 'hidden' );
								}

							} else {
								// For a smooth sliding transition, replace the movable navi with a dummy one whose buttons state hasn't been updated.
								if ( wc_composite_params.transition_type === 'slide' ) {
									composite.$composite_navigation_movable.addClass( 'hidden' );
									composite.$composite_navigation_movable.after( $movable_navi_cl );
								}
							}
						}

					} else {

						var component_message_delay = 0;

						if ( style === 'paged' && delay_nav_update ) {
							component_message_delay = wc_composite_params.transition_type === 'slide' ? composite.current_transition_delay + 100 : 200;
						}

						if ( current_component ) {
							setTimeout( function() {
								if ( current_component.$component_message.hasClass( 'inactive' ) ) {
									current_component.$component_message.slideUp( 200 );
								} else {
									current_component.$component_message.slideDown( 200 );
								}
							}, component_message_delay );
						}
					}
				};

				if ( style !== 'single' ) {
					if ( delay_nav_update ) {
						current_step.on_step_active.push( update_nav );
					} else {
						update_nav();
					}
				}


				/*
				 * Update pagination (step pagination + summary sections).
				 */

				var pagination = composite.$composite_pagination;
				var summary    = composite.$composite_summary;

				if ( pagination.length == 0 && summary.length == 0 ) {
					return false;
				}

				var deactivate_step_links = false;

				$.each( composite.composite_steps, function( step_index, step ) {

					if ( step_index > 0 ) {

						var prev_step = composite.composite_steps[ step_index - 1 ];

						if ( ! prev_step.is_review() && style !== 'single' ) {

							if ( false === prev_step.passes_validation() && ( style_variation !== 'componentized' || ( style_variation === 'componentized' && composite.composite_sequential_comp_progress === 'yes' ) ) ) {
								deactivate_step_links = true;
							} else if ( prev_step.is_blocked() ) {
								deactivate_step_links = true;
							// Don't activate new step links when going back.
							} else if ( step.view_elements.$pagination_element_link.hasClass( 'inactive' ) && composite.get_next_step().step_index != step_index && current_step.step_index < composite.last_active_step_index && step_index > composite.last_active_step_index ) {
								deactivate_step_links = true;
							}
						}
					}

					// Update simple pagination.
					if ( pagination.length > 0 ) {

						if ( step.is_current() ) {

							step.view_elements.$pagination_element_link.addClass( 'inactive' );
							step.view_elements.$pagination_element.addClass( 'pagination_element_current' );

						} else {

							if ( deactivate_step_links ) {

								step.view_elements.$pagination_element_link.addClass( 'inactive' );
								step.view_elements.$pagination_element.removeClass( 'pagination_element_current' );

							} else {

								step.view_elements.$pagination_element_link.removeClass( 'inactive' );
								step.view_elements.$pagination_element.removeClass( 'pagination_element_current' );

							}
						}
					}

					// Update summary links.
					if ( summary.length > 0 ) {

						if ( step.is_current() ) {

							step.view_elements.$summary_element_link.removeClass( 'disabled' );

							if ( style !== 'single' ) {
								step.view_elements.$summary_element_link.addClass( 'selected' );
							}

							if ( false === composite.get_step( 'review' ) ) {
								step.view_elements.$summary_element.find( '.summary_element_selection_prompt' ).slideUp( 200 );
							}

						} else {

							step.view_elements.$summary_element.find( '.summary_element_selection_prompt' ).slideDown( 200 );

							if ( deactivate_step_links ) {

								step.view_elements.$summary_element_link.removeClass( 'selected' );
								step.view_elements.$summary_element_link.addClass( 'disabled' );

							} else {

								step.view_elements.$summary_element_link.removeClass( 'disabled' );
								step.view_elements.$summary_element_link.removeClass( 'selected' );

							}
						}

					}

				} );

				// Update widget.
				composite.composite_summary_widget.update_links();

			},

			/**
			 * Updates the state of the Review/Summary template.
			 */

			update_summary: function() {

				var composite         = this;

				var composite_summary = composite.$composite_summary;
				var price_data        = composite.$composite_data.data( 'price_data' );

				if ( composite_summary.length == 0 ) {
					return false;
				}

				$.each( composite.composite_components, function( index, component ) {

					var component_id       = component.component_id;
					var item               = component.$self;
					var item_id            = component_id;

					var item_summary_outer = component.get_step().view_elements.$summary_element_wrapper;
					var item_summary_inner = component.get_step().view_elements.$summary_element_inner;

					var selections         = component.$component_options.find( '#component_options_' + component_id );
					var product_type       = component.get_selected_product_type();
					var product_id         = component.get_selected_product_id();
					var qty                = parseInt( item.find( '.component_wrap input.qty' ).val() );

					var title              = '';
					var select             = '';
					var image              = '';

					var product_title      = '';
					var product_quantity   = '';
					var product_meta       = '';
					var load_height        = 0;

					// Lock height if animating.
					if ( composite_summary.is( ':visible' ) ) {
						load_height = item_summary_inner.outerHeight( true );
						item_summary_outer.css( 'height', load_height );
					}

					// Get title and image.
					if ( product_type === 'none' ) {

						if ( component.is_optional() ) {
							title = '<span class="content_product_title none">' + selections.find( 'option.none' ).data( 'title' ) + '</span>';
						}

					} else if ( product_type === 'variable' ) {

						if ( product_id > 0 && ( qty > 0 || qty === 0 ) ) {

							product_title    = selections.find( 'option:selected' ).data( 'title' );
							product_quantity = '<strong>' + wc_composite_params.i18n_qty_string.replace( '%s', qty ) + '</strong>';
							product_title    = wc_composite_params.i18n_title_string.replace( '%t', product_title ).replace( '%q', product_quantity ).replace( '%p', '' );
							product_meta     = wc_cp_get_variable_product_attributes_description( item.find( '.variations' ) );

							if ( product_meta ) {
								title = wc_composite_params.i18n_selected_product_string.replace( '%t', product_title ).replace( '%m', '<span class="content_product_meta">' + product_meta + '</span>' );
							} else {
								title = product_title;
							}

							title = '<span class="content_product_title">' + title + '</span>';

							image = item.find( '.composited_product_image img' ).attr( 'src' );

							if ( typeof( image ) === 'undefined' ) {
								image = selections.find( 'option:selected' ).data( 'image_src' );
							}
						}

					} else if ( product_type === 'bundle' ) {

						if ( product_id > 0 && ( qty > 0 || qty === 0 ) ) {

							var selected_bundled_products = '';
							var bundled_products_num      = 0;
							var bundle                    = false;

							if ( typeof( wc_pb_bundle_scripts[ product_id ] !== 'undefined' ) ) {

								bundle = wc_pb_bundle_scripts[ product_id ];

								$.each( bundle.bundled_items, function( index, bundled_item ) {

									if ( ! bundled_item.$self.hasClass( 'bundled_item_hidden' ) ) {
										if ( bundled_item.$bundled_item_cart.data( 'quantity' ) > 0 ) {
											bundled_products_num++;
										}
									}

								} );

							}

							if ( false === bundle || bundled_products_num == 0 ) {

								title = wc_composite_params.i18n_none;

							} else {

								title = '<span class="content_product_title content_bundle_title">' + selections.find( 'option:selected' ).data( 'title' ) + '</span>';

								$.each( bundle.bundled_items, function( index, bundled_item ) {

									if ( bundled_item.$self.hasClass( 'bundled_item_hidden' ) ) {
										return true;
									}

									if ( bundled_item.$bundled_item_cart.data( 'quantity' ) > 0 ) {

										var item_title    = bundled_item.$bundled_item_cart.data( 'title' );
										var item_quantity = '<strong>' + wc_composite_params.i18n_qty_string.replace( '%s', parseInt( bundled_item.$bundled_item_cart.data( 'quantity' ) * qty ) ) + '</strong>';
										var item_meta     = wc_cp_get_variable_product_attributes_description( bundled_item.$bundled_item_cart.find( '.variations' ) );

										item_title = wc_composite_params.i18n_title_string.replace( '%t', item_title ).replace( '%q', item_quantity ).replace( '%p', '' );

										if ( item_meta ) {
											item_title = wc_composite_params.i18n_selected_product_string.replace( '%t', item_title ).replace( '%m', '<span class="content_product_meta">' + item_meta + '</span>' );
										}

										selected_bundled_products = selected_bundled_products + '<span class="content_bundled_product_title content_product_title">' + item_title + '</span>';
									}
								} );

								title = wc_composite_params.i18n_selected_product_string.replace( '%t', title ).replace( '%m', selected_bundled_products );
							}

							image = selections.find( 'option:selected' ).data( 'image_src' );
						}

					} else {

						if ( product_id > 0 ) {

							product_title    = selections.find( 'option:selected' ).data( 'title' );
							product_quantity = isNaN( qty ) ? '' : '<strong>' + wc_composite_params.i18n_qty_string.replace( '%s', qty ) + '</strong>';

							title = wc_composite_params.i18n_title_string.replace( '%t', product_title ).replace( '%q', product_quantity ).replace( '%p', '' );
							title = '<span class="content_product_title">' + title + '</span>';

							image = selections.find( 'option:selected' ).data( 'image_src' );
						}
					}

					// Selection text.
					if ( title && component.is_configured() ) {
						if ( item.hasClass( 'static') ) {
							select = '<a href="">' + wc_composite_params.i18n_summary_static_component + '</a>';
						} else {
							select = '<a href="">' + wc_composite_params.i18n_summary_configured_component + '</a>';
						}
					} else {
						select = '<a href="">' + wc_composite_params.i18n_summary_empty_component + '</a>';
					}

					// Update title.
					if ( title ) {
						component.get_step().view_elements.$summary_element_title.html( '<span class="summary_element_content">' + title + '</span><span class="summary_element_content summary_element_selection_prompt">' + select + '</span>' );
					} else {
						component.get_step().view_elements.$summary_element_title.html( '<span class="summary_element_content summary_element_selection_prompt">' + select + '</span>' );
					}

					// Update element class.
					if ( component.is_configured() ) {
						item_summary_outer.addClass( 'configured' );
					} else {
						item_summary_outer.removeClass( 'configured' );
					}

					// Hide selection text.
					if ( item.hasClass( 'active' ) ) {
						component.get_step().view_elements.$summary_element_title.find( '.summary_element_selection_prompt' ).hide();
					}

					// Update image.
					composite.update_summary_element_image( component, image );

					// Update price.
					if ( price_data[ 'per_product_pricing' ] === 'yes' && product_id > 0 && qty > 0 && component.get_step().passes_validation() ) {

						var price         = ( parseFloat( price_data[ 'prices' ][ item_id ] ) + parseFloat( price_data[ 'addons_prices' ][ item_id ] ) ) * qty;
						var regular_price = ( parseFloat( price_data[ 'regular_prices' ][ item_id ] ) + parseFloat( price_data[ 'addons_prices' ][ item_id ] ) ) * qty;

						var price_format         = wc_cp_woocommerce_number_format( wc_cp_number_format( price ) );
						var regular_price_format = wc_cp_woocommerce_number_format( wc_cp_number_format( regular_price ) );

						if ( regular_price > price ) {
							component.get_step().view_elements.$summary_element_price.html( '<span class="price summary_element_content"><del>' + regular_price_format + '</del> <ins>' + price_format + '</ins></span>' );
						} else {
							component.get_step().view_elements.$summary_element_price.html( '<span class="price summary_element_content">' + price_format + '</span>' );
						}

					} else {
						component.get_step().view_elements.$summary_element_price.html( '' );
					}

					// Send an event to allow 3rd party code to add data to the summary.
					item.trigger( 'wc-composite-component-update-summary-content', [ component, composite ] );

					// Animate.
					if ( composite_summary.is( ':visible' ) ) {

						// Measure height.
						var new_height     = item_summary_inner.outerHeight( true );
						var animate_height = false;

						if ( Math.abs( new_height - load_height ) > 1 ) {
							animate_height = true;
						} else {
							item_summary_outer.css( 'height', 'auto' );
						}

						if ( animate_height ) {
							item_summary_outer.animate( { 'height': new_height }, { duration: 200, queue: false, always: function() {
								item_summary_outer.css( { 'height': 'auto' } );
							} } );
						}
					}

				} );

				// Update Summary Widget.
				composite.composite_summary_widget.update_markup();

			},

			/**
			 * Updates images in the Review/Summary template.
	 		 */

			update_summary_element_image: function( component, img_src ) {

				var element_image = component.get_step().view_elements.$summary_element_image.find( 'img' );

				if ( element_image.length == 0 || element_image.hasClass( 'norefresh' ) ) {
					return false;
				}

				var o_src = element_image.attr( 'data-o_src' );

				if ( ! img_src ) {

					if ( typeof( o_src ) !== 'undefined' ) {
						element_image.attr( 'src', o_src );
					}

				} else {

					if ( typeof( o_src ) === 'undefined' ) {
						o_src = ( ! element_image.attr( 'src' ) ) ? '' : element_image.attr( 'src' );
						element_image.attr( 'data-o_src', o_src );
					}

					element_image.attr( 'src', img_src );
				}

			},

			/**
			 * Schedules an update of the composite totals and review/summary section.
			 * Uses a dumb scheduler to avoid queueing multiple calls of update_composite_task() - the "scheduler" simply introduces a 50msec execution delay during which all update requests are dropped.
			 */

			update_composite: function() {

				var composite = this;

				// Break out if the initialization is not finished yet (function call triggered by a 'wc-composite-component-loaded' event listener).
				if ( composite.composite_initialized !== true ) {
					return false;
				}

				// Dumb task scheduler.
				if ( composite.has_update_lock === true ) {
					return false;
				}

				composite.has_update_lock = true;

				setTimeout( function() {

					composite.update_composite_task();
					composite.has_update_lock = false;

				}, 50 );

			},

			/**
			 * Updates the composite totals and review/summary section + enables/disables the add-to-cart button.
			 */

			update_composite_task: function() {

				var composite      = this;
				var composite_data = composite.$composite_data;

				var all_set            = true;
				var component_quantity = {};
				var out_of_stock       = [];

				var price_data         = composite_data.data( 'price_data' );

				/**
				 * Validate components.
				 */

				$.each( composite.composite_components, function( index, component ) {

					var component_id    = component.component_id;
					var item            = component.$self;
					var item_id         = component_id;
					var product_type    = component.get_selected_product_type();

					// Verify submit form input data.
					var product_input   = item.find( '#component_options_' + item_id ).val();
					var quantity_input  = item.find( '.component_wrap input.qty' ).val();
					var variation_input = item.find( '.component_wrap input.variation_id' ).val();

					// Reset "configured" status.
					component.set_configured( false );

					// Copy prices.
					price_data[ 'prices' ][ item_id ]         = parseFloat( component.$component_data.data( 'price' ) );
					price_data[ 'regular_prices' ][ item_id ] = parseFloat( component.$component_data.data( 'regular_price' ) );

					// Save addons prices.
					price_data[ 'addons_prices' ][ item_id ] = 0;

					item.find( '.addon' ).each( function() {

						if ( product_type === 'bundle' && ! $( this ).closest( '.cart' ).hasClass( 'bundle_data' ) ) {
							return true;
						}

						var addon_cost = 0;

						if ( $( this ).is('.addon-custom-price') ) {
							addon_cost = $( this ).val();
						} else if ( $( this ).is('.addon-input_multiplier') ) {
							if( isNaN( $( this ).val() ) || $( this ).val() == '' ) { // Number inputs return blank when invalid
								$( this ).val( '' );
								$( this ).closest('p').find('.addon-alert').show();
							} else {
								if( $( this ).val() != '' ) {
									$( this ).val( Math.ceil( $( this ).val() ) );
								}
								$( this ).closest('p').find('.addon-alert').hide();
							}
							addon_cost = $( this ).data('price') * $( this ).val();
						} else if ( $( this ).is('.addon-checkbox, .addon-radio') ) {
							if ( $( this ).is(':checked') )
								addon_cost = $( this ).data('price');
						} else if ( $( this ).is('.addon-select') ) {
							if ( $( this ).val() )
								addon_cost = $( this ).find('option:selected').data('price');
						} else {
							if ( $( this ).val() )
								addon_cost = $( this ).data('price');
						}

						if ( ! addon_cost )
							addon_cost = 0;

						price_data[ 'addons_prices' ][ item_id ] = parseFloat( price_data[ 'addons_prices' ][ item_id ] ) + parseFloat( addon_cost );

					} );

					if ( typeof( product_type ) === 'undefined' || product_type == '' ) {
						all_set = false;
					} else if ( ! ( product_input > 0 ) && ! component.is_optional() ) {
						all_set = false;
					} else if ( product_type !== 'none' && quantity_input === '' ) {
						all_set = false;
					} else if ( product_type === 'variable' && ( typeof( variation_input ) === 'undefined' || component.$component_data.data( 'component_set' ) == false ) ) {
						all_set = false;
					} else if ( product_type !== 'variable' && product_type !== 'simple' && product_type !== 'none' && component.$component_data.data( 'component_set' ) == false ) {
						all_set = false;
					} else {

						// Set component as configured.
						component.set_configured( true );

						// Update quantity data for price calculations.
						if ( quantity_input > 0 ) {
							component_quantity[ item_id ] = parseInt( quantity_input );
						} else {
							component_quantity[ item_id ] = 0;
						}
					}

				} );

				/**
				 * Update paged layout summary state.
				 */

				composite.update_summary();

				/**
				 * Add to cart button state and price.
				 */

				if ( all_set ) {

					if ( ( price_data[ 'per_product_pricing' ] === 'no' ) && ( price_data[ 'base_price' ] === '' ) ) {
						composite.disable_add_to_cart( wc_composite_params.i18n_unavailable_text );
						return false;
					}

					price_data[ 'total' ]         = parseFloat( price_data[ 'base_price' ] );
					price_data[ 'regular_total' ] = parseFloat( price_data[ 'base_regular_price' ] );

					if ( price_data[ 'per_product_pricing' ] === 'yes' ) {

						for ( var item_id_ppp in price_data[ 'prices' ] ) {
							price_data[ 'total' ]         += ( parseFloat( price_data[ 'prices' ][ item_id_ppp ] ) + parseFloat( price_data[ 'addons_prices' ][ item_id_ppp ] ) ) * component_quantity[ item_id_ppp ];
							price_data[ 'regular_total' ] += ( parseFloat( price_data[ 'regular_prices' ][ item_id_ppp ] ) + parseFloat( price_data[ 'addons_prices' ][ item_id_ppp ] ) ) * component_quantity[ item_id_ppp ];
						}
					} else {

						for ( var item_id_sp in price_data[ 'addons_prices' ] ) {
							price_data[ 'total' ]         += parseFloat( price_data[ 'addons_prices' ][ item_id_sp ] ) * component_quantity[ item_id_sp ];
							price_data[ 'regular_total' ] += parseFloat( price_data[ 'addons_prices' ][ item_id_sp ] ) * component_quantity[ item_id_sp ];
						}
					}

					var composite_addon = composite_data.find( '#product-addons-total' );

					if ( composite_addon.length > 0 ) {
						composite_addon.data( 'price', price_data[ 'total' ] );
						composite_data.trigger( 'woocommerce-product-addons-update' );
					}

					if ( price_data[ 'total' ] == 0 && price_data[ 'show_free_string' ] === 'yes' ) {
						composite.$composite_price.html( '<p class="price"><span class="total">' + wc_composite_params.i18n_total + '</span>'+ wc_composite_params.i18n_free +'</p>' );
					} else {

						var sales_price_format   = wc_cp_woocommerce_number_format( wc_cp_number_format( price_data[ 'total' ] ) );
						var regular_price_format = wc_cp_woocommerce_number_format( wc_cp_number_format( price_data[ 'regular_total' ] ) );

						if ( price_data[ 'regular_total' ] > price_data[ 'total' ] ) {
							composite.$composite_price.html( '<p class="price"><span class="total">' + wc_composite_params.i18n_total + '</span><del>' + regular_price_format + '</del> <ins>' + sales_price_format + '</ins></p>' );
						} else {
							composite.$composite_price.html( '<p class="price"><span class="total">' + wc_composite_params.i18n_total + '</span>' + sales_price_format + '</p>' );
						}
					}

					// Check if any item is out of stock.

					var out_of_stock_found = false;

					$.each( composite.composite_components, function( index, component ) {

						if ( false === component.is_in_stock() && component_quantity[ component.component_id ] > 0 ) {
							out_of_stock_found = true;
						}

					} );


					if ( composite.composite_button_behaviour !== 'new' ) {
						composite_data.find( '.composite_wrap' ).slideDown( 200 );
					} else {
						composite.$composite_price.removeClass( 'inactive' ).slideDown( 200 );
						composite.$composite_message.addClass( 'inactive' ).slideUp( 200 );

						if ( out_of_stock_found ) {
							composite.$composite_add_to_cart_button.prop( 'disabled', true ).addClass( 'disabled' );
						} else {
							composite.$composite_add_to_cart_button.prop( 'disabled', false ).removeClass( 'disabled' );
						}
					}

					composite_data.find( '.composite_wrap' ).trigger( 'wc-composite-show-add-to-cart', [ composite ] );

				} else {

					composite.disable_add_to_cart();
				}

				/**
				 * Update composite availability string.
				 */

				$.each( composite.composite_components, function( index, component ) {
					if ( false === component.is_in_stock() && component_quantity[ component.component_id ] > 0 ) {
						out_of_stock.push( wc_composite_params.i18n_insufficient_item_stock.replace( '%s', $( '#component_options_' + component.component_id + ' option:selected' ).data( 'title' ) ).replace( '%v', component.get_title() ) );
					}
				} );

				var $overridden_stock_status = false;

				// Build out-of-stock selections string.

				if ( out_of_stock.length > 0 ) {

					var composite_out_of_stock_string = '<p class="stock out-of-stock">' + wc_composite_params.i18n_insufficient_stock + '</p>';
					var out_of_stock_string           = wc_cp_join( out_of_stock );

					$overridden_stock_status = $( composite_out_of_stock_string.replace( '%s', out_of_stock_string ) );
				}

				var $current_stock_status = composite_data.find( '.composite_wrap p.stock' );

				if ( $overridden_stock_status ) {
					if ( $current_stock_status.length > 0 ) {
						if ( $current_stock_status.hasClass( 'inactive' ) ) {
							$current_stock_status.replaceWith( $overridden_stock_status.hide() );
							$overridden_stock_status.slideDown( 200 );
						} else {
							$current_stock_status.replaceWith( $overridden_stock_status );
						}
					} else {
						composite.$composite_button.before( $overridden_stock_status.hide() );
						$overridden_stock_status.slideDown( 200 );
					}
				} else {
					if ( composite.$composite_stock_status ) {
						$current_stock_status.replaceWith( composite.$composite_stock_status );
					} else {
						$current_stock_status.addClass( 'inactive' ).slideUp( 200 );
					}
				}

				/**
				 * Update summary widget.
				 */

				composite.composite_summary_widget.update_price();
				composite.composite_summary_widget.update_error();

			},

			/**
			 * Called when the Composite can't be added-to-cart - disables the add-to-cart button and builds a string with a human-friendly reason.
			 */

			disable_add_to_cart: function( hide_message ) {

				var composite      = this;
				var composite_data = composite.$composite_data;
				var messages       = $( '<ul/>' );

				if ( composite.composite_button_behaviour === 'new' ) {

					if ( typeof( hide_message ) === 'undefined' ) {

						if ( ! composite.passes_validation() ) {

							messages.append( $( '<li/>' ).addClass( 'validation_msg' ).html( wc_composite_params.i18n_validation_issues ) );

							if ( ! composite.passes_validation() ) {
								$.each( composite.get_validation_messages(), function( index, validation_message ) {
									messages.append( $( '<li/>' ).addClass( 'indented_validation_msg validation_msg' ).html( validation_message ) );
								} );
							}
						}

					} else {
						messages.append( $( '<li/>' ).html( hide_message.toString() ) );
					}

					composite.$composite_price.addClass( 'inactive' ).slideUp( 200 );
					composite.$composite_message_content.html( messages.html() );
					composite.$composite_message.removeClass( 'inactive' ).slideDown( 200 );
					composite.$composite_add_to_cart_button.prop( 'disabled', true ).addClass( 'disabled' );

				} else {

					composite.$composite_price.html( '<p class="price"></p>' );
					composite_data.find( '.composite_wrap' ).slideUp( 200 );
				}

				composite_data.find( '.composite_wrap' ).trigger( 'wc-composite-hide-add-to-cart', [ composite ] );

			},

			/**
			 * Get all validation messages.
			 */

			add_validation_message: function( source, message ) {

				var composite = this;
				var appended  = false;

				if ( this._validation_messages.length > 0 ) {
					$.each( this._validation_messages, function( id, msg ) {
						if ( msg.content === message ) {
							var sources_new = msg.sources;
							var content_new = msg.content;
							sources_new.push( source );
							composite._validation_messages[ id ] = { sources: sources_new, content: content_new };
							appended = true;
							return false;
						}
					} );
				}

				if ( ! appended ) {
					this._validation_messages.push( { sources: [ source ], content: message.toString() } );
				}


			},

			/**
			 * Get all validation messages.
			 */

			get_validation_messages: function() {

				var messages = [];

				if ( this._validation_messages.length > 0 ) {
					$.each( this._validation_messages, function( id, msg ) {
						var sources = wc_cp_join( msg.sources );
						messages.push( wc_composite_params.i18n_validation_issues_for.replace( '%c', sources ).replace( '%e', msg.content ) );
					} );
				}

				return messages;
			},

			/**
			 * Check if any validation messages exist.
			 */

			passes_validation: function() {

				if ( this._validation_messages.length > 0 ) {
					return false;
				}

				return true;

			},

			/**
			 * Helper functions for updating the summary widget.
			 */

			init_widget: function() {

				var composite = this;

				composite.composite_summary_widget = {

					$self:     composite.$composite_summary_widget,
					$elements: composite.$composite_summary_widget.find( '.widget_composite_summary_elements' ),
					$price:    composite.$composite_summary_widget.find( '.widget_composite_summary_price' ),
					$error:    composite.$composite_summary_widget.find( '.widget_composite_summary_error' ),

					bind_event_handlers: function() {

						/**
						 * On clicking a composite summary link (widget).
						 */
						composite.$composite_summary_widget

							.on( 'click', '.summary_element_link', function( event ) {

								var composite_summary = $( this ).closest( '.composite_summary' );
								var container_id      = composite_summary.find( '.widget_composite_summary_content' ).data( 'container_id' );
								var form              = $( '#composite_data_' + container_id ).closest( '.composite_form' );

								if ( $( this ).hasClass( 'disabled' ) ) {
									return false;
								}

								if ( composite.has_transition_lock ) {
									return false;
								}

								var step_id = $( this ).closest( '.summary_element' ).data( 'item_id' );

								if ( typeof( step_id ) === 'undefined' ) {
									var element_index     = composite_summary.find( '.summary_element' ).index( $( this ).closest( '.summary_element' ) );
									step_id               = form.find( '.multistep.component:eq(' + element_index + ')' ).data( 'item_id' );
								}

								var step = composite.get_step( step_id );

								if ( step === false ) {
									return false;
								}

								if ( step.get_element().hasClass( 'progressive' ) ) {
									step.block_next_steps();
								}

								if ( ! step.is_current() || composite.composite_layout === 'single' ) {
									step.show_step();
								}

								return false;
							} )

							.on( 'click', 'a.summary_element_tap', function( event ) {
								$( this ).closest( '.summary_element_link' ).trigger( 'click' );
								return false;
							} );

					},

					update_markup: function() {

						var widget = this;

						if ( widget.$self.length > 0 ) {

							var clone = composite.$composite_summary.find( '.summary_elements' ).clone();

							clone.find( '.summary_element_wrapper' ).css( { 'height': 'auto' } );
							clone.find( '.summary_element' ).css( { 'width': '100%' } );
							clone.find( '.summary_element_selection_prompt' ).remove();

							widget.$elements.html( clone );
						}

					},

					update_links: function() {

						var widget = this;

						if ( composite.$composite_summary.length > 0 && widget.$self.length > 0 ) {

							$.each( composite.composite_steps, function( step_index, step ) {

								var summary_element      = widget.$self.find( '.summary_element_' + step.step_id );
								var summary_element_link = summary_element.find( '.summary_element_link' );

								summary_element_link.removeClass( 'disabled selected' );

								if ( step.view_elements.$summary_element_link.hasClass( 'disabled' ) ) {
									summary_element_link.addClass( 'disabled' );
								}

								if ( step.view_elements.$summary_element_link.hasClass( 'selected' ) ) {
									summary_element_link.addClass( 'selected' );
								}

							} );
						}

					},

					update_price: function() {

						var widget = this;

						if ( widget.$self.length > 0 ) {

							var price_clone = composite.$composite_price.clone();
							var html        = '';

							if ( ! price_clone.hasClass( 'inactive' ) ) {
								html = price_clone.html();
							}

							widget.$price.html( html );
						}

					},

					update_error: function() {

						var widget = this;

						if ( widget.$self.length > 0 ) {

							if ( composite.$composite_message.hasClass( 'inactive' ) ) {
								widget.$error.html( '' );
							} else {
								var error_clone = composite.$composite_message_content.clone().removeClass( 'woocommerce-error' ).addClass( 'woocommerce-info' );
								widget.$error.html( error_clone );
							}
						}
					}

				};

				composite.composite_summary_widget.$self.removeClass( 'cp-no-js' );
				composite.composite_summary_widget.bind_event_handlers();

			},

			/**
			 * Handler for viewport resizing.
			 */

			on_resize_handler: function() {

				// Add responsive classes to composite form.

				var composite  = this;
				var form_width = composite.$composite_form.width();

				if ( form_width <= wc_composite_params.small_width_threshold ) {
					composite.$composite_form.addClass( 'small_width' );
				} else {
					composite.$composite_form.removeClass( 'small_width' );
				}

				if ( form_width > wc_composite_params.full_width_threshold ) {
					composite.$composite_form.addClass( 'full_width' );
				} else {
					composite.$composite_form.removeClass( 'full_width' );
				}

				if ( wc_composite_params.legacy_width_threshold ) {
					if ( form_width <= wc_composite_params.legacy_width_threshold ) {
						composite.$composite_form.addClass( 'legacy_width' );
					} else {
						composite.$composite_form.removeClass( 'legacy_width' );
					}
				}

				// Reset relocated container if in wrong position.

				$.each( composite.composite_components, function( component_id, component ) {

					if ( component.$component_content.hasClass( 'relocated' ) ) {

						var relocation_params = component.get_content_relocation_params();

						if ( relocation_params.relocate ) {

							var relocation_target    = component.$component_options.find( '.component_option_content_container' );
							var relocation_reference = relocation_params.reference;

							relocation_reference.after( relocation_target );
						}
					}

				} );

			}

		};

		wc_cp_composite_scripts[ container_id ].init();
	};

	/**
	 * Construct a variable product selected attributes short description.
	 */

	function wc_cp_get_variable_product_attributes_description( variations ) {

		var attribute_options        = variations.find( '.attribute-options' );
		var attribute_options_length = attribute_options.length;
		var meta                     = '';

		if ( attribute_options_length == 0 ) {
			return '';
		}

		attribute_options.each( function( index ) {

			var selected = $( this ).find( 'select' ).val();

			if ( selected === '' ) {
				meta = '';
				return false;
			}

			meta = meta + $( this ).data( 'attribute_label' ) + ': ' + $( this ).find( 'select option:selected' ).text();

			if ( index !== attribute_options_length - 1 ) {
				meta = meta + ', ';
			}

		} );

		return meta;
	}

	/**
	 * Toggle-box handling.
	 */

	function wc_cp_toggle_element( container, content ) {

		if ( container.hasClass( 'animating' ) ) {
			return false;
		}

		if ( container.hasClass( 'closed' ) ) {
			setTimeout( function() {
				content.slideDown( { duration: 200, queue: false, always: function() {
					container.removeClass( 'animating' );
				} } );
			}, 10 );
			container.removeClass( 'closed' ).addClass( 'open animating' );
		} else {
			content.slideUp( { duration: 200, queue: false } );
			container.removeClass( 'open' ).addClass( 'closed' );
		}

		return true;
	}

	/**
	 * Various helper functions.
	 */

	function wc_cp_scroll_viewport( target, params ) {

		var anim_complete;
		var scroll_to;

		var partial         = typeof( params.partial ) === 'undefined' ? true : params.partial;
		var offset          = typeof( params.offset ) === 'undefined' ? 50 : params.offset;
		var timeout         = typeof( params.timeout ) === 'undefined' ? 5 : params.timeout;
		var anim_duration   = typeof( params.duration ) === 'undefined' ? 250 : params.duration;
		var anim_queue      = typeof( params.queue ) === 'undefined' ? false : params.queue;
		var always_complete = typeof( params.always_on_complete ) === 'undefined' ? false : params.always_on_complete;
		var scroll_method   = typeof( params.scroll_method ) === 'undefined' ? false : params.scroll_method;

		var do_scroll       = false;
		var $w              = $( window );
		var $d              = $( document );

		if ( typeof( params.on_complete ) === 'undefined' || params.on_complete === false ) {
			anim_complete = function() {
				return false;
			};
		} else {
			anim_complete = params.on_complete;
		}

		var scroll_viewport = function() {

			// Scroll viewport by an offset.
			if ( target === 'relative' ) {

				scroll_to = $w.scrollTop() - offset;
				do_scroll = true;

			// Scroll viewport to absolute document position.
			} else if ( target === 'absolute' ) {

				scroll_to = offset;
				do_scroll = true;

			// Scroll to target element.
			} else if ( target.length > 0 && ! target.is_in_viewport( partial ) ) {

				var window_offset = offset;

				if ( scroll_method === 'bottom' || target.hasClass( 'scroll_bottom' ) ) {
					window_offset = $w.height() - target.outerHeight( true ) - offset;
				} else if ( scroll_method === 'middle' ) {
					window_offset = $w.height() / 3 * 2 - target.outerHeight( true ) - offset;
				}

				scroll_to = target.offset().top - window_offset;

				// Ensure element top is in viewport.
				if ( target.offset().top < scroll_to ) {
					scroll_to = target.offset().top;
				}

				do_scroll = true;
			}

			if ( do_scroll ) {

				// Prevent out-of-bounds scrolling.
				if ( scroll_to > $d.height() - $w.height() ) {
					scroll_to = $d.height() - $w.height() - 100;
				}

				// Avoid scrolling both html and body.
				var pos            = $( 'html' ).scrollTop();
				var animate_target = 'body';

				$( 'html' ).scrollTop( $( 'html' ).scrollTop() - 1 );
				if ( pos != $( 'html' ).scrollTop() ) {
					animate_target = 'html';
				}

				$( animate_target ).animate( { scrollTop: scroll_to }, { duration: anim_duration, queue: anim_queue, always: anim_complete } );

			} else {
				if ( always_complete ) {
					anim_complete();
				}
			}
		};

		if ( timeout > 0 ) {
			setTimeout( function() {
				scroll_viewport();
			}, timeout );
		} else {
			scroll_viewport();
		}
	}

	function wc_cp_woocommerce_number_format( price ) {

		var remove     = wc_composite_params.currency_format_decimal_sep;
		var position   = wc_composite_params.currency_position;
		var symbol     = wc_composite_params.currency_symbol;
		var trim_zeros = wc_composite_params.currency_format_trim_zeros;
		var decimals   = wc_composite_params.currency_format_num_decimals;

		if ( trim_zeros == 'yes' && decimals > 0 ) {
			for ( var i = 0; i < decimals; i++ ) { remove = remove + '0'; }
			price = price.replace( remove, '' );
		}

		var price_format = '';

		if ( position == 'left' ) {
			price_format = '<span class="amount">' + symbol + price + '</span>';
		} else if ( position == 'right' ) {
			price_format = '<span class="amount">' + price + symbol +  '</span>';
		} else if ( position == 'left_space' ) {
			price_format = '<span class="amount">' + symbol + ' ' + price + '</span>';
		} else if ( position == 'right_space' ) {
			price_format = '<span class="amount">' + price + ' ' + symbol +  '</span>';
		}

		return price_format;
	}

	function wc_cp_number_format( number ) {

		var decimals      = wc_composite_params.currency_format_num_decimals;
		var decimal_sep   = wc_composite_params.currency_format_decimal_sep;
		var thousands_sep = wc_composite_params.currency_format_thousand_sep;

	    var n = number, c = isNaN( decimals = Math.abs( decimals ) ) ? 2 : decimals;
	    var d = typeof( decimal_sep ) === 'undefined' ? ',' : decimal_sep;
	    var t = typeof( thousands_sep ) === 'undefined' ? '.' : thousands_sep, s = n < 0 ? '-' : '';
	    var i = parseInt( n = Math.abs( +n || 0 ).toFixed(c) ) + '', j = ( j = i.length ) > 3 ? j % 3 : 0;

	    return s + ( j ? i.substr( 0, j ) + t : '' ) + i.substr(j).replace( /(\d{3})(?=\d)/g, '$1' + t ) + ( c ? d + Math.abs( n - i ).toFixed(c).slice(2) : '' );
	}

	function wc_cp_intersect_safe( a, b ) {

		var ai     = 0, bi = 0;
		var result = [];

		a.sort();
		b.sort();

		while ( ai < a.length && bi < b.length ) {

			if ( a[ai] < b[bi] ) {
				ai++;
			} else if ( a[ai] > b[bi] ) {
				bi++;
			/* they're equal */
			} else {
				result.push( a[ai] );
				ai++;
				bi++;
			}
		}

		return result;
	}

	function wc_cp_log( message ) {

		if ( window.console ) {
			window.console.log( message );
		}
	}

	function wc_cp_join( arr ) {

		var joined_arr = '';
		var count      = arr.length;

		if ( count > 0 ) {

			var loop = 0;

			for ( var i = 0; i < count; i++ ) {

				loop++;

				if ( count == 1 || loop == 1 ) {
					joined_arr = arr[ i ];
				} else {
					joined_arr = wc_composite_params.i18n_comma_sep.replace( '%s', joined_arr ).replace( '%v', arr[ i ] );
				}
			}
		}

		return joined_arr;
	}

    $.fn.is_in_viewport = function( partial, hidden, direction ) {

    	var $w = $( window );

        if ( this.length < 1 ) {
            return;
        }

        var $t         = this.length > 1 ? this.eq(0) : this,
			t          = $t.get(0),
			vpWidth    = $w.width(),
			vpHeight   = $w.height(),
			clientSize = hidden === true ? t.offsetWidth * t.offsetHeight : true;

		direction = (direction) ? direction : 'vertical';

        if (typeof t.getBoundingClientRect === 'function'){

            // Use this native browser method, if available.
            var rec = t.getBoundingClientRect(),
                tViz = rec.top    >= 0 && rec.top    <  vpHeight,
                bViz = rec.bottom >  0 && rec.bottom <= vpHeight,
                lViz = rec.left   >= 0 && rec.left   <  vpWidth,
                rViz = rec.right  >  0 && rec.right  <= vpWidth,
                vVisible   = partial ? tViz || bViz : tViz && bViz,
                hVisible   = partial ? lViz || rViz : lViz && rViz;

            if ( direction === 'both' ) {
                return clientSize && vVisible && hVisible;
            } else if ( direction === 'vertical' ) {
                return clientSize && vVisible;
            } else if ( direction === 'horizontal' ) {
                return clientSize && hVisible;
            }

        } else {

            var viewTop         = $w.scrollTop(),
                viewBottom      = viewTop + vpHeight,
                viewLeft        = $w.scrollLeft(),
                viewRight       = viewLeft + vpWidth,
                offset          = $t.offset(),
                _top            = offset.top,
                _bottom         = _top + $t.height(),
                _left           = offset.left,
                _right          = _left + $t.width(),
                compareTop      = partial === true ? _bottom : _top,
                compareBottom   = partial === true ? _top : _bottom,
                compareLeft     = partial === true ? _right : _left,
                compareRight    = partial === true ? _left : _right;

            if ( direction === 'both' ) {
                return !!clientSize && ( ( compareBottom <= viewBottom ) && ( compareTop >= viewTop ) ) && ( ( compareRight <= viewRight ) && ( compareLeft >= viewLeft ) );
            } else if ( direction === 'vertical' ) {
                return !!clientSize && ( ( compareBottom <= viewBottom ) && ( compareTop >= viewTop ) );
            } else if ( direction === 'horizontal' ) {
                return !!clientSize && ( ( compareRight <= viewRight ) && ( compareLeft >= viewLeft ) );
            }
        }
    };

    /*
     * Step class.
     */

	function WC_CP_Step( composite, $step, index ) {

		var step_id               = $step.data( 'item_id' );

		this.step_id              = step_id;
		this.step_index           = index;
		this.step_title           = $step.data( 'nav_title' );

		this._validation_messages = [];

		this._is_component        = $step.hasClass( 'component' );
		this._is_review           = $step.hasClass( 'cart' );

		this.on_step_active       = [];

		this.$self                = $step;

		this.view_elements        = {

			$summary_element:         composite.$composite_summary.find( '.summary_element_' + step_id ),
			$summary_element_link:    composite.$composite_summary.find( '.summary_element_' + step_id + ' .summary_element_link' ),

			$summary_element_wrapper: composite.$composite_summary.find( '.summary_element_' + step_id + ' .summary_element_wrapper' ),
			$summary_element_inner:   composite.$composite_summary.find( '.summary_element_' + step_id + ' .summary_element_wrapper_inner' ),

			$summary_element_title:   composite.$composite_summary.find( '.summary_element_' + step_id + ' .summary_element_selection' ),
			$summary_element_image:   composite.$composite_summary.find( '.summary_element_' + step_id + ' .summary_element_image' ),
			$summary_element_price:   composite.$composite_summary.find( '.summary_element_' + step_id + ' .summary_element_price' ),

			$pagination_element:      composite.$composite_pagination.find( '.pagination_element_' + step_id ),
			$pagination_element_link: composite.$composite_pagination.find( '.pagination_element_' + step_id + ' .element_link' ),

		};

		this.get_title = function() {

			return this.step_title;

		};

		this.get_element = function() {

			return this.$self;

		};

		this.is_review = function() {

			return this._is_review;

		};

		this.is_component = function() {

			return this._is_component;

		};

		this.get_component = function() {

			if ( this._is_component ) {
				return composite.composite_components[ this.step_index ];
			} else {
				return false;
			}
		};

		this.is_current = function() {

			return this.$self.hasClass( 'active' );

		};

		this.is_next = function() {

			return this.$self.hasClass( 'next' );

		};

		this.is_previous = function() {

			return this.$self.hasClass( 'prev' );

		};

		/**
		 * Brings a new step into view - called when clicking on a navigation element.
		 */

		this.show_step = function() {

			var step            = this;
			var form            = composite.$composite_form;
			var style           = composite.composite_layout;
			var style_variation = composite.composite_layout_variation;
			var item            = this.$self;
			var do_scroll       = ( composite.composite_initialized === false ) ? false : true;
			var is_review       = step.is_review();
			var is_component    = step.is_component();
			var component       = is_component ? step.get_component() : false;

			// Scroll to the desired section.
			if ( style === 'single' && do_scroll ) {

				wc_cp_scroll_viewport( item, { partial: false, duration: 250, queue: false } );

				// No more work when using the stacked layout.
				return false;
			}

			if ( style === 'paged' && do_scroll ) {

				if ( is_component && component.$component_content.hasClass( 'relocated' ) ) {

					/*
					 * When returning to a visited component with relocated selection details,
					 * check the 'relocated_content_reset_on_return' flag in wc_composite_params to determine how to act:
					 *
					 * - 'yes': Reset the position of the relocated container to the page top and auto-scroll to it.
					 * - 'no':  Auto scroll to the relocated container.
					 */

					if ( wc_composite_params.relocated_content_reset_on_return === 'yes' ) {

						// Wait for set_active() to complete the fadeout transition.
						step.on_step_active.push( component.reset_relocated_content );

					} else {
						wc_cp_scroll_viewport( component.$component_content, { timeout: 300, partial: false, duration: 250, queue: false, scroll_method: 'middle' } );
					}

				} else {
					wc_cp_scroll_viewport( form.find( '.scroll_show_component' ), { timeout: 20, partial: true, duration: 250, queue: false } );
				}

				setTimeout( function() {

					// Fade out or show summary widget.
					if ( composite.$composite_summary_widget.length > 0 ) {
						if ( is_review ) {
							composite.$composite_summary_widget.animate( { opacity: 0 }, { duration: 250, queue: false } );
						} else {
							composite.$composite_summary_widget.slideDown( 250 );
							composite.$composite_summary_widget.animate( { opacity: 1 }, { duration: 250, queue: false } );
						}
					}

					// Move summary widget out of the way if needed.
					if ( is_review ) {
						composite.$composite_summary_widget.slideUp( 250 );
					}

				}, 20 );

				if ( style_variation === 'componentized' ) {
					if ( is_review ) {
						composite.$composite_navigation.css( { visibility: 'hidden' } );
					} else {
						composite.$composite_navigation.css( { visibility: 'visible' } );
					}
				}

			}

			// Move active component.
			step.set_active();

			// Update blocks.
			step.update_block_state();

			// Update selections.
			step.fire_scenario_actions();

			// Autoload more results if all loaded thumbnail options are hidden.
			if ( wc_composite_params.no_compat_options_autoload === 'yes' ) {
				if ( style !== 'single' && composite.composite_selection_mode === 'thumbnails' && is_component ) {

					if ( component.append_results() && component.hide_disabled_products() ) {

						var load_more        = component.$component_pagination.find( '.component_options_load_more' );
						var results_per_page = load_more.data( 'results_per_page' );

						if ( false === component.has_compat_results || component.compat_results_count < results_per_page ) {
							// Wait for set_active() to complete the transition.
							setTimeout( function() {
								load_more.trigger( 'click' );
							}, 300 );
						}
					}
				}
			}

			// Update ui.
			composite.update_ui( true );

			// Scroll to the desired section (progressive).
			if ( style === 'progressive' && do_scroll && item.hasClass( 'autoscrolled' ) ) {
				wc_cp_scroll_viewport( item, { timeout: 250, partial: false, duration: 250, queue: false } );
			}

			item.trigger( 'wc-composite-show-component', [ step, composite ] );

		};

		/**
		 * Sets a step as active by hiding the previous one and updating the steps' markup.
		 */

		this.set_active = function() {

			var step            = this;
			var form            = composite.$composite_form;
			var style           = composite.composite_layout;
			var style_variation = composite.composite_layout_variation;
			var active_step     = composite.get_current_step();
			var is_active       = false;

			if ( active_step.step_id == step.step_id ) {
				is_active = true;
			}

			composite.last_active_step_index = active_step.step_index;

			form.children( '.multistep.active, .multistep.next, .multistep.prev' ).removeClass( 'active next prev' );

			this.get_element().addClass( 'active' );

			var next_item = this.get_element().next();
			var prev_item = this.get_element().prev();

			if ( style === 'paged' && style_variation === 'componentized' ) {
				next_item = form.find( '.multistep.cart' );
				prev_item = form.find( '.multistep.cart' );
			}

			if ( next_item.hasClass( 'multistep' ) ) {
				next_item.addClass( 'next' );
			}

			if ( prev_item.hasClass( 'multistep' ) ) {
				prev_item.addClass( 'prev' );
			}

			if ( style !== 'progressive' ) {

				if ( ! is_active ) {

					composite.has_transition_lock = true;

					if ( wc_composite_params.transition_type === 'slide' ) {

						setTimeout( function() {

							var active_step_height = active_step.get_element().height();
							var step_height        = step.get_element().height();
							var max_height         = Math.max( active_step_height, step_height );

							composite.current_transition_delay = 150 + Math.min( 450, parseInt( max_height / 5 ) );

							// Hide with a sliding effect.
							active_step.get_element().addClass( 'faded' ).slideUp( { duration: composite.current_transition_delay, always: function() {
								active_step.get_element().removeClass( 'faded' );
							} } );

							// Show with a sliding effect.
							if ( active_step.step_index < step.step_index ) {
								step.get_element().after( '<div style="display:none" class="active_placeholder"></div>' );
								step.get_element().insertBefore( active_step.get_element() );
							}

							// Run step-active triggers.
							$.each( step.on_step_active, function( index, action ) {
								action();
							} );

							// Clear triggers.
							step.on_step_active = [];

							step.get_element().slideDown( { duration: composite.current_transition_delay, always: function() {

								if ( active_step.step_index < step.step_index ) {
									composite.$composite_form.find( '.active_placeholder' ).replaceWith( step.get_element() );
								}

								composite.$composite_form.find( '.composite_navigation.cloned' ).remove();

								composite.has_transition_lock = false;

							} } );

						}, 200 );

					} else if ( wc_composite_params.transition_type === 'fade' ) {

						// Fade out.
						composite.$steps.addClass( 'faded' );
						composite.$composite_navigation.addClass( 'faded' );

						// Wait for the CSS transition to complete.
						setTimeout( function() {

								// Hide old.
								active_step.get_element().hide();

								// Run step-active triggers.
								$.each( step.on_step_active, function( index, action ) {
									action();
								} );

								// Clear triggers.
								step.on_step_active = [];

								// Show new.
								step.get_element().show();

								// Fade in.
								setTimeout( function() {
									composite.$steps.removeClass( 'faded' );
									composite.$composite_navigation.removeClass( 'faded' );
								}, 50 );

								composite.has_transition_lock = false;

						}, 250 );
					}

				} else {
					step.get_element().show();
				}
			}

			this.$self.trigger( 'wc-composite-set-active-component', [ step, composite ] );

		};

		/**
		 * Updates the block state of a progressive step that's brought into view.
		 */

		this.update_block_state = function() {

			var style = composite.composite_layout;

			if ( style !== 'progressive' ) {
				return false;
			}

			var prev_step = composite.get_previous_step();

			if ( prev_step !== false ) {
				prev_step.block_step_inputs();
				// Do not close when the component is set to remain open when blocked.
				if ( prev_step.$self.hasClass( 'block-open' ) ) {
					prev_step.$self.find( '.component_title' ).addClass( 'inactive' );
				} else {
					prev_step.toggle_step( 'closed', true );
				}
			}

			this.unblock_step_inputs();
			this.unblock_step();

			if ( prev_step !== false ) {
				var reset_options = prev_step.get_element().find( '.clear_component_options' );
				reset_options.html( wc_composite_params.i18n_reset_selection ).addClass( 'reset_component_options' );
			}

		};

		/**
		 * Unblocks access to step in progressive mode.
		 */

		this.unblock_step = function() {

			this.toggle_step( 'open', true );

			this.$self.removeClass( 'blocked' );

		};

		/**
		 * Blocks access to all later steps in progressive mode.
		 */

		this.block_next_steps = function() {

			var min_block_index = this.step_index;

			$.each( composite.composite_steps, function( index, step ) {

				if ( index > min_block_index ) {

					if ( step.get_element().hasClass( 'disabled' ) ) {
						step.unblock_step_inputs();
					}

					step.block_step();
				}
			} );

		};

		/**
		 * Blocks access to step in progressive mode.
		 */

		this.block_step = function() {

			this.$self.addClass( 'blocked' );

			this.toggle_step( 'closed', false );

		};

		/**
		 * Toggle step in progressive mode.
		 */

		this.toggle_step = function( state, active ) {

			if ( this.$self.hasClass( 'toggled' ) ) {

				if ( state === 'open' ) {
					if ( this.$self.hasClass( 'closed' ) ) {
						wc_cp_toggle_element( this.$self, this.$self.find( '.component_inner' ) );
					}

				} else if ( state === 'closed' ) {
					if ( this.$self.hasClass( 'open' ) ) {
						wc_cp_toggle_element( this.$self, this.$self.find( '.component_inner' ) );
					}
				}

				if ( active ) {
					this.$self.find( '.component_title' ).removeClass( 'inactive' );
				} else {
					this.$self.find( '.component_title' ).addClass( 'inactive' );
				}
			}

		};

		/**
		 * Unblocks step inputs.
		 */

		this.unblock_step_inputs = function() {

			this.$self.find( 'select.disabled_input, input.disabled_input' ).removeClass( 'disabled_input' ).prop( 'disabled', false );

			this.$self.removeClass( 'disabled' ).trigger( 'wc-composite-enable-component-options', [ this, composite ] );

			var reset_options = this.$self.find( '.clear_component_options' );

			reset_options.html( wc_composite_params.i18n_clear_selection ).removeClass( 'reset_component_options' );

		};

		/**
		 * Blocks step inputs.
		 */

		this.block_step_inputs = function() {

			this.$self.find( 'select, input' ).addClass( 'disabled_input' ).prop( 'disabled', 'disabled' );

			this.$self.addClass( 'disabled' ).trigger( 'wc-composite-disable-component-options', [ this, composite ] );

			var reset_options = this.$self.find( '.clear_component_options' );

			reset_options.html( wc_composite_params.i18n_reset_selection ).addClass( 'reset_component_options' );

		};

		/**
		 * True if access to the step is blocked (progressive mode).
		 */

		this.is_blocked = function() {

			return this.$self.hasClass( 'blocked' );

		};

		/**
		 * True if access to the step inputs is blocked (progressive mode).
		 */

		this.has_blocked_inputs = function() {

			return this.$self.hasClass( 'disabled' );

		};

		/**
		 * Fire state actions based on scenarios.
		 */

		this.fire_scenario_actions = function( excl_firing ) {

			if ( composite.has_scenarios_update_lock ) {
				return false;
			}

			if ( typeof( excl_firing ) === 'undefined' ) {
				excl_firing = false;
			}

			composite.actions_nesting++;

			// Update active scenarios.
			composite.update_active_scenarios( this.step_id );

			// Update selections - 'compat_group' scenario action.
			composite.update_selections( this.step_id, excl_firing );

			if ( composite.actions_nesting === 1 ) {

				// Signal 3rd party scripts to fire their own actions.
				this.get_element().trigger( 'wc-composite-fire-scenario-actions', [ this, composite ] );
			}

			composite.actions_nesting--;

		};

		/**
		 * Adds a validation message.
		 */

		this.add_validation_message = function( message ) {

			this._validation_messages.push( message.toString() );
		};

		/**
		 * Get all validation messages.
		 */

		this.get_validation_messages = function() {

			return this._validation_messages;
		};

		/**
		 * Validate component selection and stock status and add validation messages.
		 */

		this.validate = function() {

			var step = this;

			this._validation_messages = [];

			if ( this.is_component() ) {

				var component    = this.get_component();

				var product_id   = component.get_selected_product_id();
				var product_type = component.get_selected_product_type();

				var valid        = false;

				// Check if valid selection present.

				if ( product_id > 0 ) {

					if ( product_type === 'variable' && component.$component_summary.find( '.variations_button input.variation_id' ).val() > 0 ) {
						valid = true;
					} else if ( product_type === 'simple' || product_type === 'none' ) {
						valid = true;
					} else if ( component.$component_data.data( 'component_set' ) == true ) {
						valid = true;
					}

				} else if ( product_id === '' && component.is_optional() ) {
					valid = true;
				}

				// Check if in stock.

				if ( ! valid ) {

					if ( product_id > 0 ) {

						if ( product_type === 'bundle' ) {

							if ( typeof( wc_pb_bundle_scripts[ product_id ] !== 'undefined' ) ) {

								var bundle              = wc_pb_bundle_scripts[ product_id ];
								var validation_messages = bundle.get_validation_messages();

								$.each( validation_messages, function( index, message ) {
									step.add_validation_message( message );
									composite.add_validation_message( step.get_title(), message );
								} );

							} else {
								this.add_validation_message( wc_composite_params.i18n_select_product_options.replace( '%s', this.get_title() ) );
								composite.add_validation_message( this.get_title(), wc_composite_params.i18n_select_product_options_for );
							}

						} else {
							this.add_validation_message( wc_composite_params.i18n_select_product_options.replace( '%s', this.get_title() ) );
							composite.add_validation_message( this.get_title(), wc_composite_params.i18n_select_product_options_for );
						}
					} else {
						this.add_validation_message( wc_composite_params.i18n_select_component_option.replace( '%s', this.get_title() ) );
						composite.add_validation_message( this.get_title(), wc_composite_params.i18n_select_component_option_for );
					}

				}

				if ( ! component.is_in_stock() && this.$self.find( '.component_wrap input.qty' ).val() > 0 ) {
					this.add_validation_message( wc_composite_params.i18n_selected_component_options_no_stock.replace( '%s', this.get_title() ) );
				}
			}

			this.$self.triggerHandler( 'wc-composite-validate-step', [ this, composite ] );

		};

		/**
		 * Check if any validation messages exist.
		 */

		this.passes_validation = function() {

			if ( this._validation_messages.length > 0 ) {
				return false;
			}

			return true;

		};

	}
    /*
     * Component class.
     */

	function WC_CP_Component( composite, $component, index ) {

		var self                        = this;

		this.component_index            = index;
		this.component_id               = $component.attr( 'data-item_id' );
		this.component_title            = $component.data( 'nav_title' );

		this._is_optional               = false;
		this._is_configured             = false;

		this.has_compat_results         = true;
		this.compat_results_count       = 0;

		this._hide_disabled_products    = $component.hasClass( 'hide-incompatible-products' );
		this._hide_disabled_variations  = $component.hasClass( 'hide-incompatible-variations' );
		this._append_results            = $component.hasClass( 'append-results' );

		this._selection_id              = false;
		this._selection_invalid         = false;

		this.initial_selection_id       = $component.find( 'select.component_options_select' ).val();

		this.$self                      = $component;
		this.$component_summary         = $component.find( '.component_summary' );
		this.$component_selections      = $component.find( '.component_selections' );
		this.$component_content         = $component.find( '.component_content' );
		this.$component_options         = $component.find( '.component_options' );
		this.$component_options_inner   = $component.find( '.component_options_inner' );
		this.$component_inner           = $component.find( '.component_inner' );
		this.$component_pagination      = $component.find( '.component_pagination' );
		this.$component_message         = $component.find( '.component_message' );
		this.$component_message_content = $component.find( '.component_message ul.msg' );

		this.$component_data            = $();

		this.$relocation_origin         = false;

		/**
		 * True when component options are appended using a 'load more' button, instead of paginated.
		 */

		this.append_results = function() {

			return this._append_results;
		};

		/**
		 * Set the selected product id.
		 */

		this.set_selection_id = function( val ) {

			this._selection_id = val;

		};

		/**
		 * Get the selected product id.
		 */

		this.get_selection_id = function() {

			return this._selection_id;

		};

		/**
		 * Set a selection as invalid when it is currently disabled.
		 */

		this.set_selection_invalid = function( status ) {

			this._selection_invalid = status;

		};

		/**
		 * When true, hide incompatible/disabled products.
		 */

		this.hide_disabled_products = function() {

			return this._hide_disabled_products;

		};

		/**
		 * When true, hide incompatible/disabled variations.
		 */

		this.hide_disabled_variations = function() {

			return this._hide_disabled_variations;

		};

		/**
		 * Get the product type of the selected product.
		 */

		this.get_selected_product_type = function() {

			return this.$component_data.data( 'product_type' );

		};

		/**
		 * Get the product id of the selected product (non casted).
		 */

		this.get_selected_product_id = function() {

			if ( this._selection_invalid ) {
				return null;
			}

			return this.$component_options.find( '#component_options_' + this.component_id ).val();

		};

		/**
		 * True if the component has an out-of-stock availability class.
		 */

		this.is_in_stock = function() {

			if ( this.$component_summary.find( '.component_wrap .out-of-stock' ).not( '.inactive' ).length > 0 ) {
				return false;
			}

			return true;

		};

		/**
		 * True if the component is configured and ready to be purchased.
		 */

		this.is_configured = function() {

			return this._is_configured;

		};

		/**
		 * Sets _is_configured to true if the component is configured and ready to be purchased.
		 * Status saved during final validation and totals calculation in update_composite().
		 */

		this.set_configured = function( status ) {

			this._is_configured = status;

		};

		/**
		 * Initialize component scripts dependent on product type - called when selecting a new Component Option.
		 * When called with init = false, no type-dependent scripts will be initialized.
		 */

		this.init_scripts = function( init ) {

			if ( typeof( init ) === 'undefined' ) {
				init = true;
			}

			this.$component_data = this.$self.find( '.component_data' );

			if ( init ) {

				var product_type    = this.get_selected_product_type();
				var summary_content = this.$self.find( '.component_summary > .content' );

				if ( product_type === 'variable' ) {

					if ( ! summary_content.hasClass( 'cart' ) ) {
						summary_content.addClass( 'cart' );
					}

					if ( ! summary_content.hasClass( 'variations_form' ) ) {
						summary_content.addClass( 'variations_form' );
					}

					// Put filtered variations in place.
					summary_content.data( 'product_variations', this.$component_data.data( 'product_variations' ) );

					// Initialize variations script.
					summary_content.wc_variation_form();

					// Fire change in order to save 'variation_id' input.
					summary_content.find( '.variations select' ).change();

					// Complete all pending animations.
					summary_content.find( 'div' ).stop( true, true );

				} else if ( product_type === 'bundle' ) {

					if ( ! summary_content.hasClass( 'bundle_form' ) ) {
						summary_content.addClass( 'bundle_form' );
					}

					// Initialize bundles script now.
					summary_content.find( '.bundle_data' ).wc_pb_bundle_form();

					// Complete all pending animations.
					summary_content.find( 'div' ).stop( true, true );

				} else {

					if ( ! summary_content.hasClass( 'cart' ) ) {
						summary_content.addClass( 'cart' );
					}
				}
			}

		};

		/**
		 * Get the step that corresponds to this component.
		 */

		this.get_step = function() {

			return composite.get_step( this.component_id );

		};

		/**
		 * Get the title of this component.
		 */

		this.get_title = function() {

			return this.component_title;

		};

		/**
		 * Add active/filtered classes to the component filters markup, can be used for styling purposes.
		 */

		this.update_filters_ui = function() {

			var component_filters = this.$self.find( '.component_filters' );
			var filters           = component_filters.find( '.component_filter' );
			var all_empty         = true;

			if ( filters.length == 0 ) {
				return false;
			}

			filters.each( function() {

				if ( $( this ).find( '.component_filter_option.selected' ).length == 0 ) {
					$( this ).removeClass( 'active' );
				} else {
					$( this ).addClass( 'active' );
					all_empty = false;
				}

			} );

			if ( all_empty ) {
				component_filters.removeClass( 'filtered' );
			} else {
				component_filters.addClass( 'filtered' );
			}
		};

		/**
		 * Collect active component filters and options and build an object for posting.
		 */

		this.get_active_filters = function() {

			var component_filters = this.$self.find( '.component_filters' );
			var filters           = {};

			if ( component_filters.length == 0 ) {
				return filters;
			}

			component_filters.find( '.component_filter_option.selected' ).each( function() {

				var filter_type = $( this ).closest( '.component_filter' ).data( 'filter_type' );
				var filter_id   = $( this ).closest( '.component_filter' ).data( 'filter_id' );
				var option_id   = $( this ).data( 'option_id' );

				if ( filter_type in filters ) {

					if ( filter_id in filters[ filter_type ] ) {

						filters[ filter_type ][ filter_id ].push( option_id );

					} else {

						filters[ filter_type ][ filter_id ] = [];
						filters[ filter_type ][ filter_id ].push( option_id );
					}

				} else {

					filters[ filter_type ]              = {};
					filters[ filter_type ][ filter_id ] = [];
					filters[ filter_type ][ filter_id ].push( option_id );
				}

			} );

			return filters;
		};

		/**
		 * Append more component options via ajax - called upon sorting, updating filters, or viewing a new page.
		 */

		this.append_component_options = function( data ) {
			this.reload_component_options( data, true );
		};

		/**
		 * Update the available component options via ajax - called upon sorting, updating filters, or viewing a new page.
		 */

		this.reload_component_options = function( data, appending_results ) {

			var component               = this;
			var item                    = this.$self;
			var component_selections    = this.$component_selections;
			var component_options       = this.$component_options;
			var component_options_inner = this.$component_options_inner;
			var component_pagination    = this.$component_pagination;
			var load_height             = component_options.outerHeight();
			var new_height              = 0;
			var animate_height          = false;
			var reload                  = false;
			var delay                   = 250;

			var ajax_url                = wc_composite_params.use_wc_ajax === 'yes' ? composite.ajax_url.toString().replace( '%%endpoint%%', 'woocommerce_show_component_options' ) : composite.ajax_url;

			// Do nothing if the component is disabled.
			if ( item.hasClass( 'disabled' ) ) {
				return false;
			}

			if ( typeof( appending_results ) === 'undefined' ) {
				appending_results = false;
			}

			var animate_component_options = function() {

				var appended = {};

				if ( component.append_results() ) {
					appended = component_selections.find( '.appended' );
					appended.removeClass( 'appended' );
				}

				// Animate component options container.
				if ( animate_height ) {

					if ( ! appending_results ) {
						component_selections.removeClass( 'refresh_component_options blocked_content' );
					}

					component_options.animate( { 'height' : new_height }, { duration: 250, queue: false, always: function() {
						component_options.css( { 'height' : 'auto' } );
						component_selections.unblock();
						component_selections.removeClass( 'refresh_component_options blocked_content' );
					} } );

				} else {

					var release_delay = 5;

					if ( appending_results && appended.length > 0 ) {
						release_delay = 250;
					}

					setTimeout( function() {
						component_selections.unblock().removeClass( 'blocked_content refresh_component_options' );
					}, release_delay );
				}

			};

			// Block container.
			component_selections.addClass( 'blocked_content' ).block( wc_cp_block_params );

			// No wait for animations while reloading.
			if ( composite.append_results_nesting > 0 ) {
				delay = 5;
			}

			setTimeout( function() {

				// Get product info via ajax.
				$.post( ajax_url, data, function( response ) {

					// Fade thumbnails when reloading results.
					if ( ! appending_results ) {
						component_selections.addClass( 'refresh_component_options' );
					}

					setTimeout( function() {

						try {

							// Lock height.
							component_options.css( 'height', load_height );

							if ( response.result === 'success' ) {

								var component_options_select = component_options.find( 'select.component_options_select' );
								var current_selection_id     = component_options_select.val();

								var response_markup          = $( response.options_markup );

								var thumbnails_container     = component_options_inner.find( '.component_option_thumbnails_container' );
								var new_thumbnail_options    = response_markup.find( '.component_option_thumbnail_container' );

								if ( component.append_results() ) {
									new_thumbnail_options.addClass( 'new' );
									new_thumbnail_options.find( '.component_option_thumbnail' ).addClass( 'appended' );
								}

								// Put new content in place.
								if ( appending_results ) {

									// Reset select options.
									if ( typeof( component_options_select.data( 'select_options' ) ) !== 'undefined' && component_options_select.data( 'select_options' ) !== false ) {
										component_options_select.find( 'option:gt(0)' ).remove();
										component_options_select.append( component_options_select.data( 'select_options' ) );
										component_options_select.find( 'option:gt(0)' ).removeClass( 'disabled' );
										component_options_select.find( 'option:gt(0)' ).removeAttr( 'disabled' );
									}

									// Appending product thumbnails...
									var new_select_options      = response_markup.find( 'select.component_options_select option' );
									var default_selected_option = component_options_select.find( 'option[value="' + component.initial_selection_id + '"]' );

									// Clean up and merge the existing + newly loaded select options.
									new_select_options = new_select_options.filter( ':gt(0)' );

									if ( component.initial_selection_id > 0 && thumbnails_container.find( '#component_option_thumbnail_' + component.initial_selection_id ).length == 0 ) {

										default_selected_option.remove();

										if ( current_selection_id > 0 && thumbnails_container.find( '#component_option_thumbnail_' + current_selection_id ).length > 0 ) {
											new_select_options = new_select_options.not( ':selected' );
										}

									} else {

										new_select_options = new_select_options.not( ':selected' );
									}

									new_select_options.appendTo( component_options_select );

									// Append thumbnails.
									new_thumbnail_options.appendTo( thumbnails_container );

								} else {

									// Reloading product thumbnails...

									component_options_inner.html( $( response_markup ).find( '.component_options_inner' ).html() );

									component.initial_selection_id = component_options_select.val();
								}

								var thumbnail_images = component_options_inner.find( '.component_option_thumbnail_container:not(.hidden) img' );

								// Preload images before proceeding.
								var preload_images_then_show_component_options = function() {

									if ( thumbnail_images.length > 0 && thumbnails_container.is( ':visible' ) ) {

										var retry = false;

										thumbnail_images.each( function() {

											var image = $( this );

											if ( image.height() === 0 ) {
												retry = true;
												return false;
											}

										} );

										if ( retry ) {
											setTimeout( function() {
												preload_images_then_show_component_options();
											}, 100 );
										} else {
											show_component_options();
										}
									} else {
										show_component_options();
									}
								};

								var show_component_options = function() {

									var pages_left       = 0;
									var results_per_page = 0;
									var pages_loaded     = 0;
									var pages            = 0;
									var load_more;

									// Update pagination.
									if ( response.pagination_markup ) {

										component_pagination.html( $( response.pagination_markup ).html() );

										if ( component.append_results() ) {

											load_more        = component_pagination.find( '.component_options_load_more' );
											pages_loaded     = load_more.data( 'pages_loaded' );
											pages            = load_more.data( 'pages' );
											results_per_page = load_more.data( 'results_per_page' );

											pages_left       = pages - pages_loaded;
										}

										component_pagination.slideDown( 200 );

									} else {

										if ( component.append_results() ) {

											load_more        = component_pagination.find( '.component_options_load_more' );
											results_per_page = load_more.data( 'results_per_page' );

											load_more.data( 'pages_loaded', load_more.data( 'pages' ) );
										}

										component_pagination.slideUp( 200 );
									}

									// Reset options.
									component_options_select.data( 'select_options', false );

									// Update component scenarios with new data.
									var scenario_data = composite.$composite_data.data( 'scenario_data' );

									if ( appending_results ) {

										// Append product scenario data.
										$.each( response.component_scenario_data, function( product_id, product_in_scenarios ) {
											scenario_data.scenario_data[ data.component_id ][ product_id ] = product_in_scenarios;
										} );

									} else {

										// Replace product scenario data.
										scenario_data.scenario_data[ data.component_id ] = response.component_scenario_data;
									}

									// If the initial selection is not part of the result set, reset.
									// Should never happen - in thumbnails mode, the initial selection is always appended to the (hidden) dropdown.
									var initial_selection_id = current_selection_id;
									current_selection_id     = component_options_select.val();

									if ( initial_selection_id > 0 && ( current_selection_id === '' || typeof( current_selection_id ) === 'undefined' ) ) {

										component_options_select.change();

									} else {

										// Disable newly loaded products and variations.
										component.get_step().fire_scenario_actions();

										// Count how many of the newly loaded results are actually visible.
										if ( component.append_results() && component.hide_disabled_products() ) {

											var newly_added_thumbnails = thumbnails_container.find( '.component_option_thumbnail_container.new' );
											var of_which_visible       = newly_added_thumbnails.not( '.hidden' );

											newly_added_thumbnails.removeClass( 'new' );

											composite.append_results_nesting_count += of_which_visible.length;

											if ( composite.append_results_nesting_count < results_per_page && pages_left > 0 ) {

												composite.append_results_nesting++;
												reload = true;

												if ( composite.append_results_nesting > 10 ) {
													if ( window.confirm( wc_composite_params.i18n_reload_threshold_exceeded.replace( '%s', component.get_title() ) ) ) {
														composite.append_results_nesting = 0;
													} else {
														reload = false;
													}
												}
											}
										}

										// Update ui.
										if ( ! reload ) {

											composite.append_results_nesting_count = 0;
											composite.append_results_nesting       = 0;

											composite.update_ui();
										}
									}

									if ( ! reload ) {

										item.trigger( 'wc-composite-component-options-loaded', [ component, composite ] );

										// Measure height.
										new_height = component_options_inner.outerHeight( true );

										if ( Math.abs( new_height - load_height ) > 1 ) {
											animate_height = true;
										} else {
											component_options.css( 'height', 'auto' );
										}

										animate_component_options();

									} else {

										data.load_page++;

										component.append_component_options( data );
									}
								};

								if ( ! reload ) {
									preload_images_then_show_component_options();
								}

							} else {

								// Show failure message.
								component_options_inner.html( response.options_markup );

								// Measure height.
								new_height = component_options_inner.outerHeight( true );

								if ( Math.abs( new_height - load_height ) > 1 ) {
									animate_height = true;
								} else {
									component_options.css( 'height', 'auto' );
								}

								animate_component_options();
							}

						} catch ( err ) {

							// Show failure message.
							wc_cp_log( err );

							composite.append_results_nesting_count = 0;
							composite.append_results_nesting       = 0;

							animate_component_options();
						}

					}, delay );

				}, 'json' );

			}, delay );

		};

		/*
		 * Scripts run after selecting a new Component Option.
		 */

		this.updated_selection = function( product_id ) {

			var component = this;
			var step      = component.get_step();

			component.set_selection_id( product_id );

			if ( product_id ) {
				component.init_qty_input();
				component.init_scripts();
			} else {
				component.init_scripts( false );
			}

			step.fire_scenario_actions();

			composite.update_ui();
			composite.update_composite();

		};

		/*
		 * Move a relocated component_content container back to its original position.
		 */

		this.reset_relocated_content = function() {

			var component = self;

			if ( component.$component_content.hasClass( 'relocated' ) ) {

				// Move content to origin.
				component.$relocation_origin.after( component.$component_content );

				// Remove origin and relocation container.
				component.$relocation_origin.remove();
				component.$relocation_origin = false;
				component.$component_options.find( '.component_option_content_container' ).remove();

				if ( component.get_selected_product_id() > 0 ) {
					// Scroll to selected content.
					wc_cp_scroll_viewport( composite.$composite_form.find( '.scroll_show_component' ), { partial: false, duration: 250, queue: false } );
				} else {
					// Scroll to selections.
					wc_cp_scroll_viewport( component.$component_selections, { partial: false, duration: 250, queue: false } );
				}

				component.$component_content.removeClass( 'relocated' );
			}

		};

		/**
		 * Get relocation parameters for component_content containers, when allowed. Returns:
		 *
		 * - A thumbnail (list item) to be used as the relocation reference (the relocated content should be right after this element).
		 * - A boolean indicating whether the component_content container should be moved under the reference element.
		 */

		this.get_content_relocation_params = function() {

			var component                  = this;

			var relocate_component_content = false;
			var relocation_reference       = false;

			var selected_thumbnail         = component.$component_options.find( '.component_option_thumbnail.selected' ).closest( '.component_option_thumbnail_container' );
			var thumbnail_to_column_ratio  = selected_thumbnail.outerWidth( true ) / component.$component_options.outerWidth();
			var last_thumbnail_in_row      = ( selected_thumbnail.hasClass( 'last' ) || thumbnail_to_column_ratio > 0.6 ) ? selected_thumbnail : selected_thumbnail.nextAll( '.last' ).first();

			if ( last_thumbnail_in_row.length > 0 ) {
				relocation_reference = last_thumbnail_in_row;
			} else {
				relocation_reference = component.$component_options.find( '.component_option_thumbnail_container' ).last();
			}

			if ( relocation_reference.next( '.component_option_content_container' ).length === 0 ) {
				relocate_component_content = true;
			}

			return { reference: relocation_reference,  relocate: relocate_component_content };
		};

		/**
		 * Respond to selecting a new component option.
		 */

		this.select_component_option = function( data ) {

			var component                   = this;
			var component_selections        = component.$component_selections;
			var component_content           = component.$component_content;
			var component_summary           = component.$component_summary;
			var summary_content             = component.$self.find( '.component_summary > .content' );
			var style                       = composite.composite_layout;
			var scroll_to                   = component_content;
			var scroll_method               = 'middle';

			var load_height                 = component_summary.outerHeight( true );
			var new_height                  = 0;
			var animate_height              = false;

			var relocations_allowed         = false;
			var relocate_component_content  = false;
			var component_content_relocated = component_content.hasClass( 'relocated' );
			var relocation_reference        = false;
			var relocation_target           = false;

			var ajax_url                    = wc_composite_params.use_wc_ajax === 'yes' ? composite.ajax_url.toString().replace( '%%endpoint%%', 'woocommerce_show_composited_product' ) : composite.ajax_url;

			var response_received           = function( response ) {

				// Check if component_content div must be relocated.
				if ( relocate_component_content ) {

					component_content_relocated = true;

					component_content.addClass( 'relocated' );
					component_content.addClass( 'relocating' );

					relocation_target = $( '<li class="component_option_content_container">' );
					relocation_reference.after( relocation_target );

					// Animate component content height to 0 while scrolling as much as its height.
					// Then, update content.
					component_content.animate( { 'height': 0 }, { duration: 200, queue: false, always: function() {
						update_content( response );
					} } );

					if ( component_content.offset().top < relocation_target.offset().top ) {
						wc_cp_scroll_viewport( 'relative', { offset: load_height, timeout: 0, duration: 200, queue: false } );
					}

					load_height = 0;

				} else {

					// Lock height.
					component_content.css( 'height', load_height );

					// Process response content.
					update_content( response );
				}
			};

			var update_content = function( response ) {

				// Put content in place.
				summary_content.html( response.markup );

				// Relocate content.
				if ( relocate_component_content ) {
					component_content.appendTo( relocation_target );
					component.$component_options.find( '.component_option_content_container' ).not( relocation_target ).remove();
				}

				// Trigger scripts.
				if ( response.result === 'success' ) {
					component.updated_selection( data.product_id );
					component.$self.trigger( 'wc-composite-component-loaded', [ component, composite ] );
				} else {
					component.updated_selection( false );
				}

				var animation_delay = component.get_selected_product_type() === 'bundle' ? 300 : 250;

				setTimeout( function() {
					animate_updated_content();
				}, animation_delay );
			};

			var reset_content = function() {

				// Reset content.
				summary_content.html( '<div class="component_data" data-component_set="true" data-price="0" data-regular_price="0" data-product_type="none" style="display:none;"></div>' );

				// Remove appended navi and hide message if visible.
				if ( component.$self.find( '.composite_navigation.movable' ).length > 0 ) {
					composite.$composite_navigation_movable.addClass( 'hidden' );
				}

				component.reset_relocated_content();
				component.updated_selection( false );

			};

			var animate_updated_content = function() {

				// Measure height.
				new_height = component_summary.outerHeight( true );

				if ( relocate_component_content || Math.abs( new_height - load_height ) > 1 ) {
					animate_height = true;
				} else {
					component_content.css( 'height', 'auto' );
				}

				if ( component_content_relocated ) {
					component_content.removeClass( 'relocating' );
				}

				// Animate component content height and scroll to selected product details.
				if ( animate_height ) {

					// Animate component content height.
					component_content.animate( { 'height': new_height }, { duration: 200, queue: false, always: function() {

						// Scroll...
						wc_cp_scroll_viewport( scroll_to, { offset: 50, partial: false, scroll_method: scroll_method, duration: 200, queue: false, always_on_complete: true, on_complete: function() {

							// Reset height.
							component_content.css( { 'height' : 'auto' } );

							// Unblock component.
							component_selections.unblock().removeClass( 'blocked_content' );
							composite.has_transition_lock = false;

						} } );

					} } );

				} else {

					// Scroll.
					wc_cp_scroll_viewport( scroll_to, { offset: 50, partial: false, scroll_method: scroll_method, duration: 200, queue: false, always_on_complete: true, on_complete: function() {

						// Unblock component.
						component_selections.unblock().removeClass( 'blocked_content' );
						composite.has_transition_lock = false;

					} } );
				}

			};

			// Locate auto-scroll target.
			if ( style === 'paged' ) {
				if ( component.append_results() && component.$self.hasClass( 'options-style-thumbnails' ) ) {
					relocations_allowed = true;
				}
			}

			// Save initial location of component_content div.
			if ( relocations_allowed ) {
				if ( false === component.$relocation_origin ) {
					component.$relocation_origin = $( '<div class="component_content_origin">' );
					component_content.before( component.$relocation_origin );
				}
			}

			// Check if fetched component content will be relocated under current product thumbnail.
			if ( relocations_allowed && ( component_content_relocated || ! component_content.is_in_viewport( false ) ) ) {

				var relocation_params      = component.get_content_relocation_params();

				relocation_reference       = relocation_params.reference;
				relocate_component_content = relocation_params.relocate;
			}

			// Get the selected product data.
			if ( data.product_id !== '' ) {

				// Block component selections.
				component_selections.addClass( 'blocked_content' ).block( wc_cp_block_params );

				// Block composite transitions.
				composite.has_transition_lock = true;

				// Get product info via ajax.
				$.post( ajax_url, data, function( response ) {

					try {

						response_received( response );

					} catch ( err ) {

						// Show failure message...
						wc_cp_log( err );

						// Reset content.
						reset_content();

						animate_updated_content();
					}

				}, 'json' );

			} else {

				var animate = true;

				if ( component.$self.hasClass( 'resetting' ) ) {
					animate = false;
				}

				if ( animate ) {

					// Set to none just in case a script attempts to read this.
					component.$component_data.data( 'product_type', 'none' );

					// Allow the appended message container to remain visible.
					var navigation_movable_height = composite.$composite_navigation_movable.is( ':visible' ) ? composite.$composite_navigation_movable.outerHeight( true ) : 0;
					var reset_height              = component_content_relocated ? 0 : ( component_summary.outerHeight( true ) - summary_content.innerHeight() - navigation_movable_height );

					// Animate component content height.
					component_content.animate( { 'height': reset_height }, { duration: 200, queue: false, always: function() {

						// Reset content.
						reset_content();

						component_content.css( { 'height': 'auto' } );

					} } );

				} else {
					// Reset content.
					reset_content();
				}
			}

		};

		/**
		 * True if a Component is set as optional.
		 */

		this.is_optional = function() {

			return this._is_optional;

		};

		/**
		 * Set Component as optional.
		 */

		this.set_optional = function( optional ) {

			if ( optional && ! this._is_optional ) {
				this.$component_selections.find( 'option.none' ).html( wc_composite_params.i18n_none );
			} else if ( ! optional && this._is_optional ) {
				this.$component_selections.find( 'option.none' ).html( wc_composite_params.i18n_select_an_option );
			}

			this._is_optional = optional;

		};

		/**
		 * Initialize quantity input.
		 */

		this.init_qty_input = function() {

			// Quantity buttons.
			if ( wc_composite_params.is_wc_version_gte_2_3 === 'no' || wc_composite_params.show_quantity_buttons === 'yes' ) {
				this.$self.find( 'div.quantity:not(.buttons_added), td.quantity:not(.buttons_added)' ).addClass( 'buttons_added' ).append( '<input type="button" value="+" class="plus" />' ).prepend( '<input type="button" value="-" class="minus" />' );
			}

			// Target quantity inputs on product pages.
			this.$self.find( '.component_wrap input.qty' ).each( function() {

				var min = parseFloat( $( this ).attr( 'min' ) );

				if ( min >= 0 && parseFloat( $( this ).val() ) < min ) {
					$( this ).val( min );
				}

			} );

		};

	}

	/*
	 * Initialize form script.
	 */

	$( '.composite_form .composite_data' ).each( function() {
		$( this ).wc_composite_form();
	} );

} );
