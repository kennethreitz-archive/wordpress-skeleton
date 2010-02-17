<?php
if (!empty($_SERVER['SCRIPT_FILENAME']) && 'functions.php' == basename($_SERVER['SCRIPT_FILENAME']))
die ('Please do not load this page directly. Thanks!');

$themename = "LightWord";
$themeversion = "1.9.3";
$shortname = "lw";
$top_header_image_path = get_bloginfo('template_directory')."/images/header-image.png";

if ( function_exists('register_sidebar') )
register_sidebar(array('name' =>'Sidebar','before_widget' => '','after_widget' => '','before_title' => '<h3>','after_title' => '</h3>'));

$options = array (

    array(	"name" => "Welcome",
			"type" => "title"),

	array(	"type" => "open"),

    array(  "name" => __('Layout settings', 'lightword'),
            "id" => $shortname."_layout_settings",
            "options" => array(__('Original','lightword'), __('Wider','lightword')),
            "std" => __('Original','lightword'),
            "type" => "select"),

    array(  "name" => __('Cufon settings', 'lightword'),
            "id" => $shortname."_cufon_settings",
            "options" => array(__('Enabled','lightword'), __('Disabled','lightword'), __('Extra','lightword')),
            "std" => __('Enabled','lightword'),
            "type" => "select"),

    array(  "name" => __('Disable comments on pages','lightword'),
			"desc" => __('Check this box if you would like to DISABLE COMMENTS on pages','lightword'),
            "id" => $shortname."_disable_comments",
            "type" => "checkbox",
            "std" => "false"),

    array(  "name" => __('Custom image header','lightword'),
			"desc" => __('Check this box if you would like to SHOW IMAGE instead Cufon text on header.<br/>Image location: <code>lightword/images/header-image.png</code> / Max width: <code>796px</code>','lightword'),
            "id" => $shortname."_top_header_image",
            "type" => "checkbox",
            "std" => "false"),

    array(  "name" => __('Header image height in pixels','lightword'),
			"desc" => '',
            "id" => $shortname."_top_header_image_height",
            "type" => "header_image",
            "std" => "56"),

    array(  "name" => __('About author feature','lightword'),
			"desc" => __('Add information about post author on post footer','lightword'),
            "id" => $shortname."_post_author",
            "type" => "checkbox",
            "std" => "false"),

    array(  "name" => __('Enjoy this post feature','lightword'),
			"desc" => __('Check this box if you would like to ACTIVATE <em>Enjoy this post</em> feature','lightword'),
            "id" => $shortname."_enjoy_post",
            "type" => "checkbox",
            "std" => "false"),

    array(  "name" => __('Show categories on front menu','lightword'),
			"desc" => __('Check this box if you would like to SHOW CATEGORIES instead pages on front menu','lightword'),
            "id" => $shortname."_show_categories",
            "type" => "checkbox",
            "std" => "false"),

    array(  "name" => __('Exclude pages from front menu','lightword'),
			"desc" => __('Type the pages id in the box below. Example input: <code>5,19,24</code>','lightword'),
            "id" => $shortname."_exclude_pages",
            "type" => "exclude_pages",
            "std" => ""),

    array(  "name" => __('Exclude categories from front menu','lightword'),
			"desc" => __('Type the categories id in the box below. Example input: <code>5,19,24</code>','lightword'),
            "id" => $shortname."_exclude_categories",
            "type" => "exclude_categories",
            "std" => ""),

    array(  "name" => __('Remove home button','lightword'),
			"desc" => __('Remove home button from front menu','lightword'),
            "id" => $shortname."_remove_homebtn",
            "type" => "checkbox",
            "std" => "false"),

    array(  "name" => __('Remove search box','lightword'),
			"desc" => __('Remove search box and expand space for front menu','lightword'),
            "id" => $shortname."_remove_searchbox",
            "type" => "checkbox",
            "std" => "false"),

    array(  "name" => __('Remove tags from posts','lightword'),
			"desc" => __('Show only categories in post footer','lightword'),
            "id" => $shortname."_disable_tags",
            "type" => "checkbox",
            "std" => "false"),

    array(  "name" => __('Remove RSS badge','lightword'),
			"desc" => __('Remove RSS badge from blog header','lightword'),
            "id" => $shortname."_remove_rss",
            "type" => "checkbox",
            "std" => "false"),

    array(  "name" => 'Google Custom Search Engine',
			"desc" => __('Find <code>name="cx"</code> in the <strong>Search box code</strong> of Google CSE, and type the <code>value</code> here.','lightword'),
            "id" => $shortname."_google_search_code",
            "type" => "text",
            "std" => ""),

    array(  "name" => __('Sidebox settings', 'lightword'),
            "id" => $shortname."_sidebox_settings",
            "options" => array(__('Enabled','lightword'), __('Disabled','lightword'), __('Show only date','lightword'), __('Show only in posts','lightword'), __('Last two options together','lightword')),
            "std" => __('Enabled','lightword'),
            "type" => "select"),


    array(  "name" => 'Custom CSS',
			"desc" => __('Put your custom css code here','lightword'),
            "id" => $shortname."_custom_css",
            "type" => "textarea",
            "std" => ""),

	array(	"type" => "close")


);

