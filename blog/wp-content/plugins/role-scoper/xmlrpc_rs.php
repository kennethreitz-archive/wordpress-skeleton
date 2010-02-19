<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

if ( ! defined( 'WLW_XMLRPC_HACK' ) )
	return;
		
// clean up after xmlrpc clients that don't specify a post_type for mw_editPost
// Couldn't find a clean way to filter args into default methods, and this is much better than forking entire method
//
global $HTTP_RAW_POST_DATA;

if ( isset($HTTP_RAW_POST_DATA) && $pos = strpos($HTTP_RAW_POST_DATA, '<string>') )
	if ( $pos_end = strpos($HTTP_RAW_POST_DATA, '</string>', $pos) ) {
		$post_id = substr($HTTP_RAW_POST_DATA, $pos + strlen('<string>'), $pos_end - ($pos + strlen('<string>')) ); 
		
		// workaround for Windows Live Writer passing in postID = 1 for new posts
		if ( strpos($HTTP_RAW_POST_DATA, '<methodName>metaWeblog.newPost</methodName>') )
			$post_id = 0;
	}
	
if ( $post_id ) {
	global $xmlrpc_post_id_rs;
	$xmlrpc_post_id_rs = $post_id;
	
	$post_type = '';
	if ( $pos = strpos($HTTP_RAW_POST_DATA, '<name>post_type</name>') )
		if ( $pos = strpos($HTTP_RAW_POST_DATA, '<string>', $pos) )
			if ( $pos_end = strpos($HTTP_RAW_POST_DATA, '</string>', $pos) )
				$post_type = substr($HTTP_RAW_POST_DATA, $pos + strlen('<string>'), $pos_end - ($pos + strlen('<string>')) ); 
	
	if ( empty($post_type) ) {
		if ( $pos_member_end = strpos($HTTP_RAW_POST_DATA, '</member>') ) {
			if ( $pos_member_end = strpos($HTTP_RAW_POST_DATA, '</member>', $pos_member_end + 1) ) {
				$pos_insert = $pos_member_end + strlen('</member>');
	
				global $wpdb;
				if ( $post_type = scoper_get_var("SELECT post_type FROM $wpdb->posts WHERE ID = '$post_id'") ) {
					if ( 'post' != $post_type ) {
						global $xmlrpc_post_type_rs;
						$xmlrpc_post_type_rs = $post_type;
					}
				
					$insert_xml = 
"          <member>
            <name>post_type</name>
            <value>
              <string>$post_type</string>
            </value>
          </member>";
          
					$HTTP_RAW_POST_DATA = substr($HTTP_RAW_POST_DATA, 0, $pos_insert + 1) . $insert_xml . substr($HTTP_RAW_POST_DATA, $pos_insert);
					
				} // endif parsed post type
			} // endif found existing member markup
		} // endif found 2nd existing member markup
	} // endif post_type not passed
}
  

// might have to do this at someday
/*
function scoper_mw_edit_post($args) {
}
 
function scoper_flt_xmlrpc_methods($methods) {
	$methods['metaWeblog.editPost'] = 'scoper_mw_edit_post';
	$methods['wp.editPage'] = 'scoper_mw_edit_post';

	return $methods;
}

//add_filter('xmlrpc_methods', 'scoper_flt_xmlrpc_methods');
*/

?>