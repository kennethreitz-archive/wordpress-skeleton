<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

require_once( 'attachment-interceptor_rs.php' );

class AttachmentTemplate_RS {
	// Filter attachment page content prior to display by attachment template.
	// Note: teaser-subject direct file URL requests also land here
	function attachment_access() {
		global $post, $wpdb;

		if ( empty($post) ) {
			global $wp_query;

			if ( ! empty($wp_query->query_vars['attachment_id']) ) {
				$post = scoper_get_row("SELECT * FROM $wpdb->posts WHERE post_type = 'attachment' AND ID = '{$wp_query->query_vars['attachment_id']}'");
			
			} elseif ( ! empty($wp_query->query_vars['attachment']) )
				$post = scoper_get_row("SELECT * FROM $wpdb->posts WHERE post_type = 'attachment' AND post_name = '{$wp_query->query_vars['attachment']}'");
		}
		
		if ( ! empty($post) ) {
			$object_type = scoper_get_var("SELECT post_type FROM $wpdb->posts WHERE ID = '$post->post_parent'");

			// default to 'post' object type if retrieval failed for some reason
			if ( empty($object_type) )
				$object_type = 'post';
			
			if ( $post->post_parent ) {
				if ( ! current_user_can( "read_$object_type", $post->post_parent ) ) {
					if ( scoper_get_otype_option('do_teaser', 'post') ) {
						if ( $use_teaser_type = scoper_get_otype_option('use_teaser', 'post',  $object_type) )
							AttachmentTemplate_RS::impose_post_teaser($post, $object_type, $use_teaser_type);
						else
							unset( $post );
					} else
						unset( $post ); // WordPress generates 404 if teaser is not enabled
				}
			} elseif ( defined('SCOPER_BLOCK_UNATTACHED_UPLOADS') && SCOPER_BLOCK_UNATTACHED_UPLOADS ) {
				unset( $post );
			}
		}
	}
	
	function impose_post_teaser(&$object, $object_type, $use_teaser_type = 'fixed') {
		global $current_user, $scoper, $wp_query;

		require_once('teaser_rs.php');
		
		$src_name = 'post';
		
		$teaser_replace = array();
		$teaser_prepend = array();
		$teaser_append = array();
		
		$teaser_replace[$object_type]['post_content'] = ScoperTeaser::get_teaser_text( 'replace', 'content', $src_name, $object_type, $current_user );

		$teaser_replace[$object_type]['post_excerpt'] = ScoperTeaser::get_teaser_text( 'replace', 'excerpt', $src_name, $object_type, $current_user );
		$teaser_prepend[$object_type]['post_excerpt'] = ScoperTeaser::get_teaser_text( 'prepend', 'excerpt', $src_name, $object_type, $current_user );
		$teaser_append[$object_type]['post_excerpt'] = ScoperTeaser::get_teaser_text( 'append', 'excerpt', $src_name, $object_type, $current_user );

		$teaser_prepend[$object_type]['post_name'] = ScoperTeaser::get_teaser_text( 'prepend', 'name', $src_name, $object_type, $current_user );
		$teaser_append[$object_type]['post_name'] = ScoperTeaser::get_teaser_text( 'append', 'name', $src_name, $object_type, $current_user );
	
		$force_excerpt = array();
		$force_excerpt[$object_type] = ( 'excerpt' == $use_teaser_type );
		
		$args = array( 'col_excerpt' => 'post_excerpt', 'col_content' => 'post_content', 'col_id' => 'ID',
		'teaser_prepend' => $teaser_prepend, 		'teaser_append' => $teaser_append, 	'teaser_replace' => $teaser_replace, 
		'force_excerpt' => $force_excerpt );
		
		ScoperTeaser::apply_teaser( $object, $src_name, $object_type, $args );
		
		$wp_query->is_404 = false;
		$wp_query->is_attachment = true;
		$wp_query->is_single = true;
		$wp_query->is_singular = true;
		$object->ancestors = array( $object->post_parent );
		
		$wp_query->post_count = 1;
		$wp_query->is_attachment = true;
		$wp_query->posts[] = $object;
		
		if ( isset($wp_query->query_vars['error']) )
			unset( $wp_query->query_vars['error'] );
		
		if ( isset($wp_query->query['error']) )
			$wp_query->query['error'] = '';
	}

} // end class
?>