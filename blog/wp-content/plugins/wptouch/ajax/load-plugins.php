<?php	
	require_once( WP_CONTENT_DIR . '/../wp-includes/class-snoopy.php');
	
	$snoopy = new Snoopy();
	$snoopy->offsiteok = true; /* allow a redirect to different domain */
	$result = $snoopy->fetch( 'http://www.bravenewcode.com/custom/wptouch-plugin-compat-list.php' );
if($result) {
	echo $snoopy->results;
} else {
	echo '<p>We were not able to load the Wire panel on your server.</p>';
}
?>