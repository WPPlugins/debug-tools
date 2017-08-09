=== Debug Tools ===
Contributors: deltafactory
Tags: debug
Requires at least: 3.0.0
Tested up to: 3.7.1
Stable Tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Lightweight debugging and profile plugin intended for use on production sites.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `debug-tools` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Changelog ==

= 1.1 =
* Still alpha quality but now with actual functionality: Per-user and site-wide control of available and active modules
* Functioning module API for 3rd party contribution
* Standard module: Disable load monitoring on IIS/Windows. Relocate related functions
* Hooks module: Cleanup/refactoring
* Cron module (new): Add admin page with readable output.
* Query module: Minimal functionality. More coming soon.

= 0.1 =
* Initial Release (Alpha quality)

== Upgrade Notice ==

= 1.1 =
Major refactoring, functional user preferences

= 0.1 =
Initial Release (Alpha quality)

