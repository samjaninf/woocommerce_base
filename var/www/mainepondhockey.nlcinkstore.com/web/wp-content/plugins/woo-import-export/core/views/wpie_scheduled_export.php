<?php 
	if (!defined('ABSPATH'))
    die("Can't load this file directly");
	
	global $wpie_scheduled;
	
	$order_scheduled_list = $wpie_scheduled->get_order_scheduled_data();
	
	$product_scheduled_list = $wpie_scheduled->get_product_scheduled_data();
	
	$user_scheduled_list = $wpie_scheduled->get_user_scheduled_data();
	
	$product_cat_scheduled_list = $wpie_scheduled->get_product_cat_scheduled_data();
	
	$coupon_scheduled_list = $wpie_scheduled->get_coupon_scheduled_data();
	
	$get_schedules_list = wp_get_schedules();
	
	$total_records = 0;
	
	$total_order_records = 0;
	
	$total_products_records = 0;
	
	$total_products_cat_records = 0;
	
	$total_users_records = 0;
	
	$total_coupon_records = 0;
	
	if(!empty($order_scheduled_list))
	{
		$total_order_records = count($order_scheduled_list);
	}
	if(!empty($product_scheduled_list))
	{
		$total_products_records = count($product_scheduled_list);
	}
	if(!empty($product_cat_scheduled_list))
	{
		$total_products_cat_records = count($product_cat_scheduled_list);
	}
	if(!empty($user_scheduled_list))
	{
		$total_users_records = count($user_scheduled_list);
	}
	if(!empty($coupon_scheduled_list))
	{
		$total_coupon_records = count($coupon_scheduled_list);
	}
	
	$total_records = $total_order_records + $total_products_records + $total_users_records + $total_products_cat_records + $total_coupon_records;
	
	
