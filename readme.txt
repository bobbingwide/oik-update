=== oik-update ===
Contributors: bobbingwide, vsgloik
Donate link: https://www.oik-plugins.com/oik/oik-donate/
Tags: oik, plugin, update, WP-a2z, blocks, themes, APIs
Requires at least: 5.8.1
Tested up to: 6.4-RC3
Stable tag: 1.2.1

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
= 1.2.1 =
Upgrade for improvements to oik-themer.php and oik-update.php

== Changelog ==
= 1.2.1 =
* Changed: Support PHP 8.1 and PHP 8.2 #8
* Changed: Improve process for adding/updating themes #4
* Changed: Improve process for adding/updating plugins #2
* Fixed: Set overwrite_package to true when updating a theme #6
* Tested: With WordPress 6.4-RC3 and WordPress Multisite
* Tested: With Gutenberg 16.9.0
* Tested: With PHP 8.1 and PHP 8.2

== Further reading ==

If you want to read more about oik plugins and themes then please visit
[oik-plugins](https://www.oik-plugins.com/)