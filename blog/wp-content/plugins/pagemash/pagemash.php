<?php
/*
Plugin Name: pageMash
Plugin URI: http://joelstarnes.co.uk/pagemash/
Description: Manage your multitude of pages with pageMash's slick drag-and-drop style, ajax interface. Allows quick sorting, hiding and organising of parenting.
Author: Joel Starnes
Version: 1.3.0
Author URI: http://joelstarnes.co.uk/
	
*/
#########CONFIG OPTIONS############################################
$minlevel = 6;  /*[deafult=7]*/
/* Minimum user level to access page order */

$excludePagesFeature = true;  /*[deafult=true]*/
/* Allows you to set pages not to be listed */

$renamePagesFeature = true;  /*[deafult=true]*/
/* Lets you rename pages */

$CollapsePagesOnLoad = false;  /*[deafult=true]*/
/* Collapse all parent pages on load */

$ShowDegubInfo = false;  /*[deafult=false]*/
/* Show server response debug info */
###################################################################
/*
INSPIRATIONS/CREDITS:
Valerio Proietti - Mootools JS Framework [http://mootools.net/]
Stefan Lange-Hegermann - Mootools AJAX timeout class extension [http://www.blackmac.de/archives/44-Mootools-AJAX-timeout.html]
vladimir - Mootools Sortables class extension [http://vladimir.akilles.cl/scripts/sortables/]
ShiftThis - WP Page Order Plugin [http://www.shiftthis.net/wordpress-order-pages-plugin/]
Garrett Murphey - Page Link Manager [http://gmurphey.com/2006/10/05/wordpress-plugin-page-link-manager/]
*/

