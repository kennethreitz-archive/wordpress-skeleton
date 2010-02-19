<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

class ScoperAdminBulkLib {

	// object_array = db results 2D array
	function order_by_hierarchy($object_array, $col_id, $col_parent, $id_key = false) {
		$ordered_results = array();
		$find_parent_id = 0;
		$last_parent_id = array();
		
		do {
			$found_match = false;
			$lastcount = count($ordered_results);
			foreach ( $object_array as $key => $item )
				if ( $item->$col_parent == $find_parent_id ) {
					if ( $id_key )
						$ordered_results[$item->$col_id]= $object_array[$key];
					else
						$ordered_results[]= $object_array[$key];
					
					unset($object_array[$key]);
					$last_parent_id[] = $find_parent_id;
					$find_parent_id = $item->$col_id;
					
					$found_match = true;
					break;	
				}
			
			if ( ! $found_match ) {
				if ( ! count($last_parent_id) )
					break;
				else
					$find_parent_id = array_pop($last_parent_id);
			}
		} while ( true );
		
		return $ordered_results;
	}

	function display_date_limit_inputs( $role_duration = true, $content_date_limits = true ) {
		echo '
		<div id="poststuff" class="metabox-holder">
		<div id="post-body">
		<div id="post-body-content" class="rs-date-limit-inputs">
		';
		
		if ( $role_duration || $content_date_limits && scoper_get_option( 'display_hints' ) )
			if ( scoper_get_option('display_hints') ) {
				echo '<div class="rs-optionhint" style="margin: 0 0 1em 2em">';
				if ( $role_duration ) {
					_e("Role Duration specifies the time period in which a role is active.", 'scoper');
					echo ' ';
				}
				
				if ( $content_date_limits )
					_e("Content Date Limits narrow the content which the role applies to.", 'scoper');
				
				echo '<br />';
				echo '</div>';
			}
		
		if ( $role_duration ) {
			echo '<div style="margin: 0 0 1em 2em">';
			
			if ( ! empty($_POST['set_role_duration']) ) {
				$checked = "checked='checked'";
				$hide_class = '';
			} else {
				$checked = '';
				$hide_class = " hide-if-js";
			} 
			
			$js_call = "agp_display_if('role_duration_inputs', 'set_role_duration')";
			echo "<label for='set_role_duration'><input type='checkbox' id='set_role_duration' name='set_role_duration' value='1' $checked onclick=\"$js_call\" /><strong>";
			_e( 'Modify Role Duration', 'scoper' );
			echo '</strong></label><br />';
			
			echo "<ul class='rs-list_horiz rs-role_date_entry{$hide_class}' id='role_duration_inputs'>";
			
			// TODO: make these horizontal li
			
			echo '<li>';
			_e('Grant Role on:', 'scoper');
			ScoperAdminBulkLib::display_touch_time( '', '', 'start_date_gmt_' );
			echo '</li>';
			
			echo '<li>';
			_e('Expire Role on:', 'scoper');
			ScoperAdminBulkLib::display_touch_time( '', '', 'end_date_gmt_' );
			echo '</li>';
			
			echo '</ul>';
			
			echo '</div>';
		}
		
		if ( $content_date_limits ) {
			echo '<div style="margin: 0 0 1em 2em">';
			
			if ( ! empty($_POST['set_content_date_limits']) ) {
				$checked = "checked='checked'";
				$hide_class = '';
			} else {
				$checked = '';
				$hide_class = " hide-if-js";
			} 
			
			$js_call = "agp_display_if('role_date_limit_inputs', 'set_content_date_limits')";
			echo "<label for='set_content_date_limits'><input type='checkbox' id='set_content_date_limits' name='set_content_date_limits' value='1' $checked onclick=\"$js_call\" /><strong>";
			_e( 'Modify Content Date Limits', 'scoper' );
			echo '</strong></label>';
			
			echo "<ul class='rs-list_horiz rs-role_date_entry{$hide_class}' id='role_date_limit_inputs'>";
			
			echo '<li>';
			_e('Min Content Date:', 'scoper');
			ScoperAdminBulkLib::display_touch_time( '', '', 'content_min_date_gmt_' );
			echo '</li>';
			
			echo '<li>';
			_e('Max Content Date:', 'scoper');
			ScoperAdminBulkLib::display_touch_time( '', '', 'content_max_date_gmt_' );
			echo '</li>';

			echo '</ul>';
			
			echo '</div>';
		}
		
		if ( $role_duration || $content_date_limits && scoper_get_option( 'display_hints' ) )
			if ( scoper_get_option('display_hints') ) {
				echo '<div class="rs-optionhint" style="margin: 2em 0 1em 2em">';
				_e('This controls what limits to apply to the User / Group roles you select for creation or modification. <strong>Currently stored limits</strong> are indicated by a dotted border around the User or Group name.  For details, hover over the name or view the User or Group Profile.', 'scoper' );
				echo '</div>';
			}
	
		echo '
		</div>
		</div>
		</div>
		';
	}
	
