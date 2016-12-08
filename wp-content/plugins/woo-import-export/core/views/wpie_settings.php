<?php 
	if (!defined('ABSPATH'))
    die("Can't load this file directly");
	
	global $wpie_product, $wpie_order, $wpie_user ,$wpie_import_export, $wpie_product_cat, $wpie_coupon;
	
	$wpie_hostname = $_SERVER["HTTP_HOST"]; 
	
	$plugin_data = $wpie_import_export -> get_wpie_sort_order();
	
	$updated_product_field = $wpie_product -> get_updated_product_fields();
	
	$updated_order_field = $wpie_order -> get_updated_order_fields();
	
	$updated_user_field = $wpie_user -> get_updated_user_fields();
	
	$updated_product_cat_field = $wpie_product_cat -> get_updated_product_cat_fields();
	
	$updated_coupon_field = $wpie_coupon -> get_updated_coupon_fields();
		
 ?>
<div class="wpie_product_export_wrapper wpie_product_export_settings_wrapper">
	<div class="wpie_product_export_belt_wrapper">
		<div class="wpie_product_export_belt wpie_product_title_belt wpie_selected"><?php _e('Settings',WPIE_TEXTDOMAIN);?></div>
	</div>
	<div class="wpie_product_export_container">
		<div class="wpie_product_export_inner_container">
			<div class="wpie_licence_settings_frm_success wpie_success_msg"><?php _e('License Activated Successfully.',WPIE_TEXTDOMAIN)?></div>
			<div class="wpie_success_msg wpie_save_fields"><?php _e('Changes Saved Successfully.',WPIE_TEXTDOMAIN)?></div>
			<div class="wpie_licence_settings_frm_error wpie_error_msg"><?php _e('Invalid Request.',WPIE_TEXTDOMAIN)?></div>
			<?php 
				if(isset($plugin_data['plugin_status']) && $plugin_data['plugin_status']=='active'){
					$active_style = 'display:none';
					$deactive_style = "";
				 }else{
					$active_style = '';
					$deactive_style = 'display:none';
				 }
			?>
			<form method="post" class="wpie_settings_purchase_frm" style=" <?php echo $active_style;?>">
				<div class="wpie_export_settings_field_container">
					<div class="wpie_export_settings_title"><div class="wpie_toggle_open"></div><div class="wpie_toggle_close"></div><?php _e('Product License',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_setting_field_outer_wrapper wpie_product_license_activate_outer_wrapper">
						<div class="wpie_license_notice"><?php _e('A valid license key entitles you to support and enable automatic upgrades.',WPIE_TEXTDOMAIN);?></div>
						<div class="wpie_export_settings_field_wrapper">
							<div class="wpie_export_settings_container">
								<div class="wpie_export_settings_purchase_title"><?php _e('Customer Name',WPIE_TEXTDOMAIN);?> *</div>
								<input type="text" class="wpie_export_settings_purchase_element wpie_product_customer_name" name="wpie_customer_name" placeholder="<?php _e('Customer Name',WPIE_TEXTDOMAIN);?>"/>
							</div>
						</div>
						<div class="wpie_export_settings_field_wrapper">
							<div class="wpie_export_settings_container">
								<div class="wpie_export_settings_purchase_title"><?php _e('Customer Email',WPIE_TEXTDOMAIN);?> *</div>
								<input type="text" class="wpie_export_settings_purchase_element wpie_product_customer_email" name="wpie_customer_email" placeholder="<?php _e('Customer Email',WPIE_TEXTDOMAIN);?>"/>
							</div>
						</div>
						<div class="wpie_export_settings_field_wrapper">
							<div class="wpie_export_settings_container">
								<div class="wpie_export_settings_purchase_title"><?php _e('Purchase Code',WPIE_TEXTDOMAIN);?> *</div>
								<input type="text" class="wpie_export_settings_purchase_element wpie_product_purchase_code" name="wpie_customer_purchase_code" placeholder="<?php _e('Purchase Code',WPIE_TEXTDOMAIN);?>"/>
							</div>
						</div>
						<div class="wpie_export_settings_field_wrapper">
							<div class="wpie_export_settings_container">
								<div class="wpie_export_settings_purchase_title"><?php _e('Domain Name',WPIE_TEXTDOMAIN);?> *</div>
								<div class="wpie_export_settings_domain_name"><?php echo $wpie_hostname;?></div>
								<input type="hidden" name="wpie_product_domain_name" value="<?php echo $wpie_hostname;?>">
							</div>
						</div>
						<div class="wpie_export_field_btn_container">
							<div class="wpie_export_field_btn_wrapper">
								<button class="wpie_settings_btn wpie_form_submit_btn wpie_activate_license" type="button"><?php _e('Activate',WPIE_TEXTDOMAIN);?></button>
								<div class="wpie_activation_loader"></div>
							</div>
						</div>
					</div>
				</div>
			</form>
			<form class="wpie_deactivate_licence_settings_frm" method="post" style=" <?php echo $deactive_style;?>">
				<div class="wpie_export_settings_field_container">
					<div class="wpie_export_settings_title"><div class="wpie_toggle_open"></div><div class="wpie_toggle_close"></div><?php _e('Product License',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_setting_field_outer_wrapper wpie_product_license_deactivate_outer_wrapper">
						<div class="wpie_export_settings_field_wrapper">
							<div class="wpie_export_settings_container">
								<div class="wpie_export_settings_purchase_title"><?php _e('License Status',WPIE_TEXTDOMAIN);?></div>
								<div class="wpie_product_licence_element">
									<div class="wpie_license_status"><?php _e('Active',WPIE_TEXTDOMAIN);?></div>
								</div>
							</div>
						</div>
						<div class="wpie_export_field_btn_container">
							<div class="wpie_export_field_btn_wrapper">
								<button class="wpie_settings_btn wpie_form_submit_btn wpie_deactivate_license" type="button"><?php _e('Deactivate',WPIE_TEXTDOMAIN);?></button>
								<div class="wpie_deactivation_loader"></div>
							</div>
						</div>
					</div>
				</div>
			</form>
			<form class="wpie_product_field_setting" method="post">
				<div class="wpie_export_settings_field_container">
					<div class="wpie_export_settings_title"><div class="wpie_toggle_open"></div><div class="wpie_toggle_close"></div><?php _e('Product Fields',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_setting_field_outer_wrapper wpie_product_field_element_outer">
						<div class="wpie_export_settings_field_wrapper">
							<div class="wpie_export_settings_container">
								<?php 
									foreach($updated_product_field as $new_product_field)
									{
										foreach($new_product_field as $key => $value){?>
										<div class="wpie_export_settings_field_wrapper">
											<div class="wpie_export_settings_container">
												<div class="wpie_export_settings_field_title"><?php echo $value['field_title'];?></div>
												<input type="text" class="wpie_export_settings_field_element" name="<?php echo 'wpie_'.$value['field_key'].'_field';?>" placeholder="<?php echo $value['field_title'];?>" value="<?php echo $value['field_value'];?>"/>
											</div>
										</div>
										<?php }
									}
								?>
							</div>
						</div>
						<div class="wpie_export_field_btn_container">
							<div class="wpie_export_field_btn_wrapper">
								<button class="wpie_settings_btn wpie_form_submit_btn wpie_product_field_save" type="button"><?php _e('Save',WPIE_TEXTDOMAIN);?></button>
								<div class="wpie_product_field"></div>
							</div>
						</div>
					</div>
				</div>
			</form>
			<form class="wpie_order_field_setting" method="post">
				<div class="wpie_export_settings_field_container">
					<div class="wpie_export_settings_title"><div class="wpie_toggle_open"></div><div class="wpie_toggle_close"></div><?php _e('Order Fields',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_setting_field_outer_wrapper wpie_order_field_element_outer">
						<div class="wpie_export_settings_field_wrapper">
							<div class="wpie_export_settings_container">
								<?php
								foreach($updated_order_field as $new_order_field){
									 foreach($new_order_field  as $key => $value){?>
										<div class="wpie_export_settings_field_wrapper">
											<div class="wpie_export_settings_container">
												<div class="wpie_export_settings_field_title"><?php echo $value['field_title'];?></div>
												<input type="text" class="wpie_export_settings_field_element" name="<?php echo 'wpie_'.$value['field_key'].'_field';?>" placeholder="<?php echo $value['field_title'];?>" value="<?php echo $value['field_value'];?>"/>
											</div>
										</div>
									<?php }
								}
								?>
							</div>
						</div>
						<div class="wpie_export_field_btn_container">
							<div class="wpie_export_field_btn_wrapper">
								<button class="wpie_settings_btn wpie_form_submit_btn wpie_order_field_save" type="button"><?php _e('Save',WPIE_TEXTDOMAIN);?></button>
								<div class="wpie_order_field"></div>
							</div>
						</div>
					</div>
				</div>
			</form>
			<form class="wpie_user_field_setting" method="post">
				<div class="wpie_export_settings_field_container">
					<div class="wpie_export_settings_title"><div class="wpie_toggle_open"></div><div class="wpie_toggle_close"></div><?php _e('User Fields',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_setting_field_outer_wrapper wpie_order_field_element_outer">
						<div class="wpie_export_settings_field_wrapper">
							<div class="wpie_export_settings_container">
								<?php
								foreach($updated_user_field as $new_user_field){
									 foreach($new_user_field  as $key => $value){?>
										<div class="wpie_export_settings_field_wrapper">
											<div class="wpie_export_settings_container">
												<div class="wpie_export_settings_field_title"><?php echo $value['field_title'];?></div>
												<input type="text" class="wpie_export_settings_field_element" name="<?php echo 'wpie_'.$value['field_key'].'_field';?>" placeholder="<?php echo $value['field_title'];?>" value="<?php echo $value['field_value'];?>"/>
											</div>
										</div>
									<?php }
								}
								?>
							</div>
						</div>
						<div class="wpie_export_field_btn_container">
							<div class="wpie_export_field_btn_wrapper">
								<button class="wpie_settings_btn wpie_form_submit_btn wpie_user_field_save" type="button"><?php _e('Save',WPIE_TEXTDOMAIN);?></button>
								<div class="wpie_user_field"></div>
							</div>
						</div>
					</div>
				</div>
			</form>
			<form class="wpie_product_cat_field_setting" method="post">
				<div class="wpie_export_settings_field_container">
					<div class="wpie_export_settings_title"><div class="wpie_toggle_open"></div><div class="wpie_toggle_close"></div><?php _e('Product Category Fields',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_setting_field_outer_wrapper wpie_product_field_element_outer">
						<div class="wpie_export_settings_field_wrapper">
							<div class="wpie_export_settings_container">
								<?php 
									foreach($updated_product_cat_field as $new_product_cat_field)
									{
										foreach($new_product_cat_field as $key => $value){?>
										<div class="wpie_export_settings_field_wrapper">
											<div class="wpie_export_settings_container">
												<div class="wpie_export_settings_field_title"><?php echo $value['field_title'];?></div>
												<input type="text" class="wpie_export_settings_field_element" name="<?php echo 'wpie_'.$value['field_key'].'_field';?>" placeholder="<?php echo $value['field_title'];?>" value="<?php echo $value['field_value'];?>"/>
											</div>
										</div>
										<?php }
									}
								?>
							</div>
						</div>
						<div class="wpie_export_field_btn_container">
							<div class="wpie_export_field_btn_wrapper">
								<button class="wpie_settings_btn wpie_form_submit_btn wpie_product_cat_field_save" type="button"><?php _e('Save',WPIE_TEXTDOMAIN);?></button>
								<div class="wpie_product_field"></div>
							</div>
						</div>
					</div>
				</div>
			</form>
			<form class="wpie_coupon_field_setting" method="post">
				<div class="wpie_export_settings_field_container">
					<div class="wpie_export_settings_title"><div class="wpie_toggle_open"></div><div class="wpie_toggle_close"></div><?php _e('Coupon Fields',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_setting_field_outer_wrapper wpie_product_field_element_outer">
						<div class="wpie_export_settings_field_wrapper">
							<div class="wpie_export_settings_container">
								<?php 
									foreach($updated_coupon_field as $new_coupon_field)
									{
										foreach($new_coupon_field as $key => $value){?>
										<div class="wpie_export_settings_field_wrapper">
											<div class="wpie_export_settings_container">
												<div class="wpie_export_settings_field_title"><?php echo $value['field_title'];?></div>
												<input type="text" class="wpie_export_settings_field_element" name="<?php echo 'wpie_'.$value['field_key'].'_field';?>" placeholder="<?php echo $value['field_title'];?>" value="<?php echo $value['field_value'];?>"/>
											</div>
										</div>
										<?php }
									}
								?>
							</div>
						</div>
						<div class="wpie_export_field_btn_container">
							<div class="wpie_export_field_btn_wrapper">
								<button class="wpie_settings_btn wpie_form_submit_btn wpie_coupon_field_save" type="button"><?php _e('Save',WPIE_TEXTDOMAIN);?></button>
								<div class="wpie_product_field"></div>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<div class="wpie_documantation_links_wrapper">
	<div class="wpie_documantation_links_outer">
		<a target="_blank" href="<?php echo WPIE_PLUGIN_URL.'/documentation/';?>"><?php _e('Documentation',WPIE_TEXTDOMAIN);?></a> |  <a target="_blank" href="http://www.vjinfotech.com/support"><?php _e('Support',WPIE_TEXTDOMAIN);?></a>
	</div>
</div>