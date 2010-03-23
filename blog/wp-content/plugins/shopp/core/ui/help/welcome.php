<div id="welcome" class="wrap">
	<h2><img src="/wp-content/plugins/shopp/core/ui/icons/shopp.png" width="24" height="24"/> <?php _e('Welcome to Shopp','Shopp'); ?></h2>
	
	<h3><?php _e('Congratulations on choosing Shopp and WordPress for your e-commerce solution!','Shopp'); ?></h3>
	
	<p><?php _e('Before you dive in to setup, here are a few things to keep in mind:','Shopp'); ?></p>
	
	<ul>
		<li><strong><?php _e('Shopp has lots of easy to find help built-in.','Shopp'); ?></strong><br />
			<?php printf(__('Click the %sHelp menu%s to access help articles about the screen you are using, directly from the %sofficial documentation%s.','Shopp'),'<strong>','</strong>','<a href="http://docs.shopplugin.net" target="_blank">','</a>'); ?>
			<ul>
				<li><?php printf(__('You can also get community help from the %sShopp Forums</a>','Shopp'),'<a href="http://forums.shopplugin.net">','</a>'); ?></li>
				<li><?php _e('Or, get live online support by purchasing a support plan','Shopp'); ?></li>
				<li><?php _e('Find qualified Shopp professionals you can contract for customization consulting work','Shopp'); ?></li>
			</ul>
			</li>
		<li><strong><?php _e('Easy setup in just a few steps.','Shopp'); ?></strong><br /><?php _e('Setup is simple and takes about 10-15 minutes.  Just jump through each of the settings screens to configure your store.','Shopp'); ?></li>
		<li><strong><?php _e('Don\'t forget to activate your key!','Shopp'); ?></strong><br /><?php printf(__('Be sure to visit the %sShopp%s &rarr; %sSettings%s &rarr; %sUpdate%s screen and activate your update key so you can get trouble-free, automated updates.','Shopp'),'<strong>','</strong>','<strong>','</strong>','<strong><a href="admin.php?page='.$this->Flow->Admin->settings['update'][0].'">','</a></strong>'); ?></li>
		<li><strong><?php _e('Show It Off','Shopp')?></strong><br /><?php printf(__('Once you\'re up and running, drop by the Shopp website and %ssubmit your site%s to be included in the Shopp-powered website showcase.','Shopp'),'<a href="http://shopplugin.net/showcase">','</a>'); ?></li>
	</ul>
	
	<br />
	<form action="admin.php?page=<?php echo $this->Flow->Admin->settings['settings'][0]; ?>" method="post">
	<div class="alignright"><input type="submit" name="setup" value="<?php _e('Continue to Shopp Setup','Shopp'); ?>&hellip;" class="button-primary" /></div>
	
	<p><input type="hidden" name="settings[show_welcome]" value="off" /><input type="checkbox" name="settings[show_welcome]" id="welcome-toggle" value="on" <?php echo ($this->Settings->get('show_welcome') == "on")?' checked="checked"':''; ?> /><label for="welcome-toggle"> <small><?php _e('Show this screen every time after activating Shopp','Shopp'); ?></small></label></p>
	</form>
</div>