<?php

/**
 * Handle outputting Podcasting's metabox
 * @author Spiral Web Consulting
 */
class PodcastingMetabox {
	
	/**
	 * Setup the metabox for use
	 */
	function PodcastingMetabox() {
		add_action('wp_ajax_pod404', array($this, 'check404'));
		add_action('wp_ajax_podenclose', array($this, 'newEnclosureBox'));
		add_action('admin_init', array($this, 'adminInit'));
		add_action('admin_head', array($this, 'addJavascript'));
		
		# Run our custom ping method that can disable auto enclosures and fixes the disappearing enclosure bug
		remove_action('do_pings', 'do_all_pings');
		add_action('do_pings', array($this, 'do_all_pings'));
	}
	
	/**
	 * Hooks to run after the admin is initialized
	 */
	function adminInit()
	{
		# Enclosure creation methods needed by WordPress
		add_action('save_post', array($this, 'saveForm'));
		add_action('delete_post', array($this, 'deleteForm'));
		
		# Add the metabox to the interface
		add_meta_box('podcasting', 'Podcasting', array($this, 'editForm'), 'post', 'normal');
	}
	
	/**
	 * The ping method modified to use our enclosure method over WordPress'
	 */
	function do_all_pings() {
		global $wpdb;

		// Do pingbacks
		while ($ping = $wpdb->get_row("SELECT * FROM {$wpdb->posts}, {$wpdb->postmeta} WHERE {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id AND {$wpdb->postmeta}.meta_key = '_pingme' LIMIT 1")) {
			$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id = {$ping->ID} AND meta_key = '_pingme';");
			pingback($ping->post_content, $ping->ID);
		}
		
		# Do enclosures if enabled, and if doing enclosures, use our custom method
		if ( get_option('pod_disable_enclose') != 'yes' ) {
			// Do Enclosures
			while ($enclosure = $wpdb->get_row("SELECT * FROM {$wpdb->posts}, {$wpdb->postmeta} WHERE {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id AND {$wpdb->postmeta}.meta_key = '_encloseme' LIMIT 1")) {
				$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_encloseme';", $enclosure->ID) );
				$this->do_enclose($enclosure->post_content, $enclosure->ID);
			}
		}

		// Do Trackbacks
		$trackbacks = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE to_ping <> '' AND post_status = 'publish'");
		if ( is_array($trackbacks) )
			foreach ( $trackbacks as $trackback )
				do_trackbacks($trackback);

		//Do Update Services/Generic Pings
		generic_ping();
	}
	
	/**
	 * The do_enclose method without the removing of enclosures that was causing issues for many users
	 * This method has last been updated in WordPress 2.8
	 */
	function do_enclose( $content, $post_ID ) {
		global $wpdb;
		include_once( ABSPATH . WPINC . '/class-IXR.php' );

		$log = debug_fopen( ABSPATH . 'enclosures.log', 'a' );
		$post_links = array();
		debug_fwrite( $log, 'BEGIN ' . date( 'YmdHis', time() ) . "\n" );

		$pung = get_enclosed( $post_ID );

		$ltrs = '\w';
		$gunk = '/#~:.?+=&%@!\-';
		$punc = '.:?\-';
		$any = $ltrs . $gunk . $punc;

		preg_match_all( "{\b http : [$any] +? (?= [$punc] * [^$any] | $)}x", $content, $post_links_temp );

		debug_fwrite( $log, 'Post contents:' );
		debug_fwrite( $log, $content . "\n" );

		foreach ( (array) $post_links_temp[0] as $link_test ) {
			if ( !in_array( $link_test, $pung ) ) { // If we haven't pung it already
				$test = parse_url( $link_test );
				if ( isset( $test['query'] ) )
					$post_links[] = $link_test;
				elseif ( $test['path'] != '/' && $test['path'] != '' )
					$post_links[] = $link_test;
			}
		}

		foreach ( (array) $post_links as $url ) {
			if ( $url != '' && !$wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = 'enclosure' AND meta_value LIKE (%s)", $post_ID, $url . '%' ) ) ) {
				if ( $headers = wp_get_http_headers( $url) ) {
					$len = (int) $headers['content-length'];
					$type = $headers['content-type'];
					$allowed_types = array( 'video', 'audio' );
					if ( in_array( substr( $type, 0, strpos( $type, "/" ) ), $allowed_types ) ) {
						$meta_value = "$url\n$len\n$type\n";
						$wpdb->insert($wpdb->postmeta, array('post_id' => $post_ID, 'meta_key' => 'enclosure', 'meta_value' => $meta_value) );
					}
				}
			}
		}
	}
	