	function display_touch_time( $stamp, $date, $id_prefix = '', $class = 'curtime', $edit = 1, $for_post = 1, $tab_index = 0, $multi = 0, $suppress_hidden_inputs = true, $suppress_current_inputs = true, $use_js = false, $empty_month_option = true ) {  // todo: move to $args array, default suppress to false
		if ( $use_js ) {
			echo '<span id="' . $id_prefix . 'timestamp">';
			printf($stamp, $date);
			echo '</span>';
			
			echo ' <a href="' . '#' . $id_prefix . 'edit_timestamp" id="' . $id_prefix . 'edit-timestamp" class="rs_role_edit-timestamp hide-if-no-js" tabindex="4">';
			echo __awp('Edit');
			echo '</a>';
			
			$class = 'hide_if_js ';
		} else
			$class = '';
			
		echo '<div id="' . $id_prefix . 'timestampdiv" class="' . $class . 'clear" style="clear:both;">';
	
		ScoperAdminBulkLib::touch_time( $edit, $for_post, $tab_index, $multi, $id_prefix, $suppress_hidden_inputs, $suppress_current_inputs, $use_js, $empty_month_option );
		
		echo '</div>';
	}
	
	// from WP 2.8.4 core, add id_prefix argument
	function touch_time( $edit = 1, $for_post = 1, $tab_index = 0, $multi = 0, $id_prefix = '', $suppress_hidden_inputs = false, $suppress_current_inputs = false, $use_js = true, $empty_month_option = false ) {
		global $wp_locale, $post, $comment;
	
		if ( $for_post ) {
			if ( empty($post) ) {
				$edit = true;
				$current_post_date = 0;
			} else {
				$edit = ( in_array($post->post_status, array('draft', 'pending') ) && (!$post->post_date_gmt || '0000-00-00 00:00:00' == $post->post_date_gmt ) ) ? false : true;
				$current_post_date = $post->post_date;
			}
		} else {
			$edit = true;
			$current_comment_date = ( empty($comment) ) ? 0 : $comment->comment_date;
		}	
			
		$tab_index_attribute = '';
		if ( (int) $tab_index > 0 )
			$tab_index_attribute = " tabindex='$tab_index'";
	
		// echo '<label for="timestamp" style="display: block;"><input type="checkbox" class="checkbox" name="edit_date" value="1" id="timestamp"'.$tab_index_attribute.' /> '.__( 'Edit timestamp' ).'</label><br />';
	
		if ( ! empty($_POST) ) {
			$jj = ( ! empty( $_POST[$id_prefix . 'jj'] ) ) ? $_POST[$id_prefix . 'jj'] : '';
			$mm = ( ! empty( $_POST[$id_prefix . 'mm'] ) ) ? $_POST[$id_prefix . 'mm'] : '';
			$aa = ( ! empty( $_POST[$id_prefix . 'aa'] ) ) ? $_POST[$id_prefix . 'aa'] : '';
			$hh = ( ! empty( $_POST[$id_prefix . 'hh'] ) ) ? $_POST[$id_prefix . 'hh'] : '';
			$mn = ( ! empty( $_POST[$id_prefix . 'mn'] ) ) ? $_POST[$id_prefix . 'mn'] : '';
			$ss = ( ! empty( $_POST[$id_prefix . 'ss'] ) ) ? $_POST[$id_prefix . 'ss'] : '';
			
		} else { 
			$time_adj = time() + (get_option( 'gmt_offset' ) * 3600 );

			$post_date = ($for_post) ? $current_post_date : $current_comment_date;
			
			$jj = ($edit) ? mysql2date( 'd', $post_date, false ) : gmdate( 'd', $time_adj );
			$mm = ($edit) ? mysql2date( 'm', $post_date, false ) : gmdate( 'm', $time_adj );
			$aa = ($edit) ? mysql2date( 'Y', $post_date, false ) : gmdate( 'Y', $time_adj );
			$hh = ($edit) ? mysql2date( 'H', $post_date, false ) : gmdate( 'H', $time_adj );
			$mn = ($edit) ? mysql2date( 'i', $post_date, false ) : gmdate( 'i', $time_adj );
			$ss = ($edit) ? mysql2date( 's', $post_date, false ) : gmdate( 's', $time_adj );
		}
			
		if ( ! $suppress_current_inputs ) {
			$cur_jj = gmdate( 'd', $time_adj );
			$cur_mm = gmdate( 'm', $time_adj );
			$cur_aa = gmdate( 'Y', $time_adj );
			$cur_hh = gmdate( 'H', $time_adj );
			$cur_mn = gmdate( 'i', $time_adj );
		}
		
				
		$month = "<select " . ( $multi ? '' : "id='{$id_prefix}mm' " ) . "name='{$id_prefix}mm' $tab_index_attribute>\n";
		
		if ( $empty_month_option )
			$month .= "\t\t\t" . '<option value=""></option>';
		
		for ( $i = 1; $i < 13; $i = $i +1 ) {
			$month .= "\t\t\t" . '<option value="' . zeroise($i, 2) . '"';
			if ( $i == $mm )
				$month .= ' selected="selected"';
			$month .= '>' . $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) . "</option>\n";
		}
		$month .= '</select>';
	
