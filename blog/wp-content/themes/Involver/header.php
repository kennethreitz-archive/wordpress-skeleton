<?php
/**
 * @package WordPress
 * @subpackage involver
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>

<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />

<title><?php wp_title('&laquo;', true, 'right'); ?> <?php bloginfo('name'); ?></title>

<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

<?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' ); ?>

<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<div id="page">


<div id="header" role="banner">
	<div class="topHeader">
        <ul class="meniu">
        	<?php if (is_home()) { ?>
        		<li class="page_item current_page_item"><a href="<?php bloginfo('url'); ?>/">Home</a></li>
            <?php } else { ?>
            	<li class="page_item"><a href="<?php bloginfo('url'); ?>/">Home</a></li>
            <?php } ?>
			<?php wp_list_pages('title_li='); ?>
		</ul>
        <div class="rss">
        	<a href="<?php bloginfo('rss2_url'); ?>" class="feed">Subscribe To<span>Our Feed</span></a>
            <a href="http://www.twitter.com/filecluster" class="twit" target="_blank">Follow Us<span>On Twitter</span></a>
        </div>
        <br clear="all" />
    </div>
    <div class="topBar">
		<div class="logo">
            <h1><a href="<?php echo get_option('home'); ?>/"><?php bloginfo('name'); ?></a></h1>
            <div class="description"><?php bloginfo('description'); ?></div>
        </div>
        <div class="fSearch">
        	<form id="searchform" action="<?php bloginfo('url'); ?>/" method="get" role="search">
                <input id="s" type="text" name="s" value="type here &amp; press enter" onfocus="this.value=''" />
                <input id="searchsubmit" class="hidden" type="submit" value=""/>
	        </form>
        </div>
        <br clear="all" />
    </div>
</div>
<hr />
<div class="container">

