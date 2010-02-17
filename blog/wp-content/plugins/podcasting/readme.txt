=== Podcasting ===
Contributors: cavemonkey50, jesse_altman, spiralwebconsulting
Donate link: http://plugins.spiralwebconsulting.com/podcasting.html#donate
Tags: podcast, itunes, podcasting, rss, feed, enclosure
Requires at least: 2.7
Tested up to: 2.8.4
Stable tag: 2.3

*Podcasting is no longer supported. [Read this post](http://ronaldheft.com/2009/12/podcastings-plugin-development-comes-to-a-close/) for more information.*

Podcasting enhances WordPress' existing podcast support by adding multiple iTunes-compatible feeds, media players, and an easy to use interface.

== Description ==

Podcasting brings complete podcasting support to WordPress. Podcasting will take a file from somewhere on the web (either your site or another site) and it will add it to an iTunes-based feed. Podcasting also includes a player allowing visitors to your site to view the file on the web.

*Podcasting is brought to you for free by [Spiral Web Consulting](http://spiralwebconsulting.com/). Spiral Web Consulting is a small web development firm specializing in PHP development. Visit our website to learn more, and don't hesitate to ask us to develop your next big WordPress plugin idea.*

= Features =

- Adds a dedicated Podcasting feed with full iTunes support
- Includes the ability to have multiple podcasting feeds based on file format or other factors
- Includes both an audio and video player for in-post listening/watching
- Fully integrates with any existing enclosures already stored in WordPress
- Offers a migration tool for users of podPress

For more information, visit the [Podcasting plugin page](http://plugins.spiralwebconsulting.com/podcasting.html).

== Installation ==

Please visit [Spiral Web Consulting's forum](http://plugins.spiralwebconsulting.com/forums/viewtopic.php?f=8&t=22) for installation information.

== Frequently Asked Questions ==

Please visit [Spiral Web Consulting's forum](http://plugins.spiralwebconsulting.com/forums/viewforum.php?f=8) for the latest FAQ information.

== Screenshots ==

1. An example of the Podcasting enclosure box before a podcast is added.
2. The Podcasting enclosure box with an example podcast added.
3. The audio player before an episode begins playing.
4. The audio player in the middle of an episode.
5. The video player before an episode is playing.

Please visit the [Podcasting plugin page](http://plugins.spiralwebconsulting.com/podcasting.html#screenshots) for more screenshots.

== Changelog ==

= 2.3 =
* Now displays the surrounding player text in feeds with a download link.
* Adds support for the Send to Editor button to work with text editors other than tinyMCE.
* Removes a foreach warning message that could appear at the top of a blog page if PHP error reporting is enabled and no formats are specified.

= 2.2.5 =
* Corrects incorrectly stripped characters from a feed's itunes:summary and itunes:subtitle tags.

= 2.2.4 =
* Corrects incorrectly stripped characters from a feed's itunes:summary and itunes:subtitle tags.
* Corrects auto detect url for format feeds.

= 2.2.3 =
* Fixes a bug that was breaking the save button on the settings page in IE.

= 2.2.2 =
* Adds missing WordPress menu call that prevented users from loading the settings page under WordPress 2.8.1.
* Adds a new advanced option to disable the 404 check. Only use this option if you know what you're doing.
* Moves the disable auto-enclose option to a new Advanced Options subheading on the settings page.

= 2.2.1 =
* Adds support for WordPress' new changelog readme.txt standard. Version information is now available from within the plugin updater.
* Enhances the links on the plugin page. Adds a settings, FAQ, and support link.

= 2.2 =
* Fixes the disappearing / missing enclosure bug introduced with WordPress 2.8's new "enclosure prune" method.
* Adds a second-line of defense fix to WordPress 2.8's "enclosure prune" method that will prevent enclosures from becoming garbled serialized text.
* Adds logic to disable the add podcast button for 3 seconds to prevent duplicate enclosures.
* Adds an additional error message for non-standard http responses.
* Improves 404 detection for HTML error pages.

= 2.1.2 =
* Adds error messages to the admin AJAX calls.
* Adds comment with version number to header output.

= 2.1.1 =
* Fixes a UTF8 feed title encoding problem.
* Supports WordPress 2.8.

= 2.1 =
* Adds local detection of mime types and file size. No longer requires an external connection to successfully enclose a file.
* Removes stray player code that appears on some WordPress theme pages using the_excerpt.
* Upgrades the JW FLV Media Player to version 4.4.
* Adds player support (via JW FLV Media Player) for m4a files.

= 2.0.4 =
* Fixes encoding issues with certain file URLs. This should eliminate the cURL issue for most people.

= 2.0.3 =
* Corrects an incorrect function call in the podPress importer left over from converting to a class-based plugin.

= 2.02 =
* Corrects issue where a manually inserted player would not prevent the automatic player from being added.

= 2.01 =
* Fixes a blank page issue when automatically including the podcast player on posts.

= 2.0 =
* Podcasting is now supported by Spiral Web Consulting.
* Corrects feed URL issues when a category with the name "podcast" existed. Note: Due to this change, the URL for format feeds has changed slightly.
* Fixes a bug that prevented new enclosures from being added.
* Converts Podcasting to a class-based plugin. This will make adding new features easier.
* Drastically improves new/edit enclosure interface to conform with WordPress 2.7 design standards.
* Adds an option to disable WordPress' automatic enclosing of file URLs.
* Allows HTML to be entered in the text fields surrounding players.
* Adds an option to use the video player for audio files.
* Adds support for handling 404 error situations more gracefully.

= 2.0b20 =
* Corrects broken format feeds.
* Adds several better methods for enclosing a feed (mainly fixing redirects not working).
* Fixes all known feed validation issues as well as a few that would prevent iTunes clients from reading the feed.

= 2.0b19 =
* Fixes an error that could occur if cURL is missing from PHP.

= 2.0b18 =
* Corrects encoding issues with video file download links.

= 2.0b17 =
* Resolves an issue where enclosures could disappear from the main feed when Podcasting is activated.
* Resolves an issue where a local enclosure was not working for some users do to a missing magic_mime on their server.

= 2.0b16 =
* Corrects a PHP warning that could occur prevent an enclosure from occurring.

= 2.0b15 =
* Corrects a bug in remote file retrieval present since beta 13.

= 2.0b14 =
* Applies the fixes in beta 13 to the podPress importer.
* Fixes a rare PHP error related to the local enclosure attempt.
* Corrects a potential XHTML error with certain themes.

= 2.0b13 =
* Greatly improves enclosure retrieval. If the file can’t be accessed via the internet, a local attempt will be made. Anyone experiencing the missing enclosures bug should upgrade to this version.
* Adds a notification if there are issues connecting to the file.
* Corrects some plugin conflicts.
* Fixes issues with foreign characters in the blog title.

= 2.0b12 =
* Corrects an XML warning and error in the podcast feed related to the iTunes image.
* Removes a warning that could display during a podPress import.

= 2.0b11 =
* Adds support for importing via WPMU.
* Fixes a bug where the podPress importer would not handle relative URLs correctly.

= 2.0b10 =
* Improves file type detection for the Send to Editor button.
* Corrects an XML warning in the podcast feed related to the iTunes image.
* Improves robustness of script additions, possibly fixing some IE scripting errors.
* Fixes some errors where importing from podPress would fail.

= 2.0b9 =
* Now alerts the user if the file they enter does not exist (404). This should help weed out the mysterious disappearing enclosures.
* Fixes a conflict with some of WordPress 2.7’s admin jQuery (namely the show button in the media gallery).
* Corrects an XML warning in the podcast feed related to the iTunes image.

= 2.0b8 =
* Corrects an error message related to the new automatic player addition.

= 2.0b7 =
* Supports WordPress 2.7 and now requires WordPress 2.6.
* Includes an importer for migrating from podPress.
* Adds a video player (JW FLV Player) and updates the audio player (WordPress Audio Player 2.0).
* Adds an option to automatically include players above or below the content of a post.
* Adds options to configure player variables on a global or per player basis.
* Adds options for placing text above, before, and below a player, while specifying a field as a “download link”.
* Adds a more robust method for enclosing files. This new method adds relative URL support, support for enclosing any type of file, and should alleviate the problems most users were having. If a server issue is detected, a warning is displayed with more information on how to correct the problem.
* Adds an option to configure the language of RSS feeds.
* Adds a standard RSS image tag to the feed when itunes:artwork is used.
* Fixes countless potential feed validation issues.

= 1.65 =
* Corrects saved draft issue brought on by WordPress 2.6.
= 1.64 =
* Adds missing image showing the audio player’s colors.
* Fixes a bug where changing a format’s slug would forget the format’s explicit setting.

= 1.63 =
* Corrects typo preventing 1.62’s fix from working.

= 1.62 =
* Resolves an issue where an episode would not be saved once navigating away from the page.

= 1.61 =
* Resolves an issue where certain URL characters such as spaces would cause a failure creating an enclosure.
* Resolves validation issues with the RSS feed.

= 1.6 =
* Adds options to configure the audio player’s colors.

= 1.52 =
* The player is no longer replaced with the text “Download Podcast” in feeds to prevent that text from showing up in iTunes descriptions when the player is inserted first in a post.

= 1.51 =
* Fixes the Send to Editor button when the visual editor is disabled.

= 1.5 =
* Fixes compatibility issues with WordPress 2.5.
* Updates to the user interface to reflect the changes in 2.5.
* Episode addition interface is now fully AJAX. Add and delete episodes without having to refresh the page.
* Converts [podcast] tag to new shortcode API.
* Fixes Send to Editor button not working on the visual editor.
* Note: Version 1.5 requires WordPress 2.5.

= 1.02 =
* Fixes a critical Javascript error affecting Internet Explorer and possibly other browsers.
* It is recommended to install this update as soon as possible.

= 1.01 =
* Fixes a conflict with the Feedburner Feedsmith plugin.
* Resolves AJAX errors when managing formats.

= 1.0 =
* Initial release.