		$day = '<input type="text" ' . ( $multi ? '' : 'id="' . $id_prefix . 'jj" ' ) . 'name="' . $id_prefix . 'jj" value="' . $jj . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" />';
		$year = '<input type="text" ' . ( $multi ? '' : 'id="' . $id_prefix . 'aa" ' ) . 'name="' . $id_prefix . 'aa" value="' . $aa . '" size="4" maxlength="4"' . $tab_index_attribute . ' autocomplete="off" />';
		$hour = '<input type="text" ' . ( $multi ? '' : 'id="' . $id_prefix . 'hh" ' ) . 'name="' . $id_prefix . 'hh" value="' . $hh . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" />';
		$minute = '<input type="text" ' . ( $multi ? '' : 'id="' . $id_prefix . 'mn" ' ) . 'name="' . $id_prefix . 'mn" value="' . $mn . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" />';
		/* translators: 1: month input, 2: day input, 3: year input, 4: hour input, 5: minute input */
		printf(__('%1$s%2$s, %3$s @ %4$s : %5$s'), $month, $day, $year, $hour, $minute);
	
		echo '<input type="hidden" id="' . $id_prefix . 'ss" name="' . $id_prefix . 'ss" value="' . $ss . '" />';
	
		if ( $multi ) return;
	
		echo "\n\n";
		foreach ( array('mm', 'jj', 'aa', 'hh', 'mn') as $timeunit ) {
			if ( ! $suppress_hidden_inputs )
				echo '<input type="hidden" id="' . $id_prefix . 'hidden_' . $timeunit . '" name="' . $id_prefix . 'hidden_' . $timeunit . '" value="' . $$timeunit . '" />' . "\n";
			
			if ( ! $suppress_current_inputs ) {
				$cur_timeunit = 'cur_' . $timeunit;
				echo '<input type="hidden" id="' . $id_prefix . ''. $cur_timeunit . '" name="' . $id_prefix . $cur_timeunit . '" value="' . $$cur_timeunit . '" />' . "\n";
			}
		}
		
