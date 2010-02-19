<?php

// NOTE: As of Nov 2009, RS / Flutter compatibility requires that the Flutter function GetCustomWritePanels 
//		(in the RCCWP_CustomWritePanel class, file plugins/fresh-page/RCCWP_CustomWritePanel.php) 
// 		be modified to the following:

/*
function GetCustomWritePanels() 
{ 
        global $wpdb; 

        $sql = "SELECT id, name, description, display_order, capability_name, 
type, single  FROM " . RC_CWP_TABLE_PANELS; 

        $join = apply_filters( 'panels_join_fp', '' ); 
        $where = apply_filters( 'panels_where_fp', '' ); 

        $sql .= " $join WHERE 1=1 $where ORDER BY display_order ASC"; 
        $results = $wpdb->get_results($sql); 
        if (!isset($results)) 
                $results = array(); 

        return $results; 
}
*/


if ( is_admin() && defined( 'FLUTTER_NAME' ) ) {
	add_filter( 'panels_where_fp', array( 'ScoperFlutterHelper', 'flt_panels_where_fp' ) );
	add_action('admin_head-post-new.php', array('ScoperFlutterHelper', 'act_flutter_panel_access') );
	add_action('admin_head-page-new.php', array('ScoperFlutterHelper', 'act_flutter_panel_access') );
}

Class ScoperFlutterHelper {
	function flt_panels_where_fp ( $where ) {
		global $scoper;
		
		if ( $cat_ids = $scoper->get_terms( 'category', true, COL_ID_RS ) )
			$where .= " AND type != 'post' OR id IN ( SELECT panel_id FROM " . RC_CWP_TABLE_PANEL_CATEGORY . " WHERE cat_id IN ('" . implode( "','", $cat_ids ) . "') )";
		else
			$where .= " AND type != 'post' ";

		return $where;
	}

	function act_flutter_panel_access() {
		if ( ! isset( $_GET['custom-write-panel-id'] ) || is_content_administrator_rs() )
			return;

		// we currently only filter for post panels
		if ( ! $panel = RCCWP_CustomWritePanel::Get( $_GET['custom-write-panel-id'] ) )
			return;
			
		if ( 'post' != $panel->type )
			return;

		global $scoper;
		if ( $cat_ids = $scoper->get_terms( 'category', true, COL_ID_RS ) )
			if ( scoper_get_var( "SELECT panel_id FROM " . RC_CWP_TABLE_PANEL_CATEGORY . " WHERE cat_id IN ('" . implode( "','", $cat_ids ) . "') AND panel_id = '{$panel->id}'" ) )
				return;
		
		// don't block panel access if it's explicitly assigned via Panel cap
		if ( current_user_can( $panel->capability_name ) )
			return;

		// user doesn't have access based on category or panel capability for requested custom Write Panel, so redirect to generic Write Post form
		wp_redirect( 'post-new.php' );
	}
}

?>