	/**
	 * The edit form used by Podcasting
	 */
	function editForm() {
		global $wpdb, $post;
		
		# If this is a valid post, grab a list of the enclosures for this post
		if ($post->ID)
			$enclosures = $wpdb->get_results("SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE post_id = {$post->ID} AND meta_key = 'enclosure' ORDER BY meta_id", ARRAY_A);
			
		# Grab a list of all the podcasting formats
		$pod_formats = get_terms('podcast_format', 'get=all'); ?>
		
		<div id="podcasting_enclosures">
			
		<?php
		# If the list of enclosures is not empty
		if ( !empty($enclosures) ) {
		# Loop through each enclosure
		foreach ($enclosures as $enclosure) {
			# If the enclosure count is greater than none, add to a list of valid enclosures
			if ( $enclosure_count > 0 ) $pod_enclosure_ids .= ','; $enclosure_count++;
			# Append the current enclosure id to a list of enclosure ids
			$pod_enclosure_ids .= $enclosure['meta_id'];
			# Grab the contents of the enclosure
			$enclosure_value = explode("\n", $enclosure['meta_value']);
			# Grab and extract the iTunes specific data from the enclosure
			$enclosure_itunes = unserialize($enclosure_value[3]);
			# Determine the type of enclosure and mark the enclosure for a player button if necessary
			$podcast_player = ( 'mp3' == strtolower(substr(trim($enclosure_value[0]), -3)) ) ? true : false;
			$podcast_video_player_formats = array('m4v', 'mp4', 'mov', 'flv', 'm4a');
			$podcast_video_player = ( in_array(strtolower(substr(trim($enclosure_value[0]), -3)), $podcast_video_player_formats) ) ? true : false;
			
			### START THE ENCLOSURE HTML ###
			?>
			<table cellpadding="3" class="pod_enclosure" id="pod_episode_<?php echo $enclosure['meta_id']; ?>">
				<tr>
					<td class="pod-title">File</td>
					<td colspan="<?php echo ( $podcast_player || $podcast_video_player ) ? 5 : 6; ?>"><input type="text" name="pod_file_<?php echo $enclosure['meta_id']; ?>" class="pod_file" value="<?php echo $enclosure_value[0]; ?>" readonly="readonly" /></td>
					<?php if ( $podcast_player ) { ?>
					<td class="pod-player"><input name="add_editor" type="button" class="button-primary" value="Send to editor &raquo;" style="margin: 0 5px; width: 80%;" onClick="insertPodcastString('<?php echo trim($enclosure_value[0]); ?>');" /></td>
					<?php } elseif ( $podcast_video_player ) { ?>
					<td class="pod-player"><input name="add_editor" type="button" class="button-primary" style="margin: 0 5px; width: 80%;" value="Send to editor &raquo;" onClick="insertPodcastString('<?php echo trim($enclosure_value[0]); ?>', '1');" /></td>
					<?php } ?>
				</tr>
				<tr>
					<td class="pod-title">Format</td>
					<td><select name="pod_format_<?php echo $enclosure['meta_id']; ?>" class="pod_format">
						<?php
						# Get the enclosure format
						$enclosure_format = wp_get_object_terms($enclosure['meta_id'], 'podcast_format');
						# Loop through each of the available podcasting formats
						foreach ($pod_formats as $pod_format) {
							# Determine the selected podcasting format
							if ( '' != $enclosure_itunes['format'] )
								$selected = ($pod_format->slug == $enclosure_itunes['format']) ? ' selected="selected"' : '';
							elseif ( 0 < count($enclosure_format) )
								$selected = ($pod_format->slug == $enclosure_format[0]->slug) ? ' selected="selected"' : '';
							else
								$selected = ($pod_format->slug == 'default-format') ? ' selected="selected"' : '';
							# Output the option value
							echo '<option value="' . $pod_format->slug . '"' . $selected . '>' . $pod_format->name . '</option>';
						} ?>
					</select></td>
					<td class="pod-title"><a href="#" class="pod-tip" title="Up to 12 comma-separated words which iTunes uses for search placement.">Keywords</a></td>
					<td colspan="4"><input type="text" name="pod_keywords_<?php echo $enclosure['meta_id']; ?>" class="pod_keywords" value="<?php echo stripslashes($enclosure_itunes['keywords']); ?>" /></td>
				</tr>
				<tr>
					<td class="pod-title"><a href="#" class="pod-tip" title="Author name if different than default.">Author</a></td>
					<td><input type="text" name="pod_author_<?php echo $enclosure['meta_id']; ?>" class="pod_author" value="<?php echo stripslashes($enclosure_itunes['author']); ?>" /></td>
					<td class="pod-title"><a href="#" class="pod-tip" title="Length of the podcast in HH:MM:SS format.">Length</a></td>
					<td class="pod-length"><input type="text" name="pod_length_<?php echo $enclosure['meta_id']; ?>" class="pod_length" value="<?php echo stripslashes($enclosure_itunes['length']); ?>" /></td>
					<td class="pod-title"><a href="#" class="pod-tip" title="Explicit setting if different than default.">Explicit</a></td>
					<td class="pod-explicit"><select name="pod_explicit_<?php echo $enclosure['meta_id']; ?>" class="pod_format">
						<?php
						# Loop through the explicits and select the chosen one
						$explicits = array('', 'no', 'yes', 'clean');
						foreach ($explicits as $explicit) {
							$selected = ($explicit == $enclosure_itunes['explicit']) ? ' selected="selected"' : '';
							echo '<option value="' . $explicit . '"' . $selected . '>' . ucfirst($explicit) . '</option>';
						} ?>
					</select></td>
					<td class="pod-update"><input name="delete_pod_<?php echo $enclosure['meta_id']; ?>" type="button" class="button" style="margin: 0 5px; width: 80%;" value="Delete Enclosure" onclick="delete_podcast_episode(<?php echo $enclosure['meta_id']; ?>);" /></td>
				</tr>
			</table>
			
		<?php } ?>
		<?php } ?>
		<input name="pod_enclosure_ids" type="hidden" value="<?php echo $pod_enclosure_ids; ?>" />
		<input name="pod_new_enclosure_ids" id="pod_new_enclosure_ids" type="hidden" value="" />
		<input name="pod_delete_enclosure_ids" id="pod_delete_enclosure_ids" type="hidden" value="" />
		<input name="pod_ignore_enclosure_ids" id="pod_ignore_enclosure_ids" type="hidden" value="" />
		</div>
		
		<table cellpadding="3" class="pod_new_enclosure">
			<tr>
				<td class="pod-title">File URL</td>
				<td><input type="text" name="pod_new_file" class="pod_new_file" value="" /></td>
				<td class="pod-new-format"><select name="pod_new_format" class="pod_new_format">
					<?php foreach ($pod_formats as $pod_format) {
						$selected = ( 'default-format' == $pod_format->slug ) ? ' selected="selected"' : '';
						echo '<option value="' . $pod_format->slug . '"' . $selected . '>' . $pod_format->name . '</option>';
					} ?>
				</select></td>
				<td class="submit"><input name="add_episode" id="add_podcast_button" type="button" class="" value="Add" onclick="add_podcast_episode();" /></td>
			</tr>
		</table>
	<?php } // podcasting_edit_form()

