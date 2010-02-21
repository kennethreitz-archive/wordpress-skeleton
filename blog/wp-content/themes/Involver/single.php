<?php
/**
 * @package WordPress
 * @subpackage involver
 */

get_header();

?>
	
	<div id="content" class="narrowcolumn" role="main">
		<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
            <div <?php post_class() ?> id="post-<?php the_ID(); ?>">
            	
                <div class="postDate">
					<?php the_time("M jS,"); ?><span><?php the_time("Y"); ?></span>
                    <div class="sharePost">
                        <h4>Share it:</h4>
                        <div class="sharing">
                            <?php $urlHome = get_bloginfo('template_directory'); ?>
                            <a href="http://twitter.com/home?status=<?php the_permalink(); ?>" title="Click to share this post on Twitter" target="_blank" class="share twitter" rel="nofollow"><img src="<?php echo $urlHome; ?>/images/spacer.gif" width="48" height="48" alt="Share this article on Twitter" /></a> 
                            <a href="http://www.facebook.com/share.php?u=<?php the_permalink(); ?>&t=<?php the_title(); ?>" title="Click to share this post on Facebook" target="_blank" class="share facebook" rel="nofollow"><img src="<?php echo $urlHome; ?>/images/spacer.gif" width="48" height="48" alt="Share this article on Facebook" /></a> 
                            <a href="http://digg.com/submit?url=<?php the_permalink(); ?>&title=<?php the_title(); ?>" title="Click to share this post on Digg" target="_blank" class="share digg" rel="nofollow"><img src="<?php echo $urlHome; ?>/images/spacer.gif" width="48" height="48" alt="Share this article on Digg" /></a> 
                            <a href="http://delicious.com/post?url=<?php the_permalink(); ?>&title=<?php the_title(); ?>" title="Click to share this post on Delicious" target="_blank" class="share delicious" rel="nofollow"><img src="<?php echo $urlHome; ?>/images/spacer.gif" width="48" height="48" alt="Share this article on Delicious" /></a> 
                            <a href="http://stumbleupon.com/submit?url=<?php the_permalink(); ?>&title=<?php the_title(); ?>" title="Click to share this post on StumbleUpon" target="_blank" class="share su" rel="nofollow"><img src="<?php echo $urlHome; ?>/images/spacer.gif" width="48" height="48" alt="Share this article on StumbleUpon" /></a> 
                        </div>
                    </div>
                </div>
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
                        <div class="postTags">
                        	Tags:
                            <strong><?php the_tags('',', ',''); ?></strong>
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
						<?php the_content('<p class="serif">Read the rest of this entry &raquo;</p>'); ?>
	                    <?php wp_link_pages(array('before' => '<p><strong>Pages:</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
					</div>
                    <br clear="all" />
				</div>
                <br clear="all" />
            </div>
    		<div class="commentZone">
        		<?php comments_template(); ?>
            </div>
        
        <div class="navigation" style="margin-right:20px;">
            <div class="alignleft"><?php previous_post_link('%link') ?></div>
            <div class="alignright"><?php next_post_link('%link') ?></div>
            <br clear="all" />
        </div>
        <?php endwhile; else: ?>
    
            <p>Sorry, no posts matched your criteria.</p>
    
        <?php endif; ?>
	</div>
<?php get_sidebar(); ?>	
<?php get_footer(); ?>
