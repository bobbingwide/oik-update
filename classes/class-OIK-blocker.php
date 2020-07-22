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
	public $plugin_data = null; /* From the main plugin file */
	public $plugin_file = null; /* e.g. oik.php - basename ( second half ) of oik/oik.php for _oikp_name */

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
		//oik_require( "admin/oik-apis.php", "oik-shortcodes" );
		//$component_id = oiksc_get_component_by_name( $this->component );
		$this->echo( "Component:", $this->component );
		$this->echo( 'Type:', $this->component_type );
		$this->get_plugin_data();
		$this->plugin_post = oiksc_load_component( $this->component, $this->component_type );
		//print_r( $plugin_post );
		if ( null === $this->plugin_post ) {
			$this->create_oik_plugin();
		} else {
			$this->echo( "ID:", $this->plugin_post->ID );
			$this->echo( 'Title:', $this->plugin_post->post_title );
			$this->alter_oik_plugin();
		}

		if ( $this->plugin_post ) {
			$this->update_featured_image();
		}
	}

	function create_oik_plugin() {
		$this->echo( "Creating:", $this->component );

		$post = array();
		$post['post_title'] = $this->get_plugin_name();
		$post['post_content'] = $this->create_plugin_content();
		$post['post_type'] = 'oik-plugins';
		$post['post_status'] = 'publish';
		$_POST['post_author'] = 1;
		$_POST['_oikp_type'] = '1';
		$_POST['_oikp_slug'] = $this->component;
		$_POST['_oikp_name'] = $this->get_plugin_file_name();
		$_POST['_oikp_desc'] = $this->get_plugin_name();
		$_POST['_oikp_uri'] = $this->get_plugin_uri();
		//print_r( $post );
		//print_r( $_POST );
		//gob();
		$ID = wp_insert_post( $post );
		if ( $ID ) {
			$this->echo( "Created:", $ID );
			$this->plugin_post = get_post( $ID );
		} else {
			$this->echo( "Failed:", $this->component );
			gob();
		}

	}

	function alter_oik_plugin() {
		if ( $this->maybe_update_content() ) {
			$this->echo( "Updating:", $this->plugin_post->post_title );
			$this->plugin_post->post_content = $this->create_plugin_content();
			//print_r( $this->plugin_post );
			wp_insert_post( $this->plugin_post );
		}
	}

	function maybe_update_content() {
		$pos = strpos( $this->plugin_post->post_content, '[bw_plug' );
		$update = $pos === false;
		return $update;
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

	function create_plugin_content() {
		//oik_require( 'admin/oik-create-blocks.php', 'oik-shortcodes');
		$content = null;

		$para = '<p class="has-background has-luminous-vivid-orange-background-color">';
		$para .= $this->get_plugin_name();
		$para .= " v[bw_field _component_version] delivers [bw_field _oikp_block_count] blocks. The catalogue is not yet started.";
		$para .= '</p>';
		$content .= $this->generate_block( "paragraph", $this->block_atts_encode( ['backgroundColor' => 'luminous-vivid-orange'] ), $para );
		$content .= $this->generate_block( "more", null, '<!--more-->' );
		$content .= $this->generate_block( 'shortcode', null, "[bw_plug name={$this->component} banner={$this->banner_ext}]" );
		$placeholder = $this->block_atts_encode( [ "placeholder" => "Plugin short description"]);
		//$this->get_plugin_data();
		$short_description = $this->get_short_description();
		$content .= $this->generate_block( "paragraph", $placeholder, $short_description );
		//$content .= $this->generate_block( "more", null, '<!--more-->' );

		$prefix = $this->get_plugin_block_prefix();
		$atts = $this->block_atts_encode( [ 'showBatch' => 'true', 'component' => $this->component, 'prefix' => $prefix ]);
		$content .= $this->generate_block( "oik-block/blocklist", $atts );
		$content .= $this->generate_block( 'shortcode', null, "[bw_plug name={$this->component} table=y]" );

		//$content .= $this->generate_block( "heading", null, "<h2>Example</h2>" );
		//$content .= $this->generate_block( "spacer", null, '<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>' );
		//$placeholder = $this->block_atts_encode( [ "placeholder" => "Type / to choose the sample block"]);
		//$content .= $this->generate_block( "paragraph", $placeholder, "<p></p>");
		//$content .= $this->generate_block( $block_type_name );
		//$content .= $this->generate_block( "spacer", null, '<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>' );
		$content .= $this->generate_block( "separator", null, '<hr class="wp-block-separator"/>');
		$content .= $this->generate_block( "heading", null, "<h2>Notes</h2>");
		$content .= $this->generate_block( "list", null, '<ul><li>TBC</li></ul>');
		//echo $content;
		//oikb_get_response( "Continue?", true );
		//gob();
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

	/**
	 * Gets the plugin_data for the first plugin file found in the plugin.
	 * Sets the plugin_file as well as plugin_data
	 *
	 * @TODO Maybe it should get the one that matches the plugin directory
	 */

	function get_plugin_data() {
		$plugins = get_plugins( '/' . $this->component ) ;
		//oik_require( 'shortcodes/oik-plug.php', 'oik-bob-bing-wide' );
		//$this->plugin_data = bw_get_plugin_data( $this->component );
		if ( $plugins && is_array( $plugins ) ) {
			$this->plugin_file = key( $plugins );
			$this->plugin_data = $plugins[ $this->plugin_file ];
		} else {
			$this->echo( "Error:", "Missing plugin file.");
		}
		$this->echo( "Plugin file:", $this->plugin_file );

	}

	/**
	 * Returns the file name of the main plugin file
	 *
	 * @return string
	 */
	function get_plugin_file_name() {
		return $this->component . '/' . $this->plugin_file;
	}

	/**
	 * Get the plugin's Name
	 *
	 * To be used for the post_title and _oikp_desc
	 */
	function get_plugin_name() {
		$plugin_name = bw_array_get( $this->plugin_data, 'Name', $this->component);
		return $plugin_name;
	}

	/**
	 * Get's the plugin's URI
	 */
	function get_plugin_uri() {
		print_r( $this->plugin_data );
		$plugin_uri = bw_array_get( $this->plugin_data, 'PluginURI', null );
		return $plugin_uri;
	}

	/**
	 * Gets the plugin's short description for the first paragraph of the post_content
	 * @return string
	 */
	function get_short_description() {
		$short_description = '<p>';
		$short_description .= bw_array_get( $this->plugin_data, 'Description', null );
		$short_description .= '</p>';
		return $short_description;
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
		if ( '' === $featured_image || 0 == $featured_image ) {
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

	function get_plugin_block_prefix() {
		$wpod = new WP_org_v12_downloads();
		$wpod->get_download( $this->component );
		$prefix = $wpod->get_block_prefix();
		echo "Prefix: " . $prefix;
		echo PHP_EOL;
		return $prefix;
	}





}