	/**
	 * Saves information about enclosures
	 */
	function saveForm($postID) {
		global $wpdb;

		// Security prevention
		if ( !current_user_can('edit_post', $postID) )
			return $postID;

		// Extra security prevention
		if (isset($_POST['comment_post_ID'])) return $postID;
		if (isset($_POST['not_spam'])) return $postID; // akismet fix
		if (isset($_POST['comment'])) return $postID; // moderation.php fix

		// Ignore save_post action for revisions and autosave
		if (wp_is_post_revision($postID) || wp_is_post_autosave($postID)) return $postID;

		// Add new enclosures
		if ( $_POST['pod_new_enclosure_ids'] != '' ) {
			$pod_new_enclosure_ids = explode(',', substr($_POST['pod_new_enclosure_ids'], 0, -1));
			$pod_ignore_enclosure_ids = explode(',', substr($_POST['pod_ignore_enclosure_ids'], 0, -1));
			$added_enclosure_ids = array();
			foreach ( $pod_new_enclosure_ids AS $pod_enclosure_id ) {
				$pod_enclosure_id = (int) $pod_enclosure_id;

				// Check if the enclosure is on the ignore list
				if ( !in_array($pod_enclosure_id, $pod_ignore_enclosure_ids) ) {
					$pod_content = $this->prepareEnclosure($_POST['pod_new_file_' . $pod_enclosure_id]);
					$pod_format = $_POST['pod_new_format_' . $pod_enclosure_id];
					$enclosed = get_enclosed($postID);

					// Enclose the file using a custom method
					$headers = $this->getHttpHeaders($pod_content);

					# Check if the headers processed the file correctly, if they didn't try to clean up the file
					if ( $headers['response'] != '200' ) {
						$pod_content = podcasting_urlencode($pod_content);
						$headers = $this->getHttpHeaders($pod_content);
					}
					
					$length = (int) $headers['content-length'];
					$type = addslashes( $headers['content-type'] );
					if ( $headers['response'] != '404' && is_array($headers) ) {
						add_post_meta($postID, 'enclosure', "$pod_content\n$length\n$type\n");

						// Add relationship if new enclosure
						if ( !in_array($pod_content, $enclosed) ) {
							$pod_enclosure_id2 = $wpdb->get_var("SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = {$postID} AND meta_key = 'enclosure' ORDER BY meta_id DESC"); // Find the enclosure we just added
							wp_set_object_terms($pod_enclosure_id2, $pod_format, 'podcast_format', false);
						}
						$added_enclosure_ids[] = $pod_enclosure_id;
					}
				}
			}	
		}

		// Update enclosures
		if ( isset($_POST['pod_enclosure_ids']) ) {
			$pod_enclosure_ids = explode(',', $_POST['pod_enclosure_ids']);
			$pod_new_enclosure_ids = explode(',', substr($_POST['pod_new_enclosure_ids'], 0, -1));
			$pod_ignore_enclosure_ids = explode(',', substr($_POST['pod_ignore_enclosure_ids'], 0, -1));
			$pod_delete_enclosure_ids = explode(',', substr($_POST['pod_delete_enclosure_ids'], 0, -1));
			$enclosures = $wpdb->get_results("SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE post_id = {$postID} AND meta_key = 'enclosure' ORDER BY meta_id", ARRAY_A); $i = 0;

			if ( $_POST['pod_enclosure_ids'] != '' ) {
			foreach ($pod_enclosure_ids as $pod_enclosure_id) {
				// Ensure we're dealing with an ID
				$pod_enclosure_id = (int) $pod_enclosure_id;

				$itunes = serialize(array(
					'format' => $_POST['pod_format_' . $pod_enclosure_id],
					'keywords' => $_POST['pod_keywords_' . $pod_enclosure_id],
					'author' => $_POST['pod_author_' . $pod_enclosure_id],
					'length' => $_POST['pod_length_' . $pod_enclosure_id],
					'explicit' => $_POST['pod_explicit_' . $pod_enclosure_id]
					));

				// Update format
				wp_set_object_terms($pod_enclosure_id, $_POST['pod_format_' . $pod_enclosure_id], 'podcast_format', false);

				// Update enclsoure
				$enclosure = explode("\n", $enclosures[$i]['meta_value']);
				$enclosure[3] = $itunes;
				
				// Check that we have the full enclosure before updating it
				if ( is_array($enclosures) ) {
					update_post_meta($postID, 'enclosure', implode("\n", $enclosure), $enclosures[$i]['meta_value']);
				}
				
				$i++;

				// Delete enclosure
				if ( in_array($pod_enclosure_id, $pod_delete_enclosure_ids) ) {
					// Remove format
					wp_delete_object_term_relationships($pod_enclosure_id, 'podcast_format');
					// Remove enclosure
					delete_meta($pod_enclosure_id);
				}
			}
			}
			if ( count($added_enclosure_ids) > 0 ) {
			foreach ($added_enclosure_ids as $pod_enclosure_id) {
				// Ensure we're dealing with an ID
				$pod_enclosure_id = (int) $pod_enclosure_id;

				// Check if the enclosure is on the ignore list
				if ( !in_array($pod_enclosure_id, $pod_ignore_enclosure_ids) ) {
					$itunes = serialize(array(
						'format' => $_POST['pod_new_format_' . $pod_enclosure_id],
						'keywords' => $_POST['pod_new_keywords_' . $pod_enclosure_id],
						'author' => $_POST['pod_new_author_' . $pod_enclosure_id],
						'length' => $_POST['pod_new_length_' . $pod_enclosure_id],
						'explicit' => $_POST['pod_new_explicit_' . $pod_enclosure_id]
						));

					// Update format
					$meta_id = $enclosures[$i]['meta_id'];
					wp_set_object_terms($meta_id, $_POST['pod_new_format_' . $pod_enclosure_id], 'podcast_format', false);

					// Update enclsoure
					$enclosure = explode("\n", $enclosures[$i]['meta_value']);
					$enclosure[3] = $itunes;
					$enclosure_insert = implode("\n", $enclosure);
					$wpdb->query("UPDATE {$wpdb->postmeta} SET meta_value = '$enclosure_insert' WHERE meta_id = '$meta_id'");
					$i++;
				}
			}
			}
		}

		return $postID;
	} // podcasting_save_form()
	
