<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2021
 * @package oik-update
 */

/**
 * oik batch process to improve the registration of FSE themes listed in blocks.wp-a2z.org

 * Syntax: oikwp oik-themer.php theme
 *
 * e.g.
 * oikwp oik-themer.php slug url=blocks.wp.a2z
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

function oik_themer() {
    $autoloaded = oik_themer_autoload();
    if ( $autoloaded ) {
        $oik_themer = new OIK_themer();
        $component = oik_batch_query_value_from_argv( 1, 'unknown' );
        $new_version = oik_batch_query_value_from_argv( 2, '');
        //echo $component;
        //echo $new_version;
        //$component_type = oik_batch_query_value_from_argv( 3, 'plugin' );
        $oik_themer->set_component( $component );
        $oik_themer->get_theme_info();
        //$oik_themer->set_new_version( $new_version );
        //$oik_themer->set_component_type( $component_type );
        // Don't update the theme if new version is 'n'
        if ( 'n' !== $new_version) {

            $oik_themer->download_theme_version();

        }
        $oik_themer->update_oik_theme();
    } else {
        echo "oik-autoload not available";
    }
}



oik_themer();



