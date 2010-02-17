<?php
// Do not delete these lines
	if (!empty($_SERVER['SCRIPT_FILENAME']) && 'comments.php' == basename($_SERVER['SCRIPT_FILENAME']))
		die ('Please do not load this page directly. Thanks!');

	if ( post_password_required() ) { ?>
		<p class="nocomments">This post is password protected. Enter the password to view comments.</p>
	<?php
		return;
	}
?>

<!-- You can start editing here. -->

<?php if ( have_comments() ) : ?>

    <div class="commentlist x-panel" style="margin-bottom: 20px; width: auto;" id="comments-panel">
        <div class="x-panel-tl">
            <div class="x-panel-tr">
                <div class="x-panel-tc">
                    <div class="x-panel-header x-unselectable" style="-moz-user-select: none;">
                        <span class="x-panel-header-text" id="comments">
                            <?php comments_number('No Responses', 'One Response', '% Responses' );?> to &#8220;<?php the_title(); ?>&#8221;
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="x-panel-bwrap">
            <div class="x-panel-body" style="width: auto;">

<?php 
    // for WordPress 2.7.0 or higher
    if (function_exists('wp_list_comments')) { 
        wp_list_comments('avatar_size=64&style=div&callback=list_comments');
    } else { // for WordPress 2.6.3 or lower
        foreach ($comments as $comment) {
            list_comments($comment, null, null);
        }
    }
?>

            </div>
        </div>

<?php 
$next_comments_link = get_next_comments_link('<div style="float:right;padding:5px;padding-right:20px">Newer Comments<div class="x-tab-scroller-right x-unselectable" style="height: 22px; -moz-user-select: none;"></div></div>');
$previous_comments_link = get_previous_comments_link('<div style="padding:5px;padding-left:20px"><div class="x-tab-scroller-left x-unselectable" style="height: 22px; -moz-user-select: none;"></div>Older Comments</div>');
if ( !empty($next_comments_link) || !empty($previous_comments_link) ) : ?>
        <div class="x-tab-panel-header x-unselectable x-tab-scrolling x-tab-scrolling-top" style="-moz-user-select: none; width: auto;height:22px;border-top:0">
            <?php if ( !empty($next_comments_link) ) { echo $next_comments_link; } ?>
            <?php if ( !empty($previous_comments_link) ) { echo $previous_comments_link; } ?>
        </div>
<?php endif; ?>
    </div>

 <?php else : // this is displayed if there are no comments so far ?>

	<?php if ('open' == $post->comment_status) : ?>
		<!-- If comments are open, but there are no comments. -->

    <?php else : // comments are closed ?>

    <div class="commentlist x-panel" style="margin-bottom: 20px; width: auto;" id="respond">
        <div class="x-panel-tl">
            <div class="x-panel-tr">
                <div class="x-panel-tc">
                    <div class="x-panel-header x-unselectable" style="-moz-user-select: none;">
                        <span class="x-panel-header-text" id="comments">
                            Comments are closed.
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="x-panel-bl x-panel-nofooter">
            <div class="x-panel-br">
                <div class="x-panel-bc"></div>
            </div>
        </div>
    </div>
    
	<?php endif; ?>

<?php endif; ?>


