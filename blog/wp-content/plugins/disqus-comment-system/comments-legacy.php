<?php
	$permalink = get_permalink();
	$title = get_the_title();
	$excerpt = get_the_excerpt();
?>
<div id="disqus_thread"></div>
<script type="text/javascript">
	var disqus_url = '<?php echo $permalink; ?> ';
	var disqus_title = '<?php echo $title; ?>';
	var disqus_message = '<?php echo $excerpt; ?>';
</script>
<script type="text/javascript" src="<?php echo DISQUS_URL; ?>/forums/<?php echo get_option('disqus_forum_url'); ?>/embed.js"></script>
<noscript><a href="<?php echo 'http://' . get_option('disqus_forum_url') . '.' . DISQUS_DOMAIN . '/?url=' . $the_permalink; ?>">View the entire comment thread.</a></noscript>
