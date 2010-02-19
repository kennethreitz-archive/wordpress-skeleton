<?php
// This file can be called manually or by cron to clear the Role Scoper cache, even if WP is non-functional.
$id = '';	// Set any $id value to prevent anonymous public flushing of your cache (depending on your sensibilities).

// NOTE:  If you are using a custom WP_CONTENT_DIR, you must also change the following line to correspond
$dir = dirname(__FILE__) . '/wp-content' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR; // your cache location

if ( $id ) {
	parse_str($_SERVER['QUERY_STRING'], $qv);
	if ( $qv['id'] != $id ) {
		echo 'Missing parameter; no action taken.<br><br>';
		return;
	}
}

$dir = rtrim($dir, DIRECTORY_SEPARATOR);

$top_dir = $dir;
$stack = array($dir);
$index = 0;
$flushed = false;

while ($index < count($stack)) {
	# Get indexed directory from stack
	$dir = $stack[$index];

	$dh = @ opendir($dir);
	if (!$dh) {
		echo "Error opening cache directory ($dir).<br /><br />Do you need to edit rs_cache_flush.php for a custom WP_CONTENT_DIR?";
		return;
	}
	
	while (($file = @ readdir($dh)) !== false) {
		if ($file == '.' or $file == '..')
			continue;

		if (@ is_dir($dir . DIRECTORY_SEPARATOR . $file))
			$stack[] = $dir . DIRECTORY_SEPARATOR . $file;
		else if (@ is_file($dir . DIRECTORY_SEPARATOR . $file)) {
			@ unlink($dir . DIRECTORY_SEPARATOR . $file);
			$flushed = true;
		}
	}

	$index++;
}

$stack = array_reverse($stack);  // Last added dirs are deepest
foreach($stack as $dir) {
	if ( $dir != $top_dir) {
		@ rmdir($dir);
		$flushed = true;
	}
}

if ( $flushed )
	echo 'The cache was flushed';
else
	echo 'No cache to flush!';

?>