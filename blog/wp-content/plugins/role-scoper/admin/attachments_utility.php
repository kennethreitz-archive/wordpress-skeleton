<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );

if ( ! is_option_administrator_rs() )
	wp_die(__awp('Cheatin&#8217; uh?'));

function scoper_attach_linked_uploads( $echo = false ) {
	global $wpdb;

	require_once( SCOPER_ABSPATH . '/uploads_rs.php' );
	$uploads = scoper_get_upload_info();
	
	$site_url = untrailingslashit( get_option('siteurl') );
	if ( false === strpos( $uploads['baseurl'], $site_url ) ) {
		if ( $echo ) {
			_e('<strong>Note</strong>: Direct access to uploaded file attachments cannot be filtered because your WP_CONTENT_DIR is not in the WordPress branch.', 'scoper');
			echo '<br /><br />';
			_e('The operation was terminated due to an invalid configuration.', 'scoper');
		}

		return false;
	}
	
	if ( $post_ids = scoper_get_col( "SELECT ID FROM $wpdb->posts WHERE post_type IN ('post', 'page') ORDER BY post_type, post_title" ) ) {
		$stored_attachments = array();
		if ( $results = scoper_get_results( "SELECT post_parent, guid FROM $wpdb->posts WHERE post_type = 'attachment'" ) ) {
			foreach ( $results as $row ) {
				if ( ! isset($stored_attachments[$row->post_parent]) )
					$stored_attachments[$row->post_parent] = array();
				
				$stored_attachments[$row->post_parent][$row->guid] = true;
			}
		}
	
		// for reasonable memory usage, only hold 10 posts in memory at a time
		$found_links = 0;
		$num_inserted = 0;
		$num_posts = count($post_ids);
		$bite_size = 10;
		$num_bites = $num_posts / $bite_size;
		if ( $num_posts % $bite_size )
			$num_bites++;
		
		$upload_path = $uploads['baseurl'];

		if ( $echo ) {
			printf(__( "<strong>checking %s posts / pages...</strong>", 'scoper' ), $num_posts);
			echo '<br /><br />';
		}
		
		for ( $i = 0; $i < $num_bites; $i++ ) {
			$id_in = "'" . implode( "','", array_slice( $post_ids, $i * $bite_size, $bite_size ) ) . "'";
			if ( ! $results = scoper_get_results( "SELECT ID, post_content, post_author, post_title, post_type FROM $wpdb->posts WHERE ID IN ($id_in)" ) )
				continue;
			
			foreach ( $results as $row ) {
				$linked_uploads = array();
				
				// preg_match technique learned from http://stackoverflow.com/questions/138313/how-to-extract-img-src-title-and-alt-from-html-using-php
				$tags = array( 'img' => array(), 'a' => array() );
				
				$content = $row->post_content;
				
				preg_match_all('/<img[^>]+>/i', $row->post_content, $tags['img']);
				preg_match_all('/<a[^>]+>/i', $row->post_content, $tags['a']);  // don't care that this will terminate with any enclosed tags (i.e. img)
				
				foreach ( array_keys($tags) as $tag_type ) {
					foreach ( $tags[$tag_type]['0'] as $found_tag ) {
						$found_attribs = array( 'src' => '', 'href' => '', 'title' => '', 'alt' => '' );
						
						if ( ! preg_match_all('/(alt|title|src|href)=("[^"]*")/i', $found_tag, $tag_attributes) )
							continue;
						
						foreach ( $tag_attributes[1] as $key => $attrib_name )
							$found_attribs[$attrib_name] = trim($tag_attributes[2][$key], "'" . '"');

						if ( ! $found_attribs['href'] && ! $found_attribs['src'] )
							continue;

						$file_url = ( $found_attribs['src'] ) ? $found_attribs['src'] : $found_attribs['href'];
						
						// links can't be registered as attachments unless they're in the WP uploads path
						if ( false === strpos($file_url, $upload_path) ) {
							if ( $echo ) {
								//printf( _ x( '<span class="rs-brown">skipping unfilterable file in %1$s "%2$s":</span> %3$s', 'post_type, post_title, file_url', 'scoper' ), __(ucwords($row->post_type)), $row->post_title, $file_url);
								printf( __( '<span class="rs-brown">skipping unfilterable file in %1$s "%2$s":</span> %3$s', 'scoper' ), __(ucwords($row->post_type)), $row->post_title, $file_url);
								echo '<br /><br />';
							}
						
							continue;
						}
						
						$caption = ( $found_attribs['title'] ) ? $found_attribs['title'] : $found_attribs['alt'];

						// we might find the same file sourced in both link and img tags
						if ( ! isset($linked_uploads[$file_url]) || ! $linked_uploads[$file_url] ) {
							$found_links++;
							$linked_uploads[$file_url] = $caption;	
						}

					} // end foreach found tag
				} // end foreach loop on 'img' and 'a'
				
				foreach ( $linked_uploads as $file_url => $caption ) {
					$unsuffixed_file_url = preg_replace( "/-[0-9]{2,4}x[0-9]{2,4}./", '.', $file_url );
				
					$file_info = wp_check_filetype( $unsuffixed_file_url );

					if ( ! isset($stored_attachments[$row->ID][$unsuffixed_file_url]) ) {
						$att = array();
						$att['guid'] = $unsuffixed_file_url;
						
						$info = pathinfo($unsuffixed_file_url);
						if ( isset($info['filename']) ) {
							$att['post_name'] = $info['filename'];
							$att['post_title'] = $info['filename'];
						}
						$att['post_excerpt'] = $caption;
						$att['post_author'] = $row->post_author;
						$att['post_parent'] = $row->ID;
						$att['post_category'] = wp_get_post_categories( $row->ID );
						if ( isset($file_info['type']) )
							$att['post_mime_type'] = $file_info['type'];

						$num_inserted++;
						
						if ( $echo )
							printf(__( '<span class="rs-green"><strong>new attachment</strong> in %1$s "%2$s":</span> %3$s', 'scoper' ), __(ucwords($row->post_type)), $row->post_title, $file_url);
							//printf(_ x( '<span class="rs-green"><strong>new attachment</strong> in %1$s "%2$s":</span> %3$s', 'post_type, post_title, file_url', 'scoper' ), __(ucwords($row->post_type)), $row->post_title, $file_url);

						wp_insert_attachment( $att );
					} else {
						if ( $echo )
							printf(__( '<span class="rs-blue">attachment OK in %1$s "%2$s":</span> %3$s', 'scoper' ), __(ucwords($row->post_type)), $row->post_title, $file_url);
							//printf(_ x( '<span class="rs-blue">attachment OK in %1$s "%2$s":</span> %3$s', 'post_type, post_title, file_url', 'scoper' ), __(ucwords($row->post_type)), $row->post_title, $file_url);
					}
					
					if ( $echo )
						echo '<br /><br />';
					
				} // end foreach linked_uploads
				
			} // end foreach post in this bite
			
		} // endif for each 10-post bite
		
		if ( $echo ) {
			echo '<br /><strong>';

			printf( __( "Operation complete: %s linked uploads were found in your post / page content.", 'scoper' ), $found_links );
			echo '<br /><br />';
			if ( $num_inserted )
				printf(__( '<strong>%s attachment records were added to the database.</strong>', 'scoper' ), $num_inserted);
			elseif ( $found_links )
				_e('All linked uploads are already registered as attachments.', 'scoper');
		}

		return true;
	}
}
	
