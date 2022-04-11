<?php

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
