<?php
// wheather you want full ext layout ar preview only

$options  = get_option('ext_options');

if ($options['ext_mode'] === 'full') {
	if (!isset($_GET['noext']) && !isNoExtUrl() ) {
        add_action('template_redirect', 'extize');
    }
} else {
    if (isset($_GET['ext'])) {
        add_action('template_redirect', 'extize');
    }
}
function extize () {
    include(TEMPLATEPATH . '/ext-index.php');
    exit;
}

function isNoExtUrl() {
	$noextUrls = array(
		get_feed_link('rdf'),
		get_feed_link('rss'),
		get_feed_link('rss2'),
		get_feed_link('atom'),
		get_feed_link('comments_atom'),
		get_feed_link('comments_rss2'),
		get_option('siteurl') .'/xmlrpc.php'
	);
	$addressBar = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"];
	if (in_array($addressBar, $noextUrls)) {
		return true;
	}
	return false;
}

function siteCssOptions($cssOption) {
    echo '<!-- '.$cssOption.' -->';
    $options  = get_option('ext_options');
    $ext_path = get_bloginfo('template_url').'/ext-2.2.1';
        if (!empty($options['ext_cdn']) && $options['ext_cdn'] == 'yes') {
            ?><link rel="stylesheet" type="text/css" href="http://extjs.cachefly.net/ext-2.2.1/resources/css/ext-all.css"><?php
        } else {
            ?><link rel="stylesheet" type="text/css" href="<?php echo $ext_path; ?>/resources/css/ext-all.css"><?php
        }
    switch ($options[$cssOption]) {
        case 'purple':
?>
<link rel="stylesheet" type="text/css" href="<?php echo $ext_path; ?>/resources/css/xtheme-purple.css">
<?php
            break;
        case 'slate':
            if (!empty($options['ext_cdn']) && $options['ext_cdn'] == 'yes') {
                ?><link rel="stylesheet" type="text/css" href="http://extjs.cachefly.net/ext-2.2.1/resources/css/xtheme-slate.css"><?php
            } else {
                ?><link rel="stylesheet" type="text/css" href="<?php echo $ext_path; ?>/resources/css/xtheme-slate.css"><?php
            }
?>
<style>
.x-panel-bbar a, .x-btn, .x-btn a, .x-panel-header a,.x-tab-panel-header a  {color:#ffffff;} 
.x-panel-bbar a:hover, .x-btn a:hover, .x-panel-header a:hover,.x-tab-panel-header a:hover  {color:#F7ECC1;}
</style> 
<?php
            break;
        case 'gray':
            if (!empty($options['ext_cdn']) && $options['ext_cdn'] == 'yes') {
                ?><link rel="stylesheet" type="text/css" href="http://extjs.cachefly.net/ext-2.2.1/resources/css/xtheme-gray.css"><?php
            } else {
                ?><link rel="stylesheet" type="text/css" href="<?php echo $ext_path; ?>/resources/css/xtheme-gray.css"><?php
            }
            break;
        case 'light':
?>
<link rel="stylesheet" type="text/css" href="<?php echo $ext_path; ?>/resources/css/xtheme-light.css">
<style>
.x-grid3-header {
    border-top:1px solid #d0d0d0;
}
</style>
<?php
            break;
        case '2brave':
?>
<link rel="stylesheet" type="text/css" href="<?php echo $ext_path; ?>/resources/css/xtheme-2brave.css">
<style>
.x-panel-header a, .entry a, #header h1 a   {color:#ffffff;} 
.x-panel-header a:hover, .entry a:hover,#header h1 a:hover  {color:#F7ECC1;} 

#footer * {color:#fff;}
.x-panel-mc {color:#333333;} 
.x-grid3-col a {color:#ffff00;} 
.x-grid3-col a:hover {color:#fff;}
.wp-caption {border:1px solid #2E48AB !important;background-color:#3399ff !important;}

#header a.blog-title {color:#ffffff;} 
.accordion-item  a {color:#ffff00;}
.accordion-item  a:hover {color:#ffffff;}
#loading h1 a {color:#ffffff;}
/*
.x-panel-body a, .x-panel-header a, .entry a, #header h1 a   {color:#ffffff;} 
.x-panel-bwrap a, .x-panel-header a, .entry a, #header h1 a   {color:#ffffff;} 
.x-panel-body a:hover, .x-panel-header a:hover, .entry a:hover,#header h1 a:hover  {color:#F7ECC1;} 
.x-panel-bbar a {color:#1A5C9A;}
.x-panel-bbar a:hover {color:#1A5C9A;}
.x-grid3-col a {color:#ffff00;} 
.x-grid3-col a:hover {color:#fff;}
.wp-caption {border:1px solid #2E48AB !important;background-color:#3399ff !important;}
#footer * {color:#fff;}
*/

</style>
<?php
            break;
        default:
?>
<!-- No css required -->
<?php
    }
?>
<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
<?php
    if ( $cssOption == 'ext_css' ) {
?>
<style>
#header, #footer, #sidebar/*,  #commentform */ {display:none;} 
#wrap {width:100% !important; }
#content {	width: 100% !important;}
.x-s-b {color:#3366cc !important;border:1px solid #ffcc00 !important;background:#ffffcc !important}
.x-s-b * {color:#3366cc !important;}
</style>
<?php
    }

}

//loop_template();

class ExtOptions {
	function getOptions() {
		$options = get_option('ext_options');
		if (!is_array($options)) {
			$options['ext_css'] = 'default';
			update_option('fusion_options', $options);
		}
		return $options;
	}

	function add() {
		if(isset($_POST['ext_save'])) {
			$options = ExtOptions::getOptions();
			// ext mode
			$options['ext_mode']       = stripslashes($_POST['ext_mode']);
			// css
			$options['ext_css']       = stripslashes($_POST['ext_css']);
			$options['ext_css_outer'] = stripslashes($_POST['ext_css_outer']);
			// Misc
			$options['ext_home_name'] = stripslashes($_POST['ext_home_name']);
            $options['ext_sidebar_style']  = stripslashes($_POST['ext_sidebar_style']);
            $options['ext_cdn']  = stripslashes($_POST['ext_cdn']);
			update_option('ext_options', $options);
		} else {
			ExtOptions::getOptions();
		}

		add_theme_page("ExtizeMe Options", "ExtizeMe Options", 'edit_themes', basename(__FILE__), array('ExtOptions', 'display'));
	}

    function display() {
        $options = ExtOptions::getOptions();
        $cssArr = array('default', 'purple', 'slate', 'gray', 'light', '2brave');
        $sidebarArr = array('accordion', 'container');
        $cdnArr = array('yes', 'no');
   ?>
      <form action="#" method="post" enctype="multipart/form-data" name="ext_form" id="ext_form">
      <div class="wrap">
            <h2><?php _e('ExtizeMe Options'); ?></h2>
<h3>ExtizeMe Mode</h3>
            <table class="form-table">
            <tr>
                <th scope="row">
                <label for="ext_css"><?php _e('ExtJS Theme Mode:'); ?></label>
                </th>
                <td>
                    <select name="ext_mode" class="code">
                        <option value="full" <?php if (isset($options['ext_mode']) && $options['ext_mode'] == 'full') { echo 'selected'; } ?>>Full&nbsp;&nbsp;&nbsp;&nbsp;</option>
                        <option value="preview" <?php if (!isset($options['ext_mode']) || $options['ext_mode'] == 'preview') { echo 'selected'; } ?>>Preview&nbsp;&nbsp;&nbsp;&nbsp;</option>
                    </select>
                    <span class="setting-description">Wheater you want anways ExtJs Layout or a special widget appear that allows you to switch between JS mode and NON-JS mode.</span>
                </td>
            <tr>
            </table>
<h3>Color Styles</h3>
            <table class="form-table">
            <tr>
                <th scope="row">
                <label for="ext_css_outer"><?php _e('Color Style:'); ?></label>
                </th>
                <td>
                    <select name="ext_css_outer" class="code">
                    <?php 
                        foreach ($cssArr as $val) {
                    ?>
                        <option value="<?php echo $val;?>" <?php if (isset($options['ext_css_outer']) && $options['ext_css_outer'] == $val) { echo 'selected'; } ?>><?php echo ucfirst($val);?>&nbsp;&nbsp;&nbsp;&nbsp;</option>
                    <?php
                        }
                    ?>
                    </select>
                    <span class="setting-description">You are able to choose between 6 diferent Ext CSSes.</span>
                </td>
            <tr>
            <tr>
                <th scope="row">
                <label for="ext_css"><?php _e('Inner Color Style:'); ?></label>
                </th>
                <td>
                    <select name="ext_css" class="code">
                    <?php 
                        foreach ($cssArr as $val) {
                    ?>
                        <option value="<?php echo $val;?>" <?php if (isset($options['ext_css']) && $options['ext_css'] == $val) { echo 'selected'; } ?>><?php echo ucfirst($val);?>&nbsp;&nbsp;&nbsp;&nbsp;</option>
                    <?php
                        }
                    ?>
                    </select>
                    <span class="setting-description">On Ext mode you are able to select different ext color style.</span>
                </td>
            <tr>
            </table>
<h3>Miscelaneous</h3>
            <table class="form-table">
            <tr>
                <th scope="row">
                <label for="ext_home_name"><?php _e('Home Page Link Name:'); ?></label>
                </th>
                <td>
                    <input type="text" class="code" name="ext_home_name" value="<?php echo $options['ext_home_name']; ?>" />
                    <span class="setting-description">Link to home page is displaying as "Home" by default, but you are able to change this name here.</span>
                </td>
            <tr>
            <tr>
                <th scope="row">
                <label for="ext_sidebar_style"><?php _e('Sidebar Style:'); ?></label>
                </th>
                <td>
                    <select name="ext_sidebar_style" class="code">
                    <?php 
                        foreach ($sidebarArr as $val) {
                    ?>
                        <option value="<?php echo $val;?>" <?php if (isset($options['ext_sidebar_style']) && $options['ext_sidebar_style'] == $val) { echo 'selected'; } ?>><?php echo ucfirst($val);?>&nbsp;&nbsp;&nbsp;&nbsp;</option>
                    <?php
                        }
                    ?>
                    </select>
                    <span class="setting-description">Sidebar style: either accordion or container. Accordion displays one widget expanded and the rest collapsed. Container expands all widgets.</span>
                </td>
            <tr>
            <tr>
                <th scope="row">
                <label for="ext_cdn"><?php _e('Use CDN:'); ?></label>
                </th>
                <td>
                    <select name="ext_cdn" class="code">
                    <?php 
                        foreach ($cdnArr as $val) {
                    ?>
                        <option value="<?php echo $val;?>" <?php if (isset($options['ext_cdn']) && $options['ext_cdn'] == $val) { echo 'selected'; } ?>><?php echo ucfirst($val);?>&nbsp;&nbsp;&nbsp;&nbsp;</option>
                    <?php
                        }
                    ?>
                    </select>
                    <span class="setting-description">If yes, the ExtJs is taken from http://extjs.cachefly.net/ instead of local.</span>
                </td>
            <tr>
            </table>
<h3>Amazing ExtizeMe Bookmarklet (very-very-very experimental)</h3>
            <table class="form-table" >
            <tr>
                <th scope="row">
                
                </th>
                <td>
                    Drag the <b><a href="javascript:(function(){document.open();document.write('<html><head><link%20rel=\'stylesheet\'%20type=\'text/css\'%20href=\'http://extjs.cachefly.net/ext-2.2.1/resources/css/ext-all.css\'></head><body><script%20src=\'http://extjs.cachefly.net/builds/ext-cdn-771.js\'></script><script%20src=\'<?php echo get_bloginfo('template_url'); ?>/js/ux/ext.util.md5.js\'></script><script%20src=\'<?php echo get_bloginfo('template_url'); ?>/js/ux/TabCloseMenu.js\'></script><script%20src=\'<?php echo get_bloginfo('template_url'); ?>/js/ux/miframe.js\'></script><script%20src=\'<?php echo get_bloginfo('template_url'); ?>/js/ux/Ext.ux.DockPanel-1.0b.js\'></script><script%20src=\'<?php echo get_bloginfo('template_url'); ?>/js/ux/iframe-proxy.js\'></script><script%20src=\'<?php echo get_bloginfo('template_url'); ?>/js/overrides.js\'></script><script%20src=\'<?php echo get_bloginfo('template_url'); ?>/js/wp.js\'></script><script%20src=\'<?php echo get_bloginfo('template_url'); ?>/js/module-livesearch.js\'></script><script%20src=\'<?php echo get_bloginfo('template_url'); ?>/js/module-commentform.js\'></script><script%20src=\'<?php echo get_bloginfo('template_url'); ?>/js/module-galery.js\'></script><script%20src=\'<?php echo get_bloginfo('template_url'); ?>/js/module-page.js\'></script><script%20src=\'<?php echo get_bloginfo('template_url'); ?>/js/module-widget.js\'></script><script%20src=\'<?php echo get_bloginfo('template_url'); ?>/js/module-sidebar.js\'></script><script%20src=\'<?php echo get_bloginfo('template_url'); ?>/js/viewport.js\'></script><script%20src=\'<?php echo get_bloginfo('template_url'); ?>/js/app.js\'></script><script>Ext.ns(\'WP\',\'WP.config\');WP.config={sidebar:\'accordion\',linkTarget:null,viewport:{west:0,east:270},URL:WP.extizeUrl(document.location.href)};Ext.onReady(WP.App.init,%20WP.App);</script></body></html>');document.close();})()">ExtizeMe Boookmarklet</a></b>
                                                             <span class="setting-description"> to your browser bookmarktoolbar</span>
                </td>
            <tr>
            </table>
            <p class="submit">
            <input class="button-primary" type="submit" name="ext_save" value="<?php _e('Save Changes'); ?>" />
            </p>
      </div>
      </form>
   <?php
  }
}
// register functions
add_action('admin_menu', array('ExtOptions', 'add'));

/**
 * SIDEBAR
 */ 
if ( function_exists('register_sidebar') ) {
    register_sidebar(array(
        'name'=>'Left Sidebar',
        'before_widget' => '<li><div class="x-panel widget" id="%1$s"" style="margin-bottom: 20px; width: 200px;">',
        'before_title' => '<div class="x-panel-tl"><div class="x-panel-tr"><div class="x-panel-tc"><div class="x-panel-header x-unselectable" style="-moz-user-select: none;"><span class="x-panel-header-text widgettitle">',
        'after_title' => '</span></div></div></div></div><div class="x-panel-bwrap"><div class="x-panel-ml"><div class="x-panel-mr"><div class="x-panel-mc"><div class="x-panel-body widgetbody" style="width: 188px;">',
        'after_widget' => '</div></div></div></div><div class="x-panel-bl x-panel-nofooter"><div class="x-panel-br"><div class="x-panel-bc"></div></div></div></div></div></li>',
    ));
}
if ( function_exists('register_sidebar_widget') ) {
    register_sidebar_widget(__('Calendar'), 'widget_extjslike_calendar');
}

if ( function_exists('register_sidebar_widget') ) {
    register_sidebar_widget(__('Search'), 'widget_extjslike_search');
}


function widget_extjslike_search($args) {
    extract($args);
    echo $before_widget . $before_title . 'Search' . $after_title;
?>
    <form class="x-form" id="searchform" method="get" action="<?php bloginfo('home'); ?>">
        <table cellspacing="2" align="center"><tbody><tr><td>
        <input type="hidden" name="noext" id="noext" value = "<?php echo attribute_escape($_GET['noext']); ?>" />
        <input class="x-form-text x-form-field" type="text" style="width:105px;height:18px;" name="s" id="s" value = "<?php echo attribute_escape(apply_filters('the_search_query', get_search_query())); ?>" />
        </td><td>
            <table cellspacing="0"><tbody><tr><td class="x-panel-btn-td">
                <table cellspacing="0" cellpadding="0" border="0" class="x-btn-wrap x-btn"><tbody><tr><td class="x-btn-left"><i></i></td><td class="x-btn-center"><em unselectable="on">
                <button style="width:50px" type="submit" class="x-btn-text"><?php echo attribute_escape(__('Search')); ?></button>
                </em></td><td class="x-btn-right"><i></i></td></tr></tbody></table>
            </td></tr></tbody></table>
        </td></tr></tbody></table>
    </form>
<?php
    echo $after_widget; 
}

function widget_extjslike_calendar($args) {
    extract($args);
    $options = get_option('widget_calendar');
    $title = $options['title'];
    if ( empty($title) )
        $title = 'Calendar';
    echo $before_widget . $before_title . $title . $after_title;
    echo '<div id="calendar_wrap">';
    get_extjslike_calendar();
    echo '</div>';
    echo $after_widget;
}

function get_extjslike_calendar($initial = true) {
    global $wpdb, $m, $monthnum, $year, $timedifference, $wp_locale, $posts;

    $key = md5( $m . $monthnum . $year );
    if ( $cache = wp_cache_get( 'get_calendar', 'calendar' ) ) {
        if ( isset( $cache[ $key ] ) ) {
            echo $cache[ $key ];
            return;
        }
    }

    ob_start();
    // Quick check. If we have no posts at all, abort!
    if ( !$posts ) {
        $gotsome = $wpdb->get_var("SELECT ID from $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' ORDER BY post_date DESC LIMIT 1");
        if ( !$gotsome )
            return;
    }

    if ( isset($_GET['w']) )
        $w = ''.intval($_GET['w']);

    // week_begins = 0 stands for Sunday
    $week_begins = intval(get_option('start_of_week'));
    $add_hours = intval(get_option('gmt_offset'));
    $add_minutes = intval(60 * (get_option('gmt_offset') - $add_hours));

    // Let's figure out when we are
    if ( !empty($monthnum) && !empty($year) ) {
        $thismonth = ''.zeroise(intval($monthnum), 2);
        $thisyear = ''.intval($year);
    } elseif ( !empty($w) ) {
        // We need to get the month from MySQL
        $thisyear = ''.intval(substr($m, 0, 4));
        $d = (($w - 1) * 7) + 6; //it seems MySQL's weeks disagree with PHP's
        $thismonth = $wpdb->get_var("SELECT DATE_FORMAT((DATE_ADD('${thisyear}0101', INTERVAL $d DAY) ), '%m')");
    } elseif ( !empty($m) ) {
        $calendar = substr($m, 0, 6);
        $thisyear = ''.intval(substr($m, 0, 4));
        if ( strlen($m) < 6 )
                $thismonth = '01';
        else
                $thismonth = ''.zeroise(intval(substr($m, 4, 2)), 2);
    } else {
        $thisyear = gmdate('Y', current_time('timestamp'));
        $thismonth = gmdate('m', current_time('timestamp'));
    }

    $unixmonth = mktime(0, 0 , 0, $thismonth, 1, $thisyear);

    // Get the next and previous month and year with at least one post
    $previous = $wpdb->get_row("SELECT DISTINCT MONTH(post_date) AS month, YEAR(post_date) AS year
        FROM $wpdb->posts
        WHERE post_date < '$thisyear-$thismonth-01'
        AND post_type = 'post' AND post_status = 'publish'
            ORDER BY post_date DESC
            LIMIT 1");
    $next = $wpdb->get_row("SELECT    DISTINCT MONTH(post_date) AS month, YEAR(post_date) AS year
        FROM $wpdb->posts
        WHERE post_date >    '$thisyear-$thismonth-01'
        AND MONTH( post_date ) != MONTH( '$thisyear-$thismonth-01' )
        AND post_type = 'post' AND post_status = 'publish'
            ORDER    BY post_date ASC
            LIMIT 1");

    echo '<div align="center" style="padding-top:5px;padding-bottom:5px"><div class="x-date-picker x-unselectable" style="-moz-user-select: none; width: 175px;">';
    echo '<table cellspacing="0" style="width: 175px;">
    <tbody>
    <tr>
        <td class="x-date-left">
        ';
    if ( $previous ) {
        echo "\n\t\t".'<a class="x-unselectable" href="'.
        get_month_link($previous->year, $previous->month) . '" title="' . sprintf(__('View posts for %1$s %2$s'), $wp_locale->get_month($previous->month),
            date('Y', mktime(0, 0 , 0, $previous->month, 1, $previous->year))) . '" style="-moz-user-select: none;"></a>';
    } else {
        echo "\n\t\t".'<i></i>';
    }
    echo '
        </td>
        <td class="x-date-middle" align="center">
        <table class="x-btn-wrap x-btn" cellspacing="0" cellpadding="0" border="0" style="width: 139px">
        <tbody>
            <tr class="x-btn-with-menu">
                <td class="x-btn-left"><i/></td>
                <td class="x-btn-center">
                    <div class="x-btn-text">' . $wp_locale->get_month($thismonth) . ' ' . date('Y', $unixmonth) . '</div>
                </td>
                <td class="x-btn-right"><i/></td>
            </tr>
        </tbody>
        </table>
        </td>
        <td class="x-date-right">
        ';
    if ( $next ) {
        echo '<a class="x-unselectable" href="' .
        get_month_link($next->year, $next->month) . '" title="' . sprintf(__('View posts for %1$s %2$s'), $wp_locale->get_month($next->month),
            date('Y', mktime(0, 0 , 0, $next->month, 1, $next->year))) . '" style="-moz-user-select: none;" />';
    } else {
        echo '<i/>';
    }
    echo '
        </td>
    </tr>
    <tr>
        <td colspan="3">
';
    echo '<table class="x-date-inner" cellspacing="0">
    <thead>
    <tr>';

    $myweek = array();

    for ( $wdcount=0; $wdcount<=6; $wdcount++ ) {
        $myweek[] = $wp_locale->get_weekday(($wdcount+$week_begins)%7);
    }

    foreach ( $myweek as $wd ) {
        $day_name = (true == $initial) ? $wp_locale->get_weekday_initial($wd) : $wp_locale->get_weekday_abbrev($wd);
        echo "\n\t\t<th abbr=\"$wd\" scope=\"col\" title=\"$wd\"><span>$day_name</span></th>";
    }

    echo '
    </tr>
    </thead>

    <tbody>
    <tr>';

    // Get days with posts
    $dayswithposts = $wpdb->get_results("SELECT DISTINCT DAYOFMONTH(post_date)
        FROM $wpdb->posts WHERE MONTH(post_date) = '$thismonth'
        AND YEAR(post_date) = '$thisyear'
        AND post_type = 'post' AND post_status = 'publish'
        AND post_date < '" . current_time('mysql') . '\'', ARRAY_N);
    if ( $dayswithposts ) {
        foreach ( $dayswithposts as $daywith ) {
            $daywithpost[] = $daywith[0];
        }
    } else {
        $daywithpost = array();
    }

    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false || strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'camino') !== false || strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'safari') !== false)
        $ak_title_separator = "\n";
    else
        $ak_title_separator = ', ';

    $ak_titles_for_day = array();
    $ak_post_titles = $wpdb->get_results("SELECT post_title, DAYOFMONTH(post_date) as dom "
        ."FROM $wpdb->posts "
        ."WHERE YEAR(post_date) = '$thisyear' "
        ."AND MONTH(post_date) = '$thismonth' "
        ."AND post_date < '".current_time('mysql')."' "
        ."AND post_type = 'post' AND post_status = 'publish'"
    );
    if ( $ak_post_titles ) {
        foreach ( $ak_post_titles as $ak_post_title ) {
            
                $post_title = apply_filters( "the_title", $ak_post_title->post_title );
                $post_title = str_replace('"', '&quot;', wptexturize( $post_title ));
                                
                if ( empty($ak_titles_for_day['day_'.$ak_post_title->dom]) )
                    $ak_titles_for_day['day_'.$ak_post_title->dom] = '';
                if ( empty($ak_titles_for_day["$ak_post_title->dom"]) ) // first one
                    $ak_titles_for_day["$ak_post_title->dom"] = $post_title;
                else
                    $ak_titles_for_day["$ak_post_title->dom"] .= $ak_title_separator . $post_title;
        }
    }


    // See how much we should pad in the beginning
    $pad = calendar_week_mod(date('w', $unixmonth)-$week_begins);
    if ( 0 != $pad )
        echo "\n\t\t".'<td colspan="'.$pad.'" class="x-date-prevday">&nbsp;</td>';

    $daysinmonth = intval(date('t', $unixmonth));
    for ( $day = 1; $day <= $daysinmonth; ++$day ) {
        if ( isset($newrow) && $newrow )
            echo "\n\t</tr>\n\t<tr>\n\t\t";
        $newrow = false;

        echo '<td class="x-date-active ';

        if ( $day == gmdate('j', (time() + (get_option('gmt_offset') * 3600))) && $thismonth == gmdate('m', time()+(get_option('gmt_offset') * 3600)) && $thisyear == gmdate('Y', time()+(get_option('gmt_offset') * 3600)) )
            echo 'x-date-today ';

        if ( in_array($day, $daywithpost) ) { // any posts today?
            echo 'x-date-selected"> ';
            echo '<a rel="noajax" class="x-date-date" hidefocus="on" href="' . get_day_link($thisyear, $thismonth, $day) . '" title="'.$ak_titles_for_day[$day].'"><em><span>'.$day.'</span></em></a>';
        } else {
            echo '"> ';
            echo '<a style="cursor:default" class="x-date-date" hidefocus="on" title="'.$ak_titles_for_day[$day].'"><em><span>'.$day.'</span></em></a>';
        }
            //echo $day;
        echo '</td>';

        if ( 6 == calendar_week_mod(date('w', mktime(0, 0 , 0, $thismonth, $day, $thisyear))-$week_begins) )
            $newrow = true;
    }

    $pad = 7 - calendar_week_mod(date('w', mktime(0, 0 , 0, $thismonth, $day, $thisyear))-$week_begins);
    if ( $pad != 0 && $pad != 7 )
        echo "\n\t\t".'<td class="pad" colspan="'.$pad.'">&nbsp;</td>';

    echo "\n\t</tr>\n\t</tbody>\n\t</table>";
    echo "\n\t</td>\n\t</tr>\n\t</tbody>\n\t</table>";
    echo '</div></div>';
    $output = ob_get_contents();
    ob_end_clean();
    echo $output;
    $cache[ $key ] = $output;
    wp_cache_add( 'get__extjs_calendar', $cache, 'calendar' );
}

/**
 * SIDEBAR
 */ 

/**
 * COMMENT
 */ 
function list_comments($comment, $args, $depth) {
		$depth++;
		$GLOBALS['comment_depth'] = $depth;
		$GLOBALS['comment'] = $comment;
		extract($args, EXTR_SKIP);
?>
    <div <?php comment_class(empty( $args['has_children'] ) ? 'x-panel x-panel-mc' : 'parent x-panel x-panel-mc') ?> id="comment-<?php comment_ID() ?>">
		<div class="comment-author vcard">
		    <?php printf(__('<cite class="fn">%s</cite> <span class="says">says:</span>'), get_comment_author_link()) ?>
		</div>

		<div class="comment-meta commentmetadata">
            <a href="<?php echo htmlspecialchars( get_comment_link( $comment->comment_ID ) ) ?>"><?php printf(__('%1$s at %2$s'), get_comment_date(),  get_comment_time()) ?></a>
            <?php edit_comment_link(__('Edit'),'&nbsp;|&nbsp;','') ?> | <span class="reply"><?php comment_reply_link(array_merge( $args, array('add_below' => 'div-comment', 'depth' => $depth, 'max_depth' => $args['max_depth']))) ?></span>
        </div>
        <div class="x-clear"></div>
        <?php if ($args['avatar_size'] != 0) : ?>
		<div class="comment-gravatar">
    		<?php echo get_avatar( $comment, $args['avatar_size'] ); ?>
        </div>
        <?php endif; ?>

        <?php comment_text() ?>
        
		<div class="comment-waiting">
        <?php if ($comment->comment_approved == '0') : ?>
		    <?php _e('Your comment is awaiting moderation.') ?>
        <?php endif; ?>
        </div>
        <div class="x-clear"></div>
<?php
}
?>