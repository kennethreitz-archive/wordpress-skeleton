<?php require( 'blog/wp-load.php' ); ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8">
		<title>External Wordpress Example</title>
	</head>
	<body id="" onload="">
		<h1>Pages</h1>

		<?php wp_list_pages('title_li='); ?>

	</body>
</html>



