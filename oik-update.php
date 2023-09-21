<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2019-2022
 * @version 1.2.0
 */

/**
 * oik batch process to improve applying API updates for WordPress, plugins or themes
 
 * Syntax: oikwp oik-update.php component version component_type
 *
 * e.g.
 * oikwp oik-update.php gutenberg 5.5.0
 * oikwp oik-update.php genesis 2.10.1 theme
 * oikwp oik-update.php twentynineteen 1.9 theme url=core.wp-a2z.org
 *
 * oikwp oik-update.php wordpress 5.6-beta2
 * When updating WordPress ensure you're not in the \github\bobbingwide\wp-a2z directory
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

function oik_update_loaded() {
	add_action( "run_oik-update.php", "oik_update" );
}

function identify_component_type( ) {

}

/**
 * Note: autoloading will fail if the required plugins are not activated.
 * You need: oikg
 *
 * @return bool
 */

function oik_update_autoload() {
	$autloaded = false;
	$lib_autoload = oik_require_lib( "oik-autoload" );
	if ( $lib_autoload && !is_wp_error( $lib_autoload ) ) {
		add_filter( "oik_query_autoload_classes" , "oik_update_query_autoload_classes" );
		oik_autoload();
		$autoloaded = true;
	}	else {
		bw_trace2( $lib_autoload, "oik-autoload not loaded", false );
		$autoloaded = false;
	}
	return $autoloaded;
}


function oik_update() {
	$autoloaded = oik_update_autoload();
	if ( $autoloaded ) {
		$oik_component_update = new OIK_component_update();
		$component = oik_batch_query_value_from_argv( 1, 'unknown' );
		$from_version = oik_batch_query_value_from_argv( 2, 'x.y.z');
		$component_type = oik_batch_query_value_from_argv( 3, 'plugin' );
        //if ( 'x.y.z' === $new_version ) {
            $new_versions = oik_update_determine_new_versions( $component, $from_version, $component_type );
        //} else {
        //    $new_versions = bw_as_array( $new_version);
       // }
		$component_type = oik_batch_query_value_from_argv( 3, 'plugin' );
		$oik_component_update->set_component( $component );
        $oik_component_update->set_component_type( $component_type );

        foreach ( $new_versions as $new_version ) {
            $oik_component_update->set_new_version($new_version);
            $oik_component_update->perform_update();
        }
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

function oik_update_determine_new_versions( $component, $from_version, $component_type ) {
	if ( $component === 'wordpress') {
		$new_versions = [ $from_version ];
	} elseif ( $component_type === 'theme') {
		$new_versions = [ $from_version ];
	} else {
		echo "Listing plugin versions from $from_version.";
		oik_require( 'class-wp-org-v12-downloads.php', 'wp-top12' );
		$wpod=new WP_org_v12_downloads();
		$wpod->get_download( $component );
		$new_versions=$wpod->list_versions( $from_version );
		natsort( $new_versions );
		print_r( $new_versions );
		//gob();
	}
    return $new_versions;

}


oik_update_loaded();
