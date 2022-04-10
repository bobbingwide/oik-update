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
		$icon = $this->curl_file_get_contents( "http://plugins.svn.wordpress.org/$plugin/assets/icon-256x256.$extension" );
		//echo $icon;
		if ( $icon !== false ) {
			$asset_filename = $this->get_asset_filename( 'icon', $plugin, $extension );
			$written = file_put_contents( $asset_filename, $icon );
			$this->echo('Icon bytes:', $written );
			$this->icon_ext = $extension;
		}
		return $written;
	}


    function curl_file_get_contents( $url )
    {
        // create curl resource
        $ch = curl_init();
        //print_r( $ch );

        // set url
        curl_setopt($ch, CURLOPT_URL, $url);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $useragent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.125 Safari/537.36';
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);

        // $output contains the output string
        $output = curl_exec($ch);
        //echo $output;

        // also get the error and response code
        $errors = curl_error($ch);
        $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // close curl resource to free up system resources
        curl_close($ch);
        if ($response !== 200) {

            echo $response;
            echo ' ';
            echo $errors;
            //echo $output;
            $output = false;
        }

        return $output;

    }


    /*
     * https://ps.w.org/essential-blocks/assets/banner-772x250.png
     *
     * http://plugins.svn.wordpress.org"
     */

	function save_banner( $plugin, $extension="png" ) {
		$written = false;
		$banner = $this->curl_file_get_contents( "http://plugins.svn.wordpress.org/$plugin/assets/banner-772x250.$extension" );
		if ( $banner !== false ) {
			$asset_filename = $this->get_asset_filename( 'banner', $plugin, $extension );
			$written = file_put_contents( $asset_filename, $banner );
			//$written = file_put_contents( "c:/apache/htdocs/downloads/banners/$plugin-banner-772x250.$extension", $banner );
			$this->echo( 'Banner bytes:', $written );
			$this->banner_ext = $extension;
		} else {
			$this->echo( "Failed to download: $plugin $extension");

		}
		return $written;
	}

	/**
	 * Return the root directory for downloads.
	 *
	 * The directory is expected to exist.
	 *
	 * @return string
	 */
	function get_downloads_path() {
		$downloads_path = ( PHP_OS == "WINNT" ) ? 'C:/apache/htdocs/downloads/' : ABSPATH . '/downloads/';
		return $downloads_path;
	}

	/**
	 * Gets the asset filename.
	 *
	 * @param $type
	 * @param $plugin
	 * @param $extension
	 *
	 * @return string
	 */

	function get_asset_filename( $type, $plugin, $extension) {
		$asset_filename = $this->get_downloads_path();
		$asset_filename .= $type . 's/';
		$asset_filename .= $plugin;
		if ( $type === 'banner') {
			$asset_filename .= '-banner-772x250.';
		} else {
			$asset_filename .= '-icon-256x256.';
		}
		$asset_filename .= $extension;
		return $asset_filename;
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
		//$target  = 'C:/apache/htdocs/downloads/plugins/';
		$target = $this->get_downloads_path();
		$target .= 'plugins/';
		$target .= $filename;
		$error = $this->download_url_to_target( $url, $target );
		return $error;
	}

	function get_zip_file_name( $sep='.') {
		$filename  = $this->component;
		if ( $this->new_version !== "" ) {
			$filename .= $sep;
			$filename .= $this->new_version;
		}
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
		if ( $this->new_version !== "" && file_exists( $target ) ) {
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
     *
     * Update the featured image to be the latest asset
     * if the new asset is different from the current one.
     *
     * $banner_filename = c:/apache/htdocs/downloads/banners/$plugin-772x250.$ext
     * $attached_file = C:\apache\htdocs\wp-a2z/wp-content/uploads/sites/10/2019/03/block-gallery-banner-772x250-2.png
     */

    function update_featured_image( $post_id, $featured_image_filename, $title, $title_desc ) {
        include_once ABSPATH . 'wp-admin/includes/image.php';
        include_once ABSPATH . 'wp-admin/includes/media.php';

        $this->echo( "$title:", $featured_image_filename);
        $featured_image = get_post_thumbnail_id( $post_id );
        if ( '' === $featured_image || 0 == $featured_image ) {
            $featured_image = $this->create_attachment( $featured_image_filename, $title, $title_desc, $post_id );
            $this->set_thumbnail_id( $post_id, $featured_image );

        } else {
            $this->echo( 'Featured:', $featured_image );
            $attached_file = get_attached_file( $featured_image, true );
            $this->maybe_replace_featured_image( $featured_image_filename, $attached_file );
        }
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
        include_once ABSPATH  . 'wp-admin/includes/media.php';
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

	/**
	 * Sets the thumbnail ID for the post.
	 *
	 * If null then the value is the default image.
	 * In blocks.wp.a2z this is 10243, which cloned to blocks.wp-a2z.org as 4019.
	 *
	 * @param $post_id
	 * @param null $featured_image
	 */
    function set_thumbnail_id( $post_id, $featured_image=null ) {
    	if ( null === $featured_image ) {
    		$featured_image = ( PHP_OS === 'WINNT' ) ? 10243 : 4019;
	    }
        update_post_meta( $post_id, "_thumbnail_id", $featured_image );
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
