<?php
	global $dsq_response, $dsq_version;
?>

<div id="disqus_thread">
	<div id="dsq-content">
		<ul id="dsq-comments">
<?php foreach ( $dsq_response['posts'] as $comment ) : ?>
			<div id="comment-<?php echo $comment['id']; ?>"></div>

			<li id="dsq-comment-<?php echo $comment['id']; ?>">
				<div id="dsq-comment-header-<?php echo $comment['id']; ?>" class="dsq-comment-header">
					<cite id="dsq-cite-<?php echo $comment['id']; ?>">
<?php if($comment['user']['url']) : ?>
						<a id="dsq-author-user-<?php echo $comment['id']; ?>" href="<?php echo $comment['user']['url']; ?>" target="_blank" rel="nofollow"><?php echo $comment['user']['display_name']; ?></a>
<?php else : ?>
						<span id="dsq-author-user-<?php echo $comment['id']; ?>"><?php echo $comment['user']['display_name']; ?></span>
<?php endif; ?>
					</cite>
				</div>
				<div id="dsq-comment-body-<?php echo $comment['id']; ?>" class="dsq-comment-body">
					<div id="dsq-comment-message-<?php echo $comment['id']; ?>" class="dsq-comment-message"><?php echo $comment['message']; ?></div>
				</div>
			</li>
<?php endforeach; ?>
		</ul>
	</div>
</div>

<a href="http://disqus.com" class="dsq-brlink">blog comments powered by <span class="logo-disqus">Disqus</span></a>

<script type="text/javascript" charset="utf-8">
	var disqus_url = '<?php echo get_permalink(); ?> ';
	var disqus_container_id = 'disqus_thread';
	var facebookXdReceiverPath = '<?php echo DSQ_PLUGIN_URL . '/xd_receiver.htm' ?>';
</script>

<script type="text/javascript" charset="utf-8">
	var DsqLocal = {
		'trackbacks': [
<?php
	$count = 0;
	foreach ($comments as $comment) {
		$comment_type = get_comment_type();
		if ( $comment_type != 'comment' ) {
			if( $count ) { echo ','; }
?>
			{
				'author_name':	'<?php echo htmlspecialchars(get_comment_author(), ENT_QUOTES); ?>',
				'author_url':	'<?php echo htmlspecialchars(get_comment_author_url(), ENT_QUOTES); ?>',
				'date':			'<?php comment_date('m/d/Y h:i A'); ?>',
				'excerpt':		'<?php echo str_replace(array("\r\n", "\n", "\r"), '<br />', htmlspecialchars(get_comment_excerpt(), ENT_QUOTES)); ?>',
				'type':			'<?php echo $comment_type; ?>'
			}
<?php
			$count++;
		}
	}
?>
		],
		'trackback_url': '<?php trackback_url(); ?>'
	};
</script>

<script type="text/javascript" charset="utf-8" src="http://<?php echo strtolower(get_option('disqus_forum_url')); ?>.<?php echo DISQUS_DOMAIN; ?>/disqus.js?v=2.0&slug=<?php echo $dsq_response['thread_slug']; ?>&pname=wordpress&pver=<?php echo $dsq_version; ?>"></script>
