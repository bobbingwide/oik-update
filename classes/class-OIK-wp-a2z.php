<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2019
 *
 * @package oik-update
 */

/**
 * Class OIK_wp_a2z
 * Parent class for OIK_blocker and OIK_component_update
 */

class OIK_wp_a2z {

	public $owner;  // GitHub repository owner ( incl. the 'C:/github/' prefix ? )
	public $repo;   // GitHub repository name - which should match the component name, with some exceptions
	public $component; // Component name in WP-a2z WordPress system
	public $component_type; // Component type in WP-a2z WordPress system
	public $current_version; // Current version of Git repo
	public $new_version; // New version of the WordPress component to download and update to
	public $target_file_name; // File name of the target .zip file
	public $icon_ext;
	public $banner_ext;

	function __construct() {
		$this->set_owner();
		$this->set_repo();
		$this->set_component();
		$this->set_component_type();
		$this->set_current_version();
		$this->set_new_version();
		$this->set_target_file_name();
	}

	function set_owner( $owner = null ) {
		$this->owner = $owner;
	}

	function set_repo( $repo = null ) {
		$this->repo = $repo;
	}

	function set_component( $component = null ) {
		$this->component = $component;
	}

	function set_component_type( $component_type = 'plugin' ) {
		$this->component_type = $component_type;
	}

	function set_current_version( $current_version = null ) {
		$this->current_version = $current_version;
	}

	function set_new_version( $new_version = null ) {
		$this->new_version = $new_version;
	}

	function set_target_file_name( $target=null ) {
		$this->target_file_name = $target;
	}


	function is_wordpress() {
		return $this->component === 'wordpress';
	}

	function is_plugin() {
		return $this->is_component_type( 'plugin');

	}
	function is_theme() {
		return $this->is_component_type( 'theme');
	}

	function is_component_type( $component_type='plugin') {
		return $this->component_type === $component_type;
	}

	/**
	 * Downloads the assets for the component
	 *
	 * Uses a version of /apache/htdocs/bw/assets.php for plugins and
	 * the screenshot routine (to be written) for themes.
	 *
	 */
	function download_assets() {
		if ( $this->is_plugin() ) {
			$this->echo( "Run:", "assets {$this->component}" );
			$this->try_png_then_jpg();
		} else {
			$this->echo( "Run:", "screenshot {$this->component}" );
		}

	}


	function try_png_then_jpg() {

		$icon = $this->save_icon( $this->component);
		if ( false === $icon ) {
			$icon = $this->save_icon( $this->component, 'jpg');

		}
		$banner = $this->save_banner( $this->component );
		if ( false === $banner ) {
			$banner = $this->save_banner( $this->component, 'jpg');
		}
	}

	function save_icon( $plugin, $extension="png" ) {
		$written = false;
		$icon = @file_get_contents( "http://ps.w.org/$plugin/assets/icon-256x256.$extension" );
		//echo $icon;
		if ( $icon !== false ) {
			$written = file_put_contents( "c:/apache/htdocs/downloads/icons/$plugin-icon-256x256.$extension", $icon );
			$this->echo('Icon bytes:', $written );
			$this->icon_ext = $extension;
		}
		return $written;
	}


	function save_banner( $plugin, $extension="png" ) {
		$written = false;
		$banner = @file_get_contents( "http://ps.w.org/$plugin/assets/banner-772x250.$extension" );
		if ( $banner !== false ) {
			$written = file_put_contents( "c:/apache/htdocs/downloads/banners/$plugin-banner-772x250.$extension", $banner );
			$this->echo( 'Banner bytes:', $written );
			$this->banner_ext = $extension;
		}
		return $written;
	}

	/**
	 * Download the plugin version's .zip file from wordpress.org
	 *
	 *  https://downloads.wordpress.org/plugin/gutenberg.5.7.0.zip
	 */
	function download_plugin_version() {

		$filename = $this->get_zip_file_name();

		$url  = 'https://downloads.wordpress.org/plugin/';
		$url .= $filename;
		$target  = 'C:/apache/htdocs/downloads/plugins/';
		$target .= $filename;
		$error = $this->download_url_to_target( $url, $target );
		return $error;
	}

	function get_zip_file_name( $sep='.') {
		$filename  = $this->component;
		$filename .= $sep;
		$filename .= $this->new_version;
		$filename .= '.zip';
		return $filename;

	}
	/**
	 * Downloads the URL to the target file if not already downloaded
	 *
	 * This caters for Genesis theme framework being manually downloaded
	 *
	 * @param $url
	 * @param $target
	 *
	 * @return array|null
	 */

	function download_url_to_target( $url, $target ) {
		$this->set_target_file_name( $target );
		if ( file_exists( $target ) ) {
			$this->echo( "Download exists:", $target);
			$error = null;
		} else {
			$this->echo( "Downloading:", $url );
			$zip_file = file_get_contents( $url );
			if ( $zip_file === false ) {
				$error = error_get_last();
				$this->echo( "Error:", $error['message'] );
			} else {

				$written = file_put_contents( $target, $zip_file );
				$this->echo( "Written:", $target );
				$this->echo( "Bytes:", $written );
				$error = null;
			}
		}
		return $error;
	}




	function echo( $label=null, $value=null ) {
		if ( $label ) {
			echo $label;
			echo ' ';
		}
		if ( $value ) {
			echo $value;
		}
		echo PHP_EOL;
	}



}
