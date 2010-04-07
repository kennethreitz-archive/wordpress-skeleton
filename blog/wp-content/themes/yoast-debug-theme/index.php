<?php
get_header();
?>
	<div class="box double">
		<h2>Page type</h2>
		<table>
			<tbody>
		<?php
		foreach (array('is_single','is_preview','is_page','is_archive','is_date','is_year','is_month','is_day','is_time','is_author','is_category','is_tag','is_tax','is_search','is_feed','is_comment_feed','is_trackback','is_home','is_404','is_comments_popup','is_admin','is_attachment','is_singular','is_robots','is_posts_page','is_paged') as $is) {
			show_var($is, $wp_query->$is, true);
		}		?>
			</tbody>
		</table>
	</div>

	<div class="box double">
		<h2>Query</h2>
		<table>
			<tbody>
		<?php
		foreach ($wp_query->query as $var => $val) {
			show_var($var, $val, true);
		}	
		if (isset($post_count))
			show_var('post_count', $post_count);
		if (isset($found_posts))
			show_var('found_posts', $found_posts);	
		?>
			</tbody>
		</table>
	</div>
	
	<div class="box double">
		<h2>Query Vars set</h2>
		<table>
			<tbody>
		<?php
		foreach ($wp_query->query_vars as $var => $val) {
			show_var($var, $val, true);
		}		?>
			</tbody>
		</table>
	</div>

	<div class="box double">
		<h2>SQL Query</h2>
		<p><?php echo $wp_query->request ?></p>
	</div>
<?php
get_footer();
?>