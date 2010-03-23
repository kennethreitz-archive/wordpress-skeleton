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
<?php if (shopp('customer','accounts','return=true') == "none"): ?>
	<?php shopp('customer','order-lookup'); ?>
<?php return; endif; ?>

<form action="<?php shopp('customer','url'); ?>" method="post" class="shopp" id="login">

<?php if (shopp('customer','process','return=true') == "recover"): ?>

	<ul>
		<li><h3>Recover your password</h3></li>
		<li><?php shopp('customer','login-errors'); ?></li>
		<li>
		<span><?php shopp('customer','account-login','size=20&title=Login'); ?><label for="login"><?php shopp('customer','login-label'); ?></label></span>
		<span><?php shopp('customer','recover-button'); ?></span>
		</li>
		<li></li>
	</ul>

<?php else: ?>
	
<ul>
	<?php if (shopp('customer','notloggedin')): ?>
	<li><?php shopp('customer','login-errors'); ?></li>
	<li>
		<label for="login">Account Login</label>
		<span><?php shopp('customer','account-login','size=20&title=Login'); ?>
			<label for="login"><?php shopp('customer','login-label'); ?></label></span>
		<span><?php shopp('customer','password-login','size=20&title=Password'); ?>
			<label for="password">Password</label></span>
		<span><?php shopp('customer','login-button'); ?></span>
	</li>
	<li><a href="<?php shopp('customer','recover-url'); ?>">Lost your password?</a></li>
	<?php endif; ?>
</ul>

<?php endif; ?>

</form>