<?php if ('open' == $post->comment_status) : ?>
    <?php if ( get_option('comment_registration') && !$user_ID ) : ?>

    <div class="post x-panel" style="margin-bottom: 20px; width: auto;" id="respond">
        <div class="x-panel-tl">
            <div class="x-panel-tr">
                <div class="x-panel-tc">
                    <div class="x-panel-header x-unselectable" style="-moz-user-select: none;">
                        <span class="x-panel-header-text" id="comments">
                            You must be <a href="<?php echo get_option('siteurl'); ?>/wp-login.php?redirect_to=<?php echo urlencode(get_permalink()); ?>">logged in</a> to post a comment.
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="x-panel-bl x-panel-nofooter">
            <div class="x-panel-br">
                <div class="x-panel-bc"></div>
            </div>
        </div>
    </div>

    <?php else : ?>
                
    <form action="<?php echo get_option('siteurl'); ?>/wp-comments-post.php" method="post" id="commentform">

    <div class="x-panel" style="margin-bottom: 20px; width: auto;" id="respond">
        <div class="x-panel-tl">
            <div class="x-panel-tr">
                <div class="x-panel-tc">
                    <div class="x-panel-header x-unselectable" style="-moz-user-select: none;">
                        <span class="x-panel-header-text" id="comments">
                            <?php comment_form_title( 'Leave a Reply', 'Leave a Reply to %s' ); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="x-panel-bwrap">
            <div class="x-panel-ml">
                <div class="x-panel-mr">
                    <div class="x-panel-mc">
                        <div style="width: auto;" class="x-panel-body">

                                <div tabindex="-1" class="x-form-item">
                                    <div style="padding-left: 0pt;" class="x-form-element">
                                        <?php cancel_comment_reply_link(); ?>
                                    </div>
                                    <div class="x-form-clear-left"/></div>
                                </div>

                
                            <?php if ( $user_ID ) : ?>
                    
                                <div tabindex="-1" class="x-form-item">
                                    <div style="padding-left: 0pt;" class="x-form-element">
                                        Logged in as <a href="<?php echo get_option('siteurl'); ?>/wp-admin/profile.php"><?php echo $user_identity; ?></a>. <a href="<?php echo wp_logout_url(get_permalink()); ?>" title="Log out of this account">Log out &raquo;</a>
                                    </div>
                                    <div class="x-form-clear-left"/></div>
                                </div>
                    
                            <?php else : ?>

                            <div tabindex="1" class="x-form-item">
                                <label class="x-form-item-label" style="white-space:nowrap;overflow:hidden;width:30%">Name <?php if ($req) echo "(required)"; ?></label>
                                <div style="text-align:right;" class="x-form-element">
                                    <input class="x-form-text x-form-field" type="text" name="author" id="author" value="<?php echo $comment_author; ?>" size="22" tabindex="1" <?php if ($req) echo "aria-required='true'"; ?>  style="width:75%" />
                                </div>
                                <div class="x-form-clear-left"/></div>
                            </div>
                            
                            <div tabindex="2" class="x-form-item">
                                <label class="x-form-item-label" style="white-space:nowrap;overflow:hidden;width:30%">Mail (will not be published) <?php if ($req) echo "(required)"; ?></label>
                                <div style="text-align:right;" class="x-form-element">
                                    <input class="x-form-text x-form-field" type="text" name="email" id="email" value="<?php echo $comment_author_email; ?>" size="22" tabindex="2" <?php if ($req) echo "aria-required='true'"; ?>  style="width:75%" />
                                </div>
                                <div class="x-form-clear-left"/></div>        
                            </div>
                            
                            <div tabindex="2" class="x-form-item">
                                <label class="x-form-item-label" stylestyle="white-space:nowrap;overflow:hidden;width:30%">Website</label>
                                <div style="text-align:right;" class="x-form-element">
                                    <input class="x-form-text x-form-field"  name="url" id="url" value="<?php echo $comment_author_url; ?>" size="22" tabindex="3" style="width:75%" />
                                </div>
                                <div class="x-form-clear-left"/></div>        
                            </div>

                            <?php endif; ?>
                            <!-- 
                            <div tabindex="3" class="x-form-item">
                                <div style="padding-left: 0pt;" class="x-form-element">
                                    <small><strong>XHTML:</strong> You can use these tags: <code><?php echo allowed_tags(); ?></code></small>
                                </div>
                            </div>
                             -->
                            <div tabindex="4" class="x-form-item" style="text-align:right;">
                            <textarea name="comment" id="comment" cols="50" rows="10" class="x-form-textarea x-form-field" style="width:99%;" tabindex="4"></textarea>
                            </div>
                    
                        </div>
                    </div>
                </div>
            </div>
            <div class="x-panel-bl">
            <div class="x-panel-br">
            <div class="x-panel-bc">
                <div class="x-panel-footer">
                    <div class="x-panel-btns-ct">
                        <div class="x-panel-btns x-panel-btns-right">
                            
                            <a href="<?php bloginfo('comments_rss2_url'); ?>" class="feed"><?php _e('Comments <abbr title="Really Simple Syndication">RSS</abbr>'); ?></a>

                            <table cellspacing="0"><tbody><tr><td class="x-panel-btn-td">
                                <table cellspacing="0" cellpadding="0" border="0" class="x-btn-wrap x-btn" style="width: 125px;"><tbody><tr><td class="x-btn-left"><i></i></td><td class="x-btn-center"><em unselectable="on"><button name="submit" type="submit" id="submit" tabindex="5">Submit Comment</button></em></td><td class="x-btn-right"><i></i></td></tr></tbody></table>
                            </td></tr></tbody></table>
                        <div class="x-clear"></div>
                        </div>
                  </div>
                </div>
            </div>
            </div>
            </div>            
        </div>
    </div>
    <input type="hidden" name="redirect_to" value = "<?php echo  $_SERVER['REQUEST_URI']; ?>" />
    <?php comment_id_fields(); ?>
    <?php do_action('comment_form', $post->ID); ?>
                    
    </form>
                
    <?php endif; // If registration required and not logged in ?>
<?php endif; // if you delete this the sky will fall on your head ?>