	/**
	 * Create a box for enclosure editing use
	 */
	function newEnclosureBox() {
		# Check AJAX referer
		check_ajax_referer('podcasting');
		# Get the required variables
		$id = $_POST['pod_id'];
		$url = $_POST['pod_url'];
		$format = $_POST['pod_format'];
		# Grab a list of all the podcasting formats
		$pod_formats = get_terms('podcast_format', 'get=all');
		# Determine the type of enclosure and mark the enclosure for a player button if necessary
		$podcast_player = ( 'mp3' == strtolower(substr(trim($url), -3)) ) ? true : false;
		$podcast_video_player_formats = array('m4v', 'mp4', 'mov', 'flv', 'm4a');
		$podcast_video_player = ( in_array(strtolower(substr(trim($url), -3)), $podcast_video_player_formats) ) ? true : false;
		?>
		<table cellpadding="3" class="pod_enclosure" id="new_enclosure_<?php echo $id; ?>">
			<tr>
				<td class="pod-title">File</td>
				<td colspan="<?php echo ( $podcast_player || $podcast_video_player ) ? 5 : 6; ?>"><input type="text" name="pod_new_file_<?php echo $id; ?>" class="pod_file" value="<?php echo $url; ?>" readonly="readonly" /></td>
				<?php if ( $podcast_player ) { ?>
				<td class="pod-player"><input name="add_editor" type="button" class="button-primary" value="Send to editor &raquo;" style="margin: 0 5px; width: 80%;" onClick="insertPodcastString('<?php echo trim($url); ?>');" /></td>
				<?php } elseif ( $podcast_video_player ) { ?>
				<td class="pod-player"><input name="add_editor" type="button" class="button-primary" style="margin: 0 5px; width: 80%;" value="Send to editor &raquo;" onClick="insertPodcastString('<?php echo trim($url); ?>', '1');" /></td>
				<?php } ?>
			</tr>
			<tr>
				<td class="pod-title">Format</td>
				<td><select name="pod_new_format_<?php echo $id; ?>" class="pod_format">
					<?php
					# Loop through each of the available podcasting formats
					foreach ($pod_formats as $pod_format) {
						# Determine the selected podcasting format
						$selected = ($pod_format->slug == $format) ? ' selected="selected"' : '';
						# Output the option value
						echo '<option value="' . $pod_format->slug . '"' . $selected . '>' . $pod_format->name . '</option>';
					} ?>
				</select></td>
				<td class="pod-title"><a href="#" class="pod-tip" title="Up to 12 comma-separated words which iTunes uses for search placement.">Keywords</a></td>
				<td colspan="4"><input type="text" name="pod_new_keywords_<?php echo $id; ?>" class="pod_keywords" value="" /></td>
			</tr>
			<tr>
				<td class="pod-title"><a href="#" class="pod-tip" title="Author name if different than default.">Author</a></td>
				<td><input type="text" name="pod_new_author_<?php echo $id; ?>" class="pod_author" value="" /></td>
				<td class="pod-title"><a href="#" class="pod-tip" title="Length of the podcast in HH:MM:SS format.">Length</a></td>
				<td class="pod-length"><input type="text" name="pod_new_length_<?php echo $id; ?>" class="pod_length" value="" /></td>
				<td class="pod-title"><a href="#" class="pod-tip" title="Explicit setting if different than default.">Explicit</a></td>
				<td class="pod-explicit"><select name="pod_new_explicit_<?php echo $id; ?>" class="pod_format">
					<?php
					$explicits = array('', 'no', 'yes', 'clean');
					foreach ($explicits as $explicit) {
						$selected = '';
						echo '<option value="' . $explicit . '"' . $selected . '>' . ucfirst($explicit) . '</option>';
					} ?>
				</select></td>
				<td class="pod-update"><input name="delete_pod_<?php echo $id; ?>" type="button" class="button" style="margin: 0 5px; width: 80%;" value="Delete Enclosure" onclick="delete_new_podcast_episode(<?php echo $id; ?>);" /></td>
			</tr>
		</table>
		
		<?php
	}

