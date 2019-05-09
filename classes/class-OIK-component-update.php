<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2019
 *
 * @package oik-update
 */

class OIK_component_update {

	public $owner;  // GitHub repository owner ( incl. the 'C:/github/' prefix ? )
	public $repo;   // GitHub repository name - which should match the component name, with some exceptions
	public $component; // Component name in WP-a2z WordPress system
	public $component_type; // Component type in WP-a2z WordPress system
	public $current_version; // Current version of Git repo
	public $new_version; // New version of the WordPress component to download and update to

	function __construct() {
		$this->set_owner();
		$this->set_repo();
		$this->set_component();
		$this->set_component_type();
		$this->set_current_version();
		$this->set_new_version();
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
	 * Performs the update
	 *
*  Manual process to be replicated
*
* [x] 1. Identify component type ( plugin or theme ) from component name
* [x] 2. If not known determine component type: plugin or theme
* [ ] 3. Download latest assets into  c:\apache\htdocs\downloads\banners and icons
* [x] 4. Determine the repo owner ( wp-a2z or bobbingwide)
*     If the component is processed directly from the GIT repo then we don't need to do this
 * [ ] 5. Empty the git repo ( need to determine repo owner ) c:\github\wp-a2z\gutenberg leaving the .git folder
 * [ ] 6. Download plugin https://downloads.wordpress.org/plugin/gutenberg.5.5.0.zip to \apache\htdocs\downloads\plugins
 * [ ] 7. Download theme to \apache\htdocs\downloads\themes
 * [ ] 8. Run 7-zip to open the .zip file and extract to \github\wp-a2z\gutenberg
 * [ ] 9. Add all the files to the repo - git add .
 * [ ] 10. Commit the changes: git commit -m "v version version-date
 * [ ] 11. Tag the version
 * [ ] 12. Push the changes to GitHub
 * [ ] 13. Pull the changes to \apache\htdocs\wp-a2z version
 * [ ] 14. Run oik-shortcodes to rebuild the dynamic API reference
	*/
	function perform_update() {
		$this->echo( 'Performing update for...' );
		$this->echo( "Component:", $this->component );
		$this->echo( "New version:", $this->new_version );
		$this->echo( "Type:", $this->component_type );

		$owner = $this->query_owner();

		$this->echo( "Owner:", $owner );
		$repo = $this->query_repo();
		$this->echo( "Repo:", $repo );

		$this->download_assets();
		$this->empty_git_repo();

	}

	/**
	 * Determines the component_type from ...
	 *
	 * Defaults to plugin
	 */

	function query_component_type() {
		gob();
	}

	/**
	 * Queries the repository owner
	 *
	 * @return string wp-a2z | bobbingwide |
	 */
	function query_owner() {
		//$this->owner = null;
		$owners = $this->list_owners();
		foreach ( $owners as $owner ) {

			if ( is_dir( $owner . '/' . $this->component ) ) {
				$this->set_owner( $owner );
				break;
			}
		}
		return $this->owner;

	}

	function query_repo() {
		// if we've already found the owner then we must know the repo!
		$this->repo = $this->component;
		return $this->repo;
	}

	/**
	 * Returns a list of directories under C:/github
	 * which are the potential owners of the repository
	 *
	 * This is no good for plugins which do not have GitHub repositories
	 * so... if we don't find anything we'll return something to indicate that the
	 * plugin should be installed directly in the current installation's plugin/theme directory.
	 *
	 * Actually, this is only any good for bobbingwide or WP-a2z
	 *
	 * Array
	(
	[0] => C:/github/WP-a2z
	[1] => C:/github/bobbingwide
	[2] => C:/github/coblocks
	[3] => C:/github/pootlepress
	[4] => C:/github/woocommerce
	[5] => C:/github/wordpress
	[6] => C:/github/wppompey
	 */

	function list_owners() {
		$owners = glob( 'C:/github/*', GLOB_ONLYDIR );
		print_r( $owners );
		return $owners;
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
		} else {
			$this->echo( "Run:", "screenshot {$this->component}" );
		}

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

	function empty_git_repo() {
		$repo_dir = $this->owner . '/' . $this->repo;
		$this->echo( "Empty git repo:", $repo_dir );
		$this->remove_files( $repo_dir );
	}

	function remove_files( $folder ) {
		$save_dir = getcwd();
		chdir( $folder );
		$files = glob( '*' );
		//print_r( $files );
		foreach ( $files as $file ) {
			//echo $file;
			if ( is_dir( $file ) ) {
				$this->remove_files( $folder . '/' . $file );  // does this delete subdirectories ?
				rmdir( $file );
			} else {
				$this->echo( "Deleting:", $folder . '/' . $file );
				unlink( $file );
			}
		}
		chdir( $save_dir );

	}



}