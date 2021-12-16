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
        $this->set_new_version( $this->theme_info->version );
        echo "Theme info: ";
        print_r( $this->theme_info );
        // $this->set_new_version( );
    }

    function get_theme_name() {
        return $this->theme_info->name;
    }

    function get_theme_description() {
        print_r( $this->theme_info );

        return $this->theme_info->sections->description;
    }

    function get_theme_preview_url() {
        return $this->theme_info->preview_url;
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
        include( ABSPATH . 'wp-admin/includes/file.php' );
        include( ABSPATH . 'wp-admin/includes/misc.php' );
        include( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
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
            $this->update_featured_image();
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


       return $content;
    }

    function block_atts_encode( $atts ) {
        $block_atts = json_encode( $atts, JSON_UNESCAPED_SLASHES );
        return $block_atts;
    }

    function generate_block( $block_type_name, $atts=null, $content=null ) {
        $block = "<!-- wp:$block_type_name ";
        if ( $atts ) {
            $block .= $atts;
            $block .= " ";
        }
        $block .= "-->";
        $block .= "\n";
        if ( $content ) {
            $block .= $content;
            $block .= "\n";
        }
        $block .= "<!-- /wp:$block_type_name -->";
        $block .= "\n\n";
        return $block;
    }



}