<?php
	if (defined('ABSPATH') && defined('WP_UNINSTALL_PLUGIN') 
	&& strtolower(WP_UNINSTALL_PLUGIN) === 'similar-posts/similar-posts.php') {
		global $wpdb, $table_prefix;
		delete_option('similar-posts');
		delete_option('similar-posts-feed');
		delete_option('widget_rrm_similar_posts');
		$table_name = $table_prefix . 'similar_posts';
		$wpdb->query("DROP TABLE `$table_name`");
	}
?>