	/**
	 * Check for a 404 using AJAX
	 */
	function check404() {
		check_ajax_referer('podcasting');
		
		$pod_content = $this->prepareEnclosure($_POST['file']);
		$headers = $this->getHttpHeaders($pod_content);
		
		# Check if the headers processed the file correctly, if they didn't try to clean up the file
		if ( $headers['response'] != '200' ) {
			$pod_content = podcasting_urlencode($pod_content);
			$headers = $this->getHttpHeaders($pod_content);
		}

		if ( $headers['response'] == '404' )
			echo 'File not found on server (404). Verify the file exists and try again.';
		elseif ( is_numeric($headers['response']) && $headers['response'] != '200' )
			echo 'Server responded with http error code ' . $headers['response'] . '.';
		elseif ( $headers['response'] != '200' )
			echo 'Server failed to respond to remote request and did not provided error information.';
		exit;
	}

	/**
	 * Retrieves information about a given podcast through several different methods
	 * @param - the URL of the file
	 * @return an array containing file information
	 */
	function getHttpHeaders($url) {
		# Don't attempt to get enclosure information since we're accepting failure
		if ( get_option('pod_accept_fail') == 'yes' ) {
			$headers = array(
				'response' => 200,
				'content-length' => '1048576',
				'content-type' => $this->getMimeType($url)
			);
			return $headers;
		}
		
		# Try using wp_remote_head
		if ( function_exists('wp_remote_head') ) {
			$wp_head = wp_remote_head($url);
			
			# Check if the returned type is a WP_Error, if so, return nothing
			if ( is_wp_error($wp_head) )
				return array('response' => '404');
			
			$headers = array(
				'response' => $wp_head['response']['code'],
				'content-length' => $wp_head['headers']['content-length'],
				'content-type' => $this->getMimeType($url)
			);
		} else { # Try using wp_get_http_headers
			$wp_head = wp_get_http_headers($url);
			$headers = array(
				'response' => '200',
				'content-length' => $wp_head['content-length'],
				'content-type' => $this->getMimeType($url)
			);
		}

		# Try to get the headers locally if external URLs fail
		if ( $headers['response'] == '' || $headers['response'] == '404' ) {
			$local_host = $_SERVER['SERVER_NAME'];
			$file_parse_url = parse_url($url);
			$file_host = $file_parse_url['host'];
			$file_path = $_SERVER['DOCUMENT_ROOT'] . $file_parse_url['path'];

			# Double check we have a local file
			if ( $local_host == $file_host ) {
				if ( file_exists($file_path) ) {
					$headers['response'] = '200';
					$headers['content-type'] = mime_content_type($file_path);
					$headers['content-length'] = filesize($file_path);
				} else {
					$headers['response'] = '404';
				}
			}
		}

		return $headers;
	}
	