	?>
		<p>
		<?php if ( $use_js ) :?>
		<a href="#<?php echo($id_prefix)?>edit_timestamp" id="<?php echo($id_prefix)?>save-timestamp" class="rs_role_save-timestamp hide-if-no-js button"><?php echo __awp('OK'); ?></a>
		<a href="#<?php echo($id_prefix)?>edit_timestamp" id="<?php echo($id_prefix)?>cancel-timestamp" class="rs_role_cancel-timestamp hide-if-no-js"><?php echo __awp('Cancel'); ?></a>
		<?php else:?>
		<a href="#<?php echo($id_prefix)?>edit_timestamp" id="<?php echo($id_prefix)?>clear-timestamp" class="rs_role_clear-timestamp"><?php echo __awp('Clear'); ?></a>
		<?php endif;?>
		<?php
		$checked = ( ! empty($_POST["{$id_prefix}keep-timestamp"]) ) ? "checked='checked'" : '';
		?>
		&nbsp;<input type="checkbox" id="<?php echo($id_prefix)?>keep-timestamp" name="<?php echo($id_prefix)?>keep-timestamp" <?php echo $checked;?> /><?php _e('keep current setting', 'scoper'); ?>
		</p>
	<?php
	}

	
	function process_role_date_entries() {
		$return = array();
		$prefixes = array( 'start_date_gmt_', 'end_date_gmt_', 'content_min_date_gmt_', 'content_max_date_gmt_' );
						
		foreach ( $prefixes as $pfx ) {
			$key = str_replace( 'gmt_', 'gmt', $pfx );
			
			$aa = $_POST[$pfx . 'aa'];
			$mm = $_POST[$pfx . 'mm'];
			$jj = $_POST[$pfx . 'jj'];
			$hh = $_POST[$pfx . 'hh'];
			$mn = $_POST[$pfx . 'mn'];
			$ss = $_POST[$pfx . 'ss'];
			
			if ( ! empty($_POST[$pfx . 'keep-timestamp']) ) {
				$return[$key] = -1;
				continue;	
			
			} elseif ( ! $jj ) {
				if( in_array( $key, array( 'end_date_gmt', 'content_max_date_gmt' ) ) )
					$return[$key] = SCOPER_MAX_DATE_STRING;
				else
					$return[$key] = 0;	// if no day entered, treat as a non-entry
				
				continue;
				
			}

			// account for limitations in PHP strtotime() function - at least when running on a 32-bit server
			if ( $aa > 2035 )
				$aa = 2035;

			if ( ( $aa > 99 ) && ( $aa < 1902 ) )
				$aa = '1902';

			$aa = ($aa <= 0 ) ? date('Y') : $aa;
			$mm = ($mm <= 0 ) ? date('n') : $mm;
			$jj = ($jj > 31 ) ? 31 : $jj;
			$jj = ($jj <= 0 ) ? date('j') : $jj;
			$hh = ($hh > 23 ) ? $hh -24 : $hh;
			$mn = ($mn > 59 ) ? $mn -60 : $mn;
			$ss = ($ss > 59 ) ? $ss -60 : $ss;
			
			$return[$key] = get_gmt_from_date( sprintf( "%04d-%02d-%02d %02d:%02d:%02d", $aa, $mm, $jj, $hh, $mn, $ss ) );
			
			if ( ! $return[$key] )
				$return[$key] = '0';
		}
		
		return (object) $return;
	}
	
	
	function date_limits_js() {
		$ajax_url = site_url( 'wp-admin/admin-ajax.php' );
?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
	function clearDateEdit_rs( pfx ) {
		$('#' + pfx + 'mm').val('');
		$('#' + pfx + 'jj').val('');
		$('#' + pfx + 'aa').val('');
		$('#' + pfx + 'hh').val('');
		$('#' + pfx + 'mn').val('');
	}
	$('.rs_role_clear-timestamp').click(function() {
		id = this.id;
		pos = id.indexOf( 'clear-timestamp' );
		pfx = id.substr( 0, pos );
		
		clearDateEdit_rs( pfx );

		return false;
	});

});
/* ]]> */
</script>
<?php
// these js functions would be needed to support slide-down date entry, updating of caption from entries 
/* 
	function updateDateLimit_rs( pfx ) {
		if ( $('#' + pfx + 'jj').val() !== '' ) {
			$('#' + pfx + 'timestamp').html(
				' <b>' +
				$( '#' + pfx + 'mm option[value=' + $('#' + pfx + 'mm').val() + ']' ).text() + ' ' +
				$('#' + pfx + 'jj').val() + ', ' +
				$('#' + pfx + 'aa').val() + ' @ ' +
				$('#' + pfx + 'hh').val() + ':' +
				$('#' + pfx + 'mn').val() + '</b> '
			);
		} else
			$('#' + pfx + 'timestamp').html('');
	}
	function editDateLimit_rs( pfx ) {	
		if ($('#' + pfx + 'timestampdiv').is(":hidden")) {
			$('#' + pfx + 'timestampdiv').slideDown("normal");
			$('.' + pfx + 'edit-timestamp').hide();
		}
	}
	function setDateLimit_rs( pfx ) {
		$('#' + pfx + 'timestampdiv').slideUp("normal");
		$('.' + pfx + 'edit-timestamp').show();
		
		updateDateLimit_rs( pfx );
	}
	$('.rs_role_edit-timestamp').click(function () {
		id = this.id;
		pos = id.indexOf( 'edit-timestamp' );
		pfx = id.substr( 0, pos );

		editDateLimit_rs( pfx );
		return false;
	});
	$('.rs_role_cancel-timestamp').click(function() {
		id = this.id;
		pos = id.indexOf( 'cancel-timestamp' );
		pfx = id.substr( 0, pos );
		
		$('#' + pfx + 'timestampdiv').slideUp("normal");
		clearDateEdit_rs( pfx );
		$('.' + pfx + 'edit-timestamp').show();
		updateDateLimit_rs( pfx );
		
		return false;
	});
	$('.rs_role_save-timestamp').click(function () {
		id = this.id;
		pos = id.indexOf( 'save-timestamp' );
		pfx = id.substr( 0, pos );
		
		setDateLimit_rs( pfx );
		return false;
	});
*/

	} // end function date_limits_js
		
} // end class
?>