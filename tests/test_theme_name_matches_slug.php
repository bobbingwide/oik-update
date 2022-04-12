<?php

/**
 * This isn't actually a test. It a fixup routine to correct oik-themes posts
 * where the _oikth_slug was incorrectly set during a theme update.
 * The values for the previously created theme were carried over in the $_POST array.
 * Problem occurred on 2022/04/11
 *
 * @var
 * $args
 */

$args = [ 'post_type' => 'oik-themes', 'numberposts' => -1];
$posts = get_posts( $args );
echo count( $posts );
echo PHP_EOL;

foreach ( $posts as $post ) {
	echo $post->ID;
	echo ' ';
	echo $post->post_name;
	echo ' ';
	$slug = get_post_meta( $post->ID, '_oikth_slug', true  );
	echo $slug;
	if ( $slug !== $post->post_name ) {
		$slug = str_replace( '-', '', $post->post_name );
		update_post_meta( $post->ID, '_oikth_slug', $slug );
		echo " Fixed?: " . $slug;
	}
	echo PHP_EOL;

}
