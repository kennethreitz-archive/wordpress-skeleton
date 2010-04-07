<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php wp_head(); ?>
	<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" charset="utf-8"/>
</head>
<body>
	<div id="wrapper">
		<div id="header">
			<h1>Debug theme by Yoast</h1>
		</div>
		<?php if (!is_home()) { ?>
		<div class="box double">
			<a href="<?php bloginfo('wpurl'); ?>">Home</a>
		</div>
		<?php } ?>
		