?>

<div class='wrap'>
<table width = "100%"><tr>
<td width = "90%">
<h2><?php _e('Attachments Utility', 'scoper') ?></h2>
<?php 
//printf( _ x('Back to %1$sRole Scoper Options%2$s', 'arguments are link open, link close', 'scoper'), "<a href='admin.php?page=rs-options'>", '</a>');
printf( __('Back to %1$sRole Scoper Options%2$s', 'scoper'), "<a href='admin.php?page=rs-options'>", '</a>');
?>
</td>
<td>
<div class="submit" style="border:none;float:right;margin:0;">
<input type="submit" name="rs_submit" class="button-primary" value="<?php _e('Update &raquo;', 'scoper');?>" />
</div>
</td>
</tr></table>

<?php

echo '<br />';
_e("Role Scoper can limit direct URL access to files linked from your posts and pages, <strong>but only if</strong> the following requirements are met:", 'scoper');
echo '<div class="rs-instructions"><ol><li>';
_e( 'Your WP content directory must be a branch of the WordPress directory tree (i.e. wp-config.php must not be customized to separate WP_CONTENT_DIR and WP_CONTENT_URL from the main WordPress folder).', 'scoper');
echo '</li><li>';

$search_replace_url = awp_plugin_search_url('replace');

if ( false !== strpos( $upload_path, 'http://www.' ) )
	$www_msg = __('Note that to be detected as attachments, your file references must <strong>include www.</strong>');
else
	$www_msg = __('Note that to be detected as attachments, your file references must <strong>NOT include www.</strong>');

require_once( SCOPER_ABSPATH . '/uploads_rs.php' );
$uploads = scoper_get_upload_info();
	
printf(__('Files linked from WP Posts and Pages must be in %1$s (or a subdirectory of it) to be filtered. After moving files, you may use %2$s a search and replace plugin%3$s to conveniently update the URLs stored in your Post / Page content. %4$s', 'scoper'), '<strong>' . $uploads['baseurl'] . '</strong>', "<a href='$search_replace_url'>", '</a>', $www_msg);
echo '</li><li>';
_e( 'Files which are <strong>already appropriately located and linked</strong> must also have their post-file attachment relationship stored to the WP database.  This is normally accomplished by clicking the "Insert into Post" button in the WP file uploader / Media Library.  Files which were instead uploaded manually via FTP or CPanel <strong>can receive their attachment record via this utility</strong>.', 'scoper');
echo '</li></ol></div>';

if ( ! empty($_POST['rs_run_utility']) ) {
	scoper_attach_linked_uploads( true );
}
?>
<form action="" method="post">
<div class="submit" style="border:none;float:right;margin:0;">
<input type="submit" name="rs_run_utility" value="<?php _e('Register File Attachments &raquo;', 'scoper');?>" />
</div>
</form>