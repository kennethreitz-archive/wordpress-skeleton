<?php
//add_action( 'wp_dashboard_setup', 'scoper_add_dashboard_widgets' );
add_action ( 'right_now_table_end', 'scoper_right_now_pending' );

function scoper_right_now_pending() {
	if ( $num_posts = wp_count_posts( 'post' ) ) {
		if ( ! empty($num_posts->pending) ) {
			echo "\n\t".'<tr>';
	
			$num = number_format_i18n( $num_posts->pending );
			$text = _n( 'Pending Post', 'Pending Posts', intval($num_posts->pending), 'scoper' );
	
			$num = "<a href='edit.php?post_status=pending'><span class='pending-count'>$num</span></a>";
			$text = "<a class='waiting' href='edit.php?post_status=pending'>$text</a>";
	
			echo '<td class="first b b-posts b-waiting">' . $num . '</td>';
			echo '<td class="t posts">' . $text . '</td>';
			echo '<td class="b"></td>';
			echo '<td class="last t"></td>';
			echo "</tr>\n\t";
		}
	}

	if ( $num_pages = wp_count_posts( 'page' ) ) {
		if ( ! empty($num_pages->pending) ) {
			echo "\n\t".'<tr>';
	
			$num = number_format_i18n( $num_pages->pending );
			$text = _n( 'Pending Page', 'Pending Pages', intval($num_pages->pending), 'scoper' );
	
			$num = "<a href='edit-pages.php?post_status=pending'><span class='pending-count'>$num</span></a>";
			$text = "<a class='waiting' href='edit-pages.php?post_status=pending'>$text</a>";
	
			echo '<td class="first b b_pages b-waiting">' . $num . '</td>';
			echo '<td class="t posts">' . $text . '</td>';
			echo '<td class="b"></td>';
			echo '<td class="last t"></td>';
			echo "</tr>\n\t";
		}
	}
}

/*
function scoper_add_dashboard_widgets() {
	if ( awp_ver( '2.7' ) )
		wp_add_dashboard_widget( 'scoper_dashboard_stuff', __('Role Scoper', 'scoper'), 'scoper_dashboard_stuff' );	
}

function scoper_dashboard_stuff() {

}
*/

?>