<?php
if (!empty($_SERVER['SCRIPT_FILENAME']) && 'comments.php' == basename($_SERVER['SCRIPT_FILENAME']))
	die ('Please do not load this page directly. Thanks!');

	if (!empty($post->post_password)) { // if there's a password
		if ($_COOKIE['wp-postpass_' . COOKIEHASH] != $post->post_password) {
		  ?>
<p class="nocomments"><?php _e('This post is password protected. Enter the password to view comments.','lightword');  ?></p>
<?php return; } }?>
<a name="comments"></a>
<div id="tabsContainer">
<a href="#" class="tabs selected"><span><?php _e('Comments','lightword'); ?></span></a>
<span class="subscribe_comments"><?php post_comments_feed_link(__('( subscribe to comments on this post )','lightword')); ?></span>
<div class="clear_tab"></div>
<div class="tab-content selected">
<?php if ( $comments ) : ?>
<div id="comentarii">
<ol class="commentlist">

<?php foreach ($comments as $comment) : ?>
<?php $comment_type = get_comment_type(); ?>
<?php if($comment_type == 'comment') { ?>
<li class="list <?php echo $oddcomment; ?><?php if ($comment->comment_author_email == get_the_author_email()) { echo 'author_comment'; } ?>" id="comment-<?php comment_ID() ?>">
<cite>
<?php echo get_avatar( $comment, 25 ); ?>
<span class="author"><?php comment_author_link() ?></span><br /><span class="time"><?php comment_time() ?></span> on <a href="#comment-<?php comment_ID() ?>" title=""><?php comment_date(__('F jS, Y','lightword')) ?></a> <?php edit_comment_link(__('edit this comment','lightword'),'&nbsp;&nbsp;',''); ?>
</cite>

<div class="commenttext"><?php comment_text() ?></div>

<?php if ($comment->comment_approved == '0') : ?>

<em><?php _e('Your comment is awaiting moderation.','lightword'); ?></em>

<?php endif; ?>
</li>
<?php
/* Changes every other comment to a different class */
$oddcomment = ( empty( $oddcomment ) ) ? 'alt ' : '';
?>
<?php } else { $trackback = true; } ?>
<?php endforeach; /* end for each comment */ ?>
</ol>
</div>

<?php else : // If there are no comments yet ?>
<p class="no"><?php _e('No comments yet.','lightword'); ?></p>
<?php endif; ?>

<?php if ( comments_open() ) : ?>
<a name="respond"></a>
<h2 id="postcomment"><?php _e('Leave a comment','lightword'); ?></h2>

<?php if ( get_option('comment_registration') && !$user_ID ) : ?>
<p><?php printf(__('You must be <a href="%s">logged in</a> to post a comment.','lightword'), get_option('siteurl')."/wp-login.php?redirect_to=".urlencode(get_permalink()));?></p>
<?php else : ?>

<form action="<?php echo get_option('wpurl'); ?>/wp-comments-post.php" method="post" id="commentform">

<?php if ( $user_ID ) : ?>

<p><?php printf(__('Logged in as %s.','lightword'), '<a href="'.get_option('siteurl').'/wp-admin/profile.php">'.$user_identity.'</a>'); ?> <a href="<?php echo wp_logout_url(get_permalink()); ?>" title="<?php _e('Log out of this account','lightword') ?>"><?php _e('Log out &raquo;','lightword'); ?></a></p>

<?php else : ?>

<p><input type="text" name="author" id="author" value="<?php echo $comment_author; ?>" size="22" tabindex="1" />
<label for="author"><small><?php _e('Name','lightword'); ?> <?php if ($req) _e('(required)','lightword'); ?></small></label></p>

<p><input type="text" name="email" id="email" value="<?php echo $comment_author_email; ?>" size="22" tabindex="2" />
<label for="email"><small><?php _e('Mail (will not be published)','lightword');?> <?php if ($req) _e('(required)'); ?></small></label></p>

<p><input type="text" name="url" id="url" value="<?php echo $comment_author_url; ?>" size="22" tabindex="3" />
<label for="url"><small><?php _e('Website','lightword'); ?></small></label></p>

<?php endif; ?>

<!--<p><small><strong>XHTML:</strong> <?php printf(__('You can use these tags: %s','lightword'), allowed_tags()); ?></small></p>-->

<p><textarea name="comment" id="comment" cols="100%" rows="10" tabindex="4"></textarea></p>

<p><input name="submit" type="submit" id="submit" tabindex="5" value="<?php echo attribute_escape(__('Submit','lightword')); ?>" />

<input type="hidden" name="comment_post_ID" value="<?php echo $id; ?>" />
</p>
<?php do_action('comment_form', $post->ID); ?>

</form>

<?php endif; // If registration required and not logged in ?>
<?php else : // Comments are closed ?>
<p class="no"><?php _e('Sorry, the comment form is closed at this time.','lightword'); ?></p>
<?php endif; ?>
</div>
</div>