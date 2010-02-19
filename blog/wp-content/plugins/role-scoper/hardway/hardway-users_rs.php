<?php
$nomess_uris = apply_filters( 'scoper_skip_lastresort_filter_uris', array( 'p-admin/categories.php', 'p-admin/themes.php', 'p-admin/plugins.php', 'p-admin/profile.php' ) );
$nomess_uris = array_merge($nomess_uris, array('p-admin/admin-ajax.php'));

// filter users list for edit-capable users as a convenience to administrator
if ( ! agp_string_ends_in(urldecode($_SERVER['REQUEST_URI']), $nomess_uris ) )		// TODO: can we check agp_strpos_any instead (add filter even if there are query vars)?
	add_filter('query', array('ScoperHardwayUsers', 'flt_editable_user_ids') );

// wrapper to users_where filter for "post author" / "page author" dropdown (limit to users who have appropriate caps)
add_filter('get_editable_authors', array('ScoperHardwayUsers', 'flt_get_editable_authors'), 50, 1);

add_filter('wp_dropdown_users', array('ScoperHardwayUsers', 'flt_wp_dropdown_users'), 50, 1);	

	
class ScoperHardwayUsers {

	// Filter the otherwise unfilterable get_editable_user_ids() result set, which affects the admin UI
	function flt_editable_user_ids($query) {
		// Only display users who can read / edit the object in question
		if ( strpos ($query, "user_id FROM") && strpos ($query, "meta_key =") ) {
			global $wpdb, $scoper;
			
			if ( strpos ($query, "user_id FROM $wpdb->usermeta WHERE meta_key = '{$wpdb->prefix}user_level'") ) {
				//log_mem_usage_rs( 'start flt_editable_user_ids()' );
				
				if ( isset( $_POST['post_type']) ) {
					if ( 'page' == $_POST['post_type'] )
						$reqd_caps = array('edit_pages');
					else
						$reqd_caps = array('edit_posts');
				
					if ( isset($_POST['post_ID']) )
						$object_id = $_POST['post_ID'];
						
				} elseif ( $context = $scoper->admin->get_context('') ) {//arg: return reqd_caps only {
					if ( $context->source ) {
						$src_name = $context->source->name;
						$object_id = $scoper->data_sources->detect('id', $src_name);
					} else {
						$src_name = '';
						$object_id = 0;
					}
					
					$reqd_caps = $context->reqd_caps;
				}
				
				if ( ! empty($reqd_caps) ) {
					global $scoper, $current_user;

					if ( 'page' == $context->object_type_def->val )
						$current_user_reqd_cap = 'edit_others_pages';
					else
						$current_user_reqd_cap = 'edit_others_posts';
					
					$id = $scoper->data_sources->detect('id', 'post');

					// only modify the default authors list if current user can edit_others for the current post/page
					if ( current_user_can( $current_user_reqd_cap, $id ) ) {
						$users = $scoper->users_who_can($reqd_caps, COL_ID_RS, $src_name, $object_id);
	
						if ( ! in_array($current_user->ID, $users) )
							$users []= $current_user->ID;
						
						$query = "SELECT $wpdb->users.ID FROM $wpdb->users WHERE ID IN ('" . implode("','", $users) . "')";
					}
				}
				
				//log_mem_usage_rs( 'end flt_editable_user_ids()' );
			}
		}
		
		return $query;
	}
	
