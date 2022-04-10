# oik-update 
![banner](assets/oik-update-banner-772x250.jpg)
* Contributors: bobbingwide, vsgloik
* Donate link: https://www.oik-plugins.com/oik/oik-donate/
* Tags: oik, plugin, update, WP-a2z, blocks, themes, APIs
* Requires at least: 5.8.1
* Tested up to: 5.9.3
* Stable tag: 1.1.0

oik-batch routines to semiautomate the process of updating

- the WP-a2z API reference for WordPress core, selected plugins and themes
- document blocks for the WordPress block editor - Gutenberg
- document Full Site Editing themes



## Description 
oik-update provides three batch routines which are invoked by oik-batch.

- oik-blocker.php to improve the generation of blocks for plugins listed in blocks.wp-a2z.org
- oik-themer.php to improve the registration of FSE themes listed in blocks.wp-a2z.org
- oik-update.php to improve applying API updates for WordPress, plugins or themes


These are run from 3 command line routines, which use oik-batch run against --url=blocks.wp.a2z

- blocks.bat
- themes.bat
- update.bat


## Installation 
1. Install as if it were a WordPress plugin
1. Invoke using oik-batch

Dependent upon:

- oik-batch
- wp-top12
- Various plugins used in WP-a2z.

## Screenshots 

None

## Upgrade Notice 
# 1.1.0 
Upgrade for improvements to oik-blocker.php.

## Changelog 
# 1.1.0 
* Added: Implement batch update loop for plugins and their blocks #2
* Changed: Enable oik-blocker for running on live ( blocks.wp-a2z.org ) #2
* Changed: Add support for automatic updates. Don't create oik-plugins when doing this. #2
* Changed: Don't download updates to Git repos. #2
* Changed: Improve featured image setting. Update oik-plugin for each plugin update #2
* Changed: oik-blocker: Don't fetch plugins which are Git repos #2
* Added: Add oik-block-rename.php to rename core-embed blocks to core/embed variations #5
* Changed: Attempt to deal with Jetpack already loading file.php. Don't call get_theme() after fetch and save
* Fixed: Change include to include_once #4
* Changed: Add set_target_dir() and get_target_dir() to allow for different environments #4
* Changed: Load theme info from WordPress.org if not available locally #4
* Tested: With WordPress 5.9.3 and WordPress Multi Site
* Tested: With PHP 8.0

## Further reading 


If you want to read more about oik plugins and themes then please visit
[oik-plugins](https://www.oik-plugins.com/)
