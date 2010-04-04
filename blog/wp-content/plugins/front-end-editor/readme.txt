=== Front-end Editor ===
Contributors: scribu
Donate link: http://scribu.net/paypal
Tags: inline, editor, edit-in-place, visual, wysiwyg
Requires at least: 2.8
Tested up to: 3.0
Stable tag: trunk

Want to edit something? Just double-click it!

== Description ==

Front-end Editor is a plugin that lets you make changes to your content *directly* from your site. No need to load the admin backend just to correct a typo.

To edit something, just double-click it!

The main goals are to be as *fast* as possible and to be *compatible with any theme*.

**Editable fields:**

<ul>
	<li><strong>posts & pages</strong>
	<ul>
		<li>title</li>
		<li>content</li>
		<li>excerpt</li>
		<li>categories</li>
		<li>tags</li>
		<li>custom taxonomies</li>
		<li>custom fields</li>
		<li>thumbnail</li>
	</ul></li>
	<li><strong>comments</strong>
	<ul>
		<li>content</li>
	</ul></li>
	<li><strong>authors</strong>
	<ul>
		<li>description</li>
	</ul></li>
	<li><strong>widgets</strong>
	<ul>
		<li>title</li>
		<li>text widget content</li>
	</ul></li>
	<li><strong>titles</strong>
	<ul>
		<li>category</li>
		<li>tag</li>
	</ul></li>
	<li><strong>site info</strong>
	<ul>
		<li>title</li>
		<li>description</li>
		<li>options</li>
	</ul></li>
	<li><strong>theme images</strong>
</ul>

There is a settings page where you can disable editable fields that you don't want.

**Translations:**

