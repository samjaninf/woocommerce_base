<?php 

	if (!defined("THEME_NAME")):
	define( 'THEME_NAME', getbowtied_parent_theme_name() );
	endif;
	if (!defined("THEME_SLUG")):
	define( 'THEME_SLUG', getbowtied_theme_slug() );
	endif;
	define( 'GETBOWTIED_TOOLS_URL', trailingslashit( plugins_url() ) . trailingslashit( 'getbowtied-tools' ));

	function getbowtied_parent_theme_name()
	{
		$theme = wp_get_theme();
		if ($theme->parent()):
			$theme_name = $theme->parent()->get('Name');
		else:
			$theme_name = $theme->get('Name');
		endif;

		return $theme_name;
	}

	function getbowtied_theme_slug()
	{
		$theme = wp_get_theme();
		$theme_slug = $theme->template;
		return $theme_slug;
	}