	//horrible reverse engineering of dropdown_users execution because only available filter is on html output
	function flt_wp_dropdown_users($wp_output) {
		//log_mem_usage_rs( 'start flt_wp_dropdown_users()' );
		
		// if (even after our blogcap tinkering) the author list is already locked due to insufficient blog-wide caps, don't mess
		if ( ! $pos = strpos ($wp_output, '<option') )
			return $wp_output;
		
		if ( ! strpos ($wp_output, '<option', $pos + 1) )
			return $wp_output;

		global $wpdb, $scoper;
		
		$last_query = $wpdb->last_query;
		
		// This uri-checking functions will be called by flt_users_where.
		// If the current uri is not recognized, don't bother with the painful parsing. 
		$context = $scoper->admin->get_context('');
	
		if ( empty ($context->reqd_caps) )
			return $wp_output;
			
		if ( 'page' == $context->object_type_def->val )
			$current_user_reqd_cap = 'edit_others_pages';
		else
			$current_user_reqd_cap = 'edit_others_posts';
		
		$id = $scoper->data_sources->detect('id', 'post');

		// only modify the default authors list if current user has Editor role for the current post/page
		$scoper->query_interceptor->require_full_object_role = true;
		$have_cap = current_user_can( $current_user_reqd_cap, $id );
		$scoper->query_interceptor->require_full_object_role = false;
		
		//if ( ! $have_cap )
		 	//return $wp_output;
		
		$src_name = $context->source->name;
		
		$orderpos = strpos($last_query, 'ORDER BY');
		$orderby = ( $orderpos ) ? substr($last_query, $orderpos) : '';
		if ( ! strpos( $orderby, 'display_name' ) )	// sanity check in case the last_query buffer gets messed up
			$orderby = '';

		$id_in = $id_not_in = $show_option_all = $show_option_none = '';
		
		$pos = strpos($last_query, 'AND ID IN(');
		if ( $pos ) {
			$pos_close = strpos($last_query, ')', $pos);
			if ( $pos_close)
				$id_in = substr($last_query, $pos, $pos_close - $pos + 1); 
		}
		
		$pos = strpos($last_query, 'AND ID NOT IN(');
		if ( $pos ) {
			$pos_close = strpos($last_query, ')', $pos);
			if ( $pos_close)
				$id_not_in = substr($last_query, $pos, $pos_close - $pos + 1); 
		}
		
		$search = "<option value='0'>";
		$pos = strpos($wp_output, $search . __awp('Any'));
		if ( $pos ) {
			$pos_close = strpos($wp_output, '</option>', $pos);
			if ( $pos_close)
				$show_option_all = substr($wp_output, $pos + strlen($search), $pos_close - $pos - strlen($search)); 
		}
		
		$search = "<option value='-1'>";
		$pos = strpos($wp_output, $search . __awp('None'));
		if ( $pos ) {
			$pos_close = strpos($wp_output, '</option>', $pos);
			if ( $pos_close)
				$show_option_none = substr($wp_output, $pos + strlen($search), $pos_close - $pos - strlen($search)); 
		}
		
		$search = "<select name='";
		$pos = strpos($wp_output, $search);
		if ( false !== $pos ) {
			$pos_close = strpos($wp_output, "'", $pos + strlen($search));
			if ( $pos_close)
				$name = substr($wp_output, $pos + strlen($search), $pos_close - $pos - strlen($search)); 
		}
		
		$search = " id='";
		$multi = ! strpos($wp_output, $search);  // beginning with WP 2.7, some users dropdowns lack id attribute
		
		$search = " class='";
		$pos = strpos($wp_output, $search);
		if ( $pos ) {
			$pos_close = strpos($wp_output, "'", $pos + strlen($search));
			if ( $pos_close)
				$class = substr($wp_output, $pos + strlen($search), $pos_close - $pos - strlen($search)); 
		}
		
		$search = " selected='selected'";
		$pos = strpos($wp_output, $search);
		if ( $pos ) {
			$search = "<option value='";
	
			$str_left = substr($wp_output, 0, $pos);
			$pos = strrpos($str_left, $search); //back up to previous option tag

			$pos_close = strpos($wp_output, "'", $pos + strlen($search));
			if ( $pos_close)
				$selected = substr($wp_output, $pos + strlen($search), $pos_close - ($pos + strlen($search)) ); 
		}
		
		if ( ! $selected )
			$selected = $current_user->ID;	// precaution prevents default-selection of non-current user
		
		// Role Scoper filter application
		$where = "$id_in $id_not_in";
		
		$object_id = 0;
		$reqd_caps = array();
		if ( $context = $scoper->admin->get_context() ) {
			if ( ! empty($context->source) )
				$object_id = $scoper->data_sources->detect('id', $context->source);
			
			if ( isset($context->reqd_caps) )
				$reqd_caps = $context->reqd_caps;
		}
		
		$args = array();
		$args['where'] = $where;
		$args['orderby'] = $orderby;
		
		if ( $object_id > 0 ) {
			if ( $current_author = $scoper->data_sources->get_from_db('owner', $src_name, $object_id) )
				$force_user_id = $current_author;
		} else {
			global $current_user;
			$force_user_id = $current_user->ID;
		}
		
		if ( $have_cap ) {
			if ( $force_user_id )
				$args['preserve_or_clause'] = " uro.user_id = '$force_user_id'";

			$users = $scoper->users_who_can($reqd_caps, COLS_ID_DISPLAYNAME_RS, $src_name, $object_id, $args);
		} else {
			$display_name = $wpdb->get_var( "SELECT display_name FROM $wpdb->users WHERE ID = '$force_user_id'" );
			$users = array( (object) array( 'ID' => $force_user_id, 'display_name' => $display_name ) );
		}

		if ( empty($users) ) { // sanity check: users_who_can should always return Administrators
			if ( $admin_roles = awp_administrator_roles() )
				$users = scoper_get_results( "SELECT DISTINCT ID, display_name FROM $wpdb->users INNER JOIN $wpdb->user2role2object_rs AS uro ON uro.user_id = $wpdb->users.ID AND uro.scope = 'blog' AND uro.role_type = 'wp' AND uro.role_name IN ('" . implode( "','", $admin_roles ) . "')" );
			else
				return $wp_output; // the user data must be messed up
		}
			
		$show = 'display_name'; // no way to back this out

		// ----------- begin wp_dropdown_users code copy (from WP 2.7) -------------
		$id = $multi ? "" : "id='$name'";

		$output = "<select name='$name' $id class='$class'>\n";

		if ( $show_option_all )
			$output .= "\t<option value='0'>$show_option_all</option>\n";

		if ( $show_option_none )
			$output .= "\t<option value='-1'>$show_option_none</option>\n";
			
		foreach ( (array) $users as $user ) {
			$user->ID = (int) $user->ID;
			$_selected = $user->ID == $selected ? " selected='selected'" : '';
			$display = !empty($user->$show) ? $user->$show : '('. $user->user_login . ')';
			$output .= "\t<option value='$user->ID'$_selected>" . wp_specialchars($display) . "</option>\n";
		}

		$output .= "</select>";
		// ----------- end wp_dropdown_users code copy (from WP 2.7) -------------
		
		//log_mem_usage_rs( 'flt_wp_dropdown_users()' );
		
		return $output;
	}
	
