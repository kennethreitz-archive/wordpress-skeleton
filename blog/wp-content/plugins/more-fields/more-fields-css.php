<?php
	require_once(dirname(__FILE__).'/../../../wp-config.php');
	header("Content-Type: text/css");
	global $mf0;
?>



<?php

	$pages = $mf0->get_pages();
	foreach ($pages as $page) : 
		if (!($icon = $page['icon'])) { 
?>
	#adminmenu #menu-<?php echo sanitize_title($page['name']); ?> div.wp-menu-image {
		background: transparent url("../../../wp-admin/images/menu-vs.png") no-repeat scroll -151px -1px;
	}
	#adminmenu #menu-<?php echo sanitize_title($page['name']); ?>:hover div.wp-menu-image  {
		background: transparent url("../../../wp-admin/images/menu-vs.png") no-repeat scroll -151px -33px;
	}
<?php } else { ?>
	#adminmenu #menu-<?php echo sanitize_title($page['name']); ?> div.wp-menu-image {
		background: transparent url("<?php echo $icon; ?>") no-repeat
	}
	<?php if ($hover = $page['icon_hover']) : ?>
		#adminmenu #menu-<?php echo sanitize_title($page['name']); ?>:hover div.wp-menu-image  {
			background: transparent url("<?php echo $hover; ?>") no-repeat scroll;
		}
	<?php endif; ?>
<?php } ?>

<?php endforeach; ?>


.mf_label { 
	display: block; 
	margin-left: 2px; 
}
.mf_text { 
	width: 95%; 
	margin-left: 1px; 
}
.mf_textarea { 
	width: 95%; 
	margin-left: 1px; 
	height: 150px;
}

.mf_select {
	width: 96%; 
}

.wrap #post-body h2 {
	clear: none;
}
/*
#post-body .inside p{
	overflow: hidden;
	padding-bottom: 4px;
}
*/


/* Joen's CSS changes for the WYSIWYG */

.inside .mceResize {
	margin-top: -45px !important;
}
.inside .mceEditor iframe {
	border-left: 1px solid #dfdfdf;
	border-right: 1px solid #dfdfdf;
	box-sizing: border-box;
	-moz-box-sizing:border-box; /* Mozilla: Change Box Model Behaviour */
}