=== Plugin Name ===
Contributors: josephscott, automattic
Plugin Name: PushPress
Tags: pubsubhubbub, push
Requires at least: 2.9
Tested up to: 2.9
License: GPLv2

Add PubSubHubbub support to your WordPress site, with a built in hub.

== Description ==

This plugin adds PubSubHubbub ( PuSH ) support to your WordPress powered site.  The main difference between this plugin and others is that it includes the hub features of PuSH, built right in.  This means the updates will be sent directly from WordPress to your PuSH subscribers.

== Installation ==

1. Upload `pushpress.zip` to your plugins directory ( usally `/wp-content/plugins/` )
2. Unzip the `pushpress.zip` file
3. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Question ==

= How is this plugin different from other PubSubHubbub plugins? =

Other plugins use 3rd party hubs to relay updates out to subscribers.  This plugin has a built in hub, allowing WordPress to send out the updates directly.

= Is there anything to configure? =

No, once the plugin is activated it takes care of the rest.

== Changelog ==

= 0.1.2 =
* Look for WP_Error being returned when sending a ping

= 0.1.1 =
* Initial release

== Upgrade Notice ==

= 0.1.2 =
Improved error checking

= 0.1.1 =
New PubSubHubbub plugin