	function flt_get_editable_authors($unfiltered_results) {
		global $wpdb, $scoper;
		
		$context = $scoper->admin->get_context('', true); // arg: return reqd_caps only
		
		if ( ! empty ($context->reqd_caps) ) {
			if ( 'page' == $matched->object_type_def->val )
				$current_user_reqd_cap = 'edit_others_pages';
			else
				$current_user_reqd_cap = 'edit_others_posts';
			
			$object_id = $scoper->data_sources->detect('id', 'post');
			
			$scoper->query_interceptor->require_full_object_role = true;
			$have_cap = current_user_can( $current_user_reqd_cap, $object_id );
			$scoper->query_interceptor->require_full_object_role = false;
			
			if ( $have_cap )
				return $scoper->users_who_can($context->reqd_caps, COLS_ALL_RS);
			else {
				if ( $object_id ) {
					if ( $current_author = $scoper->data_sources->get_from_db('owner', 'post', $object_id) )
						$force_user_id = $current_author;
				} else {
					global $current_user;
					$force_user_id = $current_user->ID;
				}
			
				if ( $force_user_id ) {
					$display_name = $wpdb->get_var( "SELECT display_name FROM $wpdb->users WHERE ID = '$force_user_id'" );
					$users = array( (object) array( 'ID' => $force_user_id, 'display_name' => $display_name ) );
					return $users;
				}
			}
			
			//log_mem_usage_rs( 'flt_get_editable_authors()' );
		}

		return $unfiltered_results;
	}

}
?>