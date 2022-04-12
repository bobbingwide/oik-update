<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2019, 2022
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
    public $plugin_info = null;
    public $download_link = null;
    public $banner_low = null;

	private $create_plugin = true;

	function __construct() {
		parent::__construct();
		$this->set_component_type( 'plugin');
		$this->set_create_plugin();

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

	function set_create_plugin( $create_plugin=true ) {
		$this->create_plugin = $create_plugin;
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
        $this->echo("Component:", $this->component);
        $this->echo("New version:", $this->new_version);
        $this->get_plugin_info();
        if ( $this->plugin_info ) {

            if ( $this->banner_low ) {
                $this->get_banner_low();
            } else {
                $this->download_assets();
            }

            $this->download_plugin_version();
            $this->update_installed_plugin();
            $this->update_oik_plugin();
        } else {
            $this->echo( "Error:","Not found." );
        }

	}

	/**
	 * Updates the installed plugin.
	 *
	 * Basically we just want to update the plugin from the download folder.
	 *
	 * @TODO There should be no need to do this multiple times!
	 * We should check the currently active version.
	 * This can be done using the virtual field.
	 */
	function update_installed_plugin() {
		//$repo_dir = WP_PLUGIN_DIR;
		$zip_file = $this->target_file_name;
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
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
			if ( $this->create_plugin ) {
				$this->create_oik_plugin();
			}
		} else {
			$this->echo( "ID:", $this->plugin_post->ID );
			$this->echo( 'Title:', $this->plugin_post->post_title );
			$this->alter_oik_plugin();
		}

		if ( $this->plugin_post ) {
            $banner_filename = $this->get_asset_filename( 'banner', $this->component, $this->banner_ext );
            if ( file_exists( $banner_filename )) {
            	$this->update_featured_image( $this->plugin_post->ID, $banner_filename, "Banner", "Banner description" );
            	//$banner_filename = $this->get_asset_filename( 'banner', 'no', 'webp');
            } else {
            	$this->set_thumbnail_id( $this->plugin_post->ID);
            	/* Don't upload an icon file. It's the wrong shape.
	            $icon_filename=$this->get_asset_filename( 'icon', $this->component, 'png' );
	            if ( file_exists( $icon_filename ) ) {
		            $this->update_featured_image( $this->plugin_post->ID, $icon_filename, "Icon", "Icon description" );
	            }
            	*/
            }
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

	    /* We need to set meta fields as well.
	     Otherwise the values from the previous plugin are used.
	    */
        $_POST['post_author'] = 1;
        $_POST['_oikp_type'] = '1';
        $_POST['_oikp_slug'] = $this->component;
        $_POST['_oikp_name'] = $this->get_plugin_file_name();
        $_POST['_oikp_desc'] = $this->get_plugin_name();
        $_POST['_oikp_uri'] = $this->get_plugin_uri();
		if ( $this->maybe_update_content() ) {
			$this->echo( "Updating:", $this->plugin_post->post_title );
			$this->plugin_post->post_content = $this->create_plugin_content();
			//print_r( $this->plugin_post );
			wp_insert_post( $this->plugin_post );
		} else {
			$post_arr = [];
			$post_arr['ID'] = $this->plugin_post->ID;
			$post_arr['post_title'] = $this->plugin_post->post_title;
			wp_update_post( $post_arr);
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
		$content .= $this->generate_block( 'post-featured-image' );
		//$content .= $this->generate_block( 'shortcode', null, "[bw_plug name={$this->component} banner={$this->banner_ext}]" );
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

	function get_plugin_block_prefix() {
		$wpod = new WP_org_v12_downloads();
		$wpod->get_download( $this->component );
		$prefix = $wpod->get_block_prefix();
		echo "Prefix: " . $prefix;
		echo PHP_EOL;
		return $prefix;
	}

	function process_blocks() {
	    echo "Processing blocks:" . PHP_EOL;
	    $oik_block_updater = new OIK_block_updater();
	    $oik_block_updater->set_plugin_info( $this->component, $this->component_type );
	    $oik_block_updater->process_blocks();

    }

    /**
     * Obtains the plugin information from wordpress.org
     *
     *
     * @return
     */
    function get_plugin_info()
    {
        //$wpod = new WP_org_v12_downloads();
        $this->plugin_info = null;
        $wpod = new WP_org_v12_downloads();
        $fetched = $wpod->get_download($this->component);
        if ($fetched) {

            $plugin_info = $wpod->response;
            //print_r( $plugin_info );
            // We're looking for $plugin_info->download_link - for the URL to download
            // and the banner image extension.
            $this->plugin_info = $plugin_info;
            $this->download_link = $plugin_info->download_link;
            if ( $plugin_info->banners->low ) {
                $this->banner_low = $plugin_info->banners->low;
            }

            // The data also includes arrays of [blocks] and [block_assets]
        }
        return $this->plugin_info;
    }

    function get_banner_low() {

        if ( str_contains( $this->banner_low, '.png?') ) {
            $this->save_banner( $this->component );
        } else {
            $this->save_banner( $this->component, 'jpg' );
        }
    }

    /**
     * Download the plugin version's .zip file from wordpress.org
     *
     *  https://downloads.wordpress.org/plugin/gutenberg.5.7.0.zip
     *
     * Note: The download link indicates if a specific version can be downloaded.
     *
     */
    function download_plugin_version() {
        $filename = basename( $this->download_link );
        $url  = 'https://downloads.wordpress.org/plugin/';
        $url .= $filename;
        //$target  = 'C:/apache/htdocs/downloads/plugins/';
        $target = $this->get_downloads_path();
        $target .= 'plugins/';
        $target .= $filename;
        $error = $this->download_url_to_target( $url, $target );
        return $error;
    }

}