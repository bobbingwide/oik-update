<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2022
 * @package oik-update
 *
 */

/**
 * oik batch process to rename a block.
 *
 * This logic is primarily to rename core-embed blocks to their new variation.
 *

 * Syntax: oikwp oik-block-rename.php old-block[:old-variation] new-block[:variation]
 *
 * eg
 * oikwp oik-block-rename.php core-embed/amazon-kindle core/embed:amazon-kindle
 *
 */
if ( PHP_SAPI !== 'cli' ) {
    die();
}

function oik_block_rename_loaded() {
    add_action( "run_oik-block-rename.php", "oik_block_rename" );
}

function oik_block_rename_autoload() {
    $autloaded = false;
    $lib_autoload = oik_require_lib( "oik-autoload" );
    if ( $lib_autoload && !is_wp_error( $lib_autoload ) ) {
        add_filter( "oik_query_autoload_classes" , "oik_block_rename_query_autoload_classes" );
        oik_autoload();
        $autoloaded = true;
    }	else {
        bw_trace2( $lib_autoload, "oik-autoload not loaded", false );
        $autoloaded = false;
    }
    return $autoloaded;
}

function oik_block_rename_query_autoload_classes( $classes ) {
    $classes[] = array( "class" => "OIK_block_rename"
    , "plugin" => "oik-update"
    , "path" => "classes"
    , 'file' => 'classes/class-OIK-block-rename.php'
    );
    return( $classes );
}

/**
 * Renames the selected block(s).
 *
 * If first arg is 'core-embed' then do all the core-embed blocks
 * Otherwise do the selected block.
 */

function oik_block_rename() {
    $autoloaded = oik_block_rename_autoload();
    if ( $autoloaded ) {

        $old_block = oik_batch_query_value_from_argv(1, null );
        if ( $old_block === 'core-embed') {
            $renames = oik_block_core_embed_renames();
            foreach ($renames as $rename) {
                $old_block = "core-embed/$rename";
                $old_variation = null;
                $new_block = 'core/embed';
                $new_variation = $rename;
                echo "Renaming: $old_block:$old_variation to $new_block:$new_variation";
                echo PHP_EOL;
                $oik_block_rename = new OIK_block_rename($old_block, $old_variation, $new_block, $new_variation);
                $oik_block_rename->investigate();

            }
        } else {

            $old_block = oik_batch_query_value_from_argv(1, 'core-embed/amazon-kindle');
            $old = explode(':', $old_block);
            $old_block = $old[0];
            $old_variation = isset($old[1]) ? $old[1] : null;

            $new_block = oik_batch_query_value_from_argv(2, 'core/embed:amazon-kindle');
            $new_variation = null;
            if (false !== strpos($new_block, ':')) {
                $new = explode(':', $new_block);
                $new_block = $new[0];
                $new_variation = $new[1];
            }
            echo "Renaming: $old_block:$old_variation to $new_block:$new_variation";
            echo PHP_EOL;
            $oik_block_rename = new OIK_block_rename($old_block, $old_variation, $new_block, $new_variation);
            $oik_block_rename->investigate();
        }
    } else {
        echo "oik-autoload not available";
    }
}

function oik_block_core_embed_renames() {
    $renames = [];
    $renames[] = 'twitter';
    $renames[] = 'youtube';
    $renames[] = 'youtube';
    $renames[] = 'facebook';
    $renames[] = 'instagram';
    $renames[] = 'wordpress';
    $renames[] = 'soundcloud';
    $renames[] = 'spotify';
    $renames[] = 'flickr';
    $renames[] = 'vimeo';
    $renames[] = 'animoto';
    $renames[] = 'cloudup';
    $renames[] = 'collegehumor';
    $renames[] = 'crowdsignal';
    $renames[] = 'dailymotion';
    $renames[] = 'imgur';
    $renames[] = 'issuu';
    $renames[] = 'kickstarter';
    $renames[] = 'mixcloud';
    $renames[] = 'reddit';
    $renames[] = 'reverbnation';
    $renames[] = 'screencast';
    $renames[] = 'scribd';
    $renames[] = 'slideshare';
    $renames[] = 'smugmug';
    $renames[] = 'speaker-deck';
    $renames[] = 'tiktok';
    $renames[] = 'ted';
    $renames[] = 'tumblr';
    $renames[] = 'videopress';
    $renames[] = 'wordpress-tv';
    $renames[] = 'amazon-kindle';
    $renames[] = 'pinterest';
    $renames[] = 'wolfram-cloud';
    return $renames;

}

oik_block_rename_loaded();