=== Similar Posts ===
Contributors: RobMarsh
Donate link: http://rmarsh.com/donate/similar-posts/
Tags: posts, related, similar, related posts, similar posts, tags, post-plugins
Requires at least: 1.5
Tested up to: 2.6.2
Stable tag: 2.6.2.0
Displays a list of posts similar to the current one based on content, title and/or tags.

== Description ==

Similar Posts displays a list of posts that are similar or related to the current posts. The list can be customised in *many* ways. Similarity is judged according to a post's title, content, and tags and you can adjust the balance of factors to fit your own blog.

This plugin **requires** the latest version of the *Post-Plugin Library:* [download it now](http://downloads.wordpress.org/plugin/post-plugin-library.zip).

== Installation ==

1. IMPORTANT! If you are upgrading from a previous version first deactivate the plugin, then delete the plugin folder from your server.

1. If you have the *Similar Posts Feed* plugin installed you must deactivate it before installing Similar Posts (which now does the same job).

1. Upload the plugin folder to your /wp-content/plugins/ folder. If you haven't already you should also install the [Post-Plugin Library](http://wordpress.org/extend/plugins/post-plugin-library/)></a>.

1. Go to the **Plugins** page and activate the plugin.

1. Put `<?php similar_posts(); ?>` at the place in your template where you want the list of related posts to appear or use the plugin as a widget.

1. Use the **Options/Settings** page to adjust the behaviour of the plugin.

[My web site](http://rmarsh.com/) has [full instructions](http://rmarsh.com/plugins/similar-posts/) and [information on customisation](http://rmarsh.com/plugins/post-options/).

== Version History ==

* 2.6.2.0
	* fixed a problem with the stemming algorithm and overused words
	* introduced a first stab at fuzzy matching
	* new {imagealt} output tag -- rather like {imagesrc}
	* {excerpt} can now trim to whole sentences
	* content filter can now take parameter string
	* widget can now take parameter string
	* output can be appended to posts & feeds
* 2.6.1.3
	* fix - german language stemmer was crashing if mb_string fucntions not available
* 2.6.1.2
	* fix - german language stemmer file now in utf8
* 2.6.1.1
	* fix to italian language stemmer for PHP4
* 2.6.1.0
	* the current post can be marked manually
	* widgets now honour the option to show no output if list is empty
	* fixed a bug with finding the right language files
* 2.6.0.1
	* bug fix: installation code was failing on some systems
* 2.6.0.0
	* version bump to indicate compatibility with WP 2.6
	* fix to really include attachments
	* new parameter for {imagesrc} to append a suffix to the image name, e.g. to get the thumbnail for attachments
* 2.5.0.11
	* new option to include attachments
	* {php} tag now accepts nested tags
	* new output tag {authorurl} -- permalink to archive of author's posts
	* fix for numeric locale issue
* 2.5.0.10
	* new option to select algorithm for term extraction
	* new manual links option
	* fix for page selection in old versions of WP
	* fix for faulty tags in Cyrillic
* 2.5.0.9
	* new option to match the current post's author
	* extended options for snippet and excerpt output tags
* 2.5.0.7
	* new option to show by status, i.e., published/private/draft/future
	* {categorynames} and {categorylinks} apply 'single_cat_name' filter
	* fixes bug in WP pre-2.2 causing installation to fail
* 2.5.0
	* CJK digrams
	* {image} has new post, link, and default parameters
	* new {imagesrc} tag
	* fix to empty category bug
	* excluded posts bug fix
	* fix for intermittent bug with 'omit current post' option
* 2.5b28
	* improvements to Similar Posts matching
	* experiment with Chinese/Korean/Japanese matching
* 2.5b27
	* fixed bug with bulk indexing of tags
* 2.5b26
	* reverted thumbnail serving (speed)
	* fix current post after extra query
* 2.5b25
	* option to sort output, group templates
	* removed 'trim_before' option added more logical 'divider'
	* {date:raw}, {commentdate:raw}, etc.
	* fix for {image} resizing when <img > and not <img />
	* {image} now serves real thumbnails
* 2.5b24
	* fix for recursive replacement by content filter
	* fix to {gravatar} to allow for 'identicon' etc.
	* fix to {commenter} to allow trimming
	* fix a warning in safe mode
	* fix for unsanitised WP tags
* 2.5b23
	* new option to filter on custom fields
	* nested braces in {if}; condition now taggable
	* improved bug report feature
	* better way to omit user comments
* 2.5b22
	* restored automatic indexing on installation
	* moved indexing menu under settings
	* show_pages option can now show only pages
	* fix for upgraders who had utf8 selected but no mbstring
* 2.5b20
	* optimised indexing for speed and memory use
* 2.5b19
	* fixing some extended character issues
* 2.5b18
	* fix output filter bug
	* add conditional tag {if:condition:yes:no}
* 2.5b16
	* fix for {php}
* 2.5b15
	* fix more or less obscure bugs, add 'include posts' setting
* 2.5b14
	* fix file-encoding, installation error, etc.
* 2.5b12
	* fix serious bug for WP < 2.3
* 2.5b11
	* some widget fixes
* 2.5b10
	* fix for non-creation of table
* 2.5b9
	* clarifying installation instructions

* [previous versions](http://rmarsh.com/plugins/similar-posts/)
