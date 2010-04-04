<?php
/*
Plugin Name: Front-end Editor
Version: 1.8
Description: Allows you to edit your posts without going through the admin interface
Author: scribu
Author URI: http://scribu.net/
Plugin URI: http://scribu.net/wordpress/front-end-editor
Text Domain: front-end-editor
Domain Path: /lang

Copyright (C) 2010 scribu.net (scribu@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

// Load scbFramework
require dirname(__FILE__) . '/scb/load.php';

function _fee_init() {
	$dir = dirname(__FILE__);

	// Load translations
	load_plugin_textdomain('front-end-editor', '', basename($dir) . '/lang');

	// Load files
	require_once $dir . '/core.php';

	foreach ( array('base', 'post', 'other') as $name )
		require_once "$dir/fields/$name.php";

	// Load options
	$options = new scbOptions('front-end-editor', __FILE__, array(
		'disabled' => array(),
		'highlight' => true,
		'tooltip' => true,
		'rich' => true,
		'chunks' => false,
		'reset_date' => false
	));

	FEE_Core::init($options, '1.8');
	FEE_Field_Image::init(__FILE__);

	if ( is_admin() ) {
		require_once $dir . '/admin.php';
		scbAdminPage::register('Fee_Admin', __FILE__, $options);
	}
}
_fee_init();

function fee_register_defaults() {
	$fields = array(
		'the_title' => array(
			'title' => __('Post title', 'front-end-editor'),
			'class' => 'FEE_Field_Post',
			'type' => 'input',
		),

		'the_content' => array(
			'title' => __('Post content', 'front-end-editor'),
			'class' => FEE_Core::$options->chunks ? 'FEE_Field_Chunks' : 'FEE_Field_Post',
			'type' => FEE_Core::$options->rich ? 'rich' : 'textarea',
		),

		'the_excerpt' => array(
			'title' => __('Post excerpt', 'front-end-editor'),
			'class' => 'FEE_Field_Excerpt',
			'type' => 'textarea',
		),

		'the_category' => array(
			'title' => __('Post categories', 'front-end-editor'),
			'class' => 'FEE_Field_Category',
			'argc' => 3,
			'type' => 'terminput',
		),

		'the_tags' => array(
			'title' => __('Post tags', 'front-end-editor'),
			'class' => 'FEE_Field_Tags',
			'argc' => 4,
			'type' => 'terminput',
		),

		'the_terms' => array(
			'title' => __('Post terms', 'front-end-editor'),
			'class' => 'FEE_Field_Terms',
			'argc' => 5,
			'type' => 'terminput',
		),

		'post_meta' => array(
			'title' => __('Post custom fields', 'front-end-editor'),
			'class' => 'FEE_Field_Meta',
			'argc' => 5,
		),

		'post_thumbnail_html' => array(
			'title' => __('Post thumbnail', 'front-end-editor'),
			'class' => 'FEE_Field_Thumbnail',
			'argc' => 4,
			'type' => 'thumbnail',
		),

		'comment_text' => array(
			'title' => __('Comment text', 'front-end-editor'),
			'class' => 'FEE_Field_Comment',
			'type' => 'textarea',
		),

		'single_cat_title' => array(
			'title' => __('Category title', 'front-end-editor'),
			'class' => 'FEE_Field_Single_Title',
		),

		'single_tag_title' => array(
			'title' => __('Tag title', 'front-end-editor'),
			'class' => 'FEE_Field_Single_Title',
		),

		'the_author_description' => array(
			'title' => __('Author description', 'front-end-editor'),
			'class' => 'FEE_Field_Author_Desc',
			'type' => 'textarea',
			'argc' => 2,
		),

		'widget_title' => array(
			'title' => __('Widget title', 'front-end-editor'),
			'class' => 'FEE_Field_Widget',
		),

		'widget_text' => array(
			'title' => __('Text widget content', 'front-end-editor'),
			'class' => 'FEE_Field_Widget',
			'type' => FEE_Core::$options->rich ? 'rich' : 'textarea',
		),

		'bloginfo' => array(
			'title' => __('Site title and description', 'front-end-editor'),
			'class' => 'FEE_Field_Bloginfo',
			'argc' => 2,
		),

		'editable_option' => array(
			'title' => __('Site options', 'front-end-editor'),
			'class' => 'FEE_Field_Option',
			'argc' => 3,
		),

		'editable_image' => array(
			'title' => __('Theme images', 'front-end-editor'),
			'class' => 'FEE_Field_Image',
			'type' => 'image',
			'argc' => 2,
		),
	);

	foreach ( $fields as $filter => $args )
		register_fronted_field($filter, $args);

	// Safe hook for new editable fields to be registered
	do_action('front_end_editor_fields');
}
add_action('init', 'fee_register_defaults');

