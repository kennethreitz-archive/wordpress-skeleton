<?php get_header(); ?>
<div id="content" class="grid_8">
  <?php if (have_posts()) : while (have_posts()) : the_post(); 
  if( $post->ID == $do_not_duplicate ) continue; update_post_caches($posts); ?>
  <div id="post-entries">
  <div <?php post_class() ?> id="post-<?php the_ID(); ?>">
    <h3 class="storytitle"><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title(); ?>">
      <?php the_title(); ?>
       &raquo;</a></h3>

    <div class="meta"> Posted by:
      <?php the_author() ?>
      on
      <?php the_date(); ?>
      @
      <?php the_time() ?>
      <?php edit_post_link(__('Edit This')); ?>
      <br />
      <?php _e("Filed under:"); ?>
      <?php the_category(',') ?>
    </div>
    <div class="clear"></div>
  </div>
  </div>
  <?php endwhile; else: ?>
  <p>
    <?php _e('Sorry, no posts matched your criteria.'); ?>
  </p>
  <?php endif; ?>
  <?php posts_nav_link(' &#8212; ', __('&laquo; Newer Posts'), __('Older Posts &raquo;')); ?>
</div>
<?php get_sidebar(); ?>
<?php get_footer(); ?>