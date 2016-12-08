<?php 
	$active_page 		= $_GET["page"];

	$registration_page 	= "getbowtied_theme_registration";
	$tools_page 		= "getbowtied_theme";
	
	$getbowtied_settings = Getbowtied_Admin_Pages::settings();

?>
<h1>
	<?php echo THEME_NAME; ?>
	<a class="button" href="<?php echo $getbowtied_settings['theme_docs']; ?>" target="_blank"><span class="dashicons dashicons-info"></span> <?php echo __("Documentation", "getbowtied"); ?></a>
	<a class="button" href="<?php echo $getbowtied_settings['customize_link']; ?>"><span class="dashicons dashicons-admin-appearance"></span> <?php echo __("Customize", "getbowtied"); ?></a>
</h1>
<p class="version">
	<a href="<?php echo $getbowtied_settings['release_notes']; ?>" target="_blank">
		<span class="dashicons dashicons-update"></span> 
		<?php echo __( "Version", "getbowtied" ); ?> <?php echo Getbowtied_Admin_Pages::getbowtied_theme_version(); ?>
	</a>
</p>

<h2 class="nav-tab-wrapper getbowtied-tab-wrapper">
	<?php
	printf( '<a href="%s" class="nav-tab ' . ($active_page == $tools_page ? 		'nav-tab-active' : '') . '"><span class="dashicons dashicons-admin-settings"></span> %s</a>', admin_url( 'admin.php?page=' . $tools_page ), 		__( "Tools", "getbowtied" ) );
	printf( '<a href="%s" class="nav-tab ' . ($active_page == $registration_page ? 	'nav-tab-active' : '') . '"><span class="dashicons dashicons-admin-network"></span> %s</a>', admin_url( 'admin.php?page=' . $registration_page ), 	__( "Product Key", "getbowtied" ) );
	?>
</h2>