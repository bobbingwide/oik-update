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



	function perform_update() {
		$this->echo( "Component:", $this->component );
		$this->echo( "New version:", $this->new_version );
		$this->download_assets();
		$this->download_plugin_version();
		$this->extract_zip_to_plugin_dir();

	}

	/**
	 * Basically we just want to update the plugin from the download folder!
	 *
	 */

	function extract_zip_to_plugin_dir() {
		//$repo_dir = WP_PLUGIN_DIR;
		$zip_file = $this->target_file_name;
		include( ABSPATH . 'wp-admin/includes/file.php' );
		include( ABSPATH . 'wp-admin/includes/misc.php' );
		include( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		$upgrader = new Plugin_Upgrader();
		$upgraded = $upgrader->install( $zip_file );



	}





}