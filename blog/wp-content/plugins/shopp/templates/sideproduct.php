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
<?php if (shopp('product','found')): ?>
	<div class="sideproduct">
	<a href="<?php shopp('product','url'); ?>"><?php shopp('product','thumbnail'); ?></a>

	<h3><a href="<?php shopp('product','url'); ?>"><?php shopp('product','name'); ?></a></h3>

	<?php if (shopp('product','onsale')): ?>
		<p class="original price"><?php shopp('product','price'); ?></p>
		<p class="sale price"><big><?php shopp('product','saleprice'); ?></big></p>
	<?php else: ?>
		<p class="price"><big><?php shopp('product','price'); ?></big></p>
	<?php endif; ?>
	</div>
<?php endif; ?>
