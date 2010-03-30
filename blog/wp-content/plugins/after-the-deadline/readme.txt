=== After the Deadline ===
Contributors: automattic, rsmudge
Tags: writing, spell, spelling, spellchecker, grammar, style, plugin, edit, proofreading, English, French, German, Portuguese, Spanish
Stable tag: 0.49006
Requires at least: 2.8.4
Tested up to: 2.9.2

After the Deadline checks spelling, style, and grammar in your WordPress posts. Now it works with French, German, Spanish, and Portuguese.

== Description ==

After the Deadline helps you write better and spend less time editing. Click the proofread button in the visual or HTML editor toolbar to check spelling, style, and grammar.

== Screenshots ==

1. After the Deadline in the Visual Editor.
2. After the Deadline in the HTML Editor.

== Installation ==

Upload the After the Deadline plugin to your blog, Activate it, and Enjoy!

* Note: make sure After the Deadline is in a folder named "after-the-deadline". This is necessary for it to work.

== Frequently Asked Questions ==

= I want to use After the Deadline's technology in a project, what are your terms? =

After the Deadline (including the backend) is open source. You can get the GPL server code at [http://open.afterthedeadline.com](http://open.afterthedeadline.com). LGPL 
front-end resources are available on the [AtD Developer Resources](http://www.afterthedeadline.com/development.slp) page.

= Does this plugin work with WordPress-MU? =

Yes. You no longer need an API key either. Just enable it as a site-wide plugin. Settings are stored on a per user basis.

= Can I help translate this plugin? =

Yes! See [our call for volunteers](http://en.forums.wordpress.com/topic/call-for-volunteers-help-translate-after-the-deadline?replies=21). Leave a comment on the same thread if you'd like us to add your language. Translation moderation help is appreciated too.

= How do I ask a question? =

Great question! Visit [After the Deadline support](http://www.afterthedeadline.com/support) and ask. 

If you want to ask your question publicly, visit the [WordPress.org Plugins and Hacks Support Forum](http://wordpress.org/support/forum/10#postform) and write your 
message there. Tag your message with *after-the-deadline* to make sure I see it.

== Changelog ==

= 24 Mar 10 =
- Fixed two cases of parent variable polution (two for loops not declaring their vars)
- Fixed bug preventing subsequent occurences of one error (w/ the same context) from highlighting
- Error highlighter now uses beginning of word boundary to accurately find error location in text
- Fixed bug preventing misspelled words in single quotes from being highlighted
- Updated translations for pt, fr
- Added translations for de, es, it, ja, pl, and ru

= 15 Feb 10, pt 2 =
- Fixed a bug where AtD style checker preferences were ignored causing AtD to show every error.

= 15 Feb 10 =
- Removed API key requirement.
- Added proofreading support for French, German, Portuguese, and Spanish
- AtD/jQuery code (HTML Proofreader) is now jQuery 1.4 compat
- Fixed an error highlighting issue
- Added translations for Portuguese, Hindi, Japanese, French, Finnish, Bosnian, and Persian. [You can help translate too](http://en.forums.wordpress.com/topic/call-for-volunteers-help-translate-after-the-deadline?replies=21)
- Fixed double quotes in an ignore string breaking AtD

= 14 Jan 10 =
- Changed constant undefined to null.

= 13 Jan 10 =
- Updated AtD plugin to make it ready for localization
- Fixed many many bugs in HTML Proofreader
- Visual Editor and HTML Proofreader now share code for common tasks (less bugs, more consistency, smaller download)

= 11 Dec 09 =
- AtD now takes care to load its JS and CSS on appropriate admin pages (other admin pages are untouched) [Contributed by: Mohammad Jangda]
- Added option to auto-run AtD before a post and warn you if there are errors [Contributed by: Mohammad Jangda]
-- Go to your user profile to enable this, it's disabled by default
- AtD now checks if fsockopen exists, if it doesn't it notifies you that AtD won't work.
- AtD checks if API key is a WP.com API key and offers an error message to clear up the confusion (this should stop the emails I get about this :))
- Fixed a bug that (in rare cases) caused an error to highlight the wrong text
- AtD now checks for an ATD_KEY constant before asking for an API key. WPMU users can set ATD_KEY in wp-config.php for all users on their site.
- AtD also checks for ATD_HOST and ATD_PORT constants. You can define these if you're running your own AtD server want the plugin to talk to that instead.

= 27 Nov 09 =
- Removed a check that disables the AtD options when the visual editor is disabled

= 26 Nov 09 =
- Fixed a bug preventing HTML Editor Proofread function from working when WordPress is installed into a subfolder of the domain. Thanks Chip for sticking this one out with me.

= 10 Nov 09 =
- Added new style checker option (disabled by default) to restore diacritical marks and accented characters in words.  
- Fixed an issue causing errors with AtD/HTML Editor when trying to spellcheck text with pretty typography 
- 'Edit Selection' menu in AtD/HTML Editor keeps the error highlight if cancel was selected
- AtD now does a better job detecting when no errors were highlighted and alerting you to it.

= 3 Nov 09 =
- Added AtD support for the HTML Editor. Click the "proofread" button to check your writing.
- Updates to TinyMCE plugin to prevent highlighted errors from stepping on eachother
- AtD ignored phrases now show up on profile in IE6

= 9 Oct 09 =
- Fixed a bug in IE causing immediate space after an error to be eaten (in some cases)

= 6 Oct 09 =
- Fixed a bug preventing the second of two-like errors in the same span from getting highlighted. 

= 21 Sept 09 =
- Changed editor plugin to avoid namespace conflicts with Javascript when storing error precontext and strings.  

= 14 Sept 09 =
- AtD/WP.org now works with WordPress blogs configured to use SSL in the admin area.  Special thanks to Alex Rodriguez
  who patiently worked with me to track this bug down.  
- removed atd.css and made the TinyMCE plugin load the button.

= 10 Sept 09 =
- Fixed an issue with bold/italic stripped in Safari and object (YouTube embeds) tag stripped in other browsers

= 8 Sept 09 =
- small update to the TinyMCE editor plugin, fixes a rarely occuring bug where some suggestions weren't highlighted.

= 6 Sept 09 =
I'm still learning how to program, jumping from BASIC to JavaScript is tough--here are the things fixed this time:

- Fixed an issue preventing errors with hyphens not being highlighted
- Empty span tags created by cutting and pasting AtD marked text in Firefox are removed.  
  IE has the good sense to not transfer these tags.  Safari can't be helped as it creates 
  a span tag with inline styling.  
- Fixed an issue preventing some errors from being highlighted in certain situations.

= 4 Sept 09 =
- Fixed a bug that caused a fatal error in some cases

= 3 Sept 09 =
- Major updates.  Note that most errors are now optional and disabled by default.  Visit your user profile (/wp-admin/profile.php) to update your After the Deadline options.

= 17 Jun 09 =
- Added hack to make sure AtD tags are stripped.  My apologies for this bug.  

The good news--install this update and all your old posts will be free of AtD tags 
when displayed.

= 15 May 09 =
- Updated TinyMCE plugin to something more sane.
- Small cosmetic change to the AtD toolbar icon
- Added the ability to quickly ignore/unignore phrases.  

= 13 Mar 09 =
- Removed use of reference in foreach (PHP 5.0 only feature?)
  Fixes: Parse error: parse error, unexpected '&', expecting T_VARIABLE or '$'

= 2 Mar 09 =
- Removed curl dependence
