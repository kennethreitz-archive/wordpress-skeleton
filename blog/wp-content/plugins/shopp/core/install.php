<?php
/**
 * install.php
 * Performs the initial database setup
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 4 April, 2008
 * @package Shopp
 **/

global $wpdb,$wp_rewrite,$wp_version,$table_prefix;
$db = DB::get();

// Install tables
if (!file_exists(SHOPP_DBSCHEMA)) {
 	trigger_error("Could not install the shopp database tables because the table definitions file is missing: ".SHOPP_DBSCHEMA);
	exit();
}

ob_start();
include(SHOPP_DBSCHEMA);
$schema = ob_get_contents();
ob_end_clean();

$db->loaddata($schema);
unset($schema);

$parent = 0;
foreach ($this->Flow->Pages as $key => &$page) {
	if (!empty($this->Flow->Pages['catalog']['id'])) $parent = $this->Flow->Pages['catalog']['id'];
	$query = "INSERT $wpdb->posts SET post_title='{$page['title']}',
										post_name='{$page['name']}',
										post_content='{$page['content']}',
										post_parent='$parent',
										post_author='1',
										post_status='publish',
										post_type='page',
										post_date=now(),
										post_date_gmt=utc_timestamp(),
										post_modified=now(),
										post_modified_gmt=utc_timestamp(),
										comment_status='closed',
										ping_status='closed',
										post_excerpt='',
										to_ping='',     
										pinged='',      
										post_content_filtered='',
										menu_order=0";

										
	$wpdb->query($query);
	$page['id'] = $wpdb->insert_id;
	$page['permalink'] = get_permalink($page['id']);
	if ($key == "checkout") $page['permalink'] = str_replace("http://","https://",$page['permalink']);
	$wpdb->query("UPDATE $wpdb->posts SET guid='{$page['permalink']}' WHERE ID={$page['id']}");
	$page['permalink'] = preg_replace('|https?://[^/]+/|i','',$page['permalink']);
}

$this->Settings->save("pages",$this->Flow->Pages);

?>