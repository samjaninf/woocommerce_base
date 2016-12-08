<?php 
	if (!defined('ABSPATH'))
    die("Can't load this file directly");
	
	global $wpie_product;
	$log_list = $wpie_product -> wpie_get_product_import_export_log();
 ?>
<div class="wpie_product_export_wrapper">
	<div class="wpie_product_export_belt_wrapper">
		<div class="wpie_product_export_belt wpie_product_title_belt wpie_selected"><?php _e('Export Log',WPIE_TEXTDOMAIN);?><div class="wpie_total_export_count"><?php echo count($log_list);?></div></div>
	</div>
	<div class="wpie_product_export_container">
		<div class="wpie_success_msg"><?php _e('Successfully Deleted.',WPIE_TEXTDOMAIN);?></div>
		<div class="wpie_product_export_inner_container">
			<table class="widefat wpie_product_import_log" cellspacing="0">
				<thead>
					<tr>
						<th><?php _e('File Type',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('File Name',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Export Data',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Date',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Action',WPIE_TEXTDOMAIN);?></th>
					</tr>
				</thead>
				<tbody>
					<?php if(!empty($log_list)){?>
					<?php foreach($log_list as $log_data){?>
						<tr>
							<td><?php if($log_data->export_log_file_type=='csv'){?><img src="<?php echo WPIE_IMAGES_URL.'/csv_logo.png';?>" class="wpie_log_logo"/><?php }?></td>
							<td class="wpie_filename_list"><?php echo $log_data->export_log_file_name;?></td>
							<td><?php echo $log_data->export_log_data;?></td>
							<td><?php echo $log_data->create_date;?></td>
							<td>
								<div class="wpie_log_download_action"  file_name="<?php echo $log_data->export_log_file_name;?>"><?php _e('Download',WPIE_TEXTDOMAIN);?></div><div class="wpie_log_delete_action" log_id="<?php echo $log_data->export_log_id;?>" file_name="<?php echo $log_data->export_log_file_name;?>"><?php _e('Delete',WPIE_TEXTDOMAIN);?></div>
							</td>
						</tr>
					<?php }?>
					<?php }else{?>
						<tr>
							<td colspan="5" class="wpie_product_log"><?php _e('No Records found.',WPIE_TEXTDOMAIN);?></td>
						</tr>
					<?php
					}
							
					?>
				</tbody>
				<tfoot>
					<tr>
						<th><?php _e('File Type',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('File Name',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Export Data',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Date',WPIE_TEXTDOMAIN);?></th>
						<th><?php _e('Action',WPIE_TEXTDOMAIN);?></th>
					</tr>
				</tfoot>
			</table>
		</div>
	</div>
	<form method="post" class="wpie_download_exported_file_frm">
		<input type="hidden" class="wpie_download_exported_file" name="wpie_download_exported_file" value="" />
	</form>
</div>
<div class="wpie_documantation_links_wrapper">
	<div class="wpie_documantation_links_outer">
		<a target="_blank" href="<?php echo WPIE_PLUGIN_URL.'/documentation/';?>"><?php _e('Documentation',WPIE_TEXTDOMAIN);?></a> |  <a target="_blank" href="http://www.vjinfotech.com/support"><?php _e('Support',WPIE_TEXTDOMAIN);?></a>
	</div>
</div>