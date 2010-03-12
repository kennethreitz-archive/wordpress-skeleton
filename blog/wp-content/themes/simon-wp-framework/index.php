<?php get_header(); ?>
<!-- content start -->

  <?php $my_query = new WP_Query('category_name=featured&posts_per_page=1');
  while ($my_query->have_posts()) : $my_query->the_post();
  $do_not_duplicate = $post->ID; ?>
  <div class="grid_12">
  <div id="featured-post" class="">
    <h1 class="storytitle"><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title(); ?>">
      <?php the_title(); ?> &raquo;
      </a></h1>
    <h3>
      <?php
$myExcerpt = get_the_excerpt();
if ($myExcerpt != '') {
    // Some string manipulation performed
}
echo $myExcerpt; // Outputs the processed value to the page
?>
    </h3>
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
</div>
</div>
<div id="featured-sticker">Featured</div>
<?php endwhile; ?>

<div id="content" class="grid_8">
  <?php if (have_posts()) : while (have_posts()) : the_post(); 
  if( $post->ID == $do_not_duplicate ) continue; update_post_caches($posts); ?>
  <div id="post-entries">
  <div <?php post_class() ?> id="post-<?php the_ID(); ?>">
    <h3 class="storytitle"><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title(); ?>">
      <?php the_title(); ?>
       &raquo;</a></h3>
    <div class="storycontent">
      <?php
$myExcerpt = get_the_excerpt();
if ($myExcerpt != '') {
    // Some string manipulation performed
}
echo $myExcerpt; // Outputs the processed value to the page
?>
    </div>
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
      <?php wp_link_pages(); ?>
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
