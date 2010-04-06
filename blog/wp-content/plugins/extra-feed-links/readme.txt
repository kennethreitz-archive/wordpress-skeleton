=== Extra Feed Links ===
Contributors: scribu
Donate link: http://scribu.net/wordpress
Tags: archive, comments, feed, rss, aton
Requires at least: 2.5
Tested up to: 2.8
Stable tag: 1.1.5.1

Adds extra feed auto-discovery links to various page types (categories, tags, search results etc.)

== Description ==

This plugin adds feed auto-discovery links to any page type:

* Category page
* Tag page
* Search page
* Author page
* Comments feed for single articles and pages

It also has a template tag that you can use in your theme.

== Installation ==

1. Unzip the archive and put the folder into your plugins folder (/wp-content/plugins/).
1. Activate the plugin from the Plugins admin menu.
1. Customize the links in the settings page.

**Usage**

You can use `extra_feed_link()` inside your theme to display a link to the feed corresponding to the type of page:

* `<?php extra_feed_link(); ?>` (creates a link with the default text)
* `<?php extra_feed_link('Link Text'); ?>` (creates a link with the text you choose)
* `<?php extra_feed_link('http://url/of/image'); ?>` (creates an image tag linked to the feed URL)
* `<?php extra_feed_link('raw'); ?>` (just displays the feed URL)

== Frequently Asked Questions ==

= "Parse error: syntax error, unexpected T_CLASS..." Help! =

Make sure your new host is running PHP 5. Add this line to wp-config.php:

`var_dump(PHP_VERSION);`

== Changelog ==

= 1.1.5 =
* WP 2.8 compatibility

= 1.1.1 =
* italian translation

= 1.1 =
* more flexible link text format
* [more info](http://scribu.net/wordpress/extra-feed-links/efl-1-1.html)

= 1.0 =
* added options page

= 0.6 =
* extra_feed_link() template tag

= 0.5 =
* initial release

