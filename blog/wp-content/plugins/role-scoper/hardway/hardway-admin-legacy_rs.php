<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();
	
// This file is only loaded for WP < 2.7

if ( is_admin() ) {
	add_filter('dashboard_count_sentence', array('ScoperAdminHardwayLegacy', 'flt_dashboard_count_sentence'), 10, 6); 
}

class ScoperAdminHardwayLegacy {
	function flt_dashboard_count_sentence($sentence, $post_type_text, $cats_text, $tags_text, $pending_text, $comm_text = '' ) { 
		global $current_user;

		// we have no need to meddle unless user lacks one of the caps blog-wide
		//
		// ... on 2nd thought, redo posts bit for everyone so we can include privates and draft/future/pending pages in totals
		//
		//$check_caps = array( 'edit_posts', 'edit_pages', 'publish_posts', 'moderate_comments' );
		//if ( ! array_diff_key( array_flip($check_caps), $current_user->allcaps ) )
		//	return $sentence;
		
		global $scoper;
		$scoper->honor_any_objrole = true;
		
		$can_edit_posts = current_user_can('edit_posts');
		$can_edit_pages = current_user_can('edit_pages');
		
		//----------------------------------- begin WP core code from wp-admin/index.php ----------------------------
		$num_posts = wp_count_posts( 'post' );
		$num_pages = wp_count_posts( 'page' );
		
		// requery comment count so we can truncate the message if there are no comments
		$num_comm = get_comment_count( );
		
		$post_type_texts = array();
		
		if ( !empty($num_posts->publish) ) { // with feeds, anyone can tell how many posts there are.  Just unlink if !current_user_can			// note: Role Scoper mod - include private posts, pages in total
			$post_text = sprintf( ScoperAdminHardwayLegacy::_n_( '%s post', '%s posts', $num_posts->publish + $num_posts->private ), number_format_i18n( $num_posts->publish + $num_posts->private ) );
			$post_type_texts[] = $can_edit_posts ? "<a href='edit.php'>$post_text</a>" : $post_text;
		}
		if ( $can_edit_pages && !empty($num_pages->publish) ) { // how many pages is not exposed in feeds.  Don't show if !current_user_can
			$post_type_texts[] = '<a href="edit-pages.php">'.sprintf( ScoperAdminHardwayLegacy::_n_( '%s page', '%s pages', $num_pages->publish + $num_pages->private ), number_format_i18n( $num_pages->publish + $num_pages->private ) ).'</a>';
		}
		if ( $can_edit_posts && !empty($num_posts->draft) ) {
			$post_type_texts[] = '<a href="edit.php?post_status=draft">'.sprintf( ScoperAdminHardwayLegacy::_n_( '%s draft', '%s drafts', $num_posts->draft ), number_format_i18n( $num_posts->draft ) ).'</a>';
		}
		if ( $can_edit_pages && !empty($num_pages->draft) ) {
			$post_type_texts[] = '<a href="edit-pages.php?post_status=draft">'.sprintf( ScoperAdminHardwayLegacy::_n_( '%s draft page', '%s draft pages', $num_pages->draft ), number_format_i18n( $num_pages->draft ) ).'</a>';
		}
		if ( $can_edit_posts && !empty($num_posts->future) ) {
			$post_type_texts[] = '<a href="edit.php?post_status=future">'.sprintf( ScoperAdminHardwayLegacy::_n_( '%s scheduled post', '%s scheduled posts', $num_posts->future ), number_format_i18n( $num_posts->future ) ).'</a>';
		}
		if ( $can_edit_pages && !empty($num_pages->future) ) {
			$post_type_texts[] = '<a href="edit-pages.php?post_status=future">'.sprintf( ScoperAdminHardwayLegacy::_n_( '%s scheduled page', '%s scheduled pages', $num_pages->future ), number_format_i18n( $num_pages->future ) ).'</a>';
		}
		
		$pending_texts = array();
		if ( current_user_can('publish_posts') && !empty($num_posts->pending) ) {
			$pending_texts[] = '<a href="edit.php?post_status=pending">'.sprintf( ScoperAdminHardwayLegacy::_n_( '%s pending post', '%s pending posts', $num_posts->pending ), number_format_i18n( $num_posts->pending ) ).'</a>';
			$plural = ( $num_posts->pending > 1 );
		}
		if ( current_user_can('publish_pages') && !empty($num_pages->pending) ) {
			$pending_texts[] = '<a href="edit-pages.php?post_status=pending">'.sprintf( ScoperAdminHardwayLegacy::_n_( '%s pending page', '%s pending pages', $num_pages->pending ), number_format_i18n( $num_pages->pending ) ).'</a>';
			$plural = ( $num_pages->pending > 1 );
		}
		
		if ( $pending_texts ) {
			if ( 2 == count($pending_texts) )
				$pending_text = sprintf( ScoperAdminHardwayLegacy::_c_( 'There are %1$s and %2$s awaiting review.|n posts, n pages', 'scoper'), $pending_texts[0], $pending_texts[1]);
			elseif ( $plural )
				$pending_text = sprintf( ScoperAdminHardwayLegacy::_c_( 'There are %s awaiting review.|n posts', 'scoper'), $pending_texts[0]);
			else {
				// if there are no pending pages, use the WP core message to support existing translations.
				$pending_text = sprintf( ScoperAdminHardwayLegacy::_n_( 'There is <a href="%1$s">%2$s post</a> pending your review.', 'There are <a href="%1$s">%2$s posts</a> pending your review.', $num_posts->pending ), 'edit.php?post_status=pending', number_format_i18n( $num_posts->pending ) );
			}
		}
		
		
		// note: retaining original cats / tags text


		if ( empty($num_comm['total_comments']) ) {
			$comm_text = '';

		} elseif ( empty($current_user->allcaps['moderate_comments']) ) {
			$total_comments = sprintf( ScoperAdminHardwayLegacy::_n_( '%1$s total', '%1$s total', $num_comm['total_comments'] ), number_format_i18n($num_comm['total_comments']) );
			$approved_comments = sprintf( ScoperAdminHardwayLegacy::_n_( '%1$s approved', '%1$s approved', $num_comm['approved'] ), number_format_i18n($num_comm['approved']) );
			$spam_comments = sprintf( ScoperAdminHardwayLegacy::_n_( '%1$s spam', '%1$s spam', $num_comm['spam'] ), number_format_i18n($num_comm['spam']) );
			$moderated_comments = sprintf( ScoperAdminHardwayLegacy::_n_( '%1$s awaiting moderation', '%1$s awaiting moderation', $num_comm['awaiting_moderation'] ), number_format_i18n($num_comm['awaiting_moderation']) );
			
			if( current_user_can( 'moderate_comments' ) ) {
				$total_comments = "<a href='edit-comments.php'>{$total_comments}</a>";
				$approved_comments = "<a href='edit-comments.php?comment_status=approved'>{$approved_comments}</a>";
				$moderated_comments = "<a href='edit-comments.php?comment_status=moderated'>{$moderated_comments}</a>";
			}
			
			$comm_text = sprintf( ScoperAdminHardwayLegacy::_n_( 'You have %1$s comment, %2$s, %3$s and %4$s.', 'You have %1$s comments, %2$s, %3$s and %4$s.', $num_comm['total_comments'] ), $total_comments, $approved_comments, $spam_comments, $moderated_comments );
		}
		
		$post_type_text = implode(', ', $post_type_texts);
		
		// There is always a category... well, not really.  With RS, some users may be able to edit pages without editing posts.  Then again, pages may be categorized so just leave this for now. 
		if ( $post_type_text )
			$sentence = sprintf( __( 'You have %1$s, contained within %2$s and %3$s. %4$s %5$s' ), $post_type_text, $cats_text, $tags_text, $pending_text, $comm_text );
		else
			$sentence = '';
		//------------------------------------- end WP core code ----------------------------
		
		unset($scoper->honor_any_objrole);
		
		return $sentence;
	}
	
	// prevents core / legacy strings from being forced into plugin .po
	function _n_( $single, $plural, $number, $domain = 'default' ) {
		return _n( $single, $plural, $number, $domain );
	}
	
	// prevents core / legacy strings from being forced into plugin .po
	function _c_( $text, $domain = 'default' ) {
		return _c( $text, $domain );
	}
	
} // end class
?>