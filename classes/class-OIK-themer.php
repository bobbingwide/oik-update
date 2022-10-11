<?php

/**
 * @copyright (C) Copyright Bobbing Wide 2021
 *
 * @package oik-update
 */

class OIK_themer extends OIK_wp_a2z{

    /**
     * Theme info from wordpress.org
     * It's downloaded by wp-top12 into a locally cached json file.
     * @var
     */
    private $theme_info;

    /**
     * oik-theme post.
     * @var
     */
    private $theme_post;

    function __construct() {
        parent::__construct();
        $this->set_component_type( 'theme');
    }

    function perform_update() {
        $this->echo( "Component:", $this->component );
        $this->echo( "New version:", $this->new_version );
        //$this->download_assets();
        $this->get_theme_info();
        //$this->download_theme_version();
        //$this->update_installed_theme();
        //$this->update_oik_theme();

    }


    /**
     * Loads the theme info from the saved information for FSE themes.
     *
     * Saves running the queries multiple times.
     */
    function get_theme_info() {
        $wpodt = new WP_org_downloads_themes();
        if ( null === $wpodt ) {
            gob();
        }
        $wpodt->load_all_themes();
        echo "Component; ";
        echo $this->component;

        $this->theme_info = $wpodt->get_theme( $this->component );

        if ( null === $this->theme_info || is_wp_error( $this->theme_info ) ) {
            // Theme not found from the wordpress.org downloads - perhaps it's a local one
            $this->theme_info = $this->get_local_theme_info();

        }
        //print_r( $this->theme_info );


        if ( null === $this->theme_info ) {
            $this->theme_info = $wpodt->get_download( $this->component );
            print_r( $this->theme_info );
            if ( null === $this->theme_info ) {
                echo "Error: no information for theme: " . $this->component;
                gob();
            } else {
                $wpodt->save_theme_info($this->component, $this->theme_info);
                //$this->theme_info = $wpodt->get_theme( $this->component );
            }
        }
        //print_r( $this->theme_info );
        $this->set_new_version( $this->theme_info->version );
        echo "Theme info: ";
        print_r( $this->theme_info );
        // $this->set_new_version( );
    }

    function get_local_theme_info() {
        $local_theme = wp_get_theme( $this->component );
        print_r( $local_theme );

        $theme_info = new stdClass();
        $theme_info->slug = $this->component;
        $theme_info->name = $local_theme->get('Name');
        $theme_info->version = $local_theme->get( 'Version' );
        $theme_info->sections = new stdClass();
        $theme_info->sections->description = $local_theme->get('Description');
        $template = $local_theme->get('Template');
        if ( !empty( $template ) ) {
            $theme_info->template = $template;
        }
        // @TODO last_updated_time
        $theme_info->preview_url = null;
        $theme_info->screenshot_url = 'screenshot.png';
        $theme_info->last_updated_time = time();
        print_r( $theme_info );
        return $theme_info;
    }

    function get_theme_name() {
        return $this->theme_info->name;
    }

    function get_theme_description() {
        //print_r( $this->theme_info );

        return $this->theme_info->sections->description;
    }

    function get_theme_preview_url() {
        return $this->theme_info->preview_url;
    }

    /**
     * Gets the post ID of the parent theme.
     *
     * If this is a child theme we need the post ID of the parent theme.
     */
    function get_theme_template() {
        $parent = null;
        if (property_exists($this->theme_info, 'template')) {
            $parent = $this->theme_info->template;
        }
        return $parent;
    }

    /**
     * Returns the post ID of the parent theme.
     *
     * @return int|null null when it's not a child theme. 0 when parent is not registered. Otherwise post ID
     */
    function get_theme_template_ID() {
        $parent_id = 0;
        $parent = $this->get_theme_template();
        if ( $parent ) {
            $parent_post = oiksc_load_component($parent, $this->component_type);
            if ( $parent_post ) {
                $parent_id = $parent_post->ID;
            }
        } else {
            $parent_id = null;
        }
        return $parent_id;
    }

