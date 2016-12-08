<?php 
	if (!defined('ABSPATH'))
    die("Can't load this file directly");
	
	global $wpie_product,$download_product_errors,$new_product_errors;
	
	$product_cat = $wpie_product -> wpie_get_product_category();
	
 ?>
<div class="wpie_product_export_wrapper">
	<div class="wpie_product_export_belt_wrapper">
		<div class="wpie_product_export_belt wpie_product_import_belt  wpie_selected"><?php _e('Products',WPIE_TEXTDOMAIN);?></div>
		<div class="wpie_product_export_belt wpie_order_import_belt"><?php _e('Orders',WPIE_TEXTDOMAIN);?></div>
		<div class="wpie_product_export_belt wpie_user_import_belt"><?php _e('Users',WPIE_TEXTDOMAIN);?></div>
		<div class="wpie_product_export_belt wpie_product_category_import_belt"><?php _e('Product Categories',WPIE_TEXTDOMAIN);?></div>
		<div class="wpie_product_export_belt wpie_coupon_import_belt"><?php _e('Coupons',WPIE_TEXTDOMAIN);?></div>
	</div>
	<div class="wpie_product_export_container">
		<div class="wpie_product_export_inner_container">
			<div class="wpie_success_msg wpie_import_success_msg"><?php _e('Product Imported successfully.',WPIE_TEXTDOMAIN);?></div>
			<div class="wpie_error_msg wpie_import_error_msg"></div>
			<div class="wpie_process_bar_wrapper">
				<span class="wpie_process_bar"></span>
				<div class="wpie_process_bar_process">0%</div>
			</div>
 			<div id="wpie_targetLayer"></div>
			<form method="post" class="wpie_product_import_frm wpie_data_import_frm" enctype="multipart/form-data">
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Select File to Import',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<input type="file" name="wpie_import_file" class="wpie_export_field_file_element wpie_import_file"/>
						<div class="wpie_default_notice"><?php _e('Note : Select only this plugin exported file.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Enter URL to Import',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<input type="text" name="wpie_import_file_url" class="wpie_export_field_input_element wpie_import_file_url" placeholder="<?php _e('Enter URL',WPIE_TEXTDOMAIN);?>"/>
						<div class="wpie_default_notice"><?php _e('Note : Leave blank if upload file.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Select Category for Product',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<select class="wpie_export_field_select_element" name="wpie_product_category[]"  data-placeholder="<?php _e('Select Product Category',WPIE_TEXTDOMAIN);?>" multiple="multiple">
							<?php foreach($product_cat as $cat){?>
								<option value="<?php echo $cat->name;?>"><?php echo '('.__('ID',WPIE_TEXTDOMAIN).' : '.$cat->term_id.') '. $cat->name;?></option>
								<?php }?>
 						</select>
						<div class="wpie_default_notice"><?php _e('Note : Category for new product imported.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Product Create / Update / Skip',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<select class="wpie_export_field_select_element" name="wpie_product_create_method"  data-placeholder="<?php _e('Select Product Create',WPIE_TEXTDOMAIN);?>" >
								<option value="skip_product"><?php _e('Skip Product if Exist.',WPIE_TEXTDOMAIN);?></option>
								<option value="create_product"><?php _e('Create New Product and ignore if Exist.',WPIE_TEXTDOMAIN);?></option>
								<option value="update_product"><?php _e('Update Product if Exist.',WPIE_TEXTDOMAIN);?></option>
								
 						</select>
						<div class="wpie_default_notice"><?php _e('Note : Imported product is skip, updated or created if already exist.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_btn_container">
					<div class="wpie_export_field_btn_wrapper">
						<button class="wpie_product_import_btn wpie_form_submit_btn" type="button"><?php _e('Import',WPIE_TEXTDOMAIN);?></button><div class="wpie_loader_icon_wrapper" id="loader-icon" ><img class="wpie_loader_icon" src="<?php echo WPIE_IMAGES_URL;?>/wpie_loader.gif" /></div>
					</div>
				</div>
			</form>
			<form method="post" class="wpie_order_import_frm wpie_data_import_frm" style="display:none;" enctype="multipart/form-data">
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Select File to Import',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<input type="file" name="wpie_import_file" class="wpie_export_field_file_element wpie_import_file"/>
						<div class="wpie_default_notice"><?php _e('Note : Select only this plugin exported file.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Enter URL to Import',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<input type="text" name="wpie_import_file_url" class="wpie_export_field_input_element wpie_import_file_url" placeholder="<?php _e('Enter URL',WPIE_TEXTDOMAIN);?>"/>
						<div class="wpie_default_notice"><?php _e('Note : Leave blank if upload file.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Order Create / Update / Skip',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<select class="wpie_export_field_select_element" name="wpie_order_create_option"  data-placeholder="<?php _e('Select order create option',WPIE_TEXTDOMAIN);?>" >
								<option value="skip_order"><?php _e('Skip Order if Exist.',WPIE_TEXTDOMAIN);?></option>
								<option value="create_order"><?php _e('Create New Order and ignore if Exist.',WPIE_TEXTDOMAIN);?></option>
								<option value="update_order"><?php _e('Update Order if Exist.',WPIE_TEXTDOMAIN);?></option>
								
 						</select>
						<div class="wpie_default_notice"><?php _e('Note : Imported order is created, updated or skip if already exist.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_order_import_notice_wrapper">
					<div class="wpie_default_import_notice"><?php _e('Note : Product SKU or ID must be same for import and export both side.',WPIE_TEXTDOMAIN);?></div>
				</div>
				<div class="wpie_export_field_btn_container">
					<div class="wpie_export_field_btn_wrapper">
						<button class="wpie_order_import_btn wpie_form_submit_btn" type="button"><?php _e('Import',WPIE_TEXTDOMAIN);?></button><div class="wpie_loader_icon_wrapper" id="loader-icon" ><img class="wpie_loader_icon" src="<?php echo WPIE_IMAGES_URL;?>/wpie_loader.gif" /></div>
					</div>
				</div>
			</form>
			<form method="post" class="wpie_user_import_frm wpie_data_import_frm" style="display:none;" enctype="multipart/form-data">
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Select File to Import',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<input type="file" name="wpie_import_file" class="wpie_export_field_file_element wpie_import_file"/>
						<div class="wpie_default_notice"><?php _e('Note : Select only this plugin exported file.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Enter URL to Import',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<input type="text" name="wpie_import_file_url" class="wpie_export_field_input_element wpie_import_file_url" placeholder="<?php _e('Enter URL',WPIE_TEXTDOMAIN);?>"/>
						<div class="wpie_default_notice"><?php _e('Note : Leave blank if upload file.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('User Create / Update / Skip',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<select class="wpie_export_field_select_element" name="wpie_user_create_option"  data-placeholder="<?php _e('Select user create option',WPIE_TEXTDOMAIN);?>" >
								<option value="skip_user"><?php _e('Skip User if Exist.',WPIE_TEXTDOMAIN);?></option>
								<?php /*?><option value="create_user"><?php _e('Create New User and ignore if Exist.',WPIE_TEXTDOMAIN);?></option><?php */?>
								<option value="update_user"><?php _e('Update User if Exist.',WPIE_TEXTDOMAIN);?></option>
								
 						</select>
						<div class="wpie_default_notice"><?php _e('Note : Imported user is created, updated or skip if already exist.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_btn_container">
					<div class="wpie_export_field_btn_wrapper">
						<button class="wpie_user_import_btn wpie_form_submit_btn" type="button"><?php _e('Import',WPIE_TEXTDOMAIN);?></button><div class="wpie_loader_icon_wrapper" id="loader-icon" ><img class="wpie_loader_icon" src="<?php echo WPIE_IMAGES_URL;?>/wpie_loader.gif" /></div>
					</div>
				</div>
			</form>
			<form method="post" class="wpie_product_cat_import_frm wpie_data_import_frm" style="display:none;" enctype="multipart/form-data">
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Select File to Import',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<input type="file" name="wpie_import_file" class="wpie_export_field_file_element wpie_import_file"/>
						<div class="wpie_default_notice"><?php _e('Note : Select only this plugin exported file.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Enter URL to Import',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<input type="text" name="wpie_import_file_url" class="wpie_export_field_input_element wpie_import_file_url" placeholder="<?php _e('Enter URL',WPIE_TEXTDOMAIN);?>"/>
						<div class="wpie_default_notice"><?php _e('Note : Leave blank if upload file.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Product Category Update / Skip',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<select class="wpie_export_field_select_element" name="wpie_product_cat_create_method"  data-placeholder="<?php _e('Select Product Category Create',WPIE_TEXTDOMAIN);?>" >
								<option value="skip_product_cat"><?php _e('Skip Product Category if Exist.',WPIE_TEXTDOMAIN);?></option>
								<!--<option value="create_product_cat"><?php _e('Create New Product Category and ignore if Exist.',WPIE_TEXTDOMAIN);?></option>-->
								<option value="update_product_cat"><?php _e('Update Product Category if Exist.',WPIE_TEXTDOMAIN);?></option>
								
 						</select>
						<div class="wpie_default_notice"><?php _e('Note : Imported product category is skip or updated if already exist.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_btn_container">
					<div class="wpie_export_field_btn_wrapper">
						<button class="wpie_product_cat_import_btn wpie_form_submit_btn" type="button"><?php _e('Import',WPIE_TEXTDOMAIN);?></button><div class="wpie_loader_icon_wrapper" id="loader-icon" ><img class="wpie_loader_icon" src="<?php echo WPIE_IMAGES_URL;?>/wpie_loader.gif" /></div>
					</div>
				</div>
			</form>
			<form method="post" class="wpie_coupon_import_frm wpie_data_import_frm" style="display:none;" enctype="multipart/form-data">
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Select File to Import',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<input type="file" name="wpie_import_file" class="wpie_export_field_file_element wpie_import_file"/>
						<div class="wpie_default_notice"><?php _e('Note : Select only this plugin exported file.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Enter URL to Import',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<input type="text" name="wpie_import_file_url" class="wpie_export_field_input_element wpie_import_file_url" placeholder="<?php _e('Enter URL',WPIE_TEXTDOMAIN);?>"/>
						<div class="wpie_default_notice"><?php _e('Note : Leave blank if upload file.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Coupon Update / Skip',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<select class="wpie_export_field_select_element" name="wpie_coupon_create_method"  data-placeholder="<?php _e('Select Coupon Create',WPIE_TEXTDOMAIN);?>" >
								<option value="skip_coupon"><?php _e('Skip Coupon if Exist.',WPIE_TEXTDOMAIN);?></option>
								<!--<option value="create_coupon"><?php _e('Create New Coupon and ignore if Exist.',WPIE_TEXTDOMAIN);?></option>-->
								<option value="update_coupon"><?php _e('Update Coupon if Exist.',WPIE_TEXTDOMAIN);?></option>
								
 						</select>
						<div class="wpie_default_notice"><?php _e('Note : Imported Coupon is skip or updated if already exist.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_btn_container">
					<div class="wpie_export_field_btn_wrapper">
						<button class="wpie_coupon_import_btn wpie_form_submit_btn" type="button"><?php _e('Import',WPIE_TEXTDOMAIN);?></button><div class="wpie_loader_icon_wrapper" id="loader-icon" ><img class="wpie_loader_icon" src="<?php echo WPIE_IMAGES_URL;?>/wpie_loader.gif" /></div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<div class="wpie_import_preview_wrapper"></div>
<div class="wpie_documantation_links_wrapper">
	<div class="wpie_documantation_links_outer">
		<a target="_blank" href="<?php echo WPIE_PLUGIN_URL.'/documentation/';?>"><?php _e('Documentation',WPIE_TEXTDOMAIN);?></a> |  <a target="_blank" href="http://www.vjinfotech.com/support"><?php _e('Support',WPIE_TEXTDOMAIN);?></a>
	</div>
</div>