<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head profile="http://gmpg.org/xfn/11">
<title>
<?php if (is_home()) { echo bloginfo('name');
			} elseif (is_404()) {
			echo '404 Not Found';
			} elseif (is_category()) {
			echo 'Category:'; wp_title('');
			} elseif (is_search()) {
			echo 'Search Results';
			} elseif ( is_day() || is_month() || is_year() ) {
			echo 'Archives:'; wp_title('');
			} else {
			echo wp_title('');
			}
			?>
</title>
<meta http-equiv="content-type" content="<?php bloginfo('html_type') ?>; charset=<?php bloginfo('charset') ?>" />
<meta name="description" content="<?php bloginfo('description') ?>" />
<?php if(is_search()) { ?>
<meta name="robots" content="noindex, nofollow" />
<?php }?>
<link rel="stylesheet" type="text/css" href="<?php bloginfo('stylesheet_url'); ?>" media="screen" />
<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
<?php wp_head(); ?>
</head>
<body>
<!-- header START -->
<div class="container_12">
<div id="header-wrap">
  <div id="nav-bar">
    <div id="navbar-left">
      <ul id="nav">
        <li><a href="<?php echo get_settings('home'); ?>">Home</a></li>
		<?php wp_list_pages('include=63,119&title_li='); ?>
      </ul>
    </div>
    <div id="navbar-right"> <a href="<?php bloginfo('rss_url'); ?>"><img src="<?php bloginfo('template_url'); ?>/images/rss.gif" alt="Subscribe to <?php bloginfo('name'); ?>" /></a>
    </div>
  </div>
  <div class="header">
    <div id="search-bar">
      <?php include (TEMPLATEPATH . '/searchform.php'); ?>
    </div>
    <h1><a href="<?php echo get_option('home'); ?>/">
      <?php bloginfo('name'); ?>
      </a></h1>
    <div class="description">
      <?php bloginfo('description'); ?>
    </div>
    <div style="clear: both"></div>
  </div>
</div>
<!-- header END -->
<div style="clear: both"></div>