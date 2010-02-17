=== Custom Class Selector ===
Contributors: forgueam, tammyhart
Tags: css, custom, editor, formatting, html, style, theme, tinymce
Requires at least: 2.8
Tested up to: 2.8.5
Stable tag: 0.1

Allows users to style their post content using custom classes made available by the active theme.

== Description ==

The Custom Class Selector plugin allows users to style their post content using 
custom classes made available by the active theme. Theme developers can make 
custom style classes available within the visual editor by adding a simple 
function to the functions.php file included with their theme.

== Installation ==

This plugin follows the [standard WordPress installation method][]:

1. Upload the `custom-class-selector` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Custom style classes can be selected from the 'Styles' menu in the visual editor

[standard WordPress installation method]: http://codex.wordpress.org/Managing_Plugins#Installing_Plugins

== Frequently Asked Questions ==

= How can I use the custom classes? =

When editing a post or page using the visual editor, you may select custom classes 
from within the "Styles" menu on the editor toolbar.

= Theme Developers: How do I define custom classes for my theme? =

Please reference the functions-sample.php file included with this plugin. This 
file contains an example of the code that needs to be added to the functions.php 
file within your theme directory. Copy the code and make sure to change the 
configuration values accordingly.

= How do I get help if I have a problem? =

Please direct support questions to the "Plugins and Hacks" section of the
[WordPress.org Support Forum][]. Just make sure and include the tag 
'custom-class-selector'.

[WordPress.org Support Forum]: http://wordpress.org/support/

== Changelog ==
 
= version 0.1 (Nov 05, 2009) =
 - In the beginning ...