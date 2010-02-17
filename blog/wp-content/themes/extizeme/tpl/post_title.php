    <div class="x-panel-tl">
        <div class="x-panel-tr">
            <div class="x-panel-tc">
                <div class="x-panel-header x-unselectable" style="-moz-user-select: none;">
                    <span class="x-panel-header-text">

        <?php if (is_attachment()) :?>
            <a class="post-title" href="<?php echo get_permalink($post->post_parent); ?>" rev="attachment"><?php echo get_the_title($post->post_parent); ?></a> &raquo;
       <?php endif;?>
        
        <?php if (is_archive()) {?>
 	  <?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
        <a class="post-title"  href="<?php echo $_SERVER['REQUEST_URI']; ?>">
 	  <?php /* If this is a category archive */ if (is_category()) { ?>
		Archive for the &#8216;<?php single_cat_title(); ?>&#8217; Category
 	  <?php /* If this is a tag archive */ } elseif( is_tag() ) { ?>
		Posts Tagged &#8216;<?php single_tag_title(); ?>&#8217;
 	  <?php /* If this is a daily archive */ } elseif (is_day()) { ?>
		Archive for <?php the_time('F jS, Y'); ?>
 	  <?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
		Archive for <?php the_time('F, Y'); ?>
 	  <?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
		Archive for <?php the_time('Y'); ?>
	  <?php /* If this is an author archive */ } elseif (is_author()) { ?>
		Author Archive
 	  <?php /* If this is a paged archive */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
		Blog Archives
 	  <?php } ?>
        </a>
        <?php } elseif (is_search()) { ?>
		<a class="post-title" href="<?php echo bloginfo('home') . "?s=" . get_search_query(); ?>">Search Resultst for &#8216;<?php echo $_GET['s'];?>&#8217;</a>
 	  <?php } else {?>
            <a class="post-title" href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a>
 	  <?php }?>
        
                    </span>
                </div>
            </div>
        </div>
    </div>