?>
<div class="wpie_product_export_wrapper">
	<div class="wpie_product_export_belt_wrapper">
		<div class="wpie_product_export_belt wpie_selected"><?php _e('Scheduled Export',WPIE_TEXTDOMAIN);?><div class="wpie_total_export_count"><?php echo $total_records;?></div></div>
	</div>
	<div class="wpie_product_export_container">
		<div class="wpie_success_msg"><?php _e('Successfully Deleted.',WPIE_TEXTDOMAIN);?></div>
		<div class="wpie_scheduled_export_title"><div class="wpie_total_data_export_title"><?php _e('Product Scheduled Export',WPIE_TEXTDOMAIN);?> (</div><div class="wpie_total_product_export_count"><?php echo $total_products_records;?></div><div class="wpie_total_data_export_title">)</div></div>
		<div class="wpie_product_export_inner_container wpie_scheduled_export_container">
			<table class="wpie_product_scheduled_export wpie_scheduled_export_list" cellspacing="0">
				<thead>
					<tr>
						<th><?php _e('Scheduled ID',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Recurrence Time',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Send E-mail',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Recipients',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Next event',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Actions',WPIE_TEXTDOMAIN);?></th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty($product_scheduled_list)){?>
						<?php foreach($product_scheduled_list as $key=>$value){?>
						<tr>
							<td><?php echo $key;?></td>
							<td><?php echo $get_schedules_list[$value['wpie_export_interval']]['display'];?></td>
							<td><?php if(isset($value['wpie_product_scheduled_send_email']) && $value['wpie_product_scheduled_send_email']==1){_e('Yes',WPIE_TEXTDOMAIN);}else{_e('No',WPIE_TEXTDOMAIN);}?></td>
							<td><?php echo $value['wpie_scheduled_export_email_recipients'];?></td>
							<td><?php echo date_i18n(get_option( 'date_format' ).' '.get_option( 'time_format' ),wp_next_scheduled( 'wpie_cron_scheduled_product_export' ,array( $key ) ));?></td>
							<td><?php echo '<div class="wpie_delete_product_cron" cron_id='.$key.'>DELETE</div>';?></td>
						</tr>
						<?php } ?>
					<?php } ?>
				</tbody>
			</table>
		</div>
		<div class="wpie_scheduled_export_title"><div class="wpie_total_data_export_title"><?php _e('Order Scheduled Export',WPIE_TEXTDOMAIN);?> (</div><div class="wpie_total_order_export_count"><?php echo $total_order_records;?></div><div class="wpie_total_data_export_title">)</div></div>
		<div class="wpie_product_export_inner_container wpie_scheduled_export_container">
			<table class="wpie_product_scheduled_export wpie_scheduled_export_list" cellspacing="0">
				<thead>
					<tr>
						<th><?php _e('Scheduled ID',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Recurrence Time',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Send E-mail',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Recipients',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Next event',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Actions',WPIE_TEXTDOMAIN);?></th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty($order_scheduled_list)){?>
						<?php foreach($order_scheduled_list as $key=>$value){?>
						<tr>
							<td><?php echo $key;?></td>
							<td><?php echo $get_schedules_list[$value['wpie_export_interval']]['display'];?></td>
							<td><?php if(isset($value['wpie_order_scheduled_send_email']) && $value['wpie_order_scheduled_send_email']==1){_e('Yes',WPIE_TEXTDOMAIN);}else{_e('No',WPIE_TEXTDOMAIN);}?></td>
							<td><?php echo $value['wpie_scheduled_export_email_recipients'];?></td>
							<td><?php echo date_i18n(get_option( 'date_format' ).' '.get_option( 'time_format' ),wp_next_scheduled( 'wpie_cron_scheduled_order_export' ,array( $key ) ));?></td>
							<td><?php echo '<div class="wpie_delete_order_cron" cron_id='.$key.'>DELETE</div>';?></td>
						</tr>
						<?php } ?>
					<?php } ?>
				</tbody>
			</table>
		</div>
		
		<div class="wpie_scheduled_export_title"><div class="wpie_total_data_export_title"><?php _e('User Scheduled Export',WPIE_TEXTDOMAIN);?> (</div><div class="wpie_total_user_export_count"><?php echo $total_users_records;?></div><div class="wpie_total_data_export_title">)</div></div>
		<div class="wpie_product_export_inner_container wpie_scheduled_export_container">
			<table class="wpie_user_scheduled_export wpie_scheduled_export_list" cellspacing="0">
				<thead>
					<tr>
						<th><?php _e('Scheduled ID',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Recurrence Time',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Send E-mail',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Recipients',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Next event',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Actions',WPIE_TEXTDOMAIN);?></th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty($user_scheduled_list)){?>
						<?php foreach($user_scheduled_list as $key=>$value){?>
						<tr>
							<td><?php echo $key;?></td>
							<td><?php echo $get_schedules_list[$value['wpie_export_interval']]['display'];?></td>
							<td><?php if(isset($value['wpie_user_scheduled_send_email']) && $value['wpie_user_scheduled_send_email']==1){_e('Yes',WPIE_TEXTDOMAIN);}else{_e('No',WPIE_TEXTDOMAIN);}?></td>
							<td><?php echo $value['wpie_scheduled_export_email_recipients'];?></td>
							<td><?php echo date_i18n(get_option( 'date_format' ).' '.get_option( 'time_format' ),wp_next_scheduled( 'wpie_cron_scheduled_product_export' ,array( $key ) ));?></td>
							<td><?php echo '<div class="wpie_delete_user_cron" cron_id='.$key.'>DELETE</div>';?></td>
						</tr>
						<?php } ?>
					<?php } ?>
				</tbody>
			</table>
		</div>
		
		<div class="wpie_scheduled_export_title"><div class="wpie_total_data_export_title"><?php _e('Product Category Scheduled Export',WPIE_TEXTDOMAIN);?> (</div><div class="wpie_total_product_cat_export_count"><?php echo $total_products_cat_records;?></div><div class="wpie_total_data_export_title">)</div></div>
		<div class="wpie_product_export_inner_container wpie_scheduled_export_container">
			<table class="wpie_product_cat_scheduled_export wpie_scheduled_export_list" cellspacing="0">
				<thead>
					<tr>
						<th><?php _e('Scheduled ID',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Recurrence Time',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Send E-mail',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Recipients',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Next event',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Actions',WPIE_TEXTDOMAIN);?></th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty($product_cat_scheduled_list)){?>
						<?php foreach($product_cat_scheduled_list as $key=>$value){?>
						<tr>
							<td><?php echo $key;?></td>
							<td><?php echo $get_schedules_list[$value['wpie_export_interval']]['display'];?></td>
							<td><?php if(isset($value['wpie_product_scheduled_send_email']) && $value['wpie_product_scheduled_send_email']==1){_e('Yes',WPIE_TEXTDOMAIN);}else{_e('No',WPIE_TEXTDOMAIN);}?></td>
							<td><?php echo $value['wpie_scheduled_export_email_recipients'];?></td>
							<td><?php echo date_i18n(get_option( 'date_format' ).' '.get_option( 'time_format' ),wp_next_scheduled( 'wpie_cron_scheduled_product_cat_export' ,array( $key ) ));?></td>
							<td><?php echo '<div class="wpie_delete_product_cat_cron" cron_id='.$key.'>DELETE</div>';?></td>
						</tr>
						<?php } ?>
					<?php } ?>
				</tbody>
			</table>
		</div>
		
		<div class="wpie_scheduled_export_title"><div class="wpie_total_data_export_title"><?php _e('Coupon Scheduled Export',WPIE_TEXTDOMAIN);?> (</div><div class="wpie_total_coupon_export_count"><?php echo $total_coupon_records;?></div><div class="wpie_total_data_export_title">)</div></div>
		<div class="wpie_product_export_inner_container wpie_scheduled_export_container">
			<table class="wpie_product_cat_scheduled_export wpie_scheduled_export_list" cellspacing="0">
				<thead>
					<tr>
						<th><?php _e('Scheduled ID',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Recurrence Time',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Send E-mail',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Recipients',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Next event',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Actions',WPIE_TEXTDOMAIN);?></th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty($coupon_scheduled_list)){?>
						<?php foreach($coupon_scheduled_list as $key=>$value){?>
						<tr>
							<td><?php echo $key;?></td>
							<td><?php echo $get_schedules_list[$value['wpie_export_interval']]['display'];?></td>
							<td><?php if(isset($value['wpie_product_scheduled_send_email']) && $value['wpie_product_scheduled_send_email']==1){_e('Yes',WPIE_TEXTDOMAIN);}else{_e('No',WPIE_TEXTDOMAIN);}?></td>
							<td><?php echo $value['wpie_scheduled_export_email_recipients'];?></td>
							<td><?php echo date_i18n(get_option( 'date_format' ).' '.get_option( 'time_format' ),wp_next_scheduled( 'wpie_cron_scheduled_coupon_export' ,array( $key ) ));?></td>
							<td><?php echo '<div class="wpie_delete_coupon_cron" cron_id='.$key.'>DELETE</div>';?></td>
						</tr>
						<?php } ?>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<div class="wpie_documantation_links_wrapper">
	<div class="wpie_documantation_links_outer">
		<a target="_blank" href="<?php echo WPIE_PLUGIN_URL.'/documentation/';?>"><?php _e('Documentation',WPIE_TEXTDOMAIN);?></a> |  <a target="_blank" href="http://www.vjinfotech.com/support"><?php _e('Support',WPIE_TEXTDOMAIN);?></a>
	</div>
</div>