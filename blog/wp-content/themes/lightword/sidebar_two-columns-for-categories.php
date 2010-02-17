<div class="content-sidebar">
<?php

/* Widgetized sidebar, if you have the plugin installed. */

if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar() ) : ?>


<h3><?php _e('Pages','lightword'); ?></h3>
<ul>	
<?php wp_list_pages('title_li='); ?>
</ul>

<h3><?php _e('Categories','lightword'); ?></h3>
<?php
$cats = explode("<br />",wp_list_categories('title_li=&echo=0&depth=1&style=none'));
$cat_n = count($cats) - 1;
for ($i=0;$i<$cat_n;$i++):
if ($i<$cat_n/2):
$cat_left = $cat_left.'<li>'.$cats[$i].'</li>';
elseif ($i>=$cat_n/2):
$cat_right = $cat_right.'<li>'.$cats[$i].'</li>';
endif;
endfor;
?>
<ul class="left">
<?php echo $cat_left;?>
</ul>
<ul class="right">
<?php echo $cat_right;?>
</ul>
<div class="clear"></div>

<h3><?php _e('Blogroll','lightword'); ?></h3>
<ul>	
<?php wp_list_bookmarks('title_li=&categorize=0'); ?>
</ul>

<h3><?php _e('Archive','lightword'); ?></h3>
<ul>	
<?php wp_get_archives('type=monthly'); ?>
</ul>

<h3><?php _e('Meta','lightword'); ?></h3>
<ul>	
<?php wp_register(); ?>
<li><?php wp_loginout(); ?></li>
<li><a href="<?php bloginfo('rss2_url'); ?>" title="<?php _e('Syndicate this site using RSS','lightword'); ?>"><?php _e('<abbr title="Really Simple Syndication">RSS</abbr>','lightword'); ?></a></li>
<li><a href="<?php bloginfo('comments_rss2_url'); ?>" title="<?php _e('The latest comments to all posts in RSS','lightword'); ?>"><?php _e('Comments <abbr title="Really Simple Syndication">RSS</abbr>','lightword'); ?></a></li>
<?php wp_meta(); ?>
</ul>

<?php endif; ?>
</div>