	/**
	 * Retrieve the mime type of a file
	 *
	 * @param file - the file URL
	 * @return the mime type
	 **/
	function getMimeType($file)
	{
		$parts = pathinfo($file);
		switch( strtolower($parts['extension']) )
		{
			// Audio formats
			case 'mp3': // most common
			case 'mpga':
			case 'mp2':
			case 'mp2a':
			case 'm2a':
			case 'm3a':
				return 'audio/mpeg';
			case 'm4a':
				return 'audio/x-m4a';
			case 'ogg':
				return 'audio/ogg';
			case 'wma':
				return 'audio/x-ms-wma';
			case 'wax':
				return 'audio/x-ms-wax';
			case 'ra':
			case 'ram':
				return 'audio/x-pn-realaudio';
			case 'mp4a':
				return 'audio/mp4';

			// Video formats
			case 'm4v':
				return 'video/x-m4v';
			case 'mpeg':
			case 'mpg':
			case 'mpe':
			case 'm1v':
			case 'm2v':
				return 'video/mpeg';
			case 'mp4':
			case 'mp4v':
			case 'mpg4':
				return 'video/mp4';
			case 'asf':
			case 'asx':
				return 'video/x-ms-asf';
			case 'wmx':
				return 'video/x-ms-wmx';
			case 'avi':
				return 'video/x-msvideo';
			case 'wmv':
				return 'video/x-ms-wmv'; // Check this
			case 'flv':
				return 'video/x-flv';
			case 'swf':
				return 'application/x-shockwave-flash';
			case 'mov':
			case 'qt':
				return 'video/quicktime';
			case 'divx':
				return 'video/divx';
			case '3gp':
				return 'video/3gpp';

			// rarely used
			case 'mid':
			case 'midi':
				return'audio/midi';
			case 'wav':
				return 'audio/wav';
			case 'aa':
				return 'audio/audible';
			case 'pdf':
				return 'application/pdf';
			case 'torrent':
				return 'application/x-bittorrent';
			default: // Let it fall through
		}
		
		// Last case let wordpress detect it:
		return wp_check_filetype($file);
	}

