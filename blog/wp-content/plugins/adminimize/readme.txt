=== Adminimize ===
Contributors: Bueltge
Donate link: http://bueltge.de/wunschliste/
Tags: color, scheme, theme, admin, dashboard, color scheme, plugin, interface, ui, metabox, hide, editor, minimal, menu, customization, interface, administration, lite, light, usability, lightweight, layout, zen
Requires at least: 2.5
Tested up to: 3.0-alpha
Stable tag: 0.5

At first: Visually compresses the administratrive meta-boxes so that more admin page content can be initially seen. Also moves 'Dashboard' onto the main administrative menu because having it sit in the tip-top black bar was ticking me off and many other changes in the edit-area.
At second. Adminimize is a WordPress plugin that lets you hide 'unnecessary' items from the WordPress administration menu, submenu and even the 'Dashboard', with forwarding to the Manage-page. On top of that, you can also hide post meta controls on the Write page and other areas in the admin-area and Write-page, so as to simplify the editing interface. All is addicted from your rights, and other roles, also roles from Plugin [Role Manager](http://www.im-web-gefunden.de/wordpress-plugins/role-manager/ "Role Manager").

== Description ==
Visually compresses the administratrive header so that more admin page content can be initially seen. Also moves 'Dashboard' onto the main administrative menu because having it sit in the tip-top black bar was ticking me off and many other changes in the edit-area. Adminimize is a WordPress plugin that lets you hide 'unnecessary' items from the WordPress administration menu, submenu and even the 'Dashboard', with forwarding to the Manage-page. On top of that, you can also hide post meta controls on the Write page and other areas in the admin-area and Write-page, so as to simplify the editing interface. Compatible with WordPress 2.5 or later. 
Configure all metaboxes and other areas in the write-area. The new theme move the Tags- and Categorys-box to the sidebar, switch off optional metaboxes and other areas in the write-area. Scoll automatocly to the Textbox, when you click the write-button. Many options for menu, submenu and all areas, metaboxes in the write-area, separated for all roles in WordPress.

With version 1.6.1 it is possible to add own options for hide areas in the backend of WordPress. It is easy and you must only forgive ID or class of the tag. Also it is possible to use a fixed menu and header.

= Compatibility with the drop-down menu plugins =
1. [Ozh Admin Drop Down Menu](http://planetozh.com/blog/my-projects/wordpress-admin-menu-drop-down-css/ "Admin Drop Down Menu for WordPress 2.5") by Ozh
1. [Drop Down Admin Menus](http://www.stuff.yellowswordfish.com/ "Drop Down Admin Menus for WordPress 2.5") by Andy Staines

= Compatibility with the plugins for MetaBoxes in Write-area =
1. [Simple Tag](http://wordpress.org/extend/plugins/simple-tags "Simple Tag") by Amaury BALMER
1. [Text Control](http://wordpress.org/extend/plugins/text-control-2/ "Text Control") by Jeff Minard and Frank Bueltge
1. [All in One SEO Pack](http://semperfiwebdesign.com "All in One SEO Pack") by Michael Torbert
1. [TDO Mini Forms](http://thedeadone.net/software/tdo-mini-forms-wordpress-plugin/ "TDO Mini Forms") by Mark Cunningham
1. [Post Notification](http://pn.xn--strbe-mva.de/ "Post Notification") by Moritz Str&uuml;be
1. [HTML Special Characters Helper](http://coffee2code.com/wp-plugins/html-special-characters-helper "HTML Special Characters Helper") by Scott Reilly
1. You can add your own options, you must only see for css selectors

= Requirements =
1. WordPress version 2.5 and later

Please visit [the official website](http://bueltge.de/wordpress-admin-theme-adminimize/674/ "Adminimize") for further details and the latest information on this plugin.

= What does this plugin do? =
The plugin changes the administration backend and gives you the power to assign rights on certain parts. Admins can activate/deactivate every part of the menu and even parts of the submenu. Meta fields can be administered separately for posts and pages. Certain parts of the write menu can be deactivated separately for admins or non-admins. The header of the backend is minimized and optimized to give you more space and the structure of the menu gets changed to make it more logical - this can all be done per user so each user can have his own settings.

= Details =
1. the admin theme can be set per user. To change this go to user settings
1. currently you can use the theme together with the color settings for the Fresh and Classic themes
1. more colors can be easily added
1. new menu structure: on the left hand site you find classic menu points for managing and writing, while the right part is reserved for settings, design, plugins and user settings
1. the dashboard has been moved into the menu itself but this can be deactivated if its not desired
1. the menu is now smaller and takes up less space
1. the WRITE menu has been changed as follows:
1. it is no longer limited to a fixed width but flows to fill your whole browser window now
1. you can scroll all input fields now, no need to make certain parts of the WRITE screen bigger
1. categories moved to the sidebar
1. tags moved to the sidebar if you are not using the plugin "Simple Tags"
1. the editing part gets auto-scrolled which makes sense when using a small resolution
1. the media uploader now uses the whole screen width
1. supports the plugin "Admin Drop Down Menu" - when the plugin is active the user has two more backend-themes to chose from
1. supports the plugin "Lighter Menus" - when that plugin is active the user has another two backend-themes to chose from
1. two new color schemes are now available
1. the width of the sidebar is changeable to standard, 300px, 400px or 30%
1. each meta field can now be deactivated (per user setting) so it doesn't clutter up your write screen
1. you can even deactivate other parts like h2, messages or the info in the sidebar
1. the part of the user info you have on the upper - right part of your menu can be deactivated or just the log-out link
1. the dashboard can be completely removed from the backend
1. all menu and sub menu points can be completely deactivated for admins and non-admins
1. most of these changes are only loaded when needed - i.e. only in the write screen
1. set a backend-theme for difficult user
1. you can set an role to view the areas on link page, edit post, edit page and global
1. you can add own options for set rights to role
1. it is possible to disable HTML-Editor on edit-area, only Visual-tab
1. ... many more

= Localizations =
* Also Thanks to [Ovidio](http://pacura.ru/ "pacaru.ru") for an translations the details in english and [G&uuml;rkan G&uuml;r](http://www.seqizz.net/ "G&uuml;rkan G&uuml;r") for translation in turkish.
* Thanks to [Gabriel Scheffer](http://www.gabrielscheffer.com.ar "Gabriel Scheffer") for the spanish language files.
* Thanks to [Andrea Piccinelli] for the italian language files.
* Thanks to [Fat Cow](http://www.fatcow.com/ "Fat Cow") for the belarussian language files.

= Interested in WordPress tips and tricks =
You may also be interested in WordPress tips and tricks at [WP Enginner](http://wpengineer.com/) or for german people [bueltge.de](http://bueltge.de/) 

== Installation ==
1. Unpack the download-package
2. Upload folder include all files to the `/wp-content/plugins/` directory. The final directory tree should look like `/wp-content/plugins/adminimize/adminimize.php`, `/wp-content/plugins/adminimize/adminimize_page.php`, `/wp-content/plugins/adminimize/css/` and `/wp-content/plugins/adminimize/languages`
3. Activate the plugin through the `Plugins` menu in WordPress
4. Selecting Colour Scheme and Theme, selection in Your Profile, go to your User Profile (under `Users` > `Your Profile` or by clicking on your name at the top right corner of the administration panel).
5. Administrator can go to `Options` > `Adminimize` menu and configure the plugin (Menu, Submenu, Metaboxes, ...)

= Advice =
Please use the `Deinstall-Function` in the option-area before update to version 1.4! Version 1.4 and higher have only one database entry and the `Deinstall-Option` deinstall the old entrys.

See on [the official website](http://bueltge.de/wordpress-admin-theme-adminimize/674/ "Adminimize").

== Screenshots ==
1. configure-area for user/admin; options for metaboxes, areas in write-area and menu in WordPress 2.7/2.8
1. configure-area for user in WordPress 2.7/2.8
1. Small tweak for design higher WP 2.7, save 50px over the menu
1. minimize header after activate in WordPress 2.5
1. configure-area for user in WordPress 2.5
1. Adminimize Theme how in WordPress 2.3

== Changelog ==
= v1.7.6 (01/14/2010) =
* fix array-check on new option disable HTML Editor

= v1.7.5 (01/13/2010) =
* new function: disable HTML Editor on edit post/page

= v1.7.4 (01/10/2010) =
* Fix on Refresh menu and submenu on settings-page
* Fix for older WordPress verisons and  function current_theme_supports 

= v1.7.3 (01/08/2010) =
* Add Im-/Export function
* Add new meta boxes from WP 2.9 post_thumbnail, if active from the Theme
* Small modifications and code and css
* Add new functions: hide tab for help and options on edit post or edit page; category meta box with ful height, etc.

= v1.7.2 (07/08/2009) =
* Add fix for deactive user.php/profile.php

= v1.7.1 (17/06/2009) =
* Add belarussian language file, thanks to [Fat Cow](http://www.fatcow.com/ "Fat Cow")

= v1.7.1 (16/06/2009) =
* changes for load userdate on settings themes; better for performance on blogs with many Users
* small bugfixes on texdomain
* changes on hint for settings on menu
* new de_DE language file
* comments meta box add to options on post

= v1.7 (23/06/2009) =
* Bugfix for WordPress 2.6; Settings-Link
* alternate for `before_last_bar()` and change class of div

= 1.6.9 (19/06/2009) =
* Bugfix, Settingslink gefixt;
* Changes on own defines with css selectors; first name, second css selector
* Bugfix in own options to pages

= 1.6.8 (18/06/2009) =
* Bugfix in german language file

= 1.6.6-7 (10/06/2009) =
* Add Meta Link in 2.8

= 1.6.5 (08/05/2009) =
* Bugfix, Doculink only on admin page of Adminimize

= 1.6.4 (27/04/2009) =
* new Backend-Themes
* more options
* multilanguage for role-names

= 1.6.1, 1.6.3 (24/05/2009) =
* ready for own roles
* new options for link-area on WP backend
* own options for all areas, use css selectors
* ...

= v1.6 =
* ready for WP 2.7
* new options area, parting of page and post options
* add wp_nonce for own logout
* ...

= v1.5.3-8 =
* Changes for WP 2.7
* changes on CSS design
* ...

= v1.5.2 =
* own redirects possible

= v1.5.1 =
* Bugfix f&uuml;r rekursiven Array; Redirect bei deaktivem Dashboard funktionierte nicht

= v1.5 =
* F&uuml;r jede Nutzerrolle besteht nun die M&uuml;glichkeit, eigene Menus und Metaboxes zu setzen. Erweiterungen im Backend-Bereich und Vorbereitung f&uuml;r WordPress Version 2.7

= v1.4.7 =
* Bugfix CSS-Adresse f&uuml;r WP 2.5

= v1.4.3-6 =
* Aufrufe diverser JS ge&auml;ndert, einige &uuml;bergreifende Funktionen nun auch ohne aktives Adminimize-Theme

= v1.4.2 =
* kleine Erweiterungen, Variablenabfragen ge&auml;ndert

= v1.4.1 =
* Bugfixes und Umstellung Sprache

= v1.4 =
* Performanceoptimierung; <strong>Achtung:</strong> nur noch 1 Db-Eintrag, bei Update auf Version 1.4 zuvor die Deinstallation-Option nutzen und die Db von &uuml;berfl&uuml;ssigen Eintr&auml;gen befreien.

= v1.3 =
* Backendfunktn. erweitert, Update f&uuml;r PressThis im Bereich Schreiben, etc.

= v1.2 =
* Erweiterungen der MetaBoxen

= v1.1 =
* Schreiben-, Verwalten-Bereich ist deaktivierbar; CSS-Erweiterungen des WP 2.3 Themes f&uuml;r WP 2.6; Sidebar im Schreiben-Bereich noch mehr konfigurierbar, Optionsseite ausgebaut, kleine Code-Ver&auml;nderungen

= v1.0 =
* JavaScript schlanker durch die Hilfe von <a href="http://www.schloebe.de/">Oliver Schl&uuml;be</a>

= v0.8.1 =
* Hinweis im Footer m&uuml;glich, optional mit optionalen Text, Weiterleitung immer ersichtlich

= v0.8 =
* Weiterleitung nach Logout m&uuml;glich

= v0.7.9 =
* Zus&auml;tzlich ist innerhalb der Kategorien nur "Kategorien hinzuf&uuml;gen" deaktiverbar

= v0.7.8 =
* Mehrsprachigkeit erweitert

= v0.7.7 =
* Bugfix f&uuml;r Metabox ausblenden in Write Page

= v0.7.6 =
* Checkbox f&uuml;r alle ausw&auml;hlen auch in Page und Post, Korrektur in Texten

= v0.7.5 =
* Checkbox f&uuml;r alle ausw&auml;hlen, Theme zuweisen

= v0.7.3 =
* Optionale Weiterleitung bei deaktiviertem Dashboard, Einstellungen per Plugin-Seite m&uuml;glich, Admin-Footer erg&auml;nzt um Plugin-infos

= v0.7.2 =
* Update Options Button zus&auml;tzlich im oberen Abschnitt

= v0.7.1 =
* Thickbox Funktion optional

= v0.7 =
* WriteScroll optional, MediaButtons deaktivierbar

= v0.6.9 =
* Theme WordPress 2.3 hinzugekommen, Footer deaktivierbar


== Other Notes ==
= Acknowledgements =
* Thanks to [Eric Meyer](http://meyerweb.com/ "Eric Meyer") for the Idea and the Stylesheet to minimize the header of backend and thanks to [Alphawolf](http://www.schloebe.de/ "Alphawolf") for write a smaller javascript with jQuery.
* Also Thanks to [Ovidio](http://pacura.ru/ "pacaru.ru") for an translations the details in english and [G&uuml;rkan G&uuml;r](http://www.seqizz.net/ "G&uuml;rkan G&uuml;r") for translation in turkish.
* Thanks to [Gabriel Scheffer](http://www.gabrielscheffer.com.ar "Gabriel Scheffer") for the spanish language files.
* Thanks to [Andrea Piccinelli] for the italian language files.
* Thanks to [Fat Cow](http://www.fatcow.com/ "Fat Cow") for the belarussian language files.

= Help with "Your own options" =
My english ist very bad and you can see the [entry on the WP community forum](http://wordpress.org/support/topic/328449 "Plugin: Adminimize Help with Your own options (3 posts)") for help with great function.

= Licence =
Good news, this plugin is free for everyone! Since it's released under the GPL, you can use it free of charge on your personal or commercial blog. But if you enjoy this plugin, you can thank me and leave a [small donation](http://bueltge.de/wunschliste/ "Wishliste and Donate") for the time I've spent writing and supporting this plugin. And I really don't want to know how many hours of my life this plugin has already eaten ;)

= Translations =
The plugin comes with various translations, please refer to the [WordPress Codex](http://codex.wordpress.org/Installing_WordPress_in_Your_Language "Installing WordPress in Your Language") for more information about activating the translation. If you want to help to translate the plugin to your language, please have a look at the sitemap.pot file which contains all defintions and may be used with a [gettext](http://www.gnu.org/software/gettext/) editor like [Poedit](http://www.poedit.net/) (Windows).


== Frequently Asked Questions ==
= Help with "Your own options" =
My english ist very bad and you can see the [entry on the WP community forum](http://wordpress.org/support/topic/328449 "[Plugin: Adminimize] Help with "Your own options" (3 posts)") for help with great function.

= Where can I get more information? =
Please visit [the official website](http://bueltge.de/wordpress-admin-theme-adminimize/674/ "Adminimize") for the latest information on this plugin.

= I love this plugin! How can I show the developer how much I appreciate his work? =
Please visit [the official website](http://bueltge.de/wordpress-admin-theme-adminimize/674/ "Adminimize") and let him know your care or see the [wishlist](http://bueltge.de/wunschliste/ "Wishlist") of the author.
