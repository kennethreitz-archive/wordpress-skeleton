<?php
global $wp_version;
global $dsq_version;
global $dsq_api;

if ( !current_user_can('manage_options') ) {
	die();
}

if(isset($_POST['dsq_username'])) {
	$_POST['dsq_username'] = stripslashes($_POST['dsq_username']);
}

if(isset($_POST['dsq_password'])) {
	$_POST['dsq_password'] = stripslashes($_POST['dsq_password']);
}

// HACK: For old versions of WordPress
if ( !function_exists('wp_nonce_field') ) {
	function wp_nonce_field() {}
}

// Handle export function.
if( isset($_POST['export']) ) {
	require_once(dirname(__FILE__) . '/export.php');
	dsq_export_wp();
}

// Handle uninstallation.
if ( isset($_POST['uninstall']) ) {
	update_option('disqus_forum_url', '');
	update_option('disqus_api_key', '');
}

// Clean-up POST parameters.
foreach ( array('dsq_forum_url', 'dsq_username') as $key ) {
	if ( isset($_POST[$key]) ) { $_POST[$key] = strip_tags($_POST[$key]); }
}

// Handle installation process.
if ( isset($_POST['dsq_forum_url']) && isset($_POST['dsq_username']) && isset($_POST['dsq_password']) ) {
	$api_key = $dsq_api->get_forum_api_key($_POST['dsq_username'], $_POST['dsq_password'], $_POST['dsq_forum_url']);
	update_option('disqus_forum_url', $_POST['dsq_forum_url']);

	if ( is_numeric($api_key) && $api_key < 0 ) {
		update_option('disqus_replace', 'replace');
		dsq_manage_dialog('There was an error completing the installation of Disqus. If you are still having issues, refer to the <a href="http://disqus.com/comments/wordpress">WordPress help page</a>.', true);
	} else {
		update_option('disqus_api_key', $api_key);
		update_option('disqus_replace', 'all');
	}
}

// Handle advanced options.
if ( isset($_POST['disqus_forum_url']) && isset($_POST['disqus_replace']) ) {
	$disqus_forum_url = $_POST['disqus_forum_url'];
	if ( $dot_pos = strpos($disqus_forum_url, '.') ) {
		$disqus_forum_url = substr($disqus_forum_url, 0, $dot_pos);
	}
	update_option('disqus_forum_url', $disqus_forum_url);
	update_option('disqus_replace', $_POST['disqus_replace']);

	if(isset($_POST['disqus_cc_fix'])) {
		update_option('disqus_cc_fix', true);
	} else {
		update_option('disqus_cc_fix', false);
	}

	dsq_manage_dialog('Your settings have been changed.');
}

// Get installation step process (or 0 if we're already installed).
$step = intval($_GET['step']);
$step = ($step > 0 && isset($_POST['dsq_username'])) ? $step : 1;
$step = (dsq_is_installed()) ? 0 : $step;

if ( 2 == $step && isset($_POST['dsq_username']) && isset($_POST['dsq_password']) ) {
	$dsq_sites = $dsq_api->get_forum_list($_POST['dsq_username'], $_POST['dsq_password']);
	if ( $dsq_sites < 0 ) {
		$step = 1;
		if ( -2 == $dsq_sites ) {
			dsq_manage_dialog('Invalid password.', true);
		} else {
			dsq_manage_dialog('Unexpected error.', true);
		}
	}
}

// HACK: Our own styles for older versions of WordPress.
if ( $wp_version < 2.5 ) {
	echo "<link rel='stylesheet' href='" . DSQ_PLUGIN_URL . "/styles/manage-pre25.css' type='text/css' />";
}

?>
<!-- Header -->
<link rel='stylesheet' href='<?php echo DSQ_PLUGIN_URL; ?>/styles/manage.css' type='text/css' />
<script type="text/javascript" src='<?php echo DSQ_PLUGIN_URL; ?>/scripts/manage.js'></script>

