<?php
/**
 * @package WordPress
 * @subpackage involver
 */

get_header(); ?>

	<div id="content" class="narrowcolumn" role="main">

		<?php if (have_posts()) : ?>

 	  <?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
 	  <?php /* If this is a category archive */ if (is_category()) { ?>
		<h2 class="pagetitle">Archive for the &#8216;<strong><?php single_cat_title(); ?></strong>&#8217; Category</h2>
 	  <?php /* If this is a tag archive */ } elseif( is_tag() ) { ?>
		<h2 class="pagetitle">Posts Tagged &#8216;<strong><?php single_tag_title(); ?></strong>&#8217;</h2>
 	  <?php /* If this is a daily archive */ } elseif (is_day()) { ?>
		<h2 class="pagetitle">Archive for <strong><?php the_time('F jS, Y'); ?></strong></h2>
 	  <?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
		<h2 class="pagetitle">Archive for <strong><?php the_time('F, Y'); ?></strong></h2>
 	  <?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
		<h2 class="pagetitle">Archive for <strong><?php the_time('Y'); ?></strong></h2>
	  <?php /* If this is an author archive */ } elseif (is_author()) { ?>
      	<h2 class="pagetitle"><strong>Author</strong> Archive</h2>
        <div class="authPage">
        	<div class="autor">
                <div class="picture">
                    <?php 
                        global $wp_query;
                        $curauth = $wp_query->get_queried_object();
                        $urlHome = get_bloginfo('template_directory');
                        echo get_avatar("$curauth->user_email", $size = '70', $default = $urlHome . '/images/default_avatar_author.gif' ); 
                    ?>
                </div>
                <div class="info">
                    <span class="name"><?php echo $curauth->display_name; ?></span>
                    <span class="site"><a href="<?php echo $curauth->user_url; ?>" target="_blank">Visit Authors Website</a></span>
                    <span class="desc"><?php echo $curauth->user_description; ?></span>
                </div>
                <br clear="all" />
            </div>
        </div>
		
 	  <?php /* If this is a paged archive */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
		<h2 class="pagetitle"><strong>Blog</strong> Archives</h2>
 	  <?php } ?>


		<div class="navigation">
			<div class="alignleft"><?php next_posts_link('Older Entries') ?></div>
			<div class="alignright"><?php previous_posts_link('Newer Entries') ?></div>
            <br clear="all" />
		</div>

		<?php while (have_posts()) : the_post(); ?>
		<div <?php post_class() ?>>
        	<div class="postDate"><?php the_time("M jS,"); ?><span><?php the_time("Y"); ?></span></div>
            <div class="postHeader">
                <h2><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
                
                <div class="postMeta">
                	<?php if (!is_author()) { ?>
                    <div class="autor">
                        <div class="image">
                        <?php 
                            global $wp_query;
                            $curauth =  get_userdata($post->post_author);
                            $urlHome = get_bloginfo('template_directory');
                            echo get_avatar("$curauth->user_email", $size = '30', $default = $urlHome . '/images/default_avatar_author.gif' ); 
                        ?>
                        </div>
                        <div class="text">
                            Published by:
                            <strong><?php the_author_posts_link(); ?></strong>
                        </div>
                        <br clear="all" />
                    </div>
                    <?php } ?>
                    <div class="postComm">
                        <span class="nr"><?php comments_popup_link('0', '1', '%'); ?></span>
                        Comments so far
                        <a href="<?php the_permalink() ?>#respond" class="reply">Leave a reply</a>
                        <br clear="all" />
                    </div>
                    <div class="postCats">
                        Published in:
                        <strong><?php the_category(', ') ?></strong>
                    </div>
                </div>
                <?php 
                    $pic = get_post_meta($post->ID, 'post-img', true);
                    if ($pic) {
                ?>
                <div class="postImg">
                    <a href="<?php the_permalink(); ?>" rel="bookmark" title="Visualize <?php the_title_attribute(); ?>"><img src="<?php echo $pic; ?>" alt="<?php the_title(); ?>" width="400" /></a>
                </div>
                <?php } ?>
                <div class="entry <?php if ($pic) { echo "big"; } else { echo "small"; }?>">
                    <?php the_excerpt(); ?>
                </div>
                <br clear="all" />
            </div>
            <br clear="all" />
		</div>

		<?php endwhile; ?>

		<div class="navigation">
			<div class="alignleft"><?php next_posts_link('Older Entries') ?></div>
			<div class="alignright"><?php previous_posts_link('Newer Entries') ?></div>
		</div>
	<?php else :

		if ( is_category() ) { // If this is a category archive
			printf("<h2 class='center'>Sorry, but there aren't any posts in the %s category yet.</h2>", single_cat_title('',false));
		} else if ( is_date() ) { // If this is a date archive
			echo("<h2>Sorry, but there aren't any posts with this date.</h2>");
		} else if ( is_author() ) { // If this is a category archive
			$userdata = get_userdatabylogin(get_query_var('author_name'));
			printf("<h2 class='center'>Sorry, but there aren't any posts by %s yet.</h2>", $userdata->display_name);
		} else {
			echo("<h2 class='center'>No posts found.</h2>");
		}
		get_search_form();

	endif; ?>

	</div>

<?php get_sidebar(); ?>

<?php get_footer(); ?>
