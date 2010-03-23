<div class="wrap shopp">

	<h2><?php _e('Order','Shopp'); ?></h2>

	<?php if (!empty($updated)): ?><div id="message" class="updated fade"><p><?php echo $updated; ?></p></div><?php endif; ?>

	<?php include("navigation.php"); ?>
	<input type="submit" id="print-button" value="<?php _e('Print Order','Shopp'); ?>" class="button" />
	<br class="clear" />
		
	<div id="order">
		<br class="clear" />
		<div id="receipt" class="shopp">
		<table class="transaction" cellspacing="0">
			<tr><th><?php _e('Order Num','Shopp'); ?>:</th><td><?php echo $Purchase->id; ?></td></tr>	
			<tr><th><?php _e('Order Date','Shopp'); ?>:</th><td><?php echo _d(get_option('date_format'), $Purchase->created); ?></td></tr>	
			<?php if (!empty($Purchase->card) && !empty($Purchase->cardtype)): ?><tr><th><?php _e('Billed To','Shopp'); ?>:</th><td><?php (!empty($Purchase->card))?printf("%'X16d",$Purchase->card):''; ?> <?php echo (!empty($Purchase->cardtype))?'('.$Purchase->cardtype.')':''; ?></td></tr><?php endif; ?>
			<tr><th><?php _e('Transaction','Shopp'); ?>:</th><td><?php echo $Purchase->transactionid; ?></td></tr>	
			<?php if ($Purchase->gateway == "Google Checkout"):?>
				<tr><th><?php _e('Status','Shopp'); ?>:</th><td><?php echo $Purchase->transtatus; ?></td></tr>
			<?php endif; ?>
			<tr><td colspan="2"><br class="clear" /></td></tr>
			<?php if (!empty($Purchase->phone)):?>
				<tr><th><?php _e('Phone','Shopp'); ?>:</th><td><?php echo $Purchase->phone; ?></td></tr>
			<?php endif; ?>
			<?php if (!empty($Purchase->email)):?>
				<tr><th><?php _e('Email','Shopp'); ?>:</th><td><?php echo '<a href="mailto:'.$Purchase->email.'">'.$Purchase->email.'</a>'; ?></td></tr>
			<?php endif; ?>
			<tr><td colspan="2"><br class="clear" /></td></tr>
			<?php if (!empty($Purchase->data) && is_array($Purchase->data)): 
				foreach ($Purchase->data as $name => $value): ?>
				<tr><th><?php echo $name; ?>:</th><td><?php if (strpos($value,"\n")): ?><textarea name="orderdata[<?php echo $name; ?>]" readonly="readonly" cols="30" rows="4"><?php echo $value; ?></textarea><?php else: echo $value; endif; ?></td></tr>
			<?php endforeach; endif; ?>
		</table>

		<fieldset id="customer">
			<legend><?php _e('Customer','Shopp'); ?></legend>
			<address><big><?php echo "{$Purchase->firstname} {$Purchase->lastname}"; ?></big><br />
			<?php echo $Purchase->address; ?><br />
			<?php if (!empty($Purchase->xaddress)) echo $Purchase->xaddress."<br />"; ?>
			<?php echo "{$Purchase->city}".(!empty($Purchase->shipstate)?', ':'')." {$Purchase->state} {$Purchase->postcode}" ?><br />
			<?php echo $targets[$Purchase->country]; ?></address>
			<?php if (!empty($Customer->info) && is_array($Customer->info)): ?>
				<ul>
					<?php foreach ($Customer->info as $name => $value): ?>
					<li><strong><?php echo $name; ?>:</strong> <?php echo $value; ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</fieldset>

		<?php if (!empty($Purchase->shipaddress)): ?>
			<fieldset id="shipto">
				<legend><?php _e('Ship To','Shopp'); ?></legend>
				<address><big><?php echo "{$Purchase->firstname} {$Purchase->lastname}"; ?></big><br />
				<?php echo !empty($Purchase->company)?"$Purchase->company<br />":""; ?>
				<?php echo $Purchase->shipaddress; ?><br />
				<?php if (!empty($Purchase->shipxaddress)) echo $Purchase->shipxaddress."<br />"; ?>
				<?php echo "{$Purchase->shipcity}".(!empty($Purchase->shipstate)?', ':'')." {$Purchase->shipstate} {$Purchase->shippostcode}" ?><br />
				<?php echo $targets[$Purchase->shipcountry]; ?></address>
				<?php if (!empty($Purchase->shipmethod)): ?>
					<p><strong><?php _e('Shipping','Shopp'); ?>:</strong> <?php echo $Purchase->shipmethod; ?></p>
				<?php endif;?>
			</fieldset>
		<?php endif; ?>
		
		<?php if (sizeof($Purchase->purchased) > 0): ?>
		<table class="widefat" cellspacing="0">
			<thead>
			<tr>
				<th scope="col" class="item"><?php _e('Items Ordered','Shopp'); ?></th>
				<th scope="col"><?php _e('Quantity','Shopp'); ?></th>
				<th scope="col" class="money"><?php _e('Item Price','Shopp'); ?></th>
				<th scope="col" class="money"><?php _e('Item Total','Shopp'); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php $even = false; foreach ($Purchase->purchased as $id => $Item): ?>
				<tr<?php if ($even) echo ' class="alternate"'; $even = !$even; ?>>
					<td>
						<?php echo $Item->name; ?>
						<?php if (!empty($Item->optionlabel)) echo "({$Item->optionlabel})"; ?>
						<?php if (is_array($Item->data) || !empty($Item->sku)): ?>
						<ul>
						<?php if (!empty($Item->sku)): ?><li><small><?php _e('SKU','Shopp'); ?>: <strong><?php echo $Item->sku; ?></strong></small></li><?php endif; ?>
						<?php foreach ($Item->data as $key => $value): ?>
							<li><small><?php echo $key; ?>: <strong><?php echo $value; ?></strong></small></li>
						<?php endforeach; endif; ?>
						</ul>
					</td>
					<td><?php echo $Item->quantity; ?></td>
					<td class="money"><?php echo money($Item->unitprice+($Item->unitprice*$taxrate)); ?></td>
					<td class="money total"><?php echo money($Item->total+($Item->total*$taxrate)); ?></td>
				</tr>
			<?php endforeach; ?>
			<tr class="totals">
				<th scope="row" colspan="3" class="total"><?php _e('Subtotal','Shopp'); ?></th>
				<td class="money"><?php echo money($Purchase->subtotal); ?></td>
			</tr>
			<?php if ($Purchase->discount > 0): ?>
			<tr class="totals">
				<th scope="row" colspan="3" class="total"><?php _e('Discount','Shopp'); ?></th>
				<td class="money">-<?php echo money($Purchase->discount); ?>
					<?php if (!empty($Purchase->promos)): ?>
					<ul class="promos">
					<?php foreach ($Purchase->promos as $pid => $promo): ?>
						<li><small><a href="?page=shopp-promotions-edit&amp;id=<?php echo $pid; ?>"><?php echo $promo; ?></a></small></li>
					<?php endforeach; ?>
					</ul>
					<?php endif; ?>
					</td>
			</tr>
			<?php endif; ?>
			<?php if ($Purchase->freight > 0): ?>
			<tr class="totals">
				<th scope="row" colspan="3" class="total"><?php _e('Shipping','Shopp'); ?></th>
				<td class="money"><?php echo money($Purchase->freight); ?></td>
			</tr>
			<?php endif; ?>
			<?php if ($Purchase->tax > 0): ?>
			<tr class="totals">
				<th scope="row" colspan="3" class="total"><?php _e('Tax','Shopp'); ?></th>
				<td class="money"><?php echo money($Purchase->tax); ?></td>
			</tr>
			<?php endif; ?>
			<tr class="totals total">
				<th scope="row" colspan="3" class="total"><?php _e('Total','Shopp'); ?></th>
				<td class="money"><?php echo money($Purchase->total); ?></td>
			</tr>
			</tbody>
		</table>
		<?php else: ?>
			<p class="warning"><?php _e('There were no items found for this purchase.','Shopp'); ?></p>
		<?php endif; ?>
		</div>
		
		<form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post" id="order-status">
		<?php wp_nonce_field('shopp-save-order'); ?>
		<div id="notification">
		<div class="tablenav"><p class="alignright"><input type="hidden" name="receipt" value="no" /><input type="checkbox" name="receipt" value="yes" id="include-order" checked="checked" /><label for="include-order">&nbsp;<?php _e('Include a copy of the order in the message','Shopp'); ?></label></p>
		</div>
		<br class="clear" />
		<p><textarea name="message" id="message" cols="50" rows="10" ></textarea></p>
		</div>
		<div class="tablenav">
			<p class="alignright">
				<label for="txn_status_menu"><?php _e('Payment','Shopp'); ?>:</label>
				<select name="transtatus" id="txn_status_menu">
				<?php echo menuoptions($txnStatusLabels,$Purchase->transtatus,true,true); ?>
				</select>
				&nbsp;
				<label for="order_status_menu"><?php _e('Order Status','Shopp'); ?>:</label>
				<select name="status" id="order_status_menu">
				<?php echo menuoptions($statusLabels,$Purchase->status,true); ?>
				</select>
				<span class="middle"><input type="hidden" name="notify" value="no" /><input type="checkbox" name="notify" value="yes" id="notify-customer" /><label for="notify-customer">&nbsp;<?php _e('Send customer notification','Shopp'); ?></label></span>
				<button type="submit" name="update" value="status" class="button-secondary"><?php _e('Update Status','Shopp'); ?></button></p>
		</div>
		</form>
	</div>
	
</div>

<iframe id="print-receipt" name="receipt" src="?page=shopp-lookup&amp;lookup=receipt&amp;id=<?php echo $Purchase->id; ?>" width="400" height="100" class="invisible"></iframe>

<script type="text/javascript">
(function($){
$('#notification').hide();
$('#notify-customer').click(function () {
	$('#notification').animate({ 
		height: "toggle", 
		opacity:"toggle" 
	}, 500);
});

$('#print-button').click(function () {
	var frame = $('#print-receipt').get(0);
	if ($.browser.opera || $.browser.msie) {
		var preview = window.open(frame.contentWindow.location.href+"&print=auto");
		$(preview).load(function () {
			preview.close();
		});
	} else {
		frame.contentWindow.focus();
		frame.contentWindow.print();
	}

});

$('#customer').click(function () {
	window.location = "<?php echo add_query_arg(array('page'=>$this->Admin->editcustomer,'id'=>$Purchase->customer),$Shopp->wpadminurl.'admin.php'); ?>";
});


})(jQuery)

</script>