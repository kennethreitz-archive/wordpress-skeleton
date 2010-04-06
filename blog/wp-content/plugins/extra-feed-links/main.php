<?php
/*
Plugin Name: Extra Feed Links
Version: 1.1.5.1
Description: Adds extra feed auto-discovery links to various page types (categories, tags, search results etc.).
Author: scribu
Author URI: http://scribu.net/
Plugin URI: http://scribu.net/wordpress/extra-feed-links

Copyright (C) 2009 scribu.net (scribu AT gmail DOT com)

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

class extraFeedLink {
	var $format;
	var $format_name;
	var $url;
	var $title;
	var $text;

	function __construct() {
		$this->format = $GLOBALS['EFL_options']->get();
		add_action('wp_head', array($this, 'head_link'));
	}

	function head_link() {
		$this->generate();

		if( !$this->url || !$this->text )
			return;

		echo "\n" . '<link rel="alternate" type="application/rss+xml" title="' . $this->text . '" href="' . $this->url . '" />' . "\n";
	}

	function theme_link($input) {
		$this->generate(TRUE);

		if( !$this->url )
			return;

		if ( substr($input, 0, 4) == 'http' )
			echo '<a href="' . $this->url . '" title="' . $this->text . '"><img src="' . $input . '" alt="rss icon" /></a>';
		elseif ( $input == '' )
			echo '<a href="' . $this->url . '" title="' . $this->text . '">' . $this->text . '</a>';
		elseif ( $input == 'raw' )
			echo $this->url;
		else
			echo '<a href="' . $this->url . '" title="' . $this->text . '">' . $input . '</a>';
	}

	function generate($for_theme = FALSE) {
		$this->title = $this->url = NULL;

		if ( is_home() && ($this->format['home'][0] || $for_theme) ) {
			$this->url = get_bloginfo('comments_rss2_url');
			$this->format_name = 'home';
		}
		elseif ( (is_single() || is_page()) && ($this->format['comments'][0] || $for_theme) ) {
			global $post;
			if ( $post->comment_status == 'open' ) {
				$this->url = get_post_comments_feed_link($post->ID);
				$this->title = $post->post_title;
				$this->format_name = 'comments';
			}
		}
		elseif ( is_category() && ($this->format['category'][0] || $for_theme) ) {
			global $wp_query;
			$cat_obj = $wp_query->get_queried_object();

			$this->url = get_category_feed_link($cat_obj->term_id);
			$this->title = $cat_obj->name;
			$this->format_name = 'category';
		}
		elseif ( is_tag() && ($this->format['tag'][0] || $for_theme) ) {
			global $wp_query;
			$tag_obj = $wp_query->get_queried_object();

			$this->url = get_tag_feed_link($tag_obj->term_id);
			$this->title = $tag_obj->name;
			$this->format_name = 'tag';
		}
		elseif ( is_author() && ($this->format['author'][0] || $for_theme) ) {
			global $wp_query;
			$author_obj = $wp_query->get_queried_object();

			$this->url = get_author_feed_link($author_obj->ID);
			$this->title = $author_obj->user_nicename;
			$this->format_name = 'author';
		}
		elseif ( is_search() && ($this->format['search'][0] || $for_theme) ) {
			$search = attribute_escape(get_search_query());

			$this->url = get_search_feed_link($search);
			$this->title = $search;
			$this->format_name = 'search';
		}

		// Set the appropriate format
		$this->text = $this->format[$this->format_name][1];

		// Convert substitution tags
		$this->text = str_replace('%title%', $this->title, $this->text);
		$this->text = str_replace('%site_title%', get_option('blogname'), $this->text);
	}
}

// Init
function efl_init() {
	if ( !class_exists('scbOptions_06') )
		require_once(dirname(__FILE__) . '/inc/scbOptions.php');

	$GLOBALS['EFL_options'] = new scbOptions_06('efl-format');
	$GLOBALS['extraFeedLink'] = new extraFeedLink();

	if ( is_admin() ) {
		require_once (dirname(__FILE__) . '/admin.php');
		new extraFeedLinkAdmin(__FILE__);
	}
}

efl_init();

remove_action('wp_head', 'feed_links_extra', 3);

// Template tag
function extra_feed_link($input = '') {
	global $extraFeedLink;

	$extraFeedLink->theme_link($input);
}
