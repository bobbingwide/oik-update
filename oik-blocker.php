<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2019
 */

/**
 * Syntax: oikwp oik-blocker.php plugin version etc etc
 *
 * e.g.
 * oikwp oik-blocker.php gutenberg 5.7.0 url=blocks.wp.a2z
 * oikwp oik-blocker.php oik-blocks 0.4.0-alpha-20190516 url=blocks.wp.a2z
 *
 */
if ( PHP_SAPI !== 'cli' ) {
	die();
}



/**
 *  Manual process.
 *
 * [ ] 1. Download latest assets into \apache\htdocs\downloads\ banners and icons
 * [ ] 2. Determine if plugin is installed as a Git repo
 * [ ] 2. Download plugin e.g.  https://downloads.wordpress.org/plugin/gutenberg.5.7.0.zip to \apache\htdocs\downloads\plugins
 * [ ] 3. Run 7-zip to open the .zip file and extract to WP_PLUGINS_DIR
 * [ ] Determine the plugin's fields: plugin_slug, plugin_name
 * [ ] Determine the plugin's post ID
 * [ ] Determine the plugin's featured image
 * [ ] If plugin not created:
 *     - Add plugin
 *     - Add featured image
 *     - Create blocks
 * [ ] Else,
 *     - find block differences
 *     - Create new blocks
 *     - Mark blocks no longer listed as deleted
 * [ ] - Register block category
 *
 *
 *
 *
 */
function oik_blocker_autoload() {
	$autloaded = false;
	$lib_autoload = oik_require_lib( "oik-autoload" );
	if ( $lib_autoload && !is_wp_error( $lib_autoload ) ) {
		add_filter( "oik_query_autoload_classes" , "oik_blocker_query_autoload_classes" );
		oik_autoload();
		$autoloaded = true;
	}	else {
		bw_trace2( $lib_autoload, "oik-autoload not loaded", false );
	}
	return $autoloaded;
}


function oik_blocker() {
	$autoloaded = oik_blocker_autoload();
	if ( $autoloaded ) {
		$oik_blocker = new OIK_blocker();
		$component = oik_batch_query_value_from_argv( 1, 'unknown' );
		$new_version = oik_batch_query_value_from_argv( 2, 'x.y.z');
		//echo $component;
		//echo $new_version;
		//$component_type = oik_batch_query_value_from_argv( 3, 'plugin' );
		$oik_blocker->set_component( $component );
		$oik_blocker->set_new_version( $new_version );
		//$oik_blocker->set_component_type( $component_type );
		$oik_blocker->perform_update();
	} else {
		echo "oik-autoload not available";
	}
}

function oik_blocker_query_autoload_classes( $classes ) {
	$classes[] = array( "class" => "OIK_blocker"
	, "plugin" => "oik-update"
	, "path" => "classes"
	, 'file' => 'classes/class-OIK-blocker.php'
	);

	$classes[] = array( 'class' => 'OIK_wp_a2z'
		, 'plugin' => 'oik-update'
		, 'path' => 'classes'
		, 'file' => 'classes/class-OIK-wp-a2z.php'
	);

	$classes[] = array( "class" => "Git" ,
	                    "plugin" => "oik-batch",
	                    "path" => "includes",
	                    "file" => "includes/class-git.php"

	);
	return( $classes );
}


oik_blocker();
