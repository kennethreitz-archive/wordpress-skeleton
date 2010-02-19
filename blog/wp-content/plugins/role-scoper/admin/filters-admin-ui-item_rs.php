<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

class ScoperAdminFiltersItemUI {

	var $meta_box_ids = array();
	var $item_roles_ui;
	
	function ScoperAdminFiltersItemUI () {
		global $scoper;
		$this->scoper =& $scoper;
	
		add_action('admin_menu', array(&$this, 'add_meta_boxes'));
		add_action('do_meta_boxes', array(&$this, 'act_tweak_metaboxes') );
		
		// On the WP post/page edit form, object_edit_ui_rs will only fire with WP < 2.5, since we are using add_meta_boxes instead
		add_action('object_edit_ui_rs', array(&$this, 'ui_object_roles'), 10, 2);
		
		add_action('term_edit_ui_rs', array(&$this, 'ui_single_term_roles'), 10, 3);
		
		if ( ( strpos( $_SERVER['REQUEST_URI'], 'post-new.php' ) && scoper_get_otype_option( 'default_private', 'post', 'post' ) )
		|| ( strpos( $_SERVER['REQUEST_URI'], 'page-new.php' ) && scoper_get_otype_option( 'default_private', 'post', 'page' ) ) )
			add_action('admin_footer', array(&$this, 'default_private_js') );
		
		if ( ( ( strpos( $_SERVER['REQUEST_URI'], 'post-new.php' ) || strpos( $_SERVER['REQUEST_URI'], 'post.php' ) )  && scoper_get_otype_option( 'sync_private', 'post', 'post' ) )
		|| ( ( strpos( $_SERVER['REQUEST_URI'], 'page.php' ) || strpos( $_SERVER['REQUEST_URI'], 'page-new.php' ) ) && scoper_get_otype_option( 'sync_private', 'post', 'page' ) ) )
			add_action('admin_head', array(&$this, 'sync_private_js') );
	}
	
	
	function default_private_js() {
?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
	$('#visibility-radio-private').click();
	
	$('#post-visibility-display').html(
		postL10n[$('#post-visibility-select input:radio:checked').val()]
	);
});
/* ]]> */
</script>
<?php
	}
	
	
	function sync_private_js() {
?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
	$("#objscope_r1").click(function() {
		if ( this.checked ) {
			$('#visibility-radio-private').click();
			
			$('#post-visibility-display').html(
				postL10n[$('#post-visibility-select input:radio:checked').val()]
			);
		}
	});
});
/* ]]> */
</script>
<?php
	}
	
	
	function init_item_roles_ui() {
		if ( empty($this->item_roles_ui) ) {
			include_once('item_roles_ui_rs.php');
			$this->item_roles_ui = new ScoperItemRolesUI();
		}
	}
	
	function add_meta_boxes() {
		/*
		// optional hack to prevent role assignment boxes for non-Editors
		//
		//	This is now handled as a Role Scoper Option. 
		//	On the Advanced tab, Hidden Editing Elements section: select "Role administration requires a blog-wide Editor role"
		//
		// end optional hack
		*/
		
		// ========= register WP-rendered metaboxes ============
		$src_name = 'post';
		$box_object_types = array( 'post', 'page' );

		$require_blogwide_editor = scoper_get_option('role_admin_blogwide_editor_only');

		if ( ( 'admin' == $require_blogwide_editor ) && ! is_user_administrator_rs() )
			return;

		if ( ( 'admin_content' == $require_blogwide_editor ) && ! is_content_administrator_rs() )
			return;
			
		foreach ( $box_object_types as $object_type ) {
			if ( ! scoper_get_otype_option('use_object_roles', $src_name, $object_type) )
				continue;
		
			if ( $require_blogwide_editor ) {
				$required_cap = ( 'page' == $object_type ) ? 'edit_others_pages' : 'edit_others_posts';

				global $current_user;
				if ( empty( $current_user->allcaps[$required_cap] ) )
					continue;
			}
			
			$role_defs = $this->scoper->role_defs->get_matching(SCOPER_ROLE_TYPE, $src_name, $object_type);
			
			foreach ( $role_defs as $role_handle => $role_def ) {
				if ( ! isset($role_def->valid_scopes[OBJECT_SCOPE_RS]) )
					continue;

				$box_id = $role_handle;
				add_meta_box( $box_id, $this->scoper->role_defs->get_abbrev( $role_handle, OBJECT_UI_RS ), array(&$this, 'draw_object_roles_content'), $object_type );
				$this->meta_box_ids[$role_handle] = $box_id;
			}
		}
	}
	
	function act_tweak_metaboxes() {
		static $been_here;
		
		if ( isset($been_here) )
			return;

		$been_here = true;
		
		global $wp_meta_boxes;
		
		if ( empty($wp_meta_boxes) )
			return;
		
		$src_name = 'post';
		$object_type = $this->scoper->data_sources->detect('type', $src_name);
		
		if ( empty($wp_meta_boxes[$object_type]) )
			return;
		
		$object_id = $this->scoper->data_sources->detect('id', $src_name, '', $object_type);
		
		$is_administrator = is_user_administrator_rs();
		$can_admin_object = $is_administrator || $this->scoper->admin->user_can_admin_object($src_name, $object_type, $object_id);
		
		if ( $can_admin_object ) { 
			$this->init_item_roles_ui();
			$this->item_roles_ui->load_roles($src_name, $object_type, $object_id);
		}

		foreach ( $wp_meta_boxes[$object_type] as $context => $priorities ) {
			foreach ( $priorities as $priority => $boxes ) {
				foreach ( array_keys($boxes) as $box_id ) {
			
					if ( $role_handle = array_search( $box_id, $this->meta_box_ids ) ) {
						// eliminate metabox shells for roles which will be suppressed for this user
						if ( ! $is_administrator && ( ! $can_admin_object || ! $this->scoper->admin->user_can_admin_role($role_handle, $object_id, $src_name, $object_type) ) ) {
							unset( $wp_meta_boxes[$object_type][$context][$priority][$box_id] );
						}
						
						// update metabox titles with role counts, restriction indicator
						elseif ( $can_admin_object )
							if ( $title_suffix = $this->item_roles_ui->get_rolecount_caption($role_handle) )
								if ( ! strpos($wp_meta_boxes[$object_type][$context][$priority][$box_id]['title'], $title_suffix) )
									$wp_meta_boxes[$object_type][$context][$priority][$box_id]['title'] .= $title_suffix;
					}
				}
			}
		}
				
	}
	
	// wrapper function so we don't have to load item_roles_ui class just to register the metabox
	function draw_object_roles_content( $object, $box ) {
		if ( empty($box['id']) )
			return;
		
		// id format: src_name:object_type:role_handle (As of WP 2.7, this is only safe way to transfer these parameters)
		//$role_attribs = explode( ':', $box['id'] );
		
		//if ( count($role_attribs) != 3 )
		//	return;

		$object_id = ( isset($object->ID) ) ? $object->ID : 0;
		
		$src_name = 'post';
		$object_type = $this->scoper->data_sources->detect('type', 'post');
		
		$this->init_item_roles_ui();
		$this->item_roles_ui->draw_object_roles_content($src_name, $object_type, $box['id'], $object_id, false, $object);
	}
	
	function ui_single_term_roles($taxonomy, $args, $term) {
		$this->init_item_roles_ui();
		$this->item_roles_ui->single_term_roles_ui($taxonomy, $args, $term);
	}
	
	// This is now called only with WP < 2.5 and by non-post data sources which define admin_actions->object_edit_ui
	function ui_object_roles($src_name, $args = '') {
		$defaults = array( 'object_type' => '' );
		$args = array_merge( $defaults, (array) $args );
		extract($args);
	
		if ( ! $src = $this->scoper->data_sources->get($src_name) )
			return;
		
		if ( ! $object_type )
			if ( ! $object_type = $this->scoper->data_sources->detect('type', $src_name) )
				return;
				
		$object_id = $this->scoper->data_sources->detect('id', $src_name, '', $object_type);
		
		if ( ! $this->scoper->admin->user_can_admin_object($src_name, $object_type, $object_id) )
			return;
		
		$this->init_item_roles_ui();
		$this->item_roles_ui->single_object_roles_ui($src_name, $object_type, $object_id);
	} // end function ui_object_roles


} // end class

?>