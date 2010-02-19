=== Plugin Name ===
Contributors: kevinB
Donate link: http://agapetry.net/news/introducing-role-scoper/#role-scoper-download
Tags: restrict, access, permissions, cms, user, groups, members, admin, category, categories, pages, posts, page, Post, privacy, private, attachment, upload, files, rss, feed, feeds
Requires at least: 2.5
Tested up to: 2.9.2
Stable Tag: 1.1.7

CMS-like permissions for reading and editing. Content-specific restrictions and roles supplement/override WordPress roles. User groups optional.

== Description ==
Role Scoper is a comprehensive access control solution, giving you CMS-like control of reading and editing permissions.  Assign restrictions and roles to specific pages, posts or categories.

= How it works: =
Your WordPress core role definitions remain unchanged, and continue to function as default permissions.  User access is altered only as you expand it by assigning content-specific roles, or reduce it by setting content-specific restrictions.

Users of any level can be elevated to read or edit content of your choice.  Restricted content can be withheld from users lacking a content-specific role, regardless of their WP role.  Deactivation or removal of Role Scoper will return each user to their standard WordPress access (but all RS settings remain harmlessly in the database in case you change your mind).

Scoped role restrictions and assignments are reflected in every aspect of the WordPress interface, from front end content and navigation to administrative post and comment totals.  Although Role Scoper provides extreme flexibility and powerful bulk administration forms, basic usage is just a set of user checkboxes in the Post/Page Edit Form.

= Partial Feature List =
* Customize access for specific Pages, Posts, Categories
* Control Read and/or Edit access
* WP roles work as is but can be limited by content-specific restrictions
* Assign additional content-specific roles to Users or User Groups
* Assign additional blog-wide role for a specific object type
* Can elevate Subscribers to edit desired content (ensures safe failure mode)
* Control which categories users can post to
* Control which pages users can associate sub-pages to
* Specify element(s) in Edit Form to withhold from non-Editors
* Limit the duration of role assignments
* Limit the content dates which a role assignment applies to
* Front-end Page, Category and Tag listings match modified access
* Customizable Hidden Content Teaser (or hide posts/pages completely) 
* RSS Feed Filter with HTTP authentication option
* File Attachment filter blocks direct URL requests if user can't read corresponding post/page
* Inheritance of Restrictions and Roles to sub-categories / sub-pages
* Default Restrictions and Roles for new content
* Un-editable posts/pages are excluded from the editing list
* Optimized to limit additional database queries
* XML-RPC support
* Integrates with the <a href="http://wordpress.org/extend/plugins/revisionary/">Revisionary plugin</a> for moderated revisioning of published content.
* Extensive WP-mu support

= Plugin API =
* Apply restrictions and roles for any custom taxonomy
* Abstract architecture and API allow other plugins to define their own role definitions for scoping
* Author provides some <a href="http://agapetry.net/category/plugins/role-scoper/role-scoper-extensions/">extensions to support integration with other plugins</a>

= Template Functions =
Theme code can utilize the is&#95;restricted&#95;rs() and is&#95;teaser&#95;rs() functions to customize front-end styling.

Other useful functions include users&#95;who&#95;can(), which accounts for all content-specific roles and restrictions.

For more information, see the <a href="http://agapetry.net/downloads/RoleScoper_UsageGuide.htm">Usage Guide</a> or <a href="http://agapetry.net/forum/">Support Forum</a>.

= Support =
* Most Bug Reports and Plugin Compatibility issues addressed promptly following your <a href="http://agapetry.net/forum/">support forum</a> submission.
* Author is available for professional consulting to meet your configuration, troubleshooting and customization needs.


== Installation ==
Role Scoper can be installed automatically via the Plugins tab in your blog administration panel.

= To install manually instead: =
1. Upload `role-scoper&#95;?.zip` to the `/wp-content/plugins/` directory
1. Extract `role-scoper&#95;?.zip` into the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==
<strong>How can I prevent low-level users from seeing the Roles/Restrictions menus and Edit boxes?</strong>
In your blog admin, navigate to Roles > Options.  In the "Content Maintenance" section, set the option "Roles and Restrictions can be set" to "by blog-wide Editors and Administrators" or "by Administrators only".  Click the Update button.


<strong>How does Role Scoper compare to <a href="http://sourceforge.net/projects/role-manager/">Role Manager</a> or <a href="http://wordpress.org/extend/plugins/capsman/">Capability Manager</a>?</strong>
Role Scoper's functionality is entirely different and complementary to RM and CM.  RM/CM do little more than alter WordPress' definition of the capabilities included in each role.  That's a valuable task, and in many cases will be all the role customization you need.  Since RM/CM modifications are stored in the main WordPress database, they remain even if RM/CM is deactivated.

Role Scoper is useful when you want to customize access to specific content, not just blog-wide.  It will work with the WP roles as a starting point, whether customized by RM/CM or not.  To see how Role Scoper's role definitions correlate to your WordPress roles, navigate to Roles > Options > RS Role Definitions in your blog admin.  Role Scoper's modifications remain only while it stays active.