// ADMIN PAGE FUNCTIONS

function lightword_admin() {
global $themename, $shortname, $options;

if ( $_GET['page'] == basename(__FILE__) ) {
if ( 'save' == $_REQUEST['action'] ) {

foreach ($options as $value) {
update_option( $value['id'], $_REQUEST[ $value['id'] ] ); }

foreach ($options as $value) {
if( isset( $_REQUEST[ $value['id'] ] ) ) { update_option( $value['id'], $_REQUEST[ $value['id'] ]  ); } else { delete_option( $value['id'] ); } }
header("Location: themes.php?page=functions.php&saved=true");
die;

} else if( 'reset' == $_REQUEST['action'] ) {
foreach ($options as $value) {
delete_option( $value['id'] ); }
header("Location: themes.php?page=functions.php&reset=true");
die;
}
}
add_theme_page("LightWord Settings", __('LightWord Settings','lightword'), 'edit_themes', basename(__FILE__), 'lightword_admin_page');
}

// ADMIN PAGE LAYOUT

function lightword_admin_page() {
global $themename, $themeversion, $shortname, $options, $lw_top_header_image, $top_header_image_height, $lw_show_categories;
if ( $_REQUEST['saved'] ) { echo '<div id="message" class="updated fade"><p><strong>'.$themename.' '; _e('settings saved','lightword'); echo '.</strong></p></div>'; }
if ( $_REQUEST['reset'] ) { echo '<div id="message" class="updated fade"><p><strong>'.$themename.' '; _e('settings reset','lightword'); echo '.</strong></p></div>'; }
?>
<div class="wrap">

<h2><?php _e('LightWord Settings','lightword') ?></h2>

<div id="poststuff" class="metabox-holder">
<div class="stuffbox">
<h3><label for="link_url"><?php _e('Support the developer','lightword'); ?></label></h3>
<div class="inside">
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="5545477">
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
</div></div>

<div class="stuffbox">
<h3><label for="link_url"><?php _e('Theme version check','lightword'); ?> (<?php echo $themeversion; ?>)</label></h3>
<div class="inside">
<p>
<?php
$vcheck_url = "http://wp.kis.ro/lightword.txt";
define('REMOTE_VERSION', $vcheck_url);
$remoteVersion = trim(@file_get_contents(REMOTE_VERSION));
$remoteVersion = explode("||", $remoteVersion);
if (!@file_get_contents($vcheck_url)) {
_e('Version check failed.','lightword');
}else{
if(version_compare($themeversion, $remoteVersion[0], '>=')){ _e('Cool! You have the latest version.','lightword'); echo "<br/><a style=\"color:red;text-decoration:none;\" href=\"http://www.lightword-theme.com/lightword-theme/changelog\">&rarr; "; _e('view changelog','lightword'); echo "</a>";
}else{ _e('You have','lightword'); echo ": ".$themeversion."<br/>"; _e('Latest version','lightword'); echo ": <strong>".$remoteVersion[0]."</strong><br/><br/><strong>What's new? </strong><br/><small>".$remoteVersion[1]."</small><br/><a style=\"color:red;font-weight:700;\" href=\"http://wordpress.org/extend/themes/download/lightword.$remoteVersion[0].zip\">"; _e('Get the latest version','lightword'); echo "</a>"; echo " / <a style=\"color:red;font-weight:700;\" href=\"http://students.info.uaic.ro/~andrei.luca/blog/lightword-theme-updates.html#v$remoteVersion[0]\">"; _e('view changelog','lightword'); echo "</a>";}
}
?>
</p>
</div></div>

<div class="stuffbox">
<h3><label for="link_url"><?php _e('General settings','lightword'); ?></label></h3>
<div class="inside">
<form method="post">
<?php foreach ($options as $value) { switch ( $value['type'] ) { case "open": ?>
<table width="100%" border="0" style="padding:10px;">
<?php break; case "close": ?>
</table><br />
<?php break;case 'text':?>

<tr><td width="20%" rowspan="2" valign="middle"><strong style="font-size:11px;"><?php echo $value['name']; ?></strong></td>
<td width="80%"><input style="width:300px;" name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>" type="<?php echo $value['type']; ?>" value="<?php if ( get_settings( $value['id'] ) != "") { echo get_settings( $value['id'] ); } else { echo $value['std']; } ?>" /></td>
</tr><tr><td><small><?php echo $value['desc']; ?></small></td>
</tr><tr><td colspan="2" style="margin-bottom:5px;border-bottom:1px solid #E1E1E1;">&nbsp;</td></tr><tr><td colspan="2">&nbsp;</td></tr>

<?php break;case 'textarea':?>

<tr><td width="20%" rowspan="2" valign="middle"><strong><?php echo $value['name']; ?></strong></td>
<td width="80%"><textarea name="<?php echo $value['id']; ?>" style="width:400px; height:200px;" type="<?php echo $value['type']; ?>" cols="" rows=""><?php if ( get_settings( $value['id'] ) != "") { echo get_settings( $value['id'] ); } else { echo $value['std']; } ?></textarea></td></tr>
<tr><td><small><?php echo $value['desc']; ?></small></td>
</tr><tr></tr><tr><td colspan="2">&nbsp;</td></tr>

<?php break; case 'select': ?>
<tr>
<td width="20%" rowspan="2" valign="middle"><strong style="font-size:11px;"><?php _e("".$value['name']."","lightword"); ?></strong></td>
<td width="80%"><select style="width:200px;" name="<?php _e("".$value['id']."","lightword"); ?>" id="<?php echo $value['id']; ?>"><?php foreach ($value['options'] as $option) { ?><option<?php if ( get_option( $value['id'] ) == $option) { echo ' selected="selected"'; } elseif ($option == $value['std']) { echo ' selected="selected"'; } ?> value="<?php echo $option; ?>"><?php _e("".$option."","lightword"); ?></option><?php } ?></select></td>
</tr><tr><td><small><?php echo $value['desc']; ?></small></td>
</tr><tr><td colspan="2" style="margin-bottom:5px;border-bottom:1px solid #E1E1E1;">&nbsp;</td></tr><tr><td colspan="2">&nbsp;</td></tr>

<?php break; case 'header_image': ?>
<?php if($lw_top_header_image == "true") : ?>
<tr>
<td width="20%" rowspan="2" valign="middle"><strong style="font-size:11px;"><?php _e("".$value['name']."","lightword"); ?></strong></td>
<td width="80%"><input style="width:50px;" name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>" type="<?php echo $value['type']; ?>" value="<?php if ( get_option( $value['id'] ) != "") { echo get_option( $value['id'] ); } else { echo $value['std']; } ?>" /></td>
</tr><tr><td></td></tr><tr><td colspan="2" style="margin-bottom:5px;border-bottom:1px solid #E1E1E1;">&nbsp;</td></tr><tr><td colspan="2">&nbsp;</td></tr>
<?php endif; ?>

<?php break; case 'exclude_pages': ?>
<?php if($lw_show_categories == "false" || $lw_show_categories == "") : ?>
<tr>
<td width="20%" rowspan="2" valign="middle"><strong style="font-size:11px;"><?php _e("".$value['name']."","lightword"); ?></strong></td>
<td width="80%"><input style="width:300px;" name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>" type="text" value="<?php if ( get_option( $value['id'] ) != "") { echo get_option( $value['id'] ); } else { echo $value['std']; } ?>" /></td>
</tr><tr><td><small><?php _e("".$value['desc']."","lightword"); ?></small></td></tr><tr><td colspan="2" style="margin-bottom:5px;border-bottom:1px solid #E1E1E1;">&nbsp;</td></tr><tr><td colspan="2">&nbsp;</td></tr>
<?php endif; ?>

<?php break; case 'exclude_categories': ?>
<?php if($lw_show_categories == "true") : ?>
<tr>
<td width="20%" rowspan="2" valign="middle"><strong style="font-size:11px;"><?php _e("".$value['name']."","lightword"); ?></strong></td>
<td width="80%"><input style="width:300px;" name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>" type="text" value="<?php if ( get_option( $value['id'] ) != "") { echo get_option( $value['id'] ); } else { echo $value['std']; } ?>" /></td>
</tr><tr><td><small><?php _e("".$value['desc']."","lightword"); ?></small></td></tr><tr><td colspan="2" style="margin-bottom:5px;border-bottom:1px solid #E1E1E1;">&nbsp;</td></tr><tr><td colspan="2">&nbsp;</td></tr>
<?php endif; ?>

<?php break; case "checkbox": ?>
<tr>
<td width="25%" rowspan="2" valign="middle"><strong style="font-size:11px;"><?php _e("".$value['name']."","lightword"); ?></strong></td>
<td width="75%"><?php if(get_option($value['id'])){ $checked = "checked=\"checked\""; }else{ $checked = ""; } ?>
<input type="checkbox" name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>" value="true" <?php echo $checked; ?> />   <small><?php _e("".$value['desc']."","lightword"); ?></small>
</td></tr><tr></tr><tr><td colspan="2" style="margin-bottom:5px;border-bottom:1px solid #E1E1E1;">&nbsp;</td></tr><tr><td colspan="2">&nbsp;</td></tr>
<?php break; } } ?>
</div></div>
<p class="submit" style="margin-top:-2em;">
<input name="save" type="submit" value="<?php _e('Save changes','lightword'); ?>" class="button-primary" />
<input type="hidden" name="action" value="save" />
</p>
</form>

<div class="stuffbox">
<h3><label for="link_url"><?php _e('Search for help','lightword'); ?> (<a href="http://students.info.uaic.ro/~andrei.luca/blog/">blog</a> <?php _e('or','lightword'); ?> <a href="http://twitter.com/andreiluca">twitter</a>)</label></h3>
<div class="inside">
<?php
require_once(ABSPATH . WPINC . '/rss.php');
$rss_wp = fetch_rss('http://wordpress.org/support/rss/tags/lightword');
if ($rss_wp) {
$items_wp = array_slice($rss_wp->items, 0, 1);
foreach( $items_wp as $item_wp ) {
$pubdate = substr($item_wp['pubdate'], 0, 16);
$title = explode(' "',$item_wp['title']);
$title = strip_tags(str_replace('"','',$title[1]));
echo '<p><a href="'.$item_wp['link'].'" title="'.$title.'">'.$title.'</a> / <em>'.$pubdate.'</em></p>';
}
}else {
echo "<p>";
_e('No updates available.','lightword');
echo "</p>";
}
$rss_blog = fetch_rss('http://feeds2.feedburner.com/lightword');
if ($rss_blog) {
$items_blog = array_slice($rss_blog->items, 0, 4);
foreach( $items_blog as $item_blog ) {
$pubdate = substr($item_blog['pubdate'], 0, 16);
echo '<p><a href="'.$item_blog['guid'].'" title="'.$item_blog['title'].'">'.$item_blog['title'].'</a> / <em>'.$pubdate.'</em></p>';
}
}else {
echo "<p>";
_e('No updates available.','lightword');
echo "</p>";
}
?>
</div></div>

<div class="stuffbox">
<h3><label for="link_url"><?php _e('What is Cufon?','lightword'); ?> (<a href="http://cufon.shoqolate.com/generate/">website</a>)</label></h3>
<div class="inside">
<p>&sup1;Cuf&oacute;n is a Javascript Dynamic Text Replacement, like sIFR without flash plugin, just javascript.<br/>
<br/>&sup2;Extra Cuf&oacute;n contains (~<b>300kb js file</b>): Basic latin, uppercase, lowercase, numerals, punctuation, <br/>Latin-1 Supplement, Latin Extended-A, Cyrillic Alphabet, Russian Alphabet, Greek and Coptic; usefull for some accents and special characters.
<br/><br/>Korean characters are not supported (11000+ glyps is a bit too much - enormous file -> slow loading).</p>
</div></div>
<form method="post" style="float:right;">
<input name="reset" type="submit" value="<?php _e('Click here to reset all settings','lightword'); ?>" style="cursor:pointer;" />
<input type="hidden" name="action" value="reset" />
</form>
</div>
<?php
}

