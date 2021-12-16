<?php

/**
 * @copyright (C) Copyright Bobbing Wide 2021
 * @package oik-update
 */

class OIK_block_updater
{
    // plugin or theme slug
    private $component = null;
    // plugin or theme
    private $component_type = null;

    private $block_json_files = [];

    function __construct() {

    }

    function set_plugin_info( $component, $component_type ) {
        $this->component = $component;
        $this->component_type = $component_type;
    }

    function process_blocks() {
        $path = $this->get_component_path();
        $this->locate_block_json_files($path);
        echo "Blocks: " . count($this->block_json_files) . PHP_EOL;
        oik_require( 'admin/oik-create-or-update-block.php', 'oik-shortcodes');
        foreach ( $this->block_json_files as $file ) {
            $this->process_block($file);
        }
    }

    /**
     * Locates all the block.json files in the plugin.
     *
     * @param $path
     */
    function locate_block_json_files( $path ) {
        //echo "Locating block.json files in:" . $path . PHP_EOL;
        $files = glob( $path .'/block.json');
        //print_r( $files );
        if ( count( $files )) {
            $this->add_block_json_files( $files );
        }
        $dirs = glob( $path . '/*', GLOB_ONLYDIR);
        //print_r( $dirs );

        foreach ( $dirs as $dir ) {
            //echo $dir . PHP_EOL;
            // Don't process the folder again.
            if ( $dir === $path ) continue;
            //echo $dir . PHP_EOL;
            $subdir = basename( $dir );
            //echo $subdir . PHP_EOL;
            // Ignore the node_modules directory tree
            if ( 'node_modules' === $subdir) continue;
            $this->locate_block_json_files( $dir );
        }
    }

    /**
     * Merges the newly found file(s) with the current array.
     *
     * @param $files
     */
    function add_block_json_files( $files ) {
        $this->block_json_files = array_merge( $this->block_json_files, $files );
    }

    function get_component_path() {
        $path = ABSPATH;
        if ( 'plugin' === $this->component_type ) {
            $path = WP_PLUGIN_DIR;
        } else {
            $path = WP_THEME_DIR;
        }
        //echo $path;
        $path .= '/';
        $path .= $this->component;
        return $path;
    }

    /**
     * Processes an individual block.json file.
     *
     * @param $file
     */
    function process_block( $file ) {
        //echo $file . PHP_EOL;
        $contents = file_get_contents( $file );
        $block = json_decode( $contents );
        // print_r( $contents );
        echo $block->name . PHP_EOL;
        echo $block->title . PHP_EOL;
        $this->populate_request( $block );
        oiksc_lazy_create_or_update_block();
        bw_flush();

        //echo $block->description . PHP_EOL;
        //echo $block->category . PHP_EOL;
        //print_r( $block);
        /*
        [name] => woocommerce/checkout-totals-block
        [version] => 1.0.0
    [title] => Checkout Totals
        [description] => Column containing the checkout totals.
        [category] => woocommerce

        */
    }

    /**
     * Populates $_REQUEST with the fields used by oiksc_lazy_create_or_update_block().
     *
     * This is a QAD solution. It doesn't support:
     * - blocks which are variations
     * - setting of the icon information
     * - Setting of the parent block, used by some inner blocks
     *
     * See https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/
     * @param $block
     */
    function populate_request( $block ) {
        $_REQUEST['title'] = $this->get_property( $block, 'title') ;
        $_REQUEST['name'] = $this->get_property( $block, 'name' );
        $_REQUEST['component'] = $this->component;
        $_REQUEST['keywords'] = $this->get_property( $block, 'keywords' );
        $_REQUEST['category'] = $this->get_property( $block, 'category' );
        $_REQUEST['icon'] = null; // $this->get_property( $block, 'icon' );
        $_REQUEST['description' ] = $this->get_property( $block, 'description' );
        $_REQUEST['variation'] = null;
    }

    /**
     * Safely gets a property, if present.
     *
     * @param object $block JSON decoded block.json
     * @param string property name - top level only
     * @returns mixed property value or null
     */
    function get_property( $block, $property) {
        if ( property_exists( $block, $property) ) {
            $value = $block->{ $property };
        } else {
            $value = null;
        }
        return $value;
    }

}