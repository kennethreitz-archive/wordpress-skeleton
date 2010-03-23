<?php
/** 
 ** WARNING! DO NOT EDIT!
 **
 ** These templates are part of the core Shopp files 
 ** and will be overwritten when upgrading Shopp.
 **
 ** For editable templates, setup Shopp theme templates:
 ** http://docs.shopplugin.net/Setting_Up_Theme_Templates
 **
 **/
?>
<div id="errors" class="shopp">
	<h3>Error</h3>
	<p>
		<!-- ERROR CODE: <?php shopp('checkout','error','show=code'); ?> -->
		<?php shopp('checkout','error'); ?>
	</p>
</div>