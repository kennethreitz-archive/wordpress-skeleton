<!-- begin sidebar -->
<div id="sidebar-wrap" class="grid_4">
<div class="postauthor">
<?php echo get_avatar( get_the_author_id() , 80 ); ?>
Author: <a href="<?php the_author_url(); ?>">
<?php the_author_firstname(); ?> <?php the_author_lastname(); ?></a><br>
<?php the_author_description(); ?>
<p><a href="<?php bloginfo('url'); ?>/?author=<?php the_author_ID(); ?>">
<?php the_author_firstname(); ?> <?php the_author_lastname(); ?> has posted 
<?php the_author_posts(); ?> articles.</a></p>
</div>
<div id="sidebar">
 <ul>
 <?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar() ) : ?>
    <li id="categories">
      <h2>Categories</h2>
      <ul>
        <?php wp_list_categories('sort_column=name&title_li='); ?>
      </ul>
    </li>
    <li id="archives">
      <h2>Archives</h2>
      <ul>
        <?php wp_get_archives('type=monthly'); ?>
      </ul>
    </li>
    <li id="links">
      <h2>Links</h2>
      <ul>
        <?php get_links(-1, '<li>', '</li>', ' - '); ?>
      </ul>
    </li>
    <li id="meta">
      <h2>Meta</h2>
      <ul>
        <?php wp_register(); ?>
        <li>
          <?php wp_loginout(); ?>
        </li>
        <li><a href="http://www.wordpress.org/">WordPress</a></li>
        <?php wp_meta(); ?>
        <li><a href="http://validator.w3.org/check?uri=referer">XHTML</a></li>
      </ul>
    </li>
    <?php endif; ?>
  </ul>
</div>
</div>
<!-- end sidebar -->