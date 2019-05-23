<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2019
 *
 * @package oik-update
 */

class OIK_blocker extends OIK_wp_a2z{

	//public $owner;  // GitHub repository owner ( incl. the 'C:/github/' prefix ? )
	//public $repo;   // GitHub repository name - which should match the component name, with some exceptions
	//public $component; // Component name in WP-a2z WordPress system
	//public $component_type; // Component type in WP-a2z WordPress system
	//public $current_version; // Current version of Git repo
	//public $new_version; // New version of the WordPress component to download and update to
	//public $target_file_name; // File name of the target .zip file
	public $plugin_post = null;

	function __construct() {
		parent::__construct();
		$this->set_component_type( 'plugin');

	}

	function set_owner( $owner=null ) {
		$this->owner = $owner;
	}
	function set_repo( $repo=null) {
		$this->repo = $repo;
	}

	function set_component( $component=null ) {
		$this->component = $component;
	}

	function set_component_type( $component_type='plugin') {
		$this->component_type = $component_type;
	}

	function set_current_version( $current_version=null ) {
		$this->current_version = $current_version;
	}

	function set_new_version( $new_version=null ) {
		$this->new_version = $new_version;
	}


	/**
	 * Applies updates for a new plugin version
	 *
	 * - Download the banner and icon
	 * - Download the new plugin version
	 * - Update the installed plugin to the new version
	 * - Update the oik_plugin, replacing the featured image 
	 */



	function perform_update() {
		$this->echo( "Component:", $this->component );
		$this->echo( "New version:", $this->new_version );
		$this->download_assets();
		$this->download_plugin_version();
		$this->update_installed_plugin();
		$this->update_oik_plugin();

	}

	/**
	 * Updates the installed plugin
	 * Basically we just want to update the plugin from the download folder
	 * @TODO There should be no need to do this multiple times!
	 * We should check the currently active version.
	 * This can be done using the virtual field.
	 */

	function update_installed_plugin() {
		//$repo_dir = WP_PLUGIN_DIR;
		$zip_file = $this->target_file_name;
		include( ABSPATH . 'wp-admin/includes/file.php' );
		include( ABSPATH . 'wp-admin/includes/misc.php' );
		include( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		$upgrader = new Plugin_Upgrader();
		$upgraded = $upgrader->install( $zip_file );
	}

	function update_oik_plugin() {
		oik_require( "admin/oik-apis.php", "oik-shortcodes" );
		//$component_id = oiksc_get_component_by_name( $this->component );
		$this->echo( "Component:", $this->component );
		$this->echo( 'Type:', $this->component_type );
		$plugin_post = oiksc_load_component( $this->component, $this->component_type );
		//print_r( $plugin_post );
		if ( null === $plugin_post ) {
			$plugin_post = $this->create_oik_plugin();
		} else {
			$this->echo( "ID:", $plugin_post->ID );
			$this->echo( 'Title:', $plugin_post->post_title );
		}

		$this->plugin_post = $plugin_post;

		$this->update_featured_image();
	}

	/**
	 *
	 * Update the featured image to be the latest asset
	 * if the new asset is different from the current one.
	 *
	 * $banner_filename = c:/apache/htdocs/downloads/banners/$plugin-772x250.$ext
	 * $attached_file = C:\apache\htdocs\wp-a2z/wp-content/uploads/sites/10/2019/03/block-gallery-banner-772x250-2.png
	 */

	function update_featured_image() {
		include_once ABSPATH . 'wp-admin/includes/image.php';
		$banner_filename = $this->get_asset_filename( 'banner', $this->component, $this->banner_ext );
		$this->echo( "Banner:", $banner_filename);
		$featured_image = get_post_thumbnail_id( $this->plugin_post->ID );
		if ( '' === $featured_image ) {
			$featured_image = $this->create_attachment( $banner_filename, "Banner", "Banner desc", $this->plugin_post->ID );
			$this->set_thumbnail_id( $featured_image );

		} else {
			$this->echo( 'Featured:', $featured_image );
			$attached_file = get_attached_file( $featured_image, true );
			$this->maybe_replace_featured_image( $banner_filename, $attached_file );

		}
		//$this->set_thumbnail_id( $featured_image );
	}

	/**
	 * Creates the attachment file from the temporary file
	 *
	 * Upload the file to a new attachment and make it the featured image
	 * Note: If we don't copy the $file then this gets deleted
	 * We need to create a temporary file
	 *
	 * Use media_handle_sideload() to do the validation and storage stuff
	 */
	function create_attachment( $file, $name, $desc, $post_id=0 ) {
		$file_array['tmp_name'] = $this->copy_to_tmp_name( $file );
		$file_array['type'] = mime_content_type( $file );
		$file_array['name'] = basename( $file );

		bw_trace2( $file_array );
		include ABSPATH  . 'wp-admin/includes/media.php';
		$id = media_handle_sideload( $file_array, $post_id, $desc );
		if ( is_wp_error( $id ) ) {
			bw_trace2( $id );
			print_r( $id );
			gob();
		} else {
			// e( "attachment: $id" );
		}
		return( $id );
	}

	function copy_to_tmp_name( $file ) {
		$tmp_name = wp_tempnam( $file );
		copy( $file, $tmp_name );
		$this->echo( 'tmp_name', $tmp_name );
		return $tmp_name;
	}

	function set_thumbnail_id( $featured_image ) {
		update_post_meta( $this->plugin_post->ID, "_thumbnail_id", $featured_image );
	}

	function maybe_replace_featured_image( $banner_filename, $attached_file ) {
		if ( file_exists( $banner_filename ) ) {
			copy( $banner_filename, $attached_file );
			$this->echo( 'Attached:', $attached_file );
		} else {
			$this->echo( "Banner gone:", $banner_filename );
		}
	}





}