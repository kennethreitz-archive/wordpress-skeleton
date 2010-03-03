<?php
/**
 * @package WordPress
 * @subpackage involver
 */

get_header();
?>

	<div id="content" class="narrowcolumn">

  <?php if (have_posts()) : while (have_posts()) : the_post(); ?>

		<div class="post" id="post-<?php the_ID(); ?>">
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
                    <p class="attachment"><a href="<?php echo wp_get_attachment_url($post->ID); ?>"><?php echo wp_get_attachment_image( $post->ID, 'medium' ); ?></a></p>
                    <div class="caption"><?php if ( !empty($post->post_excerpt) ) the_excerpt(); // this is the "caption" ?></div>
                    <?php the_content('<p class="serif">Read the rest of this entry &raquo;</p>'); ?>
                </div>
                <br clear="all" />
            </div>
            <br clear="all" />
            <div class="commentZone">
        		<?php comments_template(); ?>
            </div>
		</div>

			

	<?php endwhile; else: ?>

		<p>Sorry, no attachments matched your criteria.</p>

<?php endif; ?>

	</div>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
