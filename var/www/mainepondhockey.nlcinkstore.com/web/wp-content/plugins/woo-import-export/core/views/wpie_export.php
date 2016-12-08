<?php 
	if (!defined('ABSPATH'))
    die("Can't load this file directly");
	
	global $wpie_product, $wpie_order, $wpie_user, $wpie_product_cat, $wpie_coupon;
	
	$product_cat = $wpie_product -> wpie_get_product_category();

	$product_list = $wpie_product -> wpie_get_product();
	
	$author_list = $wpie_product -> wpie_get_author_list();
	
	$order_status = $wpie_order -> get_woo_order_status();
	
	$order_ids = $wpie_order -> get_order_list();
	
	$get_schedules_list = wp_get_schedules();
	
	$user_list = $wpie_user -> get_user_list();
	
	$coupon_list = $wpie_coupon -> get_coupon_list();
	
	$product_total = count($product_list)<2000?count($product_list):'2000+';
	
	$order_total = count($order_ids)<2000?count($order_ids):'2000+';
	
	$user_total = count($user_list)<2000?count($user_list):'2000+';
	
	$product_cat_total = count($product_cat)<2000?count($product_cat):'2000+';
	
	$coupon_total = count($coupon_list)<2000?count($coupon_list):'2000+';

	
?>
<div class="wpie_product_export_wrapper">
	<div class="wpie_product_export_belt_wrapper">
		<div class="wpie_product_export_belt wpie_product_title_belt wpie_selected">
			<?php _e('Products',WPIE_TEXTDOMAIN);?>
			<div class="wpie_total_export_count"><?php echo $product_total;?></div>
		</div>
		<div class="wpie_product_export_belt wpie_order_title_belt">
			<?php _e('Orders',WPIE_TEXTDOMAIN);?>
			<div class="wpie_total_export_count"><?php echo $order_total;?></div>
		</div>
		<div class="wpie_product_export_belt wpie_user_title_belt">
			<?php _e('Users',WPIE_TEXTDOMAIN);?>
			<div class="wpie_total_export_count"><?php echo $user_total;?></div>
		</div>
		<div class="wpie_product_export_belt wpie_product_cat_title_belt">
			<?php _e('Product Categories',WPIE_TEXTDOMAIN);?>
			<div class="wpie_total_export_count"><?php echo $product_cat_total;?></div>
		</div>
		<div class="wpie_product_export_belt wpie_coupons_title_belt">
			<?php _e('Coupons',WPIE_TEXTDOMAIN);?>
			<div class="wpie_total_export_count"><?php echo $coupon_total;?></div>
		</div>
	</div>
	<div class="wpie_product_export_container">
		<div class="wpie_product_export_inner_container">
			<div class="wpie_success_msg wpie_scheduled_export_success_msg"><?php _e('Export Data Successfully Scheduled.',WPIE_TEXTDOMAIN);?></div>
			<form method="post" class="wpie_product_export_frm wpie_all_export_frm">
				<input type="hidden" value="0" name="wpie_product_export_verify" class="wpie_product_export_verify" />
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Filter By Product Category',WPIE_TEXTDOMAIN);?> <div class="wpie_field_tital_recordes">(<?php echo  __('Total Categories',WPIE_TEXTDOMAIN).' : '.$product_cat_total;?>)</div></div>
					<div class="wpie_export_field_wrapper">
						<select class="wpie_export_field_select_element" name="wpie_product_category[]" multiple="multiple" data-placeholder="<?php _e('Select Product Category',WPIE_TEXTDOMAIN);?>">
							<?php foreach($product_cat as $cat){?>
								<option value="<?php echo $cat->term_id;?>"><?php echo '('.__('ID',WPIE_TEXTDOMAIN).' : '.$cat->term_id.') '. $cat->name;?> (<?php echo  __('Total Products',WPIE_TEXTDOMAIN).' : '.$cat->count;?>)</option>
								<?php }?>
 						</select>
						<div class="wpie_default_notice"><?php _e('Default : All Categories.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Filter By Product ID / Name',WPIE_TEXTDOMAIN);?>  <div class="wpie_field_tital_recordes">(<?php echo __('Total Products',WPIE_TEXTDOMAIN).' : '.$product_total;?>)</div></div>
					<div class="wpie_export_field_wrapper">
						<select class="wpie_export_field_select_element" name="wpie_product_ids[]" multiple="multiple" data-placeholder="<?php _e('Select Product',WPIE_TEXTDOMAIN);?>">
							<?php foreach($product_list as $product_data){?>
								<option value="<?php echo $product_data->ID;?>"><?php echo '('.__('ID',WPIE_TEXTDOMAIN).' : '.$product_data->ID.') '.$product_data->post_title;?></option>
								<?php }?>
 						</select>
						<div class="wpie_default_notice"><?php _e('Default : All Products.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Filter By Product Author Name / Email',WPIE_TEXTDOMAIN);?>  <div class="wpie_field_tital_recordes">(<?php echo __('Total Authors',WPIE_TEXTDOMAIN).' : '.$user_total;?>)</div></div>
					<div class="wpie_export_field_wrapper">
						<select class="wpie_export_field_select_element" name="wpie_product_author_ids[]" multiple="multiple" data-placeholder="<?php _e('Select Product Author',WPIE_TEXTDOMAIN);?>">
							<?php foreach($author_list as $author_data){?>
								<option value="<?php echo $author_data->ID;?>"><?php echo $author_data->display_name.' ( '.$author_data->user_email.' )';?></option>
								<?php }?>
 						</select>
						<div class="wpie_default_notice"><?php _e('Default : All Authors.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Limit Records',WPIE_TEXTDOMAIN);?> <div class="wpie_field_tital_recordes"> (<?php echo __('Total Records',WPIE_TEXTDOMAIN).' : '.$product_total;?>)</div></div>
					<div class="wpie_export_field_wrapper">
						<input type="text" class="wpie_export_field_input_element" name="wpie_total_records" placeholder="<?php _e('Enter Limit Records',WPIE_TEXTDOMAIN);?>"/>
						<div class="wpie_default_notice"><?php _e('Default : All Records.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Offset Records',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<input type="text" class="wpie_export_field_input_element" name="wpie_offset_records" placeholder="<?php _e('Enter Offset Records',WPIE_TEXTDOMAIN);?>"/>
						<div class="wpie_default_notice"><?php _e('Default : 0.',WPIE_TEXTDOMAIN);echo " ";_e('Note : Fetch Records after XX Records.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Filter By Date',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<div class="wpie_export_start_date_field_wrapper">
							<input type="text" class="wpie_export_field_input_element wpie_export_field_date_element" name="wpie_start_date" placeholder="<?php _e('Start Date',WPIE_TEXTDOMAIN);?>"/>
							<div class="wpie_default_notice"><?php _e('Date Format',WPIE_TEXTDOMAIN);?> : mm-dd-yyyy</div>
						</div>
						<div class="wpie_export_end_date_field_wrapper">
							<input type="text" class="wpie_export_field_input_element wpie_export_field_date_element" name="wpie_end_date" placeholder="<?php _e('End Date',WPIE_TEXTDOMAIN);?>"/>
							<div class="wpie_default_notice"><?php _e('Date Format',WPIE_TEXTDOMAIN);?> : mm-dd-yyyy</div>
						</div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Scheduled Export',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<input type="checkbox" id="wpie_product_scheduled_export" class="wpie_export_field_input_element wpie_scheduled_export_check_element" name="wpie_product_scheduled_export" placeholder="<?php _e('Enter Offset Records',WPIE_TEXTDOMAIN);?>"/><label for="wpie_product_scheduled_export" class="wpie_product_scheduled_export_label"><?php _e('Scheduled Export',WPIE_TEXTDOMAIN);?></label>
						<div class="wpie_scheduled_export_wrapper">
							<div class="wpie_scheduled_export_outer_details">
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Export Interval',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<select class="wpie_export_field_select_element" data-placeholder="<?php _e('Select Interval',WPIE_TEXTDOMAIN);?>" name="wpie_export_interval">
											<?php foreach($get_schedules_list as $key=>$value){?>
											<option value="<?php echo $key;?>"><?php echo $value['display'];?></option>
											<?php }?>
										</select>
									</div>
								</div>
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Send E-mail',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<input type="checkbox" class="wpie_export_field_input_element wpie_scheduled_send_email" name="wpie_product_scheduled_send_email" value="1"/>
										<div class="wpie_default_notice"><?php _e('Send E-mail with attachment.',WPIE_TEXTDOMAIN);?></div>
									</div>
								</div>
							</div>
							<div class="wpie_scheduled_export_email_details">
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Enter Email Recipient(s)',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<input class="wpie_export_field_input_element wpie_scheduled_export_email_recipients" type="text" placeholder="<?php _e('Enter Email Recipient(s)',WPIE_TEXTDOMAIN);?>" name="wpie_scheduled_export_email_recipients">
										<div class="wpie_default_notice">Exa. example@gmail.com, demo@yahoo.com</div>
									</div>
								</div>
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Enter Email Subject',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<input class="wpie_export_field_input_element wpie_scheduled_export_email_subject" type="text" placeholder="<?php _e('Enter Email Subject',WPIE_TEXTDOMAIN);?>" name="wpie_scheduled_export_email_subject">
									</div>
								</div>
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Enter Email message',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<textarea class="wpie_scheduled_export_email_content wpie_scheduled_export_text_area" name="wpie_scheduled_export_email_content" placeholder="<?php _e('Enter Email message',WPIE_TEXTDOMAIN);?>"></textarea>
									</div>
								</div>
							</div>
						</div>
 					</div>
						
				</div>
				<div class="wpie_export_field_btn_container">
					<div class="wpie_export_field_btn_wrapper">
						
						<button class="wpie_product_preview_btn wpie_form_submit_btn" type="button"><div class="wpie_ajax_loader"></div><?php _e('Preview',WPIE_TEXTDOMAIN);?></button>
						<button class="wpie_product_export_btn wpie_form_submit_btn" type="button"><?php _e('Export',WPIE_TEXTDOMAIN);?></button>
						<button class="wpie_product_scheduled_export_btn wpie_scheduled_export_btn wpie_form_submit_btn" type="button"><?php _e('Save Scheduled',WPIE_TEXTDOMAIN);?></button>
					</div>
				</div>
			</form>
			<form class="wpie_order_export_frm wpie_all_export_frm " method="post" name="wpie_order_export_data">
				<input type="hidden" value="0" class="wpie_ordert_export_verify" name="wpie_ordert_export_verify"/>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Filter By Order Status',WPIE_TEXTDOMAIN);?> <div class="wpie_field_tital_recordes"> (<?php echo __('Total Status',WPIE_TEXTDOMAIN).' : '.count($order_status);?>)</div></div>
					<div class="wpie_export_field_wrapper">
						<select class="wpie_export_field_select_element" name="wpie_order_status[]" multiple="multiple" data-placeholder="<?php _e('Select Order Status',WPIE_TEXTDOMAIN);?>">
 							<?php 
								if(function_exists('wc_get_order_statuses'))
								{
									
									global $wpdb;
									
									foreach ($order_status as $key=>$value){
									
										$total_query = ' SELECT COUNT(*) as nb from '.$wpdb->prefix.'posts where post_status="'.$key.'" and post_type="shop_order" ';
				
											$total = $wpdb->get_var($total_query);
										
										?>
										<option value="<?php echo $key;?>" ><?php echo $value; ?> (<?php echo $total; ?>)</option>
										
									<?php 
									}
								}
								else
								{	
									
								 foreach ($order_status as $status){ ?>
									<option value="<?php echo $status->term_id; ?>" ><?php _e($status->name, 'woocommerce'); ?> (<?php echo $status->count;?>)</option>				
								<?php } 
								
								}
								?>
  						</select>
						<div class="wpie_default_notice"><?php _e('Default : All Status',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Filter By Product Category',WPIE_TEXTDOMAIN);?><div class="wpie_field_tital_recordes"> (<?php echo __('Total Categories',WPIE_TEXTDOMAIN).' : '.$product_cat_total;?>)</div></div>
					<div class="wpie_export_field_wrapper">
						<select class="wpie_export_field_select_element" name="wpie_product_category[]" multiple="multiple" data-placeholder="<?php _e('Select Product Category',WPIE_TEXTDOMAIN);?>">
							<?php foreach($product_cat as $cat){?>
								<option value="<?php echo $cat->term_id;?>"><?php echo '('.__('ID',WPIE_TEXTDOMAIN).' : '.$cat->term_id.') '. $cat->name;?> (<?php echo  __('Total Products',WPIE_TEXTDOMAIN).' : '.$cat->count;?>)</option>
								<?php }?>
 						</select>
						<div class="wpie_default_notice"><?php _e('Default : All Category.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Filter By Product ID / Name',WPIE_TEXTDOMAIN);?><div class="wpie_field_tital_recordes"> (<?php echo __('Total Products',WPIE_TEXTDOMAIN).' : '.$product_total;?>)</div></div>
					<div class="wpie_export_field_wrapper">
						<select class="wpie_export_field_select_element" name="wpie_product_ids[]" multiple="multiple" data-placeholder="<?php _e('Select Product',WPIE_TEXTDOMAIN);?>">
							<?php foreach($product_list as $product_data){?>
								<option value="<?php echo $product_data->ID;?>"><?php echo '('.__('ID',WPIE_TEXTDOMAIN).' : '.$product_data->ID.') '.$product_data->post_title;?></option>
								<?php }?>
 						</select>
						<div class="wpie_default_notice"><?php _e('Default : All Product.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Filter By Order Id',WPIE_TEXTDOMAIN);?><div class="wpie_field_tital_recordes"> (<?php echo __('Total Order',WPIE_TEXTDOMAIN).' : '.$order_total;?>)</div></div>
					<div class="wpie_export_field_wrapper">
						<select class="wpie_export_field_select_element" name="wpie_order_ids[]" multiple="multiple" data-placeholder="<?php _e('Select Order ID',WPIE_TEXTDOMAIN);?>">
							<?php foreach($order_ids as $order_data){?>
								<option value="<?php echo $order_data;?>"><?php _e('Order ID :',WPIE_TEXTDOMAIN)?> <?php echo $order_data;?></option>
								<?php }?>
 						</select>
						<div class="wpie_default_notice"><?php _e('Default : All Order ID.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
 				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Filter By Date',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<div class="wpie_export_start_date_field_wrapper">
							<input type="text" class="wpie_export_field_input_element wpie_export_field_date_element" name="wpie_start_date" placeholder="<?php _e('Start Date',WPIE_TEXTDOMAIN);?>"/>
							<div class="wpie_default_notice"><?php _e('Date Format',WPIE_TEXTDOMAIN);?> : mm-dd-yyyy</div>
						</div>
						<div class="wpie_export_end_date_field_wrapper">
							<input type="text" class="wpie_export_field_input_element wpie_export_field_date_element" name="wpie_end_date" placeholder="<?php _e('End Date',WPIE_TEXTDOMAIN);?>"/>
							<div class="wpie_default_notice"><?php _e('Date Format',WPIE_TEXTDOMAIN);?> : mm-dd-yyyy</div>
						</div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Scheduled Export',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<input type="checkbox" id="wpie_order_scheduled_export" class="wpie_export_field_input_element wpie_scheduled_export_check_element" name="wpie_product_scheduled_export" placeholder="<?php _e('Enter Offset Records',WPIE_TEXTDOMAIN);?>"/><label for="wpie_order_scheduled_export" class="wpie_product_scheduled_export_label"><?php _e('Scheduled Export',WPIE_TEXTDOMAIN);?></label>
						<div class="wpie_scheduled_export_wrapper">
							<div class="wpie_scheduled_export_outer_details">
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Export Interval',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<select class="wpie_export_field_select_element" data-placeholder="<?php _e('Select Interval',WPIE_TEXTDOMAIN);?>" name="wpie_export_interval">
											<?php foreach($get_schedules_list as $key=>$value){?>
											<option value="<?php echo $key;?>"><?php echo $value['display'];?></option>
											<?php }?>
										</select>
									</div>
								</div>
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Send E-mail',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<input type="checkbox" class="wpie_export_field_input_element wpie_scheduled_send_email" name="wpie_order_scheduled_send_email" value="1"/>
										<div class="wpie_default_notice"><?php _e('Send E-mail with attachment.',WPIE_TEXTDOMAIN);?></div>
									</div>
								</div>
							</div>
							<div class="wpie_scheduled_export_email_details">
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Enter Email Recipient(s)',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<input class="wpie_export_field_input_element wpie_scheduled_export_email_recipients" type="text" placeholder="<?php _e('Enter Email Recipient(s)',WPIE_TEXTDOMAIN);?>" name="wpie_scheduled_export_email_recipients">
										<div class="wpie_default_notice">Exa. example@gmail.com, demo@yahoo.com</div>
									</div>
								</div>
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Enter Email Subject',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<input class="wpie_export_field_input_element wpie_scheduled_export_email_subject" type="text" placeholder="<?php _e('Enter Email Subject',WPIE_TEXTDOMAIN);?>" name="wpie_scheduled_export_email_subject">
									</div>
								</div>
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Enter Email message',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<textarea class="wpie_scheduled_export_email_content wpie_scheduled_export_text_area" name="wpie_scheduled_export_email_content" placeholder="<?php _e('Enter Email message',WPIE_TEXTDOMAIN);?>"></textarea>
									</div>
								</div>
							</div>
						</div>
 					</div>
						
				</div>
				<div class="wpie_export_field_btn_container">
					<div class="wpie_export_field_btn_wrapper">
						
						<button class="wpie_order_preview_btn wpie_form_submit_btn" type="button"><div class="wpie_ajax_loader"></div><?php _e('Preview',WPIE_TEXTDOMAIN);?></button>
						<button class="wpie_order_export_btn wpie_form_submit_btn" type="button"><?php _e('Export',WPIE_TEXTDOMAIN);?></button>
						
						<button class="wpie_order_scheduled_export_btn wpie_scheduled_export_btn wpie_form_submit_btn" type="button"><?php _e('Save Scheduled',WPIE_TEXTDOMAIN);?></button>
					</div>
				</div>
				
			</form>
			<form method="post" class="wpie_user_export_frm wpie_all_export_frm">
				<input type="hidden" value="0" name="wpie_user_export_verify" class="wpie_user_export_verify" />
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Filter By User ID / Username / Email',WPIE_TEXTDOMAIN);?><div class="wpie_field_tital_recordes"> (<?php echo __('Total Users',WPIE_TEXTDOMAIN).' : '.$user_total;?>)</div></div>
					<div class="wpie_export_field_wrapper">
						<select class="wpie_export_field_select_element" name="wpie_user_id[]" multiple="multiple" data-placeholder="<?php _e('Select User',WPIE_TEXTDOMAIN);?>">
							<?php
							if(!empty($user_list) ){
								foreach($user_list as $new_user){?>
										<option value="<?php echo $new_user->ID;?>"><?php echo '('.__('ID',WPIE_TEXTDOMAIN).' : '.$new_user->ID.') '.$new_user->display_name.' ( '.$new_user->user_email.' )';?></option>
								<?php }
							}?>
 						</select>
						<div class="wpie_default_notice"><?php _e('Default : All Users.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Filter By User Role',WPIE_TEXTDOMAIN);?><div class="wpie_field_tital_recordes"> (<?php echo __('Total Roles',WPIE_TEXTDOMAIN).' : '.count(get_editable_roles());?>)</div></div>
					<div class="wpie_export_field_wrapper">
						<select class="wpie_export_field_select_element" name="wpie_user_role[]" multiple="multiple" data-placeholder="<?php _e('Select User Role',WPIE_TEXTDOMAIN);?>">
						<?php wp_dropdown_roles();?>
							
 						</select>
						<div class="wpie_default_notice"><?php _e('Default : All User Roles.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
                <div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Filter By User Minimum Spend',WPIE_TEXTDOMAIN);?><div class="wpie_field_tital_recordes"></div></div>
					<div class="wpie_export_field_wrapper">
						<input type="text" class="wpie_export_field_input_element" name="wpie_user_min_spend" placeholder="<?php _e('Enter Amount',WPIE_TEXTDOMAIN);?>"/>
						<div class="wpie_default_notice"><?php _e('Default',WPIE_TEXTDOMAIN);?> : 0</div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Limit Records',WPIE_TEXTDOMAIN);?><div class="wpie_field_tital_recordes"> (<?php echo __('Total Users',WPIE_TEXTDOMAIN).' : '.$user_total;?>)</div></div>
					<div class="wpie_export_field_wrapper">
						<input type="text" class="wpie_export_field_input_element" name="wpie_total_records" placeholder="<?php _e('Enter Limit Records',WPIE_TEXTDOMAIN);?>"/>
						<div class="wpie_default_notice"><?php _e('Default : All Records.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Offset Records',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<input type="text" class="wpie_export_field_input_element" name="wpie_offset_records" placeholder="<?php _e('Enter Offset Records',WPIE_TEXTDOMAIN);?>"/>
						<div class="wpie_default_notice"><?php _e('Default : 0.',WPIE_TEXTDOMAIN);echo " ";_e('Note : Fetch Records after XX Records.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Filter By Date',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<div class="wpie_export_start_date_field_wrapper">
							<input type="text" class="wpie_export_field_input_element wpie_export_field_date_element" name="wpie_start_date" placeholder="<?php _e('Start Date',WPIE_TEXTDOMAIN);?>"/>
							<div class="wpie_default_notice"><?php _e('Date Format',WPIE_TEXTDOMAIN);?> : mm-dd-yyyy</div>
						</div>
						<div class="wpie_export_end_date_field_wrapper">
							<input type="text" class="wpie_export_field_input_element wpie_export_field_date_element" name="wpie_end_date" placeholder="<?php _e('End Date',WPIE_TEXTDOMAIN);?>"/>
							<div class="wpie_default_notice"><?php _e('Date Format',WPIE_TEXTDOMAIN);?> : mm-dd-yyyy</div>
						</div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Scheduled Export',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<input type="checkbox" id="wpie_user_scheduled_export" class="wpie_export_field_input_element wpie_scheduled_export_check_element" name="wpie_user_scheduled_export" placeholder="<?php _e('Enter Offset Records',WPIE_TEXTDOMAIN);?>"/><label for="wpie_user_scheduled_export" class="wpie_product_scheduled_export_label"><?php _e('Scheduled Export',WPIE_TEXTDOMAIN);?></label>
						<div class="wpie_scheduled_export_wrapper">
							<div class="wpie_scheduled_export_outer_details">
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Export Interval',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<select class="wpie_export_field_select_element" data-placeholder="<?php _e('Select Interval',WPIE_TEXTDOMAIN);?>" name="wpie_export_interval">
											<?php foreach($get_schedules_list as $key=>$value){?>
											<option value="<?php echo $key;?>"><?php echo $value['display'];?></option>
											<?php }?>
										</select>
									</div>
								</div>
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Send E-mail',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<input type="checkbox" class="wpie_export_field_input_element wpie_scheduled_send_email" name="wpie_user_scheduled_send_email" value="1"/>
										<div class="wpie_default_notice"><?php _e('Send E-mail with attachment.',WPIE_TEXTDOMAIN);?></div>
									</div>
								</div>
							</div>
							<div class="wpie_scheduled_export_email_details">
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Enter Email Recipient(s)',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<input class="wpie_export_field_input_element wpie_scheduled_export_email_recipients" type="text" placeholder="<?php _e('Enter Email Recipient(s)',WPIE_TEXTDOMAIN);?>" name="wpie_scheduled_export_email_recipients">
										<div class="wpie_default_notice">Exa. example@gmail.com, demo@yahoo.com</div>
									</div>
								</div>
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Enter Email Subject',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<input class="wpie_export_field_input_element wpie_scheduled_export_email_subject" type="text" placeholder="<?php _e('Enter Email Subject',WPIE_TEXTDOMAIN);?>" name="wpie_scheduled_export_email_subject">
									</div>
								</div>
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Enter Email message',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<textarea class="wpie_scheduled_export_email_content wpie_scheduled_export_text_area" name="wpie_scheduled_export_email_content" placeholder="<?php _e('Enter Email message',WPIE_TEXTDOMAIN);?>"></textarea>
									</div>
								</div>
							</div>
						</div>
 					</div>
						
				</div>
				<div class="wpie_export_field_btn_container">
					<div class="wpie_export_field_btn_wrapper">
						
						<button class="wpie_user_preview_btn wpie_form_submit_btn" type="button"><div class="wpie_ajax_loader"></div><?php _e('Preview',WPIE_TEXTDOMAIN);?></button>
						<button class="wpie_user_export_btn wpie_form_submit_btn" type="button"><?php _e('Export',WPIE_TEXTDOMAIN);?></button>
						<button class="wpie_user_scheduled_export_btn wpie_scheduled_export_btn wpie_form_submit_btn" type="button"><?php _e('Save Scheduled',WPIE_TEXTDOMAIN);?></button>
					</div>
				</div>
			</form>
			<form method="post" class="wpie_product_cat_export_frm wpie_all_export_frm">
				<input type="hidden" value="0" name="wpie_product_cat_export_verify" class="wpie_product_cat_export_verify" />
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Filter By Product Category',WPIE_TEXTDOMAIN);?> <div class="wpie_field_tital_recordes">(<?php echo  __('Total Categories',WPIE_TEXTDOMAIN).' : '.$product_cat_total;?>)</div></div>
					<div class="wpie_export_field_wrapper">
						<select class="wpie_export_field_select_element" name="wpie_product_category[]" multiple="multiple" data-placeholder="<?php _e('Select Product Category',WPIE_TEXTDOMAIN);?>">
							<?php foreach($product_cat as $cat){?>
								<option value="<?php echo $cat->term_id;?>"><?php echo '('.__('ID',WPIE_TEXTDOMAIN).' : '.$cat->term_id.') '. $cat->name;?> (<?php echo  __('Total Products',WPIE_TEXTDOMAIN).' : '.$cat->count;?>)</option>
								<?php }?>
 						</select>
						<div class="wpie_default_notice"><?php _e('Default : All Categories.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Limit Records',WPIE_TEXTDOMAIN);?> <div class="wpie_field_tital_recordes"> (<?php echo __('Total Records',WPIE_TEXTDOMAIN).' : '.$product_cat_total;?>)</div></div>
					<div class="wpie_export_field_wrapper">
						<input type="text" class="wpie_export_field_input_element" name="wpie_total_records" placeholder="<?php _e('Enter Limit Records',WPIE_TEXTDOMAIN);?>"/>
						<div class="wpie_default_notice"><?php _e('Default : All Records.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Offset Records',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<input type="text" class="wpie_export_field_input_element" name="wpie_offset_records" placeholder="<?php _e('Enter Offset Records',WPIE_TEXTDOMAIN);?>"/>
						<div class="wpie_default_notice"><?php _e('Default : 0.',WPIE_TEXTDOMAIN);echo " ";_e('Note : Fetch Records after XX Records.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Scheduled Export',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<input type="checkbox" id="wpie_product_cat_scheduled_export" class="wpie_export_field_input_element wpie_scheduled_export_check_element" name="wpie_product_scheduled_export" placeholder="<?php _e('Enter Offset Records',WPIE_TEXTDOMAIN);?>"/><label for="wpie_product_cat_scheduled_export" class="wpie_product_scheduled_export_label"><?php _e('Scheduled Export',WPIE_TEXTDOMAIN);?></label>
						<div class="wpie_scheduled_export_wrapper">
							<div class="wpie_scheduled_export_outer_details">
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Export Interval',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<select class="wpie_export_field_select_element" data-placeholder="<?php _e('Select Interval',WPIE_TEXTDOMAIN);?>" name="wpie_export_interval">
											<?php foreach($get_schedules_list as $key=>$value){?>
											<option value="<?php echo $key;?>"><?php echo $value['display'];?></option>
											<?php }?>
										</select>
									</div>
								</div>
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Send E-mail',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<input type="checkbox" class="wpie_export_field_input_element wpie_scheduled_send_email" name="wpie_product_scheduled_send_email" value="1"/>
										<div class="wpie_default_notice"><?php _e('Send E-mail with attachment.',WPIE_TEXTDOMAIN);?></div>
									</div>
								</div>
							</div>
							<div class="wpie_scheduled_export_email_details">
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Enter Email Recipient(s)',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<input class="wpie_export_field_input_element wpie_scheduled_export_email_recipients" type="text" placeholder="<?php _e('Enter Email Recipient(s)',WPIE_TEXTDOMAIN);?>" name="wpie_scheduled_export_email_recipients">
										<div class="wpie_default_notice">Exa. example@gmail.com, demo@yahoo.com</div>
									</div>
								</div>
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Enter Email Subject',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<input class="wpie_export_field_input_element wpie_scheduled_export_email_subject" type="text" placeholder="<?php _e('Enter Email Subject',WPIE_TEXTDOMAIN);?>" name="wpie_scheduled_export_email_subject">
									</div>
								</div>
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Enter Email message',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<textarea class="wpie_scheduled_export_email_content wpie_scheduled_export_text_area" name="wpie_scheduled_export_email_content" placeholder="<?php _e('Enter Email message',WPIE_TEXTDOMAIN);?>"></textarea>
									</div>
								</div>
							</div>
						</div>
 					</div>
						
				</div>
				<div class="wpie_export_field_btn_container">
					<div class="wpie_export_field_btn_wrapper">
						
						<button class="wpie_product_cat_preview_btn wpie_form_submit_btn" type="button"><div class="wpie_ajax_loader"></div><?php _e('Preview',WPIE_TEXTDOMAIN);?></button>
						<button class="wpie_product_cat_export_btn wpie_form_submit_btn" type="button"><?php _e('Export',WPIE_TEXTDOMAIN);?></button>
						<button class="wpie_product_cat_scheduled_export_btn wpie_scheduled_export_btn wpie_form_submit_btn" type="button"><?php _e('Save Scheduled',WPIE_TEXTDOMAIN);?></button>
					</div>
				</div>
			</form>
			<form method="post" class="wpie_coupon_export_frm wpie_all_export_frm">
				<input type="hidden" value="0" name="wpie_coupon_export_verify" class="wpie_coupon_export_verify" />
				
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Filter By Coupons ID / Code',WPIE_TEXTDOMAIN);?>  <div class="wpie_field_tital_recordes">(<?php echo __('Total Coupons',WPIE_TEXTDOMAIN).' : '.$coupon_total;?>)</div></div>
					<div class="wpie_export_field_wrapper">
						<select class="wpie_export_field_select_element" name="wpie_coupon_ids[]" multiple="multiple" data-placeholder="<?php _e('Select Coupons',WPIE_TEXTDOMAIN);?>">
							<?php foreach($coupon_list as $coupon_data){?>
								<option value="<?php echo $coupon_data->ID;?>"><?php echo '('.__('ID',WPIE_TEXTDOMAIN).' : '.$coupon_data->ID.') '.$coupon_data->post_title;?></option>
								<?php }?>
 						</select>
						<div class="wpie_default_notice"><?php _e('Default : All Coupons.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Limit Records',WPIE_TEXTDOMAIN);?> <div class="wpie_field_tital_recordes"> (<?php echo __('Total Records',WPIE_TEXTDOMAIN).' : '.$coupon_total;?>)</div></div>
					<div class="wpie_export_field_wrapper">
						<input type="text" class="wpie_export_field_input_element" name="wpie_total_records" placeholder="<?php _e('Enter Limit Records',WPIE_TEXTDOMAIN);?>"/>
						<div class="wpie_default_notice"><?php _e('Default : All Records.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Offset Records',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<input type="text" class="wpie_export_field_input_element" name="wpie_offset_records" placeholder="<?php _e('Enter Offset Records',WPIE_TEXTDOMAIN);?>"/>
						<div class="wpie_default_notice"><?php _e('Default : 0.',WPIE_TEXTDOMAIN);echo " ";_e('Note : Fetch Records after XX Records.',WPIE_TEXTDOMAIN);?></div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Filter By Date',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<div class="wpie_export_start_date_field_wrapper">
							<input type="text" class="wpie_export_field_input_element wpie_export_field_date_element" name="wpie_start_date" placeholder="<?php _e('Start Date',WPIE_TEXTDOMAIN);?>"/>
							<div class="wpie_default_notice"><?php _e('Date Format',WPIE_TEXTDOMAIN);?> : mm-dd-yyyy</div>
						</div>
						<div class="wpie_export_end_date_field_wrapper">
							<input type="text" class="wpie_export_field_input_element wpie_export_field_date_element" name="wpie_end_date" placeholder="<?php _e('End Date',WPIE_TEXTDOMAIN);?>"/>
							<div class="wpie_default_notice"><?php _e('Date Format',WPIE_TEXTDOMAIN);?> : mm-dd-yyyy</div>
						</div>
					</div>
				</div>
				<div class="wpie_export_field_container">
					<div class="wpie_export_field_title"><?php _e('Scheduled Export',WPIE_TEXTDOMAIN);?></div>
					<div class="wpie_export_field_wrapper">
						<input type="checkbox" id="wpie_coupon_scheduled_export" class="wpie_export_field_input_element wpie_scheduled_export_check_element" name="wpie_product_scheduled_export" placeholder="<?php _e('Enter Offset Records',WPIE_TEXTDOMAIN);?>"/><label for="wpie_coupon_scheduled_export" class="wpie_product_scheduled_export_label"><?php _e('Scheduled Export',WPIE_TEXTDOMAIN);?></label>
						<div class="wpie_scheduled_export_wrapper">
							<div class="wpie_scheduled_export_outer_details">
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Export Interval',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<select class="wpie_export_field_select_element" data-placeholder="<?php _e('Select Interval',WPIE_TEXTDOMAIN);?>" name="wpie_export_interval">
											<?php foreach($get_schedules_list as $key=>$value){?>
											<option value="<?php echo $key;?>"><?php echo $value['display'];?></option>
											<?php }?>
										</select>
									</div>
								</div>
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Send E-mail',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<input type="checkbox" class="wpie_export_field_input_element wpie_scheduled_send_email" name="wpie_product_scheduled_send_email" value="1"/>
										<div class="wpie_default_notice"><?php _e('Send E-mail with attachment.',WPIE_TEXTDOMAIN);?></div>
									</div>
								</div>
							</div>
							<div class="wpie_scheduled_export_email_details">
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Enter Email Recipient(s)',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<input class="wpie_export_field_input_element wpie_scheduled_export_email_recipients" type="text" placeholder="<?php _e('Enter Email Recipient(s)',WPIE_TEXTDOMAIN);?>" name="wpie_scheduled_export_email_recipients">
										<div class="wpie_default_notice">Exa. example@gmail.com, demo@yahoo.com</div>
									</div>
								</div>
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Enter Email Subject',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<input class="wpie_export_field_input_element wpie_scheduled_export_email_subject" type="text" placeholder="<?php _e('Enter Email Subject',WPIE_TEXTDOMAIN);?>" name="wpie_scheduled_export_email_subject">
									</div>
								</div>
								<div class="wpie_scheduled_export_inner">
									<div class="wpie_scheduled_export_data_label"><?php _e('Enter Email message',WPIE_TEXTDOMAIN);?></div>
									<div class="wpie_scheduled_export_data_element_wrapper">
										<textarea class="wpie_scheduled_export_email_content wpie_scheduled_export_text_area" name="wpie_scheduled_export_email_content" placeholder="<?php _e('Enter Email message',WPIE_TEXTDOMAIN);?>"></textarea>
									</div>
								</div>
							</div>
						</div>
 					</div>
						
				</div>
				<div class="wpie_export_field_btn_container">
					<div class="wpie_export_field_btn_wrapper">
						
						<button class="wpie_coupon_preview_btn wpie_form_submit_btn" type="button"><div class="wpie_ajax_loader"></div><?php _e('Preview',WPIE_TEXTDOMAIN);?></button>
						<button class="wpie_coupon_export_btn wpie_form_submit_btn" type="button"><?php _e('Export',WPIE_TEXTDOMAIN);?></button>
						<button class="wpie_coupon_scheduled_export_btn wpie_scheduled_export_btn wpie_form_submit_btn" type="button"><?php _e('Save Scheduled',WPIE_TEXTDOMAIN);?></button>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<div class="wpie_export_preview_wrapper"></div>
<div class="wpie_documantation_links_wrapper">
	<div class="wpie_documantation_links_outer">
		<a target="_blank" href="<?php echo WPIE_PLUGIN_URL.'/documentation/';?>"><?php _e('Documentation',WPIE_TEXTDOMAIN);?></a> |  <a target="_blank" href="http://www.vjinfotech.com/support"><?php _e('Support',WPIE_TEXTDOMAIN);?></a>
	</div>
</div>