global $options;
foreach ($options as $value) {
if (get_option( $value['id'] ) === FALSE) { $$value['id'] = $value['std']; } else { $$value['id'] = get_option( $value['id'] ); }
}

/**
 * count for trackback, pingback, comment, pings
 *
 * embed like this:
 * fb_comment_type_count('pings');
 * fb_comment_type_count('comment');
 * http://code.google.com/p/wp-basis-theme/
 */

 function fb_get_comment_type_count( $type='all', $zero = false, $one = false, $more = false, $post_id = 0) {
                global $cjd_comment_count_cache, $id, $post;

                if ( !$post_id )
                        $post_id = $post->ID;
                if ( !$post_id )
                        return;

                if ( !isset($cjd_comment_count_cache[$post_id]) ) {
                        $p = get_post($post_id);
                        $p = array($p);
                        fb_update_comment_type_cache($p);
                }
                ;
                if ( $type == 'pingback' || $type == 'trackback' || $type == 'comment' )
                        $count = $cjd_comment_count_cache[$post_id][$type];
                elseif ( $type == 'pings' )
                        $count = $cjd_comment_count_cache[$post_id]['pingback'] + $cjd_comment_count_cache[$post_id]['trackback'];
                else
                        $count = array_sum((array) $cjd_comment_count_cache[$post_id]);

                return apply_filters('fb_get_comment_type_count', $count);
        }

