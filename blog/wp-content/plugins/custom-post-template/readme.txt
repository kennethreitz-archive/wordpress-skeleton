=== Custom Post Template ===
Contributors: simonwheatley
Donate link: http://www.simonwheatley.co.uk/wordpress/
Tags: post, template, theme
Requires at least: 2.9
Tested up to: 2.9.1
Stable tag: 1.1

Provides a drop-down to select different templates for posts from the post edit screen. The templates replace single.php for the specified post.

== Description ==

**This plugin requires PHP5 (see Other Notes > PHP4 for more).**

Provides a drop-down to select different templates for posts from the post edit screen. The templates are defined similarly to page templates, and will replace single.php for the specified post.

Post templates, as far as this plugin is concerned, are configured similarly to [page templates](http://codex.wordpress.org/Pages#Creating_Your_Own_Page_Templates) in that they have a particular style of PHP comment at the top of them. Each post template must contain the following, or similar, at the top:
<code>
<?php
/*
Template Name Posts: Snarfer
*/
?>
</code>

Note that *page* templates use "_Template Name:_", whereas *post* templates use "_Template Name Posts:_".

Plugin initially produced on behalf of [Words & Pictures](http://www.wordsandpics.co.uk/).

Is this plugin lacking a feature you want? I'm happy to discuss ideas, or to accept offers of feature sponsorship: [contact me](http://www.simonwheatley.co.uk/contact-me/) and we can have a chat.

Any issues: [contact me](http://www.simonwheatley.co.uk/contact-me/).

== Installation ==

The plugin is simple to install:

1. Download the plugin, it will arrive as a zip file
1. Unzip it
1. Upload `custom-post-template` directory to your WordPress Plugin directory
1. Go to the plugin management page and enable the plugin
1. Upload your post template files (see the Description for details on configuring these), and choose them through the new menu
1. Give yourself a pat on the back

== PHP4 ==

Many of my plugin now require at least PHP5. I know that WordPress officially supports PHP4, but I don't. PHP4 is a mess and makes coding a lot less efficient, and when you're releasing stuff for free these things matter. PHP5 has been out for several years now and is fully production ready, as well as being naturally more secure and performant.

If you're still running PHP4, I strongly suggest you talk to your hosting company about upgrading your servers. All reputable hosting companies should offer PHP5 as well as PHP4.

Right, that's it. Grump over. ;)

== Change Log ==

= v1.1 2010/01/27 =

* IDIOTFIX: Managed to revert to an old version somehow, this version should fix that.

= v1 2010/01/15 (released 2010/01/26) =

* BUGFIX: Theme templates now come with a complete filepath, so no need to add WP_CONTENT_DIR constant to the beginning.
* ENHANCEMENT: Metabox now shows up on the side, under the publish box... where you'd expect.

= v0.9b 2008/11/26 =

* Plugin first released

= v0.91b 2008/11/28 =

* BUGFIX: The plugin was breaking posts using the "default" template, this is now fixed. Apologies for the inconvenience.
* Tested up to WordPress 2.7-beta3-9922

= v0.91b 2008/11/28 =

* BUGFIX: The plugin was breaking posts using the "default" template, this is now fixed. Apologies for the inconvenience.
* Tested up to WordPress 2.7-beta3-9922* Tested up to WordPress 2.7-beta3-9922

= v0.92b 2008/12/04 =

* Minor code tweaks
* Blocked direct access to templates

== Frequently Asked Questions ==

= I get an error like this: <code>Parse error: syntax error, unexpected T_STRING, expecting T_OLD_FUNCTION or T_FUNCTION or T_VAR or '}' in /web/wp-content/plugins/custom-post-template/custom-post-templates.php</code> =

This is because your server is running PHP4. Please see "Other Notes > PHP4" for more information.