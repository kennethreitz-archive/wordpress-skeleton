<?php
/* this function comes almost verbatim from akismet.php... its allowing me to get rid of the curl dependence */
function AtD_http_get($host, $path, $port = 80) {
	$http_request  = "GET $path HTTP/1.0\r\n";
	$http_request .= "Host: $host\r\n";
	$http_request .= "User-Agent: AtD/0.1\r\n";
	$http_request .= "\r\n";

	$response = '';
	if( false != ( $fs = @fsockopen($host, $port, $errno, $errstr, 10) ) ) {
		fwrite($fs, $http_request);

		while ( !feof($fs) ) {
			$response .= fgets($fs);
		}
		fclose($fs);
		$response = explode("\r\n\r\n", $response, 2);
	}
	return $response;
}

/* these functions exist to abstract a difference from AtD/WP.org and AtD/WP.com */
function AtD_update_setting($user_id, $name, $value) {
	update_usermeta( $user_id, $name, $value);
}

function AtD_get_setting($user_id, $name, $value = null) {
	return get_usermeta( $user_id, $name, $value);
}
