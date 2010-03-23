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
<div id="receipt" class="shopp">
<table class="transaction">
	<tr><th>Order Num:</th><td><?php shopp('purchase','id'); ?></td></tr>	
	<tr><th>Order Date:</th><td><?php shopp('purchase','date','format=F j, Y'); ?></td></tr>	
	<tr><th>Billed To:</th><td><?php shopp('purchase','card'); ?> (<?php shopp('purchase','cardtype'); ?>)</td></tr>	
	<tr><th>Transaction:</th><td><?php shopp('purchase','transactionid'); ?></td></tr>	
</table>

<fieldset>
	<legend>Billed to</legend>
	<address><big><?php shopp('purchase','firstname'); ?> <?php shopp('purchase','lastname'); ?></big><br />
	<?php shopp('purchase','company'); ?><br />
	<?php shopp('purchase','address'); ?><br />
	<?php shopp('purchase','xaddress'); ?>
	<?php shopp('purchase','city'); ?>, <?php shopp('purchase','state'); ?> <?php shopp('purchase','postcode'); ?><br />
	<?php shopp('purchase','country'); ?></address>
</fieldset>

<?php if (shopp('purchase','hasfreight')): ?>
	<fieldset class="shipping">
		<legend>Ship to</legend>
		<address><big><?php shopp('purchase','firstname'); ?> <?php shopp('purchase','lastname'); ?></big><br />
		<?php shopp('purchase','shipaddress'); ?><br />
		<?php shopp('purchase','shipxaddress'); ?>
		<?php shopp('purchase','shipcity'); ?>, <?php shopp('purchase','shipstate'); ?> <?php shopp('purchase','shippostcode'); ?><br />
		<?php shopp('purchase','shipcountry'); ?></address>
		
		<p>Shipping: <?php shopp('purchase','shipmethod'); ?></p>
	</fieldset>	
<?php endif; ?>

<br class="clear" />

<?php if (shopp('purchase','hasitems')): ?>
<table class="order widefat">
	<thead>
	<tr>
		<th scope="col" class="item">Items Ordered</th>
		<th scope="col">Quantity</th>
		<th scope="col" class="money">Item Price</th>
		<th scope="col" class="money">Item Total</th>
	</tr>
	</thead>

	<?php while(shopp('purchase','items')): ?>
		<tr>
			<td><?php shopp('purchase','item-name'); ?><?php shopp('purchase','item-options','before= – '); ?><br />
				<?php shopp('purchase','item-sku'); ?><br />
				<?php shopp('purchase','item-download'); ?>
				<?php shopp('purchase','item-inputs-list'); ?>
			</td>
			<td><?php shopp('purchase','item-quantity'); ?></td>
			<td class="money"><?php shopp('purchase','item-unitprice'); ?></td>
			<td class="money"><?php shopp('purchase','item-total'); ?></td>
		</tr>
	<?php endwhile; ?>

	<tr class="totals">
		<th scope="row" colspan="3" class="total">Subtotal</th>
		<td class="money"><?php shopp('purchase','subtotal'); ?></td>
	</tr>
	<?php if (shopp('purchase','hasdiscount')): ?>
	<tr class="totals">
		<th scope="row" colspan="3" class="total">Discount</th>
		<td class="money">-<?php shopp('purchase','discount'); ?></td>
	</tr>
	<?php endif; ?>
	<?php if (shopp('purchase','hasfreight')): ?>
	<tr class="totals">
		<th scope="row" colspan="3" class="total">Shipping</th>
		<td class="money"><?php shopp('purchase','freight'); ?></td>
	</tr>
	<?php endif; ?>
	<?php if (shopp('purchase','hastax')): ?>
	<tr class="totals">
		<th scope="row" colspan="3" class="total">Tax</th>
		<td class="money"><?php shopp('purchase','tax'); ?></td>
	</tr>
	<?php endif; ?>
	<tr class="totals">
		<th scope="row" colspan="3" class="total">Total</th>
		<td class="money"><?php shopp('purchase','total'); ?></td>
	</tr>
</table>

<?php if(shopp('purchase','has-data')): ?>
	<ul>
	<?php while(shopp('purchase','orderdata')): ?>
		<li><strong><?php shopp('purchase','data','name'); ?>:</strong> <?php shopp('purchase','data'); ?></li>
	<?php endwhile; ?>
	</ul>
<?php endif; ?>

<?php else: ?>
	<p class="warning">There were no items found for this purchase.</p>
<?php endif; ?>
</div>