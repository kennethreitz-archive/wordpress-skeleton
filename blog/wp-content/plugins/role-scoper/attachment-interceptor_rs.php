<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

class AttachmentInterceptor_RS {

	function AttachmentInterceptor_RS() {
		add_action('parse_query', array(&$this, 'act_parse_query_for_direct_access') );
		add_action('template_redirect', array(&$this, 'act_attachment_access') );
		
		add_action( 'add_attachment', 'scoper_flush_file_rules' );
		add_action( 'edit_attachment', 'scoper_flush_file_rules' );
		add_action( 'delete_attachment', 'scoper_flush_file_rules' );
	}
	
	// handle access to uploaded file where request was a direct file URL, which was rewritten according to our .htaccess addition
	function act_parse_query_for_direct_access ( &$query ) {
		if ( empty($query->query_vars['attachment']) || ( false === strpos($_SERVER['QUERY_STRING'], 'rs_rewrite') ) ) {
			//rs_errlog( 'not an attachment: ' . serialize($_SERVER) );
			return;
		}

		require_once('attachment-filters_rs.php');
		AttachmentFilters_RS::parse_query_for_direct_access( $query );
	}
	
	// Filter attacment page content prior to display by attachment template.
	// Note: teaser-subject direct file URL requests also land here
	function act_attachment_access() {
		if ( is_admin() || defined('DISABLE_QUERYFILTERS_RS') || is_content_administrator_rs() || ! scoper_get_option( 'file_filtering' ) )
			return;

		// if ( is_attachment() ) {  as of WP 2.6, is_attachment() returns false for custom permalink attachment URL 
		if ( is_attachment_rs() ) {
			//rs_errlog( 'IS an attachment:' );
			
			require_once('attachment-template_rs.php');
			AttachmentTemplate_RS::attachment_access();
		}
	}
} // end class
?>