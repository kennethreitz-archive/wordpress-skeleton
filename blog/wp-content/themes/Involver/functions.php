<?php
/**
 * @package WordPress
 * @subpackage involver
 */

automatic_feed_links();

if ( function_exists('register_sidebar') )
register_sidebar(array('name'=>'sidebar',
'before_widget' => '<li id="%1$s" class="widget %2$s">',
'after_widget' => '</li>',
'before_title' => '<h2 class="widgettitle">',
'after_title' => '</h2>',
));



function new_excerpt_length($length) {
	return 30;
}
add_filter('excerpt_length', 'new_excerpt_length');

function new_excerpt_more($excerpt) {
	global $wp_query;
	$id = $wp_query->ID;
	$the_link = get_permalink($id);
	$more = '... <a href="'.$the_link.'">read more</a>';
	return str_replace('[...]', $more, $excerpt);
}
add_filter('wp_trim_excerpt', 'new_excerpt_more');


?>