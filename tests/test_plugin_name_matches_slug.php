<?php

/**
 * This isn't actually a test. It a fixup routine to correct oik-plugins posts
 * where the _oikp_slug was incorrectly set during a plugin update.
 * The other post meta fields are also incorrect.
 *
 * The values for the previously created plugin were carried over in the $_POST array.
 * Problem occurred on 2022/04/11
 *
 * @var
 * $args
 */
oik_require( 'includes/bw_posts.php');
$plugins = oops_installed_plugins();
foreach ( $plugins as $plugin_name => $plugin_info ) {
    echo $plugin_name ;
    echo $plugin_info['Name'];
    $post = get_oik_plugin_by_name( $plugin_info['Name'] );
    if  ( $post ) {
        update_post($post, $plugin_name, $plugin_info);
    } else {
        //$post= get_oik_plugin_by_
    }

}


function get_oik_plugin_by_name( $name ) {
    $post_name = sanitize_title( $name );
    echo "$name:  $post_name", PHP_EOL;

    $args =   $args = ['post_type' => 'oik-plugins', 'numberposts' => -1, 'post_name' => $post_name];
    $posts = bw_get_posts( $args );
    $count = count( $posts );
    switch ( $count ) {
        case 0:
            echo "Post not found for: $name $post_name" . PHP_EOL . PHP_EOL;
            return null;
        case 1:
            return $posts[0];
        default:
            echo "Wrong number of posts for: $name $post_name" . PHP_EOL;
            print_r( $posts );
            echo PHP_EOL;
            return null;
    }
    return $posts[0];
}

function update_post( $post, $plugin_name, $plugin_info ) {
    //update_post_meta( $post->ID, '_oikp_slug', $plugin_info)
    echo "Updating post: " . $post->ID;
    echo PHP_EOL;
    //echo "New slug:" . $plugin_info->
    $parts = explode( '/', $plugin_name );
    $slug = $parts[0];
    echo "New slug: " . $slug;
    echo PHP_EOL;
    echo "New plugin name: " .  $plugin_name;
    echo PHP_EOL;
    echo "Name: " . $plugin_info['Name'];
    echo PHP_EOL;
    //echo "Title: " . $plugin_info['Title'];
    //echo PHP_EOL;
    //echo "Desc: " . $plugin_info['Description'];
    if ( $plugin_info['Name'] !== $plugin_info['Title']) { gob(); }


    //print_r( $plugin_info );

    if ( true ) {
        update_post_meta($post->ID, '_oikp_slug', $slug);
        update_post_meta($post->ID, '_oikp_name', $plugin_name);
        update_post_meta($post->ID, '_oikp_desc', $plugin_info['Name']);
        $plugin_uri = isset( $plugin_info['PluginURI']) ? $plugin_info['PluginURI'] : '';
        update_post_meta($post->ID, '_oikp_uri', $plugin_uri );
    }
    //$_POST['_oikp_slug'] = $this->component;
    //$_POST['_oikp_name'] = $this->get_plugin_file_name();
    // $_POST['_oikp_desc'] = $this->get_plugin_name();
    //$_POST['_oikp_uri'] = $this->get_plugin_uri();

}


function get_oik_plugins()
{
    $args = ['post_type' => 'oik-plugins', 'numberposts' => -1];
    $posts = get_posts($args);
    echo count($posts);
    echo PHP_EOL;

    foreach ($posts as $post) {
        echo $post->ID;
        echo ' ';
        echo $post->post_name;
        echo ' ';
        $slug = get_post_meta($post->ID, '_oikp_slug', true);
        echo $slug;

        //if ($slug !== $post->post_name) {
        //    $slug = str_replace('-', '', $post->post_name);
        //    update_post_meta($post->ID, '_oikth_slug', $slug);
        //    echo " Fixed?: " . $slug;
        //}
        echo PHP_EOL;

    }
    return $posts;
}

function oops_installed_plugins() {
    $plugins = get_plugins();
    //print_r( $plugins );
    return $plugins;
}
