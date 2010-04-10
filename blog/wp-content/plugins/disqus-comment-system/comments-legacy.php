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
<noscript>Please enable JavaScript to view the <a href="<?php echo 'http://disqus.com/?ref_noscript=' . get_option('disqus_forum_url') ?>">comments powered by Disqus.</a></noscript>
