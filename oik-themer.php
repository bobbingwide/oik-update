<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2021, 2022
 * @package oik-update
 */

/**
 * oik batch process to improve the registration of FSE themes listed in blocks.wp-a2z.org

 * Syntax: oikwp oik-themer.php [theme]
 *
 * e.g.
 * oikwp oik-themer.php slug url=blocks.wp.a2z
 *
 * oikwp oik-themer.php url=blocks.wp.a2z
 *
 */
if ( PHP_SAPI !== 'cli' ) {
    die();
}



/**
 *  Manual process.
 * 
 * [ ] 1. Download theme to \apache\htdocs\downloads\themes
 * [ ] 2. Run 7-zip to open the .zip file and extract to WP_THEMES_DIR
 * [ ] 3. Determine the theme's fields - fetch theme info using WordPress API
 * [ ] 4. Create / update the oik-theme
 * [ ] 5. Set featured image to screenshot 
 * [ ] 6. Find patterns folder
 * [ ] 7. Test everything's OK
 * [ ] 8. Clone oik-theme to blocks.wp-a2z.org
 * [ ] 9. Install theme on wp-a2z.org
 */

function oik_themer_autoload() {
    $autloaded = false;
    $lib_autoload = oik_require_lib( "oik-autoload" );
    if ( $lib_autoload && !is_wp_error( $lib_autoload ) ) {
        add_filter( "oik_query_autoload_classes" , "oik_themer_query_autoload_classes" );
        oik_autoload();
        $autoloaded = true;
    }	else {
        bw_trace2( $lib_autoload, "oik-autoload not loaded", false );
    }
    return $autoloaded;
}

function oik_themer_query_autoload_classes( $classes ) {
    $classes[] = array( "class" => "OIK_themer"
    , "plugin" => "oik-update"
    , "path" => "classes"
    , 'file' => 'classes/class-OIK-themer.php'
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
    $classes[] = array( 'class' => 'WP_org_v12_downloads',
        'plugin' => 'wp-top12',
        'path' => null,
        'file' => 'class-wp-org-v12-downloads.php'
    );

    $classes[] = array( 'class' => 'oik_remote',
        'plugin' => 'oik',
        'path' => 'libs',
        'file' => 'libs/class-oik-remote.php'
    );

    $classes[] = array( 'class' => 'OIK_block_updater'
    , 'plugin' => 'oik-update'
    , 'path' => 'classes'
    , 'file' => 'classes/class-OIK-block-updater.php'
    );

    $classes[] = array( "class" => "OIK_component_update"
    , "plugin" => "oik-update"
    , "path" => "classes"
    , 'file' => 'classes/class-OIK-component-update.php'
    );

    $classes[] = array( "class" => "WP_org_downloads_themes"
    , "plugin" => "wp-top12"
    , "path" => "libs"
    , 'file' => 'libs/class-wp-org-downloads-themes.php'
    );


    return( $classes );
}

/**
 * Checks if the component is a Git repository.
 *
 * @param string $component the name of the component ( plugin ).
 * @return bool true if it's a Git repository
 *
 */
function oik_themer_component_is_git_repo( $component ) {
	// oik_path assumes it's a plugin!
	//$path = oik_path( null, $component );
	//$path = untrailingslashit( $path );
	$path = get_theme_root() . '/' .  $component;
	echo $path;
	$git = new Git();
	$has_dot_git = $git->has_dot_git( $path );
	$is_git_repo = $has_dot_git !== null;
	return $is_git_repo;
}


/**
 * Updates a single theme or does the lot.
 */
function oik_themer() {
    $autoloaded = oik_themer_autoload();
    if ( $autoloaded ) {
        $oik_themer = new OIK_themer();
        $component = oik_batch_query_value_from_argv( 1, 'unknown' );
        $new_version = oik_batch_query_value_from_argv( 2, '');
	    if ( $component !== 'unknown') {
		    oik_themer_update_component_version( $oik_themer, $component, $new_version );
	    } else {
		    oik_themer_update_components_that_need_it( $oik_themer );
	    }
    } else {
        echo "oik-autoload not available";
    }
}

function oik_themer_update_component_version( $oik_themer, $component, $new_version ) {
	//echo $component;
	//echo $new_version;
	//$component_type = oik_batch_query_value_from_argv( 3, 'plugin' );
	$oik_themer->set_component( $component );
	$oik_themer->get_theme_info();
	// $oik_blocker->set_component_type( $component_type );
	// Don't update the plugin if it's a git repo.
	// Don't update the plugin if new version is 'n'
	if ( false === oik_themer_component_is_git_repo( $component )) {
		if ( 'n' !== $new_version ) {
			//$oik_themer->perform_update();
			$oik_themer->download_theme_version();
		} else {
			gob();
		}
	} else {
		echo "Component is a Git repo: " . $component . PHP_EOL;
	}

	$oik_themer->update_oik_theme();
	$oik_themer->preview_theme();

}

/**
 * Processes all themes that have updates available.
 *
 */
function oik_themer_update_components_that_need_it( $oik_themer ) {
	$wpodt = new WP_org_downloads_themes();
    if ( null === $wpodt ) {
	        gob();
    }
    $wpodt->maybe_query_all_themes();
    $wpodt->load_all_themes();
    $themes = $wpodt->get_fse_themes();
    //print_r( $themes );
    if ( $themes && count( $themes )) {
		foreach ( $themes as $theme => $theme_info ) {

			echo $theme;
			// Only update themes which have new versions.
			//
			$new_version = $oik_themer->is_new_version( $theme_info  );
			if ( $new_version ) {
				echo " ";
				echo $new_version;
				echo PHP_EOL;
				//$new_version = $theme_info->version;
				oik_themer_update_component_version( $oik_themer, $theme, $new_version );
			} else {
				echo PHP_EOL;
			}
		}
	}
}

oik_themer();