	/**
	 * Cleans up after a deleted enclosure
	 */
	function deleteForm($postID) {
		$pod_enclosure_ids = explode(',', $_POST['pod_enclosure_ids']);
		foreach ($pod_enclosure_ids as $pod_enclosure_id) {
			$pod_enclosure_id = (int) $pod_enclosure_id;
			wp_delete_object_term_relationships($pod_enclosure_id, 'podcast_format');
		}
		return $postID;
	}

	/**
	 * Clean up a URL or file for entering into Podcasting
	 * @param url - the url of the file
	 * @return the cleaned up information
	 */
	function prepareEnclosure($url) {
		$url = trim($url);

		# Add the domain if given a relative URL
		if ( substr($url, 0, 4) != 'http' )
			if ( substr($url, 0, 1) != '/' )
				$url = get_option('home') . '/' . $url;
			else
				$url = get_option('home') . $url;

		return $url;
	}
	
	/**
	 * Add the Javascript needed to edit enclosure information
	 */
	function addJavascript() {
		?><script type='text/javascript'>
		
			jQuery(document).ready(function(){
				jQuery('#add_podcast_button').click( function() { 
					var button = this; 
					button.disabled = true; 
					setTimeout( function() { button.disabled = false; }, 3000 ); 
				});
			});

			var newEnclosureId = 1000;

			// This function will add Javascript to make a new episode appear on the post without refreshing the page
			function add_podcast_episode() {
				// Grab the variables
				var existingEnclosureIds = jQuery("#pod_new_enclosure_ids").val();
				var newFile = jQuery("table.pod_new_enclosure input.pod_new_file").val();
				var newFormat = jQuery("table.pod_new_enclosure select.pod_new_format").html();
				var newFormatVal = jQuery("table.pod_new_enclosure select.pod_new_format").val();

				// Check for a 404 before continuing
				jQuery.ajax({
					type: "post",
					url: "<?php echo admin_url('admin-ajax.php'); ?>",
					data: { action: 'pod404', file: newFile, _ajax_nonce: '<?php echo wp_create_nonce("podcasting"); ?>' },
					success: function(html){
						if ( html != '' ) {
							alert(html);
							return 0;
						}
						
						// Create the episode box
						jQuery.ajax({
							type: "post",
							url: "<?php echo admin_url('admin-ajax.php'); ?>",
							data: { action: 'podenclose', pod_id: newEnclosureId, pod_url: newFile, pod_format: newFormatVal, _ajax_nonce: '<?php echo wp_create_nonce("podcasting"); ?>' },
							success: function(newEnclosure){
								// Add the episode to the page
								jQuery(newEnclosure).appendTo("#podcasting_enclosures");
								jQuery("#pod_new_enclosure_ids").val( existingEnclosureIds + newEnclosureId + ',' );

								// Reset the add form
								jQuery("table.pod_new_enclosure input.pod_new_file").val('');

								// Increase the episode counter
								newEnclosureId = newEnclosureId + 1;
							},
							error: function(){
								alert('Passed 404 check, but failed to add file.');
							}
						});
					},
					error: function(){
						alert('Failed checking for file 404.');
					}
				});

			}

			// This function will remove the HTML for an episode, marking the episode for deletion on the next save
			function delete_podcast_episode(id) {
				var existingRemovals = jQuery("#pod_delete_enclosure_ids").val();

				confirm_delete = confirm("Are you sure you want to delete this enclosure?");

				if ( confirm_delete == true ) {
					jQuery("#pod_episode_" + id).hide('slow');
					jQuery("#pod_delete_enclosure_ids").val( existingRemovals + id + ',' );
				}
			}

			// This function will remove the episode for episodes that have been added without saving
			function delete_new_podcast_episode(id) {
				var existingRemovals = jQuery("#pod_ignore_enclosure_ids").val();

				confirm_delete = confirm("Are you sure you want to delete this enclosure?");

				if ( confirm_delete == true ) {
					jQuery("#new_enclosure_" + id).hide('slow');
					jQuery("#pod_ignore_enclosure_ids").val( existingRemovals + id + ',' );
				}
			}

			// Insert myValue (podcast special url string) into an editor window
			function insertPodcastString(myValue, type) {
				// Set the correct podcast tag
				if ( type == 1 )
					myValue = '[podcast format="video"]' + myValue + '[/podcast]';
				else
					myValue = '[podcast]' + myValue + '[/podcast]';

				send_to_editor(myValue);
			}
		</script>	
		<?php
	}
	
}

# Start the metabox
$podcasting_metabox = new PodcastingMetabox();

?>