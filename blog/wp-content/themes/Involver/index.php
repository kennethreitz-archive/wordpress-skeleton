<?php
/**
 * @package WordPress
 * @subpackage involver
 */

get_header(); ?>

	<div id="content" class="narrowcolumn" role="main">
    
    <?php if (is_home()) { ?>
    	<h2 class="pagetitle">Latest Articles</h2>
    <?php } ?>

	<?php if (have_posts()) : ?>

		<?php while (have_posts()) : the_post(); ?>

			<div <?php post_class() ?> id="post-<?php the_ID(); ?>">
            	<div class="postDate"><?php the_time("M jS,"); ?><span><?php the_time("Y"); ?></span></div>
				<div class="postHeader">
                    <h2><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
                    
                    <div class="postMeta">
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
			<div class="alignleft"><?php next_posts_link('&laquo; Older Entries') ?></div>
			<div class="alignright"><?php previous_posts_link('Newer Entries &raquo;') ?></div>
            <br clear="all" />
		</div>

	<?php else : ?>

		<h2 class="pagetitle">Not Found</h2>
		<p class="center">Sorry, but you are looking for something that isn't here.</p>
		<?php get_search_form(); ?>

	<?php endif; ?>

	</div>

<?php get_sidebar(); ?>

<?php get_footer(); ?>