<strong>Why are there so many options? Do I really need Role Scoper?</strong>
It depends on what you're trying to accomplish with your WordPress installation.  Role Scoper is designed to be functionally comprehensive and flexible.  Great pains were taken to maintain performance and user-friendliness.  Yet there are simpler permission plugins out there, particularly if you only care about read access.  Review Role Scoper's feature list and decide what's important to you.

<strong>Why doesn't Role Scoper limit direct access to files that I've uploaded via FTP?</strong>
Role Scoper only filters files in the WP uploads folder (or a subfolder).  The uploads folder must be a branch of the WordPress directory tree.  The files must be formally attached to a post / page via the WordPress uploader or via the RS Attachments Utility.

In your blog admin, navigate to Roles > Options > Features > Attachments > Attachments Utility.

<strong>Where does Role Scoper store its settings?  How can I completely remove it from my database?</strong>
Role Scoper creates and uses the following tables: groups&#95;rs, user2group&#95;rs, role&#95;scope&#95;rs, user2role2object&#95;rs.  All RS-specific options stored to the WordPress options table have an option name prefixed with "scoper&#95;".

Due to the potential damage incurred by accidental deletion, no automatic removal is currently available.  You can use a SQL editing tool such as phpMyAdmin to drop the tables and delete the scoper options.


== Screenshots ==

1. Admin menus
2. Role boxes in Edit Post Form
3. Role boxes in Edit Page Form
4. Category Restrictions
5. Category Roles
6. <a href="http://agapetry.net/demos/category_roles/index.html">View an html sample of the Category Roles bulk admin form</a>
7. <a href="http://agapetry.net/demos/rs-options_demo.htm">View an html sample of Role Scoper Options</a>
8. <a href="http://agapetry.net/news/introducing-role-scoper/">View more screenshots</a>


== Changelog ==

= 1.1.7 - 15 Feb 2010 =
* BugFix : In Post/Page Edit Form, user checkboxes for role assignment were not sorted alphabetically (since 1.1.RC1)
* Feature : Omit other users' trashed posts from Edit Posts / Edit Pages listing if not editable by logged user
* Compat : Initial WP 3.0 compat (fixing errors related to custom post type and changes to contextual help API)
* Change : Widen Page Parent dropdown on Edit Page Form to full width of Attributes box


**1.1.6 - 13 Feb 2010**

= File Filtering Fixes =
* BugFix : File Filtering failed on some installations, possibly causing an Internal Server Error
* BugFix : In WP-MU with File Filtering enabled, .htaccess file in uploads folders was regenerated on each site access (since 1.1.2)
* BugFix : In non-MU installations, .htaccess file was not immediately updated on activation / deactivation of File Filtering

