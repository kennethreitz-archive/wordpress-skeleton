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
<?php if (shopp('cart','hasitems')): ?>
<form id="cart" action="<?php shopp('cart','url'); ?>" method="post">
<big>
	<a href="<?php shopp('catalog','url'); ?>">&laquo; Continue Shopping</a>
	<?php if (shopp('checkout','local-payment')): ?>
	<a href="<?php shopp('checkout','url'); ?>" class="right">Proceed to Checkout &raquo;</a>
	<?php endif; ?>
</big>

<?php shopp('cart','function'); ?>
<table class="cart">
	<tr>
		<th scope="col" class="item">Cart Items</th>
		<th scope="col">Quantity</th>
		<th scope="col" class="money">Item Price</th>
		<th scope="col" class="money">Item Total</th>
	</tr>

	<?php while(shopp('cart','items')): ?>
		<tr>
			<td>
				<a href="<?php shopp('cartitem','url'); ?>"><?php shopp('cartitem','name'); ?></a>
				<?php shopp('cartitem','options'); ?>
				<?php shopp('cartitem','inputs-list'); ?>
			</td>
			<td><?php shopp('cartitem','quantity','input=text'); ?>
				<?php shopp('cartitem','remove','input=button'); ?></td>
			<td class="money"><?php shopp('cartitem','unitprice'); ?></td>
			<td class="money"><?php shopp('cartitem','total'); ?></td>
		</tr>
	<?php endwhile; ?>

	<?php while(shopp('cart','promos')): ?>
		<tr><td colspan="4" class="money"><?php shopp('cart','promo-name'); ?><strong><?php shopp('cart','promo-discount','before=&mdash;'); ?></strong></td></tr>
	<?php endwhile; ?>
	
	<tr class="totals">
		<td colspan="2" rowspan="5">
			<?php if (shopp('cart','needs-shipping-estimates')): ?>
			<small>Estimate shipping &amp; taxes for:</small>
			<?php shopp('cart','shipping-estimates'); ?>
			<?php endif; ?>
			<?php shopp('cart','promo-code'); ?>
		</td>
		<th scope="row">Subtotal</th>
		<td class="money"><?php shopp('cart','subtotal'); ?></td>
	</tr>
	<?php if (shopp('cart','hasdiscount')): ?>
	<tr class="totals">
		<th scope="row">Discount</th>
		<td class="money">-<?php shopp('cart','discount'); ?></td>
	</tr>
	<?php endif; ?>
	<?php if (shopp('cart','needs-shipped')): ?>
	<tr class="totals">
		<th scope="row"><?php shopp('cart','shipping','label=Shipping'); ?></th>
		<td class="money"><?php shopp('cart','shipping'); ?></td>
	</tr>
	<?php endif; ?>
	<tr class="totals">
		<th scope="row"><?php shopp('cart','tax','label=Tax'); ?></th>
		<td class="money"><?php shopp('cart','tax'); ?></td>
	</tr>
	<tr class="totals total">
		<th scope="row">Total</th>
		<td class="money"><?php shopp('cart','total'); ?></td>
	</tr>
	<tr class="buttons">
		<td colspan="4"><?php shopp('cart','update-button'); ?></td>
	</tr>
</table>

<big>
	<a href="<?php shopp('catalog','url'); ?>">&laquo; Continue Shopping</a>
	<?php if (shopp('checkout','local-payment')): ?>
	<a href="<?php shopp('checkout','url'); ?>" class="right">Proceed to Checkout &raquo;</a>
	<?php endif; ?>
</big>

</form>

<div class="xcheckout">
<?php shopp('checkout','xco-buttons'); ?>
</div>

<?php else: ?>
	<p class="warning">There are currently no items in your shopping cart.</p>
<?php endif; ?>