<div class="wrap" id="dsq-wrap">
	<img src="<?php echo DSQ_PLUGIN_URL; ?>/images/logo.png">

	<ul id="dsq-tabs">
		<li class="selected" id="dsq-tab-main"><?php echo (dsq_is_installed() ? 'Manage' : 'Install'); ?></li>
		<li id="dsq-tab-advanced">Advanced Options</li>
	</ul>
<!-- /Header -->

	<div id="dsq-main" class="dsq-content">
<?php
switch ( $step ) {
case 2:
?>
		<div id="dsq-step-2" class="dsq-main">
			<h2>Install Disqus Comments</h2>

			<form method="POST" action="?page=disqus">
			<?php wp_nonce_field('dsq-install-2'); ?>
			<table class="form-table">
				<tr>
					<th scope="row" valign="top">Select a website</th>
					<td>
<?php
foreach ( $dsq_sites as $counter => $dsq_site ):
?>
						<input name="dsq_forum_url" type="radio" id="dsq-site-<?php echo $counter; ?>" value="<?php echo $dsq_site['short_name']; ?>" />
						<label for="dsq-site-<?php echo $counter; ?>"><strong><?php echo $dsq_site['name']; ?></strong> (<u><?php echo $dsq_site['short_name']; ?>.disqus.com</u>)</label>
						<br />
<?php
endforeach;
?>
						<hr />
						<a href="http://disqus.com/comments/register/">Or register a new one on the Disqus website</a>.
					</td>
				</tr>
			</table>

			<p class="submit" style="text-align: left">
				<input type="hidden" name="dsq_username" value="<?php echo $_POST['dsq_username']; ?>">
				<input type="hidden" name="dsq_password" value="<?php echo str_replace('"', '&quot;', $_POST['dsq_password']); ?>">
				<input name="submit" type="submit" value="Next &raquo;" />
			</p>
			</form>
		</div>
<?php
	break;
case 1:
?>
		<div id="dsq-step-1" class="dsq-main">
			<h2>Install Disqus Comments</h2>

			<form method="POST" action="?page=disqus&step=2">
			<?php wp_nonce_field('dsq-install-1'); ?>
			<table class="form-table">
				<tr>
					<th scope="row" valign="top">Username</th>
					<td>
						<input id="dsq-username" name="dsq_username" tabindex="1">
						<a href="http://disqus.com/profile/">(don't have a Disqus Profile yet?)</a>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top">Password</th>
					<td>
						<input type="password" id="dsq-password" name="dsq_password" tabindex="2">
						<a href="http://disqus.com/forgot/">(forgot your password?)</a>
					</td>
				</tr>
			</table>

			<p class="submit" style="text-align: left">
				<input name="submit" type="submit" value="Next &raquo;" tabindex="3">
			</p>

			<script type="text/javascript"> document.getElementById('dsq-username').focus(); </script>
			</form>
		</div>
<?php
	break;
case 0:
?>
		<div class="dsq-main">
			<h2>DISQUS Comments</h2>
			<hr />
			<iframe src="<?php echo DISQUS_API_URL; ?>/admin/moderate/<?php echo get_option('disqus_forum_url'); ?>/?template=wordpress" style="width: 100%; height: 800px"></iframe>
		</div>
<?php } ?>
	</div>

<?php
	$dsq_replace = get_option('disqus_replace');
	$dsq_forum_url = strtolower(get_option('disqus_forum_url'));
	$dsq_api_key = get_option('disqus_api_key');
	$dsq_cc_fix = get_option('disqus_cc_fix');

	if(dsq_is_installed()) {
		$dsq_last_import_id = get_option('disqus_last_import_id');
		$dsq_import_status = $dsq_api->get_import_status($dsq_last_import_id);
	}
?>
	<!-- Advanced options -->
	<div id="dsq-advanced" class="dsq-content" style="display:none;">
		<h2>Advanced Options</h2>
		Version: <?php echo $dsq_version; ?>
<?php
if(function_exists('curl_init')) {
	echo ' (Using cURL libraries.)';
} else if(ini_get('allow_url_fopen') && function_exists('stream_get_contents')) {
	echo ' (Using fopen.)';
} else {
	echo ' (Using fsockopen.)';
}
?>
		<form method="POST">
		<?php wp_nonce_field('dsq-advanced'); ?>
		<table class="form-table">
			<tr>
				<th scope="row" valign="top">Disqus short name</th>
				<td>
					<input name="disqus_forum_url" value="<?php echo $dsq_forum_url; ?>" tabindex="1">
					<br />
					This is the unique identifier for your website on Disqus Comments.
				</td>
			</tr>

			<tr>
				<th scope="row" valign="top">Disqus API Key</th>
				<td>
					<input type="text" name="disqus_api_key" value="<?php echo $dsq_api_key; ?>" tabindex="2">
					<br />
					This is set for you when going through the installation steps.
				</td>
			</tr>

			<tr>
				<th scope="row" valign="top">Use Disqus Comments on</th>
				<td>
					<select name="disqus_replace" tabindex="3" class="disqus-replace">
						<?php if ( dsq_legacy_mode() ) : ?>
							<option value="empty" <?php if('empty'==$dsq_replace){echo 'selected';}?>>Only future blog posts and existing posts without WordPress comments.</option>
						<?php endif ; ?>
						<option value="all" <?php if('all'==$dsq_replace){echo 'selected';}?>>On all existing and future blog posts.</option>
						<option value="closed" <?php if('closed'==$dsq_replace){echo 'selected';}?>>Only on blog posts with closed comments.</option>
					</select>
					<br />
					NOTE: Your WordPress comments will never be lost.
				</td>
			</tr>
			
			<tr>
				<th scope="row" valign="top">Comment Count</th>
				<td>
					<input type="checkbox" id="disqus_comment_count" name="disqus_cc_fix" <?php if($dsq_cc_fix){echo 'checked="checked"';}?> >
					<label for="disqus_comment_count">Check this if you have a problem with comment counts not showing on permalinks</label> (<a href="http://disqus.com/docs/wordpress/#comment-count" target="_blank">more info</a>).
				</td>
			</tr>
			
		</table>

		<p class="submit" style="text-align: left">
			<input name="submit" type="submit" value="Save" tabindex="4">
		</p>
		</form>

		<table class="form-table">
			<tr>
				<th scope="row" valign="top">Import comments into Disqus</th>
				<td>
					<form action="?page=disqus" method="POST">
						<?php wp_nonce_field('dsq-export'); ?>
						<input type="submit" value="Import" name="export"
<?php if($dsq_last_import_id) : ?>
							onclick="return confirm('You\'ve already imported your comments.  Are you sure you want to do this again?');"
<?php endif; ?>
						> This will sync your WordPress comments with Disqus
						<br />
						<span style="font-size: 14px;">
<?php if($dsq_last_import_id) : ?>
							<strong>Import status:</strong> <?php echo $dsq_import_status['status_name']; ?><br />
	<?php if($dsq_import_status['finished_at']) : ?>
							<strong>Finished:</strong> <?php echo $dsq_import_status['finished_at']; ?><br />
	<?php elseif($dsq_import_status['started_at']) : ?>
							<strong>Started:</strong> <?php echo $dsq_import_status['started_at']; ?><br />
	<?php endif; ?>
							<br /><br />
<?php endif; ?>
						</span>
					</form>
				</td>
			</tr>

			<tr>
				<th scope="row" valign="top">Uninstall Disqus Comments</th>
				<td>
					<form action="?page=disqus" method="POST">
						<?php wp_nonce_field('dsq-uninstall'); ?>
						<input type="submit" value="Uninstall" name="uninstall" onclick="return confirm('Are you sure you want to uninstall DISQUS?')">
					</form>
				</td>
			</tr>
		</table>
	</div>
</div>
