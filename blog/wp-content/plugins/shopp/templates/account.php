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
<?php if (!shopp('customer','process','return=true')): ?>
<?php if(shopp('customer','errors-exist')): ?>
	<p><?php shopp('customer','login-errors'); ?></p>
<?php endif; ?>

<ul>
<?php while (shopp('customer','menu')): ?>
	<li><a href="<?php shopp('customer','management','url'); ?>"><?php shopp('customer','management'); ?></a></li>
<?php endwhile; ?>
</ul>

<?php return true; endif; ?>

<form action="<?php shopp('customer','url'); ?>" method="post" class="shopp">

<?php if (shopp('customer','process','return=true') == "account"): ?>
	<p><a href="<?php shopp('customer','url'); ?>">&laquo; Return to Account Management</a></p>
	<ul>
		<li>
			<label for="firstname">Your Account</label>
			<span><?php shopp('checkout','firstname','required=true&minlength=2&size=8&title=First Name'); ?><label for="firstname">First</label></span>
			<span><?php shopp('customer','lastname','required=true&minlength=3&size=14&title=Last Name'); ?><label for="lastname">Last</label></span>
		</li>
		<li>
			<span><?php shopp('customer','company','size=20&title=Company'); ?><label for="company">Company</label></span>
		</li>
		<li>
			<span><?php shopp('customer','phone','format=phone&size=15&title=Phone'); ?><label for="phone">Phone</label></span>
		</li>
		<li>
			<span><?php shopp('customer','email','required=true&format=email&size=30&title=Email'); ?>
			<label for="email">Email</label></span>
		</li>
		<?php while (shopp('customer','hasinfo')): ?>
		<li>
			<span><?php shopp('customer','info','type=text'); ?>
			<label></label></span>
		</li>
		<?php endwhile; ?>
		<li>
			<label for="password">Change Your Password</label>
			<span><?php shopp('checkout','password','required=true&minlength=3&size=14&title=New Password'); ?><label for="password">New Password</label></span>
			<span><?php shopp('customer','confirm-password','required=true&minlength=3&size=14&title=Confirm Password'); ?><label for="confirm-password">Confirm Password</label></span>
		</li>
	</ul>	
	<br class="clear" />
	<p><?php shopp('customer','save-button','label=Save'); ?></p>
	<p><a href="<?php shopp('customer','url'); ?>">&laquo; Return to Account Management</a></p>
	
<?php endif; // end account ?>

<?php if (shopp('customer','process','return=true') == "downloads"): ?>
	
	<h3>Downloads</h3>
	<p><a href="<?php shopp('customer','url'); ?>">&laquo; Return to Account Management</a></p>
	<?php if (shopp('customer','has-downloads')): ?>
	<table cellspacing="0" cellpadding="0">
		<thead>
			<tr>
				<th scope="col">Product</th>
				<th scope="col">Order</th>
				<th scope="col">Amount</th>
			</tr>
		</thead>
		<?php while(shopp('customer','downloads')): ?>
		<tr>
			<td><?php shopp('customer','download','name'); ?> <?php shopp('customer','download','variation'); ?><br />
				<small><a href="<?php shopp('customer','download','url'); ?>">Download File</a> (<?php shopp('customer','download','size'); ?>)</small></td>
			<td><?php shopp('customer','download','purchase'); ?><br />
				<small><?php shopp('customer','download','date'); ?></small></td>
			<td><?php shopp('customer','download','total'); ?><br />
				<small><?php shopp('customer','download','downloads'); ?> Downloads</small></td>
		</tr>
		<?php endwhile; ?>
	</table>
	<?php else: ?>
	<p>You have no digital product downloads available.</p>
	<?php endif; // end 'has-downloads' ?>
	<p><a href="<?php shopp('customer','url'); ?>">&laquo; Return to Account Management</a></p>

<?php endif; // end downloads ?>

<?php if (shopp('customer','process','return=true') == "history"): ?>
	<?php if (shopp('customer','has-purchases')): ?>
		<p><a href="<?php shopp('customer','url'); ?>">&laquo; Return to Account Management</a></p>
		<table cellspacing="0" cellpadding="0">
			<thead>
				<tr>
					<th scope="col">Date</th>
					<th scope="col">Order</th>
					<th scope="col">Status</th>
					<th scope="col">Total</th>
				</tr>
			</thead>
			<?php while(shopp('customer','purchases')): ?>
			<tr>
				<td><?php shopp('purchase','date'); ?></td>
				<td><?php shopp('purchase','id'); ?></td>
				<td><?php shopp('purchase','status'); ?></td>
				<td><?php shopp('purchase','total'); ?></td>
				<td><a href="<?php shopp('customer','receipt'); ?>">View Order</a></td>
			</tr>
			<?php endwhile; ?>
		</table>
		<p><a href="<?php shopp('customer','url'); ?>">&laquo; Return to Account Management</a></p>
	<?php else: ?>
	<p>You have no orders, yet.</p>
	<?php endif; // end 'has-purchases' ?>
	
<?php endif; // end history ?>

<?php if (shopp('customer','process','return=true') == "status"): ?>
	<?php if (shopp('customer','has-purchases','daysago=15')): ?>
		<p><a href="<?php shopp('customer','url'); ?>">&laquo; Return to Account Management</a></p>
		<table cellspacing="0" cellpadding="0">
			<thead>
				<tr>
					<th scope="col">Date</th>
					<th scope="col">Order</th>
					<th scope="col">Status</th>
					<th scope="col">Total</th>
				</tr>
			</thead>
			<?php while(shopp('customer','purchases')): ?>
			<tr>
				<td><?php shopp('purchase','date'); ?></td>
				<td><?php shopp('purchase','id'); ?></td>
				<td><?php shopp('purchase','status'); ?></td>
				<td><?php shopp('purchase','total'); ?></td>
				<td><a href="<?php shopp('customer','receipt'); ?>">View Order</a></td>
			</tr>
			<?php endwhile; ?>
		</table>
		<p><a href="<?php shopp('customer','url'); ?>">&laquo; Return to Account Management</a></p>
	<?php else: ?>
	<p>There are no recent orders within the last 15 days.</p>
	<?php endif; // end 'has-purchases' ?>
	
<?php endif; // end status ?>

<br class="clear" />
</form>
