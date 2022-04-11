=== oik-update ===
Contributors: bobbingwide, vsgloik
Donate link: https://www.oik-plugins.com/oik/oik-donate/
Tags: oik, plugin, update, WP-a2z, blocks, themes, APIs
Requires at least: 5.8.1
Tested up to: 5.9.3
Stable tag: 1.2.0

oik-batch routines to semiautomate the process of updating

- the WP-a2z API reference for WordPress core, selected plugins and themes
- document blocks for the WordPress block editor - Gutenberg
- document Full Site Editing themes

== Description ==
oik-update provides three batch routines which are invoked by oik-batch.

- oik-blocker.php to improve the generation of blocks for plugins listed in blocks.wp-a2z.org
- oik-themer.php to improve the registration of FSE themes listed in blocks.wp-a2z.org
- oik-update.php to improve applying API updates for WordPress, plugins or themes


These are run from 3 command line routines, which use oik-batch run against --url=blocks.wp.a2z

- blocks.bat 
- themes.bat
- update.bat


== Installation ==
1. Install as if it were a WordPress plugin
1. Invoke using oik-batch

Dependent upon:

- oik-batch
- wp-top12
- Various plugins used in WP-a2z.

== Screenshots ==

None

== Upgrade Notice ==
= 1.2.0 =
Upgrade for improvements to oik-themer.php

== Changelog ==
= 1.2.0 =
* Changed: Add preview_theme() #4
* Changed: Implement update for components that need it #4
* Changed: Add is_new_version() method #4
* Changed: Add some logic to actually update the oik-theme post #4
* Changed: Start to implement auto updates #4
* Changed: Set default featured image for environment #2
* Tested: With WordPress 5.9.3 and WordPress Multi Site
* Tested: With PHP 8.0

== Further reading ==

If you want to read more about oik plugins and themes then please visit
[oik-plugins](https://www.oik-plugins.com/)