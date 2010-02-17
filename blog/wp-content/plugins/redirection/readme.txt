=== Plugin Name ===
Contributors: johnny5
Donate link: http://urbangiraffe.com/about/
Tags: post, admin, seo, pages, manage, 301, 404, redirect, permalink
Requires at least: 2.3
Tested up to: 2.8.6
Stable tag: trunk

Redirection is a WordPress plugin to manage 301 redirections and keep track of 404 errors without requiring knowledge of Apache .htaccess files.

== Description ==

Redirection is a WordPress plugin to manage 301 redirections, keep track of 404 errors, and generally tidy up any loose ends your site may have. This is particularly useful if you are migrating pages from an old website, or are changing the directory of your WordPress installation.

New features include:

* 404 error monitoring - captures a log of 404 errors and allows you to easily map these to 301 redirects
* Custom 'pass-through' redirections allowing you to pass a URL through to another page, file, or website.
* Full logs for all redirected URLs
* All URLs can be redirected, not just ones that don't exist
* Redirection methods - redirect based upon login status, redirect to random pages, redirect based upon the referrer!

Existing features include:

* Automatically add a 301 redirection when a post's URL changes
* Manually add 301, 302, and 307 redirections for a WordPress post, or for any other file
* Full regular expression support
* Apache .htaccess is not required - works entirely inside WordPress
* Strip or add www to all your WordPress pages
* Redirect index.php, index.html, and index.htm access
* Redirection statistics telling you how many times a redirection has occurred, when it last happened, who tried to do it, and where they found your URL
* Fully localized

Redirection is available in:

* English
* French by Oncle Tom
* Hebrew by Rami
* Spanish by Juan
* Simplified Chinese by Sha Miao
* Catalan by Robert Bu
* Japanese by Naoko McCracken
* Hindi by Ashish
* Russian by Grib
* Bahasa Indonesia by Septian Fujianto
* German by Fabian Schulz
* Italian by Raffaello Tesi
* Ukrainian by WordPress plugins Ukraine
* Polish by Kuba Majerczyk

== Installation ==

The plugin is simple to install:

1. Download `redirection.zip`
1. Unzip
1. Upload `redirection` directory to your `/wp-content/plugins` directory
1. Go to the plugin management page and enable the plugin
1. Configure the options from the `Manage/Redirection` page

You can find full details of installing a plugin on the [plugin installation page](http://urbangiraffe.com/articles/how-to-install-a-wordpress-plugin/).

== Frequently Asked Questions ==

= Why would I want to use this instead of .htaccess? =

Ease of use.  Redirections are automatically created when a post URL changes, and it is a lot easier to manually add redirections than to hack around a .htaccess.  You also get the added benefit of being able to keep track of 404 errors.

= What is the performance of this plugin? =

The plugin works in a similar manner to how WordPress handles permalinks and should not result in any noticeable slowdown to your site.

== Screenshots ==

1. Simple interface to add a redirection
2. A graphical interface to manage all your redirections

== Documentation ==

Full documentation can be found on the [Redirection](http://urbangiraffe.com/plugins/redirection/) page.

== Changelog ==

= 2.0    = 
* New version

= 2.0.1  = 
* Install defaults when no existing redirection setup

= 2.0.2  = 
* Correct DB install
* Fix IIS problem

= 2.0.3  = 
* Fix #248
* Update plugin.php to better handle odd directories

= 2.0.4  = 
* get_home_path seems not be available for some people

= 2.0.5  = 
* Fix #264

= 2.0.6  = 
* Support for wp-load.php

= 2.0.7  = 
* Fix incorrect automatic redirection with static home pages

= 2.0.8  = 
* Refix log delete

= 2.0.9  = 
* Fix delete redirects

= 2.0.10 = 
* Fix small issues in display with WP 2.7

= 2.0.11 = 
* Hebrew translation

= 2.0.12 = 
* Disable category monitor in 2.7

= 2.1    = 
* Change to jQuery
* Nonce protection
* Fix #352, #353, #339, #351
* Add #358, #316.

= 2.1.1  = 
* Force JS cache
* Fix log deletion

= 2.1.2  = 
* Minor button changes

= 2.1.3  = 
* Re-enable import feature

= 2.1.4  = 
* RSS feed token

= 2.1.5  = 
* Fix #366, #371, #378, #390, #400.
* Add #370, #357

= 2.1.6  = 
* Redirection loops

= 2.1.7  = 
* Fix #422, #426

= 2.1.8  = 
* Fix category change 'quick edit'

= 2.1.9  = 
* Fix 'you do not permissions' error on some non-English sites

= 2.1.10 = 
* Missing localisations

= 2.1.11 = 
* Errors on some sites

= 2.1.12 = 
* Add icons
* Disable category monitoring

= 2.1.13 = 
* Add Spanish and Chinese translation

= 2.1.14 = 
* Fix #457
* Add #475, #427
* Add Catalan translation.
* WP2.8 compatibility

= 2.1.15 = 
* Use WP Ajax
* Add Japanese

= 2.1.16 = 
* Fix group edit and log add entry

= 2.1.17 = 
* Log JS fixes

= 2.1.18 = 
* Fix module deletion

= 2.1.19 = 
* Add Hindi translation
* Fix some ajax

= 2.1.20 =
* Fix for some users with problems deleting redirections

= 2.1.21 =
* Fix #620
* Add Russian translation

= 2.1.22 =
* Pre WP2.8 compatibility fix

= 2.1.23 =
* Add Bahasa Indonesian translation
* Add German translation
* Add patch to disable logs (thanks to Simon Wheatley!)

= 2.1.24 =
* Add Ukrainian translation
* Add Polish translation
* Database optimisation