    /**
     * Returns the local filename for the screenshot.
     *
     * The screenshot may be a .png or .jpg file
     * so we need to find the basename from screenshot_url
     *
     * @return string
     */
    function get_screenshot_filename() {
        $filename = parse_url( $this->theme_info->screenshot_url, PHP_URL_PATH );
        $basename = basename( $filename );

        $screenshot_filename = WP_CONTENT_DIR;
        $screenshot_filename .= '/themes/';
        $screenshot_filename .= $this->component;
        $screenshot_filename .= '/';
        $screenshot_filename .= $basename;

        return $screenshot_filename;
    }


    function download_theme_version() {
        $oik_component_update = new OIK_component_update();
        $oik_component_update->set_component( $this->component );
        $oik_component_update->set_new_version( $this->new_version );
        $oik_component_update->set_component_type( $this->component_type );
        $error = $oik_component_update->download_theme_version();
        print_r( $error );
        $this->zip_file = $oik_component_update->target_file_name;
        //$this->unpack_theme_version( $oik_component_update );
        $this->update_installed_theme();
    }

    /**
     * Unpacks the theme to the themes folder.
     *
     * Note: This unpacks the theme to the wp-a2z theme folder.
     * This could be symlinked and also a Git repo.
     * But that shouldn't really be a problem should it?
     *
     * @param $oik_component_update
     */
    function unpack_theme_version( $oik_component_update ) {
        $repo_dir = WP_CONTENT_DIR . '/themes';
        echo $repo_dir;
        $zip_file = $this->zip_file;
        $save_dir = getcwd();
        chdir( $repo_dir );
        $oik_component_update->do7zip_extract( $zip_file, null  );
        chdir( $save_dir );
    }

