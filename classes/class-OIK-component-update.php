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
	public $target_file_name; // File name of the target .zip file

    private $target_dir;

	function __construct() {
		$this->set_owner();
		$this->set_repo();
		$this->set_component();
		$this->set_component_type();
		$this->set_current_version();
		$this->set_new_version();
		$this->set_target_file_name();
		$this->set_target_dir();
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

	function set_target_file_name( $target=null ) {
		$this->target_file_name = $target;
	}

	function set_target_dir() {
	    if ( PHP_OS == "WINNT" ) {
	        $this->target_dir = 'C:/apache/htdocs/downloads/';
        } else {
	        $this->target_dir = ABSPATH . '/downloads/';
        }
    }

    function get_target_dir( $subdir=null ) {
	    return $this->target_dir . $subdir;
    }

	/**
	 * Performs the update
	 *
	 *  Manual process to be replicated
	 *
	 * [x] 1. Identify component type ( plugin or theme ) from component name
	 * [x] 2. If not known determine component type: plugin or theme
	 * [ ] 3. Download latest assets into
	 * [x] 4. Determine the repo owner ( wp-a2z or bobbingwide)
	 *     If the component is processed directly from the GIT repo then we don't need to do this
	 *
	 * [x] 5. Download WordPress to \apache\htdocs\downloads\wordpress
	 * [x] 6. Download plugin https://downloads.wordpress.org/plugin/gutenberg.5.5.0.zip to \apache\htdocs\downloads\plugins
	 * [x] 7. Download theme to \apache\htdocs\downloads\themes
	 *
	 * [x] 8. Empty the git repo ( need to determine repo owner ) c:\github\wp-a2z\gutenberg leaving the .git folder
	 * [x] 9. Run 7-zip to open the .zip file and extract to \github\wp-a2z\gutenberg
	 * [x] 10. Add all the files to the repo - git add .
	 * [x] 11. Commit the changes: git commit -m "v version version-date
	 * [x] 12. Tag the version
	 *
	 * [ ] 13. Push the changes to GitHub
	 * [ ] 14. Pull the changes to \apache\htdocs\wp-a2z version
	 * [ ] 15. Run oik-shortcodes to rebuild the dynamic API reference
	*/
	function perform_update() {
		$this->echo( 'Performing update for...' );
		$this->echo( "Component:", $this->component );
		$this->echo( "New version:", $this->new_version );
		$this->echo( "Type:", $this->component_type );

		$repo = $this->query_repo();
		$this->echo( "Repo:", $repo );

		$owner = $this->query_owner();
		$this->echo( "Owner:", $owner );

		$this->download_assets(); // @TODO

		// What about WordPress ? - it's the special repo wp-a2z
		if ( $this->is_wordpress() ) {
			$error = $this->download_wordpress_version();
		} elseif ( $this->is_plugin() ) {
			$error = $this->download_plugin_version();
		} else {
			$error = $this->download_theme_version();
		}

		if ( null === $error ) {
			$this->empty_git_repo();
			$this->extract_zip_to_git_repo();
			$this->apply_git_changes();
		}
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
	 * @return string wp-a2z | bobbingwide | some other repository owner
	 */
	function query_owner() {
		$owners = $this->list_owners();
		foreach ( $owners as $owner ) {

			if ( is_dir( $owner . '/' . $this->repo ) ) {
				$this->set_owner( $owner );
				break;
			}
		}
		return $this->owner;

	}

	/**
	 * Sets and returns the repository name based on the component
	 * Case sensitive checking of wordpress
	 *
	 * @return string
	 */
	function query_repo() {
		if ( $this->component === "wordpress") {
			$this->repo = "wp-a2z";
		} else {
			$this->repo = $this->component;
		}
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

	function extract_zip_to_git_repo() {
		$repo_dir = $this->owner;
		$zip_file = $this->target_file_name;
		$save_dir = getcwd();
		chdir( $repo_dir );
		if ( $this->is_wordpress() ) {
			rename( $repo_dir . "/wp-a2z", $repo_dir . "/wordpress" );
		}
		$this->do7zip_extract( $zip_file, $this->repo );
		if ( $this->is_wordpress() ) {
			rename( $repo_dir . "/wordpress", $repo_dir . "/wp-a2z");
		}

		chdir( $save_dir );
	}

	/**
	 * Extracts the zip file to the current directory
	 *
	 * Command should be x to eXtract files with full paths.
	 * If we name the target directory then we may get the wrong output for WordPress
	 * which should be extracted to wp-a2z
	 *
	 * The -y flag replies 'y' to the prompt "Would you like to replace the existing file:"
	 *
	 * @param $filename
	 * @param $repo
	 */
	function do7zip_extract( $filename, $repo ) {

		$cmd = '"C:\\Program Files\\7-Zip\\7z.exe"';
		$cmd .= " x -y ";
		//$cmd .= "-o" . '../' . $repo;
		$cmd .= ' "';
		$cmd .= $filename;
		$cmd .= '"';
		$output = array();
		$return_var = null;
		echo $cmd;
		echo PHP_EOL;
		$lastline = exec( $cmd, $output, $return_var );
		echo $return_var;
		print_r( $output );


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

	/**
	 * Git add, then commit and tag
	 */
	function apply_git_changes() {

		$save_dir = getcwd();
		$repo_dir = $this->owner . '/' . $this->repo;
		chdir( $repo_dir );
		$git = new Git();
		$git->command( 'status' );

		$git->command( "add .");
		$this->get_version_date( $repo_dir );
		$message = sprintf( '%1$s %2$s - %3$s', $this->repo, $this->new_version, $this->version_date );
		$git->command( "commit", "-m \"$message\"" );
		$git->command( "tag", $this->new_version );
		// git add .
		// git commit -m "$component $new_version - ccyy/mm/dd
		chdir( $save_dir );

	}

	/**
	 * Sets the version date for the commit comment
	 *
	 * We like to know the date of the version for the commit.
	 * Using the current date is a fallback method.
	 * Check the date on the folder?
	 *
	 * @param string $repo_dir - fully qualified directory name for the GitHub repo
	 *
	 */

	function get_version_date( $repo_dir ) {
		$this->version_date = bw_format_date();
		// get the date of the current directory.
		//echo filemtime( $repo_dir );
		$this->version_date =  bw_format_date( filemtime( $repo_dir ) );
		//gob();

	}
	/**
	 * Download the WordPress version's .zip file from wordpress.org
	 * See:
	 * https://wordpress.org/download/releases/
	 *
	 * https://wordpress.org/wordpress-5.2.zip
	 *
	 */
	function download_wordpress_version() {

		$filename = $this->get_zip_file_name( '-');

		$url  = 'https://wordpress.org/';
		$url .= $filename;
		$target  = $this->get_target_dir( 'wordpress');
		$target .= '/';
		$target .= $filename;
		$error = $this->download_url_to_target( $url, $target );
		return $error;

	}

	/**
	 * Download the plugin version's .zip file from wordpress.org
	 *
	 *  https://downloads.wordpress.org/plugin/gutenberg.5.5.0.zip
	 */
	function download_plugin_version() {

		$filename = $this->get_zip_file_name();

		$url  = 'https://downloads.wordpress.org/plugin/';
		$url .= $filename;
		//$target  = 'C:/apache/htdocs/downloads/plugins/';
        $target  = $this->get_target_dir( 'plugins');
        $target .= '/';
		$target .= $filename;
		$error = $this->download_url_to_target( $url, $target );
		return $error;
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
		$this->echo( "New version: " . $this->new_version . " Target: " . $target );
		if ( $this->new_version !== "" && file_exists( $target )  ) {
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

	/**
	 * Download the themes version's .zip file from wordpress.org
	 *
	 *  https://downloads.wordpress.org/theme/twentynineteen.1.4.zip
	 */
	function download_theme_version() {
		$filename = $this->get_zip_file_name();
		$url  = 'https://downloads.wordpress.org/theme/';
		$url .= $filename;
        $target  = $this->get_target_dir( 'themes');
        $target .= '/';
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

}