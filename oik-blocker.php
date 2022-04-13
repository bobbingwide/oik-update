<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2019, 2022
 */

/**
 * oik batch process to improve the generation of blocks for a plugin listed in blocks.wp-a2z.org
 
 * Syntax: oikwp oik-blocker.php plugin version etc etc
 *
 * e.g.
 * oikwp oik-blocker.php gutenberg 5.7.0 url=blocks.wp.a2z
 * oikwp oik-blocker.php oik-blocks 0.4.0-alpha-20190516 url=blocks.wp.a2z
 * oikwp oik-blocker.php oik v4.8.0 url=s.b/oikcom
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

/**
 * Checks if the component is a Git repository.
 *
 * @param string $component the name of the component ( plugin ).
 * @return bool true if it's a Git repository
 *
 */
function oik_blocker_component_is_git_repo( $component ) {
	$path = oik_path( null, $component );
	$path = untrailingslashit( $path );
	$git = new Git();
	$has_dot_git = $git->has_dot_git( $path );
	$is_git_repo = $has_dot_git !== null;
	return $is_git_repo;
}

function oik_blocker() {
	$autoloaded = oik_blocker_autoload();
	if ( $autoloaded ) {
		$oik_blocker = new OIK_blocker();
		$component = oik_batch_query_value_from_argv( 1, 'unknown' );
		$new_version = oik_batch_query_value_from_argv( 2, '');
		if ( $component !== 'unknown') {
			oik_blocker_update_component_version( $oik_blocker, $component, $new_version );
		} else {
			oik_blocker_update_components_that_need_it( $oik_blocker );
		}
		oik_blocker_reload_loader();
	} else {
		echo "oik-autoload not available";
	}
}

function oik_blocker_update_component_version( $oik_blocker, $component, $new_version ) {
	echo "Component:" .  $component;
	echo "New version: " .  $new_version;
	echo PHP_EOL;
	//$component_type = oik_batch_query_value_from_argv( 3, 'plugin' );
	$oik_blocker->set_component( $component );
	$oik_blocker->set_new_version( $new_version );
	// $oik_blocker->set_component_type( $component_type );
	// Don't update the plugin if it's a git repo.
	// Don't update the plugin if new version is 'n'
	if ( false === oik_blocker_component_is_git_repo( $component )) {
		if ( 'n' !== $new_version ) {
			$oik_blocker->perform_update();
		} else {
			gob();
		}
	} else {
		echo "Component is a Git repo: " . $component;
	}
	$oik_blocker->process_blocks();

}

/**
 * Processes all plugins that have updates available.
 *
 * @TODO get_site_transient() may appear to return nothing.
 * This occurs when the transient expires. When this happens the plugins need to be rechecked.
 *
 * When we're updating existing plugins we don't create the oik-plugin entry if it's not there.
 * This allows for non-block plugins that need updating.
 */
function oik_blocker_update_components_that_need_it( $oik_blocker ) {
	$oik_blocker->set_create_plugin( false );
	$option = get_site_transient( "update_plugins" );
	//print_r( $option );
	if ( $option && is_array( $option->response ) ) {
		foreach ( $option->response as $plugin => $plugin_info ) {
			//echo $plugin_info->slug;
			//echo ' ';
			//echo $plugin_info->new_version;
			//echo PHP_EOL;
            if ( oik_blocker_should_update( $plugin_info ) ) {
                oik_blocker_update_component_version($oik_blocker, $plugin_info->slug, $plugin_info->new_version);
            }
		}
	}
}

function oik_blocker_should_update( $plugin_info ) {
    $should_update = true;
    if ( $plugin_info->slug === 'gutenberg' && $plugin_info->plugin !== 'gutenberg/gutenberg.php') {
        echo "Skipping: " . $plugin_info->plugin . PHP_EOL;
        $should_update = false;
    }
    return $should_update;
}

/**
 * Rebuilds the oik-loader map file.
 */
function oik_blocker_reload_loader() {
    if ( function_exists('oik_loader_run_oik_loader' )) {
        oik_loader_run_oik_loader();
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


	return( $classes );
}


oik_blocker();