<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2019
 */

/**
 * Syntax: oikwp oik-update.php component version component_type
 *
 * e.g.
 * oikwp oik-update.php gutenberg 5.5.0
 * oikwp oik-update.php genesis 2.10.1 theme
 * oikwp oik-update.php twentynineteen 1.9 theme url=core.wp-a2z.org
 *
 */
if ( PHP_SAPI !== 'cli' ) {
	die();
}



/**
 *  Manual process to be replicated
 *
 * [ ] 1. Identify component type ( plugin or theme ) from component name
 * [ ] 2. If not known determine component type: plugin or theme
 * [ ] 3. Download latest assets into
 * [ ] 4. Determine the repo owner ( wp-a2z or bobbingwide)
 *     If the component is processed directly from the GIT repo then we don't need to do this
 *
 * [ ] 5. Download WordPress to \apache\htdocs\downloads\wordpress
 * [ ] 6. Download plugin https://downloads.wordpress.org/plugin/gutenberg.5.5.0.zip to \apache\htdocs\downloads\plugins
 * [ ] 7. Download theme to \apache\htdocs\downloads\themes
 *
 * [ ] 8. Empty the git repo ( need to determine repo owner ) c:\github\wp-a2z\gutenberg leaving the .git folder
 * [ ] 9. Run 7-zip to open the .zip file and extract to \github\wp-a2z\gutenberg
 * [ ] 10. Add all the files to the repo - git add .
 * [ ] 11. Commit the changes: git commit -m "v version version-date
 * [ ] 12. Tag the version
 *
 * [ ] 13. Push the changes to GitHub
 * [ ] 14. Pull the changes to \apache\htdocs\wp-a2z version
 * [ ] 15. Run oik-shortcodes to rebuild the dynamic API reference
 *
 *
 *
 */


function identify_component_type( ) {

}


function oik_update_autoload() {
	$autloaded = false;
	$lib_autoload = oik_require_lib( "oik-autoload" );
	if ( $lib_autoload && !is_wp_error( $lib_autoload ) ) {
		add_filter( "oik_query_autoload_classes" , "oik_update_query_autoload_classes" );
		oik_autoload();
		$autoloaded = true;
	}	else {
		bw_trace2( $lib_autoload, "oik-autoload not loaded", false );
	}
	return $autoloaded;
}


function oik_update() {
	$autoloaded = oik_update_autoload();
	if ( $autoloaded ) {
		$oik_component_update = new OIK_component_update();
		$component = oik_batch_query_value_from_argv( 1, 'unknown' );
		$new_version = oik_batch_query_value_from_argv( 2, 'x.y.z');
		$component_type = oik_batch_query_value_from_argv( 3, 'plugin' );
		$oik_component_update->set_component( $component );
		$oik_component_update->set_new_version( $new_version );
		$oik_component_update->set_component_type( $component_type );
		$oik_component_update->perform_update();
	} else {
		echo "oik-autoload not available";
	}
}

function oik_update_query_autoload_classes( $classes ) {
	$classes[] = array( "class" => "OIK_component_update"
	, "plugin" => "oik-update"
	, "path" => "classes"
	, 'file' => 'classes/class-OIK-component-update.php'
	);

	$classes[] = array( "class" => "Git" ,
						 "plugin" => "oik-batch",
		"path" => "includes",
		"file" => "includes/class-git.php"

	);
	return( $classes );
}


oik_update();