if ( !function_exists('fb_update_comment_type_cache') ) {
        function fb_update_comment_type_cache($queried_posts) {
                global $cjd_comment_count_cache, $wpdb;

                if ( !$queried_posts )
                        return $queried_posts;

                foreach ( (array) $queried_posts as $post )
                        if ( !isset($cjd_comment_count_cache[$post->ID]) )
                                $post_id_list[] = $post->ID;

                if ( $post_id_list ) {
                        $post_id_list = implode(',', $post_id_list);

                        foreach ( array('', 'pingback', 'trackback') as $type ) {
                                $counts = $wpdb->get_results("SELECT ID, COUNT( comment_ID ) AS ccount
                                                        FROM $wpdb->posts
                                                        LEFT JOIN $wpdb->comments ON ( comment_post_ID = ID AND comment_approved = '1' AND comment_type='$type' )
                            WHERE (post_status = 'publish' OR (post_status = 'inherit' AND post_type = 'attachment')) AND ID IN ($post_id_list)
                                                        GROUP BY ID");

                                if ( $counts ) {
                                        if ( '' == $type )
                                                $type = 'comment';
                                        foreach ( $counts as $count )
                                                $cjd_comment_count_cache[$count->ID][$type] = $count->ccount;
                                }
                        }
                }

                return $queried_posts;
        }

        add_filter('the_posts', 'fb_update_comment_type_cache');
}

/**
 * Smart cache-busting
 * http://toscho.de/2008/frisches-layout/#comment-13
 */

if ( !function_exists('fb_css_cache_buster') ) {
        function fb_css_cache_buster($info, $show) {
                if ($show == 'stylesheet_url') {

                        // Is there already a querystring? If so, add to the end of that.
                        if (strpos($pieces[1], '?') === false) {
                                return $info . "?" . filemtime(WP_CONTENT_DIR . $pieces[1]);
                        } else {
                                $morsels = explode("?", $pieces[1]);
                                return $info . "&" . filemtime(WP_CONTENT_DIR . $morsles[1]);
                        }
                } else {
                        return $info;
                }
        }

        add_filter('bloginfo_url', 'fb_css_cache_buster', 9999, 2);
}

// FRONT MENU / LIST PAGES OR CATEGORIES

function lw_wp_list_pages(){
global $lw_show_categories, $lw_exclude_pages, $lw_exclude_categories;
if ($lw_show_categories == "true") {
$top_list = wp_list_categories("echo=0&depth=2&title_li=&exclude=".$lw_exclude_categories."");
$top_list = str_replace(array('">','</a>','<span><a','current-cat"><a'),array('"><span>','</span></a>','<a','"><a class="s"'), $top_list);
return $top_list;
}else{
$top_list = wp_list_pages("echo=0&depth=2&title_li=&exclude=".$lw_exclude_pages."");
$top_list = str_replace(array('">','</a>','<span><a','current_page_item"><a'),array('"><span>','</span></a>','<a','"><a class="s"'), $top_list);
return $top_list;
}
}

// HEADER IMAGE

function lw_header_image(){
global $lw_top_header_image, $lw_top_header_image_height, $top_header_image_path;
if($lw_top_header_image == "" || $lw_top_header_image == "true") {
?>
<a name="top" title="<?php bloginfo('name'); ?>" href="<?php bloginfo('url'); ?>"><span id="top" style="background:url('<?php echo $top_header_image_path; ?>') no-repeat;height:<?php echo $lw_top_header_image_height; ?>px"><strong><?php bloginfo('name'); ?></strong></span></a>
<?php }else{ ?>
<div id="top"><h1 id="logo"><a name="top" title="<?php bloginfo('name'); ?>" href="<?php bloginfo('url'); ?>"><?php bloginfo('name'); ?></a> <small><?php bloginfo('description'); ?></small></h1></div>
<?php
}
}

// COMMENTS PINGBACKS / TABS JQUERY

function comment_tabs(){
if(is_single()||is_page()){
?>
<script type="text/javascript" src="<?php bloginfo('template_directory'); ?>/js/tabs.js"></script>
<script type="text/javascript">jQuery(document).ready(function(){jQuery('tabs').tabs({linkClass : 'tabs',containerClass : 'tab-content',linkSelectedClass : 'selected',containerSelectedClass : 'selected',onComplete : function(){}});});</script>
<?php
}
}

// CUFON SETTINGS

if ($lw_cufon_settings == "Enabled") {$cufon_enabled = 1; $cufon_extra = 0;}
if ($lw_cufon_settings == "Extra") {$cufon_extra = 1; $cufon_enabled = 1;}

function cufon_header(){
global $cufon_enabled, $cufon_extra;
$cufon_header_script = "\n<script src=\"".get_bloginfo('template_directory')."/js/cufon.js\" type=\"text/javascript\"></script>\n<script src=\"".get_bloginfo('template_directory')."/js/mp.font.js\" type=\"text/javascript\"></script>";
if($cufon_extra == 1) $cufon_header_script = str_replace("mp.font.js", "extra_mp.font.js", $cufon_header_script);
if($cufon_enabled == 1) echo $cufon_header_script;
}

function cufon_footer(){
global $cufon_enabled;
$cufon_footer_script = "\n<script type=\"text/javascript\">/* <![CDATA[ */ Cufon.now(); /* ]]> */ </script>\n";
if($cufon_enabled == 1) echo $cufon_footer_script;
}

// HOME BUTTON

function lw_homebtn($homebtn_value){
global $lw_remove_homebtn; if($lw_remove_homebtn == "false") { if(is_front_page()) $selected="s"; ?><li><a class="<?php echo $selected; ?>" title="<?php echo $homebtn_value; ?>" href="<?php bloginfo('url'); ?>"><span><?php echo $homebtn_value ?></span></a></li>
<?php
}
}

// CANONICAL COMMENTS

function canonical_for_comments() {
global $cpage, $post;
if ( $cpage > 1 ) :
echo "\n";
echo "<link rel='canonical' href='";
echo get_permalink( $post->ID );
echo "' />\n";
endif;
}

// SEARCH BOX / WORDPRESS BASIC SEARCH OR GOOGLE CSE

function lw_searchbox(){
global $lw_remove_searchbox, $lw_google_search_code;
$lw_google_search_code = trim(str_replace(" ","",$lw_google_search_code));
if($lw_remove_searchbox != "true")
if(!empty($lw_google_search_code)){
?>
<form action="http://www.google.com/cse" method="get" id="searchform">
<input type="text" class="textfield" name="q" size="24" id="s"/>
<input type="submit" class="button" name="sa" value="" id="go"/>
<input type="hidden" name="cx" value="<?php echo $lw_google_search_code; ?>" />
<input type="hidden" name="ie" value="UTF-8" />
</form>
<?php }else{ ?>
<form method="get" id="searchform" action="<?php bloginfo('url'); ?>"> <input type="text" value="" name="s" id="s" /> <input type="submit" id="go" value="" alt="<?php _e('Search'); ?>" title="<?php _e('Search'); ?>" /></form>
<?php
}
}

// REMOVE SEARCHBOX
function lw_expmenu(){
global $lw_remove_searchbox;
if($lw_remove_searchbox=="true") echo " class=\"expand\"";
}

// SIDEBOX

function lw_show_sidebox(){
global $lw_sidebox_settings;

switch ($lw_sidebox_settings)
{
case "Enabled":
default:
/* START ENABLED */
echo "<div class=\"comm_date\"><span class=\"data\"><span class=\"j\">".get_the_time('j')."</span>".get_the_time('M/y')."</span><span class=\"nr_comm\">";
//if(function_exists('dsq_is_installed')) echo "<a class=\"nr_comm_spot\" href=\"".get_permalink()."\">N/A</a>";
//else
echo "<a class=\"nr_comm_spot\" href=\"".get_permalink()."#comments\">";
if(!comments_open()) _e('Off','lightword'); else echo fb_get_comment_type_count('comment');
echo "</a></span></div>\n";
/* END ENABLED */
break;

case "Disabled":
/* START DISABLED */
/* END DISABLED */
break;

case "Show only in posts":
/* START ENABLED */
if(is_single()){
echo "<div class=\"comm_date\"><span class=\"data\"><span class=\"j\">".get_the_time('j')."</span>".get_the_time('M/y')."</span><span class=\"nr_comm\">";
//if(function_exists('dsq_is_installed')) echo "<a class=\"nr_comm_spot\" href=\"".get_permalink()."\">N/A</a>";
//else
echo "<a class=\"nr_comm_spot\" href=\"".get_permalink()."#comments\">";
if(!comments_open()) _e('Off','lightword'); else echo fb_get_comment_type_count('comment')."</a>";
echo "</span></div>\n";
}
/* END ENABLED */
break;

case "Show only date":
/* START ONLY DATE */
echo "<div class=\"comm_date only_date\"><span class=\"data\"><span class=\"j\">".get_the_time('j')."</span>".get_the_time('M/y')."</span><span class=\"nr_comm\">";
echo "</span></div>\n";
/* END ONLY DATE */
break;

case "Last two options together":
/* START  LAST TWO */
if(is_single()){
echo "<div class=\"comm_date only_date\"><span class=\"data\"><span class=\"j\">".get_the_time('j')."</span>".get_the_time('M/y')."</span><span class=\"nr_comm\">";
echo "</span></div>\n";
}
/* END LAST TWO */
break;

} // end switch
} // end function

function lw_simple_date(){
global $lw_sidebox_settings;
if($lw_sidebox_settings == "Disabled"){
echo "<div class=\"simple_date\">".__('Posted on','lightword')." ".get_the_time('F j, Y')."</div>";
}
}

// LEGACY COMMENTS / FOR OLD VERSION OF WORDPRESS

function legacy_comments($file) {
if(!function_exists('wp_list_comments')) : // WP 2.7-only check
$file = TEMPLATEPATH.'/legacy.comments.php';
endif;
return $file;
}

// COMMENT OPTIONS

function options_comment_link($id) {
if (current_user_can('edit_post')) {
echo '<a class="comment-edit-link" href="'.admin_url("comment.php?action=cdc&c=$id").'">'.__('delete','lightword').'</a>   ';
echo '<a class="comment-edit-link" href="'.admin_url("comment.php?action=cdc&dt=spam&c=$id").'">'.__('spam','lightword').'</a>';
edit_comment_link(__('edit','lightword'),'&nbsp;','');
}
}

// SPAM PROTECT

function check_referrer() {
if (!isset($_SERVER['HTTP_REFERER']) || $_SERVER['HTTP_REFERER'] == “”) {
wp_die( __('Please enable referrers in your browser, or, if you\'re a spammer, bugger off!','lightword') );
}
}

// RSS FEED BADGE OPTIONS

function rss_feed_css_false(){
echo "<style type=\"text/css\">/*<![CDATA[*/* html #searchform{margin-top:-13px;}*+ html #searchform{margin-top:-13px;}  #content-body,x:-moz-any-link{float:left;margin-right:28px;}#content-body, x:-moz-any-link, x:default{float:none;margin-right:25px;} /*]]>*/</style>";
}
function rss_feed_css_true(){
global $lw_layout_settings;
if($lw_layout_settings == "Wider"){
echo "<style type=\"text/css\">/*<![CDATA[*/ #header{background:transparent url(".get_bloginfo('template_directory')."/images/wider/content_top_no_rss.png) no-repeat; } #content-body,x:-moz-any-link{float:left;margin-right:28px;}#content-body, x:-moz-any-link, x:default{float:none;margin-right:25px;}/*]]>*/</style>";
}else{
echo "<style type=\"text/css\">/*<![CDATA[*/ #header{background:transparent url(".get_bloginfo('template_directory')."/images/content_top_no_rss.png) no-repeat; } #content-body,x:-moz-any-link{float:left;margin-right:28px;}#content-body, x:-moz-any-link, x:default{float:none;margin-right:25px;}/*]]>*/</style>";
}
}

function lw_rss_feed(){
global $lw_remove_rss;
if($lw_remove_rss == "false"){ ?>
<a id="rss-feed" title="<?php _e('Syndicate this site using RSS','lightword'); ?>" href="<?php bloginfo('rss2_url'); ?>"><?php _e('Subscribe via RSS','lightword'); ?></a>
<?php } } if($lw_remove_rss == "false") add_action('wp_head','rss_feed_css_false'); else add_action('wp_head','rss_feed_css_true');

// IE6 PNG CSS FIX

function ie_png_transparency(){
global $lw_remove_rss, $lw_layout_settings, $lw_sidebox_settings;
$lw_layout_wider = "";
if($lw_layout_settings=="Wider") $lw_layout_wider = "wider/";
echo "\n<!--[if IE 6]><style type=\"text/css\">/*<![CDATA[*/";
if($lw_remove_rss == "false"){
echo "#header{background-image: none; filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='".get_bloginfo('template_directory')."/images/".$lw_layout_wider."content_top.png',sizingMethod='scale'); }";
}else{
echo "#header{background-image: none; filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='".get_bloginfo('template_directory')."/images/".$lw_layout_wider."content_top_no_rss.png',sizingMethod='scale'); }";
}
echo "#footer{background:transparent url(".get_bloginfo('template_directory')."/images/".$lw_layout_wider."content_bottom.gif) no-repeat;height:8px;}";

if($lw_sidebox_settings == "Show only date" || $lw_sidebox_settings == "Last two options together"){
echo ".only_date{background-image: none; filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='".get_bloginfo('template_directory')."/images/data_box.png',sizingMethod='scale'); }";
}else{
echo ".comm_date{background-image: none; filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='".get_bloginfo('template_directory')."/images/date_comm_box.png',sizingMethod='scale'); }";
}

echo "/*]]>*/</style><![endif]-->";
}

// THREADED COMMENTS

function nested_comments($comment, $args, $depth) { $GLOBALS['comment'] = $comment; ?>
<li <?php comment_class(); ?> id="li-comment-<?php comment_ID() ?>"><div id="comment-<?php comment_ID(); ?>">
<div class="comment_content"><div class="comment-meta commentmetadata"><div class="alignleft"><?php echo get_avatar($comment,$size='36'); ?></div>
<div class="alignleft" style="padding-top:5px;"><strong class="comment_author"><?php comment_author_link() ?></strong><br/><a href="#comment-<?php comment_ID() ?>" title=""><?php comment_date(__('F jS, Y - H:i','lightword')) ?></a> <?php options_comment_link(get_comment_ID()); ?></div><div class="clear"></div></div>
<?php comment_text() ?>
<div class="reply"><?php comment_reply_link(array_merge( $args, array('reply_text' => __('( REPLY )','lightword'), 'depth' => $depth, 'max_depth' => $args['max_depth']))) ?></div>
<?php if ($comment->comment_approved == '0') : ?><span class="moderation"><?php _e('Your comment is awaiting moderation.','lightword'); ?></span><br /><?php endif; ?></div><div class="clear"></div></div>
<?php
}

// CUSTOM CSS

function lw_custom_css(){
global $lw_custom_css;
if($lw_custom_css){
echo "\n<style type=\"text/css\">\n/*<![CDATA[*/\n".$lw_custom_css."\n /*]]>*/\n</style>\n";
}
}

// REMOVE SEARCH WIDGET
function my_unregister_widgets() {
unregister_widget('WP_Widget_Search');
}

// LOCALIZATION

load_theme_textdomain('lightword', get_template_directory() . '/lang');

// ENABLE FUNCTIONS


add_action('admin_menu', 'lightword_admin');
add_action('wp_head',    'cufon_header');
add_action('wp_head',    'lw_custom_css');
add_action('wp_head',    'ie_png_transparency');
add_action('wp_footer',  'cufon_footer');
add_action('wp_footer',  'comment_tabs');
add_action( 'wp_head', 'canonical_for_comments' );
add_action('widgets_init', 'my_unregister_widgets');
add_filter('comments_template', 'legacy_comments');

remove_action('wp_head', 'wp_generator');
remove_filter('the_content', 'wptexturize');
?>