/*  Copyright 2008  Joel Starnes  (email : joel@joelstarnes.co.uk)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Pre-2.6 compatibility
if ( !defined('WP_CONTENT_URL') )
	define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if ( !defined('WP_CONTENT_DIR') )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
// Guess the location
$pageMash_path = WP_CONTENT_DIR.'/plugins/'.plugin_basename(dirname(__FILE__));
$pageMash_url = WP_CONTENT_URL.'/plugins/'.plugin_basename(dirname(__FILE__));

// load localisation files
load_plugin_textdomain('pmash','wp-content/plugins/pagemash/');

function pageMash_getPages($post_parent){
	//this is a recurrsive function which calls itself to produce a nested list of elements
	//$post_parent should be 0 for root pages, or contain a pageID to return it's sub-pages
	global $wpdb, $wp_version, $excludePagesFeature, $excludePagesList, $renamePagesFeature;
	if($wp_version >= 2.1){ //get pages from database
		$pageposts = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_type = 'page' AND post_parent = '$post_parent' ORDER BY menu_order");
	}else{
		$pageposts = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_status = 'static' AND post_parent = '$post_parent' ORDER BY menu_order");
	}
	
	if ($pageposts == true){ //if $pageposts == true then it does have sub-page(s), so list them.
		echo (0 === $post_parent) ? '<ul id="pageMash_pages">' : '<ul>'; //add this ID only to root 'ul' element
		foreach ($pageposts as $page): //list pages, [the 'li' ID must be pm_'page ID'] ?>
			<?php $status = $page->post_status; ?>
			<li id="pm_<?php echo $page->ID; ?>"<?php if(get_option('exclude_pages')){ if(in_array($page->ID, $excludePagesList)) echo ' class="remove"'; }//if page is in exclude list, add class remove ?>>
				<span class="title"><?php echo $page->post_title;?></span>
				<?php if ($status == 'draft' || $status == 'pending') { print ' <span class="pm_status">('.__($status).')</span>'; } ?>
				<span class="pageMash_box">
					<span class="pageMash_more">&raquo;</span>
					<span class="pageMash_pageFunctions">
						id:<?php echo $page->ID;?>
						[<a href="<?php echo get_bloginfo('wpurl').'/wp-admin/post.php?action=edit&post='.$page->ID; ?>" title="<?php _e('Edit This Page'); ?>"><?php _e('edit'); ?></a>]
						<?php if($excludePagesFeature): ?>
							[<a href="#" title="<?php _e('Show|Hide'); ?>" class="excludeLink" onclick="toggleRemove(this); return false"><?php _e('hide') ?></a>]
						<?php endif; ?>
						<?php if($renamePagesFeature): ?>
							[<a href="#" title="<?php _e('Rename Page'); ?>" class="rename"><?php _e('Rename'); ?></a>]
						<?php endif; ?>
					</span>
				</span>
				<?php pageMash_getPages($page->ID)  //call this function to list any sub-pages (passing it the pageID) ?>
			</li>
		<?php endforeach;
		echo '</ul>';
		return true;
	} else {
		return false;
	}
}

function pageMash_main(){
	global $excludePagesFeature, $excludePagesList, $ShowDegubInfo;
	if(!is_array(get_option('exclude_pages'))) $excludePagesList=array(); else $excludePagesList = get_option('exclude_pages'); //if it's empty set as an empty array
	?>
	<div id="debug_list"<?php if(false==$ShowDegubInfo) echo' style="display:none;"'; ?>></div>
	<div id="pageMash" class="wrap">
		<div id="pageMash_checkVersion" style="float:right; font-size:.7em; margin-top:5px;">
		    version [1.3.0]
		</div>
		<h2 style="margin-bottom:0; clear:none;"><?php _e('pageMash - pageManagement     ','pmash');?></h2>
		<p style="margin-top:4px;">
			<?php _e('Just drag the pages <strong>up</strong> or <strong>down</strong> to change the page order and <strong>left</strong> or <strong>right</strong> to change the page`s parent, then hit "update".     ','pmash');?> <br />
			<?php _e('The icon to the left of each page shows if it has child pages, <strong>double click</strong> on that item to toggle <strong>expand|collapse</strong> of it`s children.     ','pmash');?> <br />           
		</p>
		<p><a href="#" id="expand_all"><?php _e('Expand All','pmash');?></a> | <a href="#" id="collapse_all"><?php _e('Collapse All     ','pmash');?></a></p>
		
		<?php pageMash_getPages(0); //pass 0, as initial parent ?>
		
		<p class="submit">
			<div id="update_status" style="float:left; margin-left:40px; opacity:0;"></div>
				<input type="submit" id="pageMash_submit" tabindex="2" style="font-weight: bold; float:right;" value=<?php _e('Update        ','pmash');?> name="submit"/>
		</p>
		<br style="margin-bottom: .8em;" />
	</div>

	<div class="wrap" style="width:160px; margin-bottom:0; padding:0;"><p><a href="#" id="pageMashInfo_toggle">Show|Hide Further Info</a></p></div>
	<div class="wrap" id="pageMashInfo" style="margin-top:-1px;">
		<h2><?php _e('How to Use     ','pmash');?></h2>
		<p><?php _e('pageMash works with the wp_list_pages function. The easiest way to use it is to put the pages widget in your sidebar [WP admin page > Appeaarance > Widgets]. Click the configure button on the widget and ensure that \'sort by\' is set to \'page order\'. Hey presto, you\'re done.     ','pmash');?></p>
		<p><?php _e('You can also use the function anywhere in your theme code. e.g. in your sidebar.php file (but the code in here will not run if you\'re using any widgets) or your header.php file (somewhere under the body tag, you may want to use the depth=1 parameter to only show top level pages). The code should look something like the following:','pmash');?></p>
		<p style="margin-bottom:0; font-weight:bold;"><?php _e('Code:','pmash');?></p>
		<code id="pageMash_code">
			<span class="white">&lt;?php</span> <span class="blue">wp_list_pages(</span><span class="orange">'title_li=&lt;h2&gt;Pages&lt;/h2&gt;&amp;depth=0'</span><span class="blue">);</span> <span class="white">?&gt;</span>
		</code>
		<p><?php _e('You can also hard-code pages to exclude and these will be merged with the pages you set to exclude in your pageMash admin.','pmash');?></p>
		<p><?php _e('The code here is very simple and flexible, for more information look up <a href="http://codex.wordpress.org/Template_Tags/wp_list_pages" title="wp_list_pages Documentation">wp_list_pages() in the Wordpress Codex</a> as it is very well documented and if you have any further questions or feedback I like getting messages, so <a href="http://joelstarnes.co.uk/contact/" title="email Joel Starnes">drop me an email</a>.','pmash');?></p>
		<br />
	</div>
	<?php
}

function pageMash_head(){
	//stylesheet & javascript to go in page header
	global $pageMash_url, $CollapsePagesOnLoad;
	
	wp_deregister_script('prototype');//remove prototype since it is incompatible with mootools
	wp_enqueue_script('pagemash_mootools', $pageMash_url.'/nest-mootools.v1.11.js', false, false); //code is not compatible with other releases of moo
	wp_enqueue_script('pagemash_nested', $pageMash_url.'/nested.js', array('pagemash_mootools'), false);
	wp_enqueue_script('pagemash_inline_edit', $pageMash_url.'/inline-edit.v1.2.js', array('pagemash_mootools'), false);
	wp_enqueue_script('pagemash', $pageMash_url.'/pagemash.js', array('pagemash_mootools'), false);
	add_action('admin_head', 'pageMash_add_css', 1);
}

function pageMash_add_css(){
	global $pageMash_url, $CollapsePagesOnLoad;
	?>
	<script type="text/javascript" charset="utf-8">
		<?php if($CollapsePagesOnLoad): ?>
			window.addEvent('domready', function(){ 
				$ES('li','pageMash_pages').each(function(el) {
					if(el.hasClass('children')) el.addClass('collapsed');
				});
			});
		<?php endif; ?>
		window.pmash = {
			"update": "<?php _e('Database Updated') ?>",
			"showInfo": "<?php _e('Show Further Info') ?>",
			"hideInfo": "<?php _e('Hide Further Info') ?>"
		}
	</script>
	<?php
	printf('<link rel="stylesheet" type="text/css" href="%s/pagemash.css" />', $pageMash_url);
	?>
<!--                    __  __           _     
      WordPress Plugin |  \/  |         | |    
  _ __  __ _  __ _  ___| \  / | __ _ ___| |__  
 | '_ \/ _` |/ _` |/ _ \ |\/| |/ _` / __| '_ \ 
 | |_)  (_| | (_| |  __/ |  | | (_| \__ \ | | |
 | .__/\__,_|\__, |\___|_|  |_|\__,_|___/_| |_|
 | |          __/ |  Author: Joel Starnes
 |_|         |___/   URL: pagemash.joelstarnes.co.uk
 
 >>pageMash Admin Page
-->
	<?php
}

function pageMash_add_excludes($excludes){
	//merge array of hardcoded exclude pages with pageMash ones
	if(is_array(get_option('exclude_pages'))){
		$excludes = array_merge( get_option('exclude_pages'), $excludes );
	}
	sort($excludes);
	return $excludes;
}

function pageMash_add_pages(){
	//add menu link
	global $minlevel, $wp_version;
	if($wp_version >= 2.7){
		$page = add_submenu_page('edit-pages.php', 'pageMash: Page Management', __('pageMash          ','pmash'), $minlevel,  __FILE__, 'pageMash_main'); 
	}else{
		$page = add_management_page('pageMash: Page Management', 'pageMash', $minlevel, __FILE__, 'pageMash_main');
	}
	add_action("admin_print_scripts-$page", 'pageMash_head'); //add css styles and JS code to head
}

add_action('admin_menu', 'pageMash_add_pages'); //add admin menu under management tab
add_filter('wp_list_pages_excludes', 'pageMash_add_excludes'); //add exclude pages to wp_list_pages funct


?>