* Danish - [Georg](http://wordpress.blogos.dk/)
* Dutch - [Ron Hartman](http://www.fr-fanatic.com/)
* French - [Li-An](http://www.li-an.fr)
* Italian - [Gianni Diurno](http://gidibao.net)
* Georgian - Levani Melikishvili
* German - Gottfried
* Japaneze - kzh
* Norwegian - John Myrstad
* Polish - [Expromo](http://expromo.pl)
* Portuguese - [Fernanda Foertter](http://www.hpcprogrammer.com)
* Belarusian - [M. Comfi](http://www.comfi.com)
* Russian - BoreS
* Spanish - [Esteban](http://netmdp.com/)
* Swedish - [Müfit Kiper](http://kitkonsult.se/)
* Turkish - [Burak Gulbahce](http://www.saylangoz.com/wordpress/)

If you want to translate this plugin, please read [this](http://scribu.net/wordpress/translating-plugins.html).


== Installation ==

You can either install it automatically from the WordPress admin, or do it manually:

1. Unzip "Front-end Editor" archive and put the folder into your plugins folder (/wp-content/plugins/).
1. Activate the plugin from the Plugins menu.

== Frequently Asked Questions ==

= Error on activation: "Parse error: syntax error, unexpected T_CLASS..." =

Make sure your host is running PHP 5. Add this line to wp-config.php to check:

`var_dump(PHP_VERSION);`

= Why doesn't it work with my theme? =

See [Common Mistakes in Themes](http://scribu.net/wordpress/front-end-editor/common-mistakes-in-themes.html).

= Does it work with WP Super Cache? =

To avoid problems with WP Super Cache or W3 Total Cache, you have to disable caching for logged-in users.

= If I use this plugin, won't everybody be able to edit my content? =

No. To edit a field, a user must be logged in and have the right permissions. For example, to edit the post content from the front-end, a user must be able to edit the post content from the regular back-end editor.

= How can I change the hover color? =

You can add this line to *style.css* in your theme directory:

`.fee-field:hover, .fee-field:hover * {background-color: mycolor !important}`

where *mycolor* is one of these values: [CSS colors](http://www.w3schools.com/CSS/css_colors.asp).

= How can I edit custom fields? =

Since custom fields can be used in so many ways, you have to make some code replacements in your theme:

Replace something like this:

`<?php echo get_post_meta($post->ID, 'my_key', true); ?>`

with this:

`<?php editable_post_meta(get_the_ID(), 'my_key', 'textarea'); ?>`

The third parameter is optional and allows you to pick which type of field you want: *input*, *textarea* or *rich*.

If you have a custom field with multiple values, you can use `get_editable_post_meta()`. For example:

`
<ul>
<?php
$values = get_editable_post_meta(get_the_ID(), 'my_key');
foreach ( $values as $value )
	echo '<li>' . $value . '</li>';
?>
</ul>
`

= How can I make theme images editable? =

Again, you have to modify your theme's code. Replace something like this:

`<img src="<?php bloginfo('template_url'); ?>/images/header_1.jpg" width="970" height="140" alt="<?php bloginfo('name'); ?> header image 1" title="<?php bloginfo('name'); ?> header image 1" />`

with this:

`<?php editable_image('header-1', 
	get_bloginfo('template_url') . '/images/header_1.jpg', 
	array('width' => 970, 'height' => 140, 'alt' => get_bloginfo('name'))); 
?>`

The editable_image() template tag is located in fields/other.php.

= Can I make my own editable fields? =

Yes, but you have to know your way around WordPress' internals. Here is the [developer guide](http://scribu.net/wordpress/front-end-editor/developer-guide.html) to get you started.

== Screenshots ==

1. The tooltip
2. Editing the post content
3. Editing the post title
4. Changing a theme image
5. The settings page

== Changelog ==

= 1.8 =
* added tooltip
* restyled buttons
* fixed widget editing
* exposed JavaScript field types
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-8.html)

= 1.7.2 =
* fixed narrow textarea problem
* fixed IE8 error
* included nicEdit upload plugin

= 1.7.1 =
* made date reset optional
* better lightbox detection

= 1.7 =
* new editable fields: post thumbnails & arbitrary options
* added font-family and font-color buttons to nicEdit
* made the nicEdit configuration filterable
* dropped Growfield from textareas
* load CSS only when needed
* standardized CSS ids and classes
* renamed hooks from 'front_ed_*' to 'front_end_editor_*'
* fixed: when editing the post content, the post date isn't updated
* fixed: when editing tags, the input bounces to a new line
* fixed: after editing linked post title, the title is not linked anymore
* fixed: editable_post_meta() doesn't work outside The Loop
* fixed: warning when a NULL is passed to FEE_Field_Base::wrap()
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-7.html)

= 1.6.1 =
* fixed escaping issues

= 1.6 =
* new editable field: post categories
* added editing of custom fields with multiple values
* added editing of any widget title
* improved script loading
* added placeholder to editable_post_meta
* fixed issue with comment paragraphs
* fixed issues with the $post global
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-6.html)

= 1.5.1 =
* fixed auto-upgrade error
* added German translation

= 1.5 =
* new editable field: theme images
* switched to NicEdit
* don't remove blockquotes when editing a single paragraph
* better handling of text widgets
* compress JS & CSS
* compatibility with Ajaxed WordPress plugin
* added ES translation
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-5.html)

= 1.4 =
* new editable fields: category title and tag title
* added $echo parameter to editable_post_meta()
* easier way to restrict editable content
* don't load CSS & JS if the current user can't edit any of the fields
* switched from Autogrow to Growfield (fixes IE compatibility)
* added Georgian translation
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-4.html)

= 1.3.3 =
* fixed duplicate header error

= 1.3.2 =
* site title bugfix

= 1.3.1 =
* settings page bugfix
* updated translations

= 1.3 =
* new editable fields: site title & site description
* the rich editor respects .alignleft etc.
* ability to add extra css to the rich editor via front-end-editor.css
* added Polish translation
* use id="" instead of rel=""
* postThumbs compatibility
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-3.html)

= 1.2.1 =
* widget bugfix

= 1.2 =
* made author description editable
* yellow background while hovering over editable field
* experimental wysiwyg autogrow
* hopefully valid xHTML
* HTML code is cleaned up before saving
* added Portuguese translation
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-2.html)

= 1.1.4 =
* fix white screen error for non-admins

= 1.1.3 =
* css bugfix
* added Belarusian translation
* updated italian translation

= 1.1 =
* new editable field: post terms
* usability improvements
* added Turkish translation
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-1.html)

= 1.0.6 =
* fixed links with target="_blank"
* inputs and textareas are focused after double-clicking
* added Russian translation

= 1.0.5 =
* added align buttons, fixed autogrow issue
* the_title and the_tags improvements
* added Swedish translation

= 1.0 =
* single paragraph editing
* [more info](http://scribu.net/wordpress/front-end-editor/fee-1-0.html)

= 0.9 =
* new editable field: post custom fields
* [more info](http://scribu.net/wordpress/front-end-editor/fee-0-9.html)

= 0.8 =
* rich text editor (jWYSIWYG)
* l10n
* [more info](http://scribu.net/wordpress/front-end-editor/fee-0-8.html)

= 0.7 =
* settings page
* [more info](http://scribu.net/wordpress/front-end-editor/fee-0-7.html)

= 0.6 =
* new editable field: post tags
* [more info](http://scribu.net/wordpress/front-end-editor/fee-0-6.html)

= 0.5 =
* initial release
* [more info](http://scribu.net/wordpress/front-end-editor/fee-0-5.html)

