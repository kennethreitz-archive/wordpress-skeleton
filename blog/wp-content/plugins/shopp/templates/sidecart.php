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
<div id="shopp-cart-ajax"></div>
<?php if (shopp('cart','hasitems')): ?>	
	<p class="status">
		<span id="shopp-sidecart-items"><?php shopp('cart','totalitems'); ?></span> <strong>Items</strong><br />
		<span id="shopp-sidecart-total" class="money"><?php shopp('cart','total'); ?></span> <strong>Total</strong> 
	</p>
	<ul>
		<li><a href="<?php shopp('cart','url'); ?>">Edit shopping cart</a></li>
		<?php if (shopp('checkout','local-payment')): ?>
		<li><a href="<?php shopp('checkout','url'); ?>">Proceed to Checkout</a></li>
		<?php endif; ?>
	</ul>
<?php else: ?>
	<p class="status">Your cart is empty.</p>
<?php endif; ?>