= Other Changes =
* BugFix : Non-Administrators could not modify any Roles or Restrictions via bulk admin forms, even if some are delegated to them
* Lang : Added Spanish translation (Rafael P&eacute;rez Gana - http://www.rafo.cl/)
* Change : Use https link for Role Scoper css and js files if ssl is being used / forced for the current uri
* BugFix : Archives listing using postbypost listing type did not display private posts to logged Administrator
* BugFix : Template function is_protected() / is_restricted() did not work with secondary queries
* BugFix : Private posts / pages not sometimes hidden from logged Administrators in front-end custom query results
* BugFix : PHP warnings on Edit Post / Page form (if WordPress debug mode enabled)
* BugFix : PHP notice for undefined constant (SCOPER_FORCE_FILE_INCLUSIONS)
* Feature : Support SCOPER_TEASER_HIDE_PAGE_LISTING definition, to suppress teased pages from front-end listing (while still applying teaser on direct access)


= 1.1.5 - 28 Jan 2010 =
* BugFix : Fatal error under some configurations: Call to undefined function is_site_admin()
* Change : Don't create a blank .htaccess file in uploads folder if there are no restricted attachments to filter
* Feature : Observe SCOPER_DEFAULT_MONITOR_GROUPS definition to disable custom editing of Pending Revision Monitors, Scheduled Revision Monitors groups


= 1.1.4 - 27 Jan 2010 =
* BugFix : Fatal error on activation with wp-MU if File Filtering enabled
* BugFix : File Filtering was ineffective with wp-MU under some configurations
* BugFix : Updates to Role assignments fail if MySQL does not convert nullstring to zero value for datetime storage
* Lang : Reverted _x() translation calls to __(), due to issues with poEdit support


**1.1.3 - 22 Jan 2010**

= WP-mu Fatal Error =
* BugFix : Fatal error on wp-MU version upgrade, due to failed get_home_path() call (since 1.1.RC1)

= File Filtering =
* BugFix : .htaccess file was not regenerated when File Filtering is re-enabled following a disable (since 1.1.RC1)
* BugFix : File Filtering was not imposed for new attachments to private / restricted posts (since 1.1.RC1)

= WP 2.9 Trash Function =
* WP Compat : Trashed posts / pages were included in edit listing when status filter set to default "All"
* WP Compat : Trashed pages were included in Page Parent dropdown

= Significant, Prevalent Bugs (new in 1.1 code base) =
* BugFix : Main Page was not selectable when Quick Editing a Page
* BugFix : Posts were included in get_pages listing if "Include private pages in listing" option was disabled and Hidden Content Teaser turned off
* BugFix : When Contributor / Author category selection is limited, valid default category was not automatically selected
* BugFix : Some Category Roles were inappropriately auto-deleted on blogs which originated with WP < 2.3 (and have cats with term_taxonomy_id != term_id)
* BugFix : With Limited Editing Elements option enabled, some Post/Page Edit Form elements were inappropriately hidden from Editors / Authors / Contributors

= Significant but Obscure 1.1 Bugs (only affect nonstandard config) =
* BugFix : "Not valid" error message when a non-administrator saves a post/page with Role Type option set to "WP"
* BugFix : If RS Realm was customized for Page Roles only, the Restrictions menu included an invalid link to Category Restrictions
* BugFix : Some custom taxonomy queries were not filtered correctly
* BugFix : If "Remap terms" option was disabled, Category Edit Form did not list editable categories whose parent is uneditable

= Significant but Rare 1.1 Bugs (only affect some installations) =
* BugFix : New Pages / Posts did not inherit parent restrictions, in some installations
* BugFix : New Role assignments fail if MySQL does not convert nullstring to zero value for datetime storage
* BugFix : If Additional Object Roles option was enabled for some role, Page/Post assignments of that role could not be removed
* BugFix : PHP Warning on Group creation, in some installations

= Hidden Content Teaser =
* BugFix : Template function is_teaser_rs() did not work unless post ID was explicitly passed in (should default to ID of global $post)
* BugFix : Hidden Content Teaser, when applying "first x chars" teaser, stripped out img tag but not image caption
* Feature : Support SCOPER_NO_FEED_TEASER constant definition to prevent teasing of feed items even if teaser is enabled for main posts/pages listing

= Nuisance Bugs =
* BugFix : Convenience links to Category / Page Restrictions and Roles (within caption text) were invalid
* BugFix : "Browse Members" link on User Groups management page was broken
* BugFix : On General Roles assignment attempt, role selections were not preserved if user/group selection is missing

= Plugin Compatibility =
* Compat : PHP Warnings with WP Facebook Connect plugin


= 1.1.2 - 31 Dec 2009 =
* Change : Disable File Filtering by default, due to undiagnosed errors on some installations


= 1.1.1 - 30 Dec 2009 =
* BugFix : Recursive execution of category filter caused memory error in some installations


= 1.1 - 30 Dec 2009 =
* Feature : Additional "Lock Top Pages" option to allow any Page Author to set or remove top-level pages
* Feature : If HTTP authentication is enabled, append the http_auth argument to Category, Tag, Author and Comment feed links also 
* BugFix : Changes to restrictions, roles did not clear internal cache for anonymous user (since 1.1.RC1)
* BugFix : Contributors could not upload an image before a category is set, if editing rights are based on category
* BugFix : Edit Posts listing for Published status included non-published posts (since 1.1.RC1)
* BugFix : Edit Pages listing for Published status included non-published pages (since 1.1.RC1)
* BugFix : Category Restrictions were not correctly noted in Edit Posts listing or front-end template functions (since 1.1.RC1)
* BugFix : In WP-mu dashboard, PHP warnings on first execution (since 1.1.RC1)
* BugFix : Attachments Utility did not load (since 1.1.RC1)
* Compat : Simple Section Nav: page selection list in Widget setup was broken with latest SSN version


= 1.1.RC3 - 18 Dec 2009 =
* BugFix : Categories listing filter was inactive for new installations and following RS Options re-save (since 1.1.RC1)
* BugFix : Invalid HTML formatting of Page Parent dropdown if no published pages exist


= 1.1.RC2 - 17 Dec 2009 =
* BugFix : Custom Taxonomy Restrictions were not applied correctly (since 1.1.RC1)
* BugFix : Activation Error (since 1.1.RC1)


**1.1.RC1 - 12 Dec 2009**

= WP-mu: =
* Feature : Option for site-wide groups when running on WP-mu
* Feature : Most RS options can be applied either site-wide or blog-specific
* Feature : Default settings for per-blog options can be customized via Site Admin > Role Defaults
* BugFix : User RS Blog Roles were not added / removed appropriately with mu user addition / removal for specific blogs
* BugFix : RS General Role assignments were not effective; attempt to add post/page caused redirect to profile page of main blog
* BugFix : Internal cache returned categories from other blogs in some situations

= Date Limits: =
* Feature : Roles can be assigned with limited duration (grant and expire dates)
* Feature : General Roles and Category Roles can be assigned with content date limits (role only applies for posts/pages dated within specified range)

= File Filtering: =
* Feature : New filtering scheme eliminates many quirks by using header redirect rather than opening and sending file contents directly
* BugFix : Attachment filtering blocked some unattached files or public files.  New scheme uses per-file RewriteRules, does not filter unprotected files at all.
* Feature : File filtering can be disabled / enabled via RS Option.
* Feature : Definition / removal of DISABLE_ATTACHMENT_FILTERING constant definition now forces automatic .htaccess regeneration / restoration
* BugFix : Fatal error due to failed flush_rules call on initialization, in some upgrade scenarios
* BugFix : Auto-regenerate .htaccess if it gets out of sync with DB-stored file access key(s)
* Perf : Reduce unnecessary script loading / execution when applying file filtering

= Performance Enhacement Results: =
* Default memory usage is lower than v 1.0.8 despite feature additions.
* Further memory savings possible by disabling various features (see below).
* Decreased database execution time in several areas.

= Performance Enhancements Details: =
* Perf : User role sync at activation (for WP role assignments) was executing a separate query for each user (leading to long delays on some installations) 
* Perf : Do not resync all users on each user registration / profile update
* Perf : Extensive optimization of code structure and inclusion logic to prevent unnecessary memory usage.
* Change : Require MySQL >= 4.1 so LEFT JOINs can be replaced by subqueries
* Perf : Converted LEFT JOIN in posts query to subselect
* Perf : Eliminated unnecessary LEFT JOIN in terms query
* Perf : Further wp-admin memory savings via option to disable filtering of Post Author dropdown (if "Indicate Blended Roles" and "Limit eligible users" also disabled)
* Perf : Further front-end memory savings if you define SCOPER_GET_PAGES_LEAN (don't retrieve page content just to list page titles) 
* Perf : Further wp-admin memory savings if you define SCOPER_EDIT_POSTS_LEAN, SCOPER_EDIT_PAGES_LEAN
* Perf : Eliminated redundant filtering for page parent dropdown
* Perf : Eliminated unnecessary RS queries in Media Library
* Perf : Eliminated unnecessary RS-initiated post/page retrieval queries
* Perf : No construction / translation of role names in wp-admin until they are needed
* Perf : set RS option records to autoload = no, since RS does its own buffering
* Perf : Eliminated lots of PHP warnings for unset variables / array keys
* Change : Stop storing postmeta last_parent entry for pages / posts that have no Parent setting

= User Editing / Role Assignment: =
* Feature : support distinction between Content Administrator, User Administrator and Option Administrator.  Currently designate cap for each via define( 'SCOPER_CONTENT_ADMIN_CAP', 'cap_name' );
* Feature : Option to allow role assignment only by Content Administrators / User Administrators
* Feature : Don't allow the editing of users with a higher level than logged user (can disable via RS Option)
* Feature : Don't allow the assignment of a WP role with a higher level than logged user's level (can disable via RS Option)

= Role Definition: =
* Feature : Synchronize RS Role Defs to WP Role Defs at installation (eliminates unexpected results when WP roles are customized)
* Feature : On RS Role Defs tab, warn if WP Roles do not have normal RS role containment (WP Author contains RS Post Author, etc.) due to extra caps in RS Role def
* Feature : On RS Role Defs tab, option to synchronize WP Contributor / Author / Editor role def with current RS Post Contributor / Post Author / Post Editor / Page Editor role def
* BugFix : WP Role Definitions tab empty on reload after updating RS Options
* Change : Post Editor / Page Editor role assignment also grants unfiltered_html capability for that content.  Can be disabled via Roles > RS Role Defs.

= Group Roles: =
* Feature : Metagroup for anonymous users - define SCOPER_ANON_METAGROUP.  Only to be used when some content should be seen by anon users but not all logged users.
* BugFix : WordPress roles with name longer than 25 characters caused RS metagroup record to be perpetually regenerated with new group_id, leaving orphaned role assignments
* BugFix : Group deletion did not always delete all associated roles
* BugFix : Incorrect eligible groups count if orphaned Group Role assignments are stored
* Change : Delete all orphaned group role assignments on plugin re-activation

= Media Library: =
* BugFix : non-administrators could not view unattached uploads via View link in Media Library
* BugFix : non-administrators could not see unattached uploads in Library tab of uploader
* BugFix : Authors were not allowed to edit or delete their unattached uploads in Media Library

= Post / Page Edit Form: =
* Feature : Option to default new posts and/or pages to Private visibility
* Feature : Option to auto-select Private visibility when the Reader role is restricted in Page/Post Edit Form
* BugFix : On post creation, default category was not applied in some situations when author had save / publish capability for it
* BugFix : On post creation, first available category was not applied in some situations when author did not select any categories (and does not have save/publish capability for default cat)
* BugFix : Authors could not edit their own private posts / pages in some configurations
* BugFix : Non-editors were sometimes unable to save subpages of pages based on their Page Associate role; received a "cannot associate with the Main Page" error message
* BugFix : WP Metagroup Category/General Role assignments were not indicated by color coding in Post/Page Edit Form role metaboxes
* BugFix : "Attempt has failed" error when submitting post with some certain WP/RS Role Definitions and editing roles restricted in all categories
* BugFix : Out of memory / timeout error on some servers when non-Administrator views Edit Posts listing
* BugFix : In some configurations where user can edit a subpage based on propagated Page Editor role, that role assignment was lost when they saved a change to the page content.
* Change : Implicit role ownership via Category/General Role assignment is indicated by slashes around user/group name.  Previous versions used square brackets.

= Post / Page Edit Form - Limited Editing Elements: =
* Feature : Option to require blog-wide Administrator / Editor / Author / Contributor role for specified Limited Editing Element IDs
* BugFix : Comment and Trackbacks status turned off when a post was edited with Discussion metabox (commentstatusdiv) hidden via Limited Editing Elements
* BugFix : Custom Post Excerpt cleared when a post was edited with Post Excerpt metabox hidden via Limited Editing Elements setting
* Change : If a specified Limited Editing Element is not a metabox, hide it via CSS
* Change : "Limited Editing Elements" includes customdiv, pagecustomdiv, revisionsdiv by default

= Edit Posts / Pages Listing: =
* Feature : Custom Role / Restriction indicator columns in Edit Posts and Edit Pages listing can be selectively disabled
* Feature : Custom columns are suppressed if logged user does not satisfy RS Option requirement for "Roles and Restrictions can be set by"
* BugFix : Custom Roles / Restriction indicator columns were sometimes displayed even if none of the listed posts used them
* BugFix : Edit Posts column indicated some false positives for Category Restrictions
* BugFix : Edit Posts listing included Term Roles column even if none of the listed posts had Term Roles

= Page / Category Listing (Front End): =
* Feature : When remapping a page to visible ancestor, Option for whether remap can bypass an explictly excluded ancestor
* Feature : When remapping a term to visible ancestor, Option for whether remap can bypass an explictly excluded ancestor
* Feature : Support remap_parents, enforce_actual_depth, remap_thru_excluded_parent args to override defaults in a get_pages() / get_terms() call
* Compat : Never remap pages if get_pages called without hierarchical arg (unnecessary, caused conflict with Flexi Pages plugin)
* Compat : Never remap terms if get_terms called without hierarchical arg
* Change : RS Option "Remap Hiden Pages to Visible Ancestor" disabled by default, to avoid conflict with template code that relies on exclude+depth arguments being treated as exclude_tree
* Change : RS Option "Remap Hiden Terms to Visible Ancestor" disabled by default, to avoid conflict with template code that relies on exclude+depth arguments being treated as exclude_tree

= XML-RPC: =
* BugFix : XML-RPC post submissions created without category selection for users without less than blog-wide Editor role
* BugFix : With some XML-RPC clients, non-administrators can publish new posts but cannot edit them following publish 
* BugFix : XML-RPC retrieval of recent posts only returned one post

= Custom Taxonomies: =
* Feature : Support Restrictions on custom taxonomies
* BugFix : When custom taxonomies are enabled for use with RS, "Category Restrictions and Roles for Posts" checkbox caption was not modified accordingly 
* BugFix : Invalid edit URL from bulk role administration form for Post Tags, Custom Taxonomies
* Workaround : WP core forces display of published posts only in Edit Posts listing when filtering by a custom taxonomy term

= Front-End Misc: =
* BugFix : get_comments() function did not include comments on attachments to private posts
* BugFix : In some installations with a language defined and "suppress private caption" option enabled, fatal error from translate call in template-interceptor 
* BugFix : template function is_restricted_rs() indicated some false positives for category restrictions
* BugFix : Tags filter defaulted to limiting number of displayed tags to 45
* BugFix : tag__not_in argument was not supported for manual calls to WP_Query

= Admin Misc: =
* Feature : Add pending posts and pages total to Dashboard Right Now list
* BugFix : Cannot approve / unapprove comments when capability is granted via Category Role or Page/Post Role
* BugFix : Roles, Restrictions menu icons were not displayed if custom WP_CONTENT_DIR set
* BugFix : PHP Warning on installation / version update due to DB key name conflicting with an existing WP key name
* BugFix : In User Profile, link to edit individual Object Role yielded "insufficient permissions" message
* BugFix : RS roles were hidden from User Profile for users who cannot assign roles due to blogwide role requirement set in RS Options
* Change : Prevent activation (with helpful error message) if another copy of RS is already active
* Change : If RS_DEBUG is defined and the script is plugins.php or edit-plugins.php, don't initialize the plugin (prevents hung server on bad edits via Plugin Editor)
* Change : On RS Options form, rearranged and recaptioned "Realm" options for clarity
* Change : Popup confirmation box before reverting RS Options to defaults
* Change : Update button in all RS forms styled the same as WordPress Update buttons

= Misc: =
* BugFix : Warning messages on servers with open_basedir restriction
* BugFix : RS Internal Cache did not work if custom WP_CONTENT_DIR set

= API: =
* API : ScoperAdminLib::create_group($name, $desript), returns group_id
* API : ScoperAdminLib::get_group_by_name($name), returns group object
* API : ScoperAdminLib::get_group($group_id), returns group object

= Plugin Compatibility: =
* Compat : Formatting of table header in Role / Restriction bulk admin forms was thrown off by BuddyPress
* Compat : Pages listing was broken when Theme My Login active with option to exclude login page from listing
* Compat : Automatically switch Roles, Restrictions tabs to default(bottom) positioning if some other plugin has moved the Users tab
* Compat : Support nonstandard usage of wp_dropdown_pages filter by Simple Section Nav plugin
* Compat : PHP Warning after AMember creates a role with no capabilities
* Compat : Apply RS restrictions and roles to Snazzy Archives plugin listing
* Compat : PHP Warning "Missing argument 2" with WMPL plugin
* Compat : Suppress RS filtering when another plugin has initiated a scheduled operation via WP Cron (conflict with WP Robot, Twitter News Feeds)
* Compat : Tiny MCE Advanced (conflict was present in RS 1.1 beta versions)
* Compat : Flutter (may require Flutter code patch, see Notes)
* Compat : Use display names and plural display names defined by Custom Taxonomies plugin

= Browser Compat (wp-admin): =
* BugFix : Background color not applied to RS Options form in some versions of IE
* BugFix : IE8 tab, checkbox positioning in Post/Page Edit Form role metaboxes

= Translation: =
* Lang : Added Italian translation (Alberto Ramacciotti - http://obertfsp.com)
* Feature : Default teaser strings included in .po file for translation.  Must add this to wp-config.php: define( 'SCOPER_TRANSLATE_TEASER', true );



= 1.0.8 - 18 Aug 2009 =
* Feature : Option to prevent non-Administrators from assigning or viewing content-specific roles
* Feature : For front-end Page and Category listings, parent remapping behavior is now adjustable via RS Options
* Change : When a depth limit is specified for pages/categories listing, default to enforcing that limit based on actual depth prior to parent remap
* Bugfix : Fix compatibility with various custom child_of / depth / exclude / order combinations in pages, categories listing
* BugFix : Pages listing filter did not honor number, offset arguments
* BugFix : Terms (categories) listing filter did not apply custom ordering filter with WP 2.8
* Change : Work around WP bug when page / category listing is generated with child_of arg, but first element in result array is not a direct child
* Compat : Simple Section Navigation plugin displayed misplaced subpages in some situations
* Compat : My Category Order plugin
* Compat : Flutter/FreshPage plugin (disable custom menu indexing if plugin is active)
* Compat : Capability Manager plugin (automatically re-sync role defs on role creation / deletion)
* BugFix : When hiding other users' unattached uploads in Media Library, attachment count did not match 
* Change : Blog-wide Editors always see all unattached uploads in Media Library, regardless of option setting
* Doc : Replaced standalone change log and plugin compat documents with readme.txt sections


= 1.0.7 - 10 August 2009 =
* BugFix : With WP 2.8, new users were not assigned membership in WP Role metagroup until cache flush
* Change : Exclude role metagroups from groups column in users list (to reduce clutter)
* Feature : Option for whether non-administrators can see unattached uploads from other users
* Change : By default, non-administrators see only their own unattached uploads in Media Library
* Compat : Disable caching of pages, categories listing if QTranslate plugin is active
* Change : remap get&#95;pages exclude argument to exclude&#95;tree if called with depth=1
* Change : remap get&#95;terms exclude argument to exclude&#95;tree if called with depth=1
* Change : more descriptive error message when another plugin prevents RS initialization


= 1.0.6 - 6 August 2009 =
* BugFix : Failed to re-activate after WordPress auto-update
* BugFix : In WP-mu, Category Roles not inherited from parent on new category creation
* BugFix : Users with Category Manager role for a limited no. of cats could change Cat Parent to None
* BugFix : Users with Category Manager role for a limited no. of cats could create new top-level cats
* BugFix : Category Edit Form offered selection of a category as its own parent (though not stored)
* BugFix : In Bulk Roles / Restrictions form, "Collapse All" script hid some Categories / Pages inappropriately


= 1.0.5.1 - 5 August 2009 =
* Bump up version number to force wordpress.org to regenerate .zip.  The 1.0.5 zip was missing many files.


= 1.0.5 - 5 August 2009 =
* Change : Hidden Editing Elements now hidden securely on server side, not via CSS.
* Change : In RS Options, recaption "Hidden Editing Elements" as "Limited Editing Elements"
* Change : Updated sample IDs displayed on Role Scoper Options form for Hidden Editing Elements
* Change : Updated default IDs for Hidden Editing Elements
* Compat : Conflict with QTranslation plugin - translation of page titles, term names, bulk admin post titles
* Compat : Support SCOPER&#95;DISABLE&#95;MENU&#95;TWEAK definition for compat with Flutter plugin
* BugFix : New pages by non-Editors initially saved as Pending even if Publish was clicked
* BugFix : Administrator could not modify default category with WP 2.8
* BugFix : Default Groups could not be edited with WP 2.8
* BugFix : Attachments Utility (in RS Options) was not accessible under WP 2.8
* BugFix : In some configurations, fatal error when unavailable user&#95;can&#95;for&#95;any&#95;object() function called with administrator logged in
* BugFix : When editing group, could not remove last group administrator
* BugFix : Group roles were not displayed in group edit form if no members in group
* BugFix : Eliminated orphaned role deletion (no longer needed and deleted non-orphan group roles in some situations)
* BugFix : Object Roles, Blog Roles cache was not flushed following group membership change
* BugFix : On some server, the internal cache did not update following user profile edit
* BugFix : RS menu links were broken if role scoper activated within custom-named directory


= 1.0.4.1 - 28 June 2009 =
* BugFix : Roles, Restrictions menu links were broken for administrators (since 1.0.4)


= 1.0.4 - 26 June 2009 =
* Change : Deny implicit comment moderation rights to Authors if they lack moderate&#95;comments cap
* BugFix : In Edit Post form, non-editors could see / select other users as "author"
* BugFix : Option "role assignment requires blog-wide editor role" was only requiring blog-wide contributor role
* BugFix : Page Parent filtering was broken for Quick Edit
* BugFix : Category Restrictions were not inherited upon new category creation
* BugFix : Option "role assignment requires blog-wide editor role" did not suppress Roles, Restrictions sidebar menu
* BugFix : XML-RPC support (ScribeFire, WLW) was broken for non-administrators
* BugFix : User groups were unusable on DB servers that do not support default value on text columns
* BugFix : exclude&#95;tree argument was ineffective in get&#95;terms / wp&#95;list&#95;categories call
* BugFix : invalid Category / Object role edit links displayed in user profile for non-editors in some configurations 
* BugFix : Role Scoper Options inaccessable to administrator with WP 2.8.1
* Change : Moved option "Role administration requires a blog-wide Editor role" to main Options tab


= 1.0.3.4 - 8 May 2009 =
* BugFix : Fifth attempt to prevent re-activation failure following Role Scoper update via WP auto-updater
* BugFix : WP 2.8 Compat: Moved Restrictions, Roles menus back to familiar location unders Users menu


= 1.0.3.3 - 8 May 2009 =
* BugFix : Fourth attempt to prevent plugin activity during WordPress update operation, to prevent re-activation failure


= 1.0.3.2 - 8 May 2009 =
* BugFix : Third attempt to prevent plugin activity during WordPress update operation, to prevent re-activation failure


= 1.0.3.1 - 8 May 2009 =
* BugFix : Second attempt to prevent plugin activity during WordPress update operation, to prevent re-activation failure


= 1.0.3 - 8 May 2009 =
* BugFix : Prevent plugin activity during WordPress update operation, to prevent re-activation failure


= 1.0.2 - 7 May 2009 =
* BugFix : Template function is&#95;restricted&#95;rs / is&#95;exclusive&#95;rs was non-functional on home page (since rc9.9311)
* BugFix : With Attachments Filter enabled, attachments larger than 10MB fail to download on some installations
* BugFix : Fatal Error when viewing a single post entry after RS Options modified to disable front-end filtering
* BugFix : Auto-delete orphaned role assignments left in DB by previous versions following category / group deletion
* BugFix : After an empty group was deleted, its role assignments were left in the database
* BugFix : Event Calendar events without an associated post were not displayed without calendar refresh 
* BugFix : Post Restrictions and Post Roles did not display on PHP 4 servers
* BugFix : In Post/Page Edit Form, Author selection was inappropriately available to non-editors
* BugFix : Orphaned role assignments already stored to database will be autodeleted on RS version update
* BugFix : If the object type of a requested attachment parent cannot be determined, assume post
* BugFix : Teaser message displayed in header with some themes
* BugFix : http authentication prompt for RSS feeds with logged administrators on some installations
* BugFix : Hidden Editing Elements settings were not effective for unpublished posts/pages
* BugFix : If a memberless group was deleted, any assigned roles were left (orphaned) in the database
* Plugin :  Conflict with WP-Wall plugin caused non-listing or double-listing of wall comments
* Feature : Option to accept CSV entry for user role assignment
* Feature : Bottom-right submit button on bulk admin forms if SCOPER&#95;EXTRA&#95;SUBMIT&#95;BUTTON is defined


= 1.0.1 - 27 March 2009 =
* BugFix : In some situations, non-attachments were included in Media Library listing
* BugFix : Low level users could not edit uploads from Media Library based on a Post/Page/Category role assignment
* BugFix : Cannot set static front page with Role Scoper activated
* BugFix : Users with editing role via Page / Category assignment could not bulk-delete posts/pages
* BugFix : Post/Page Edit divs configured as Hidden Editing Elements were not hidden for draft posts/pages
* BugFix : After a group was deleted, its role assignments were left in the database
* BugFix : PHP warnings viewing users list with WP < 2.8
* Change : WP 2.7 users with hacked WP template.php user&#95;row code must define("scoper&#95;users&#95;custom&#95;column", "true");
* BugFix : Failed to return results for manual WP&#95;Query calls which include category exclusion argument
* BugFix : Role Scoper error messages were formatted with unreadable colors with WP 2.7
* BugFix : Conflict with ozhAdminMenus plugin - Page menus missing in some configurations
* BugFix : Conflict with WP-Wall plugin caused fatal error
* Feature : Options to hide User Groups, Scoped Roles from user profile


= 1.0.0 - 21 March 2009 =
* BugFix : In some installations, DB error for anonymous user front-end access (since rc9.9220)


== Other Notes ==

= Documentation =
* A slightly outdated <a href="http://agapetry.net/downloads/RoleScoper_UsageGuide.htm">Usage Guide</a> is available.  It includes both an overview of the permissions model and a How-To section with step by step directions.  Volunteer contributions to expand, revise or reformat this document are welcome.
* Role Scoper's menus, onscreen captions and inline descriptive footnotes <a href="http://weblogtoolscollection.com/archives/2007/08/27/localizing-a-wordpress-plugin-using-poedit/">can be translated using poEdit</a>.  I will gladly include any user-contributed languages!.

= General Plugin Compatibility Requirements =
* No other plugin or theme shall define function wp&#95;set&#95;current&#95;user() or function set&#95;current&#95;user().  A custom merge of the code may be possible in some situations.
* No other plugin or theme shall make an include or require call to force early execution of the file pluggable.php (for the reason listed above).

= Specific Plugin Compatibility Issues =
* WP Super Cache : set WPSC option to disable caching for logged users (unless you only use Role Scoper to customize editing access).
* QTranslate : use Role Scoper 1.0.7 or later, which disables caching of pages, terms listing.  To enable caching, change QTranslate get&#95;pages and get&#95;terms filter priority to 2 or higher, then add the following line to wp-config.php: `define('SCOPER_QTRANSLATE_COMPAT', true);`
* Get Recent Comments : not compatible due to direct database query. Use WP Recent Comments widget or Snazzy Archives instead.
* Maintenance Mode : not compatible due to forced early login check.  To resolve conflict, disable front-end access by administrators during maintenance. Comment out the following line in maintenance-mode.php: `&& !mw&#95;current&#95;user&#95;can&#95;access&#95;on&#95;maintenance()`
* Flutter : As of Nov 2009, RS filtering of Flutter categories requires that the Flutter function GetCustomWritePanels (in the RCCWP_CustomWritePanel class, file plugins/fresh-page/RCCWP_CustomWritePanel.php) be modified to the following:

    function GetCustomWritePanels() 
    { 
        global $wpdb; 

        $sql = "SELECT id, name, description, display_order, capability_name, 
type, single  FROM " . RC_CWP_TABLE_PANELS; 

        $join = apply_filters( 'panels_join_fp', '' ); 
        $where = apply_filters( 'panels_where_fp', '' ); 

        $sql .= " $join WHERE 1=1 $where ORDER BY display_order ASC"; 
        $results = $wpdb->get_results($sql); 
        if (!isset($results)) 
                $results = array(); 

        return $results; 
    }


**Attachment Filtering**

Read access to uploaded file attachments is normally filtered to match post/page access.

To disable this attachment filtering, disable the option in Roles > Options or copy the following line to wp-config.php:
    define('DISABLE&#95;ATTACHMENT&#95;FILTERING', true);

To reinstate attachment filtering, remove the definition from wp-config.php and re-enable File Filtering via Roles > Options.

To fail with a null response when file access is denied (no WP 404 screen, but still includes a 404 in response header), copy the folling line to wp-config.php: 
    define ('SCOPER&#95;QUIET&#95;FILE&#95;404', true);

Normally, files which are in the uploads directory but have no post/page attachment will not be blocked.  To block such files, copy the following line to wp-config.php: 
    define('SCOPER&#95;BLOCK&#95;UNATTACHED&#95;UPLOADS', true);


**Hidden Content Teaser**

The Hidden Content Teaser may be configured to display the first X characters of a post/page if no excerpt or more tag is available.

To specify the number of characters (default is 50), copy the following line to wp-config.php: 
    define('SCOPER&#95;TEASER&#95;NUM&#95;CHARS', 100); // set to any number of your choice