    /**
     * Updates the installed theme
     *
     * Basically we just want to update the theme from the download folder
     *
     * @TODO There should be no need to do this multiple times!
     * We should check the currently active version.
     * This can be done using the virtual field.
     */
    function update_installed_theme() {
        //$zip_file = $this->target_file_name;
        if ( !function_exists( 'get_file_description')) {
            include_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        include_once( ABSPATH . 'wp-admin/includes/misc.php' );
        include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
        $upgrader = new Theme_Upgrader();
        $upgraded = $upgrader->install( $this->zip_file );
    }

    function update_oik_theme() {
        //oik_require( "admin/oik-apis.php", "oik-shortcodes" );
        //$component_id = oiksc_get_component_by_name( $this->component );
        $this->echo( "Component:", $this->component );
        $this->echo( 'Type:', $this->component_type );
        //$this->get_theme_data();
        $this->theme_post = oiksc_load_component( $this->component, $this->component_type );
        //print_r( $theme_post );
        if ( null === $this->theme_post ) {
            $this->create_oik_theme();
        } else {
            $this->echo( "ID:", $this->theme_post->ID );
            $this->echo( 'Title:', $this->theme_post->post_title );
            $this->alter_oik_theme();
        }

        if ( $this->theme_post ) {

            $screenshot_filename = $this->get_screenshot_filename();
            $this->update_featured_image( $this->theme_post->ID, $screenshot_filename, "Screenshot", "Screenshot for " . $this->theme_post->post_title );
        }
    }

    function create_oik_theme() {
        $this->echo( "Creating:", $this->component );

        $post = array();
        $post['post_title'] = $this->get_theme_name();
        $post['post_content'] = $this->create_theme_content();
        $post['post_type'] = 'oik-themes';
        $post['post_status'] = 'publish';
        $_POST['post_author'] = 1;

        $_POST['_oikth_type'] = '8';
        $_POST['_oikth_slug'] = $this->component;
        $_POST['_oikth_desc'] = $this->get_theme_description();
        $_POST['_oikth_demo'] = $this->get_theme_preview_url();
        $_POST['_oikth_template'] = $this->get_theme_template_ID();

        // Yoast SEO post meta data
        $_POST['_yoast_wpseo_focuskw'] = $this->get_theme_name() . ' WordPress Full Site Editing theme';
        $_POST['_yoast_wpseo_metadesc'] = $this->get_theme_name() . ' is a WordPress Full Site Editing theme.';
        //print_r( $post );
        //print_r( $_POST );
        //gob();
        $ID = wp_insert_post( $post );
        if ( $ID ) {
            $this->echo( "Created:", $ID );
            $this->theme_post = get_post( $ID );
        } else {
            $this->echo( "Failed:", $this->component );
            gob();
        }

    }

    /*
$template[] = [ 'core/paragraph', [ 'placeholder' => 'Copy the plugin description'] ];
$template[] = [ 'core/shortcode', [ 'text' => '[bw_plug name=plugin banner=p]' ] ];
$template[] = [ 'core/paragraph', [ 'content' => 'This plugin provides xx blocks' ] ];
$template[] = [ 'core/more' ];
$template[] = [ 'oik-block/blocklist' ];
$template[] = [ 'core/shortcode', [ 'text' => '[bw_plug name=plugin table=y]' ] ];

{"prefix":"advgb","showBatch":true,"component":"advanced-gutenberg"}
*/

    function create_theme_content() {
        //oik_require( 'admin/oik-create-blocks.php', 'oik-shortcodes');
        $content = null;
        $content = $this->generate_block( "paragraph", null, $this->get_theme_description() );
        $content .= $this->generate_block( "more", null, '<!--more-->' );
        $content .= $this->generate_block( 'post-featured-image', null, null);
       return $content;
    }

    function alter_oik_theme() {
    	$post_arr = [];
    	$post_arr['ID'] = $this->theme_post->ID;
    	$post_arr['post_title'] = $this->theme_post->post_title;
    	// Can we control what this gets set to?
    	$post_arr['modified'] = $this->theme_info->last_updated_time;
	    //print_r( $this->theme_post );
	    //print_r( $this->theme_info );
	    //gob();

	    $_POST['post_author'] = 1;

	    $_POST['_oikth_type'] = '8';
	    $_POST['_oikth_slug'] = $this->component;
	    $_POST['_oikth_desc'] = $this->get_theme_description();
	    $_POST['_oikth_demo'] = $this->get_theme_preview_url();
	    $_POST['_oikth_template'] = $this->get_theme_template_ID();

	    // Yoast SEO post meta data
	    $_POST['_yoast_wpseo_focuskw'] = $this->get_theme_name() . ' WordPress Full Site Editing theme';
	    $_POST['_yoast_wpseo_metadesc'] = $this->get_theme_name() . ' is a WordPress Full Site Editing theme.';
	    //print_r( $post );
	    wp_update_post( $post_arr );

    }

	/**
	 * Returns the new version of the theme, if necessary.
	 *
	 * @param $theme_info
	 */
    function is_new_version( $theme_info ) {
        $new_version = $theme_info->version;
        // Temporarily force new version.
        //return $new_version;

    	//print_r( $theme_info );
    	$theme = wp_get_theme( $theme_info->slug );
    	//print_r( $theme );
    	if ( $theme ) {
    	    if ( version_compare(  $new_version, $theme->Version, 'le' ) ) {
		        $new_version= null;
	        }
        }
    	return $new_version;
    }

	/**
	 * Previews the theme in order to cache the theme's patterns.
	 *
	 */
    function preview_theme() {
	    $permalink = get_permalink( $this->theme_post->ID);
	    $permalink = add_query_arg('preview_theme', $this->theme_info->slug, $permalink );
	    echo "Previewing theme: " . $permalink . PHP_EOL;
		$response = wp_remote_get( $permalink, [ 'ssl_verify' => false ] );
    }
    
}