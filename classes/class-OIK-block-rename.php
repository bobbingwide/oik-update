<?php

/**
 * @copyright (C) Copyright Bobbing Wide 2022
 * @package oik-update
 */

class OIK_block_rename
{

    private $old_block_name;
    private $old_variation_name;
    private $new_block_name;
    private $new_variation_name;

    /**
     * When it works the old block / old variation is re-used.
     * @var
     */
    private $old_block;
    private $old_variation;
    private $new_block;
    private $new_variation;

    private $post_to_process;

    function __construct( $old_block, $old_variation, $new_block, $new_variation ) {
        $this->new_rename( $old_block, $old_variation, $new_block, $new_variation );
    }

    function new_rename( $old_block, $old_variation, $new_block, $new_variation ) {
        $this->reset();
        $this->old_block_name = $old_block;
        $this->old_variation_name = $old_variation;
        $this->new_block_name = $new_block;
        $this->new_variation_name = $new_variation;
    }


    /**
     * Resets the posts.

     */
    function reset() {
        $this->old_block = null;
        $this->old_variation = null;
        $this->new_block = null;
        $this->new_variation = null;
    }

    /**
     * Investigate
     *
     * - Check if the new block already exists
     * - Check if the new variation already exists
     * - Process depending on what's there.
     *
     * This table is harder to create than it is to write the code!
     *
     * New block | New variation | Old block | Old variation | Processing
     * --------- | ------------- | --------- | ------------- | ------
     * x        |   null        |  n/a      | n/a           | New block already exists: x
     * x        |   y           |  n/a      | n/a           | New variation already exists: y
     * not exists | null        |  x        | null          | Update: x
     * not exists | null        |  x        | y             | Update: y.  The old block remains. Unlikely scenario
     * not exists | not exists  |  x        | null          | Update: x
     *
     */
    function investigate() {
        //oik_require( "admin/oik-create-or-update-block.php", "oik-shortcodes" );
        oik_require( 'admin/oik-update-blocks.php', 'oik-shortcodes');
        oik_require( 'admin/oik-create-blocks.php', 'oik-shortcodes');
        oik_require( "includes/bw_posts.php" );
        echo "Investigating" , PHP_EOL;
        $this->new_block = oiksc_get_block( $this->new_block_name, 0, null );
        if ( $this->new_block && null === $this->new_variation_name ) {
            echo "New block already exists: " . $this->new_block->ID;
            return;
        }
        if ( $this->new_variation_name && $this->new_block ) {
            $this->new_variation = oiksc_get_block( null, $this->new_block->ID, $this->new_variation_name );
            if ( $this->new_variation ) {
                echo "New variation already exists: " . $this->new_variation->ID;
                return;
            }
        }

        $this->old_block = oiksc_get_block( $this->old_block_name, 0, null );
        if ( !$this->old_block ) {
            echo "Old block doesn't exist";
            return;
        } else {
            if ( $this->old_variation_name ) {
                $this->old_variation = oiksc_get_block( null, $this->old_block->ID, $this->old_variation_name );
                if ( !$this->old_variation ) {
                    echo "Old variation doesn't exist";
                    return;
                } else {
                    $this->post_to_process = $this->old_variation;
                }
            } else {
                $this->post_to_process = $this->old_block;
            }
        }


        $this->update_block();
    }

    /**
     * Updates the existing block to the new block name or variation.
     *
     * When creating a variation we need the new_block ID for the parent
     * When creating a block we need to set the parent to 0
     *
     * We should also consider updating any instances of the block's title in the post content.
     * But this can be done prior to the clone, which is a manual process.
     * Other things that'll need changing
     *
     * - title
     * - blockicon
     * - screenshot
     * - examples
     * - toolicons
     * - SEO stuff:
     *   - focus keyphrase
     *   - slug
     *   - meta description
     * - featured image file name
     * - excerpt
     *
     * Note: block categories should remain as embed.
     */

    function update_block() {
        echo "Updating: " . $this->post_to_process->ID;
        $post = get_post( $this->post_to_process );
        $post->post_name = $this->post_name( $post->post_name );
        $post->post_parent = ( null !== $this->new_variation_name ) ? $this->new_block->ID : 0;

        $post->post_title = $this->post_title( $post->post_title );
        $_POST['_block_type_name'] = $this->new_block_name;
        $_POST['_block_variation'] = $this->new_variation_name;
        //print_r( $post );
        wp_update_post( $post );
    }

    function post_name( $post_name ) {
        echo "Post name: $post_name";
        $old_block_name = str_replace( '/', '-', $this->old_block_name );
        $new_block_name = str_replace( '/', '-', $this->new_block_name );
        //if ( null === $this->new_variation_name ) {
            $new_post_name = str_replace( $old_block_name, $new_block_name, $post_name );
        //}

        echo "Becomes: " . $new_post_name;
        return $new_post_name;
    }

    /**
     * Returns the new post title.
     *
     * When it's a variation the post title becomes the variation title - block_name
     * When it's a normal block the post title becomes the block title - block name
     *
     * @param $post_title
     * @return array|string|string[]
     */
    function post_title( $post_title ) {
        $new_post_title = str_replace( $this->old_block_name, $this->new_block_name, $post_title );
        return $new_post_title;
    }

}