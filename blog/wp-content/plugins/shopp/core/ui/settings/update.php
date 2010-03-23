<div class="wrap shopp">
	<?php if (!empty($updated)): ?><div id="message" class="updated fade"><p><?php echo $updated; ?></p></div><?php endif; ?>

	<h2><?php _e('Upgrade Settings','Shopp'); ?></h2>

	<form name="settings" id="update" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
		<?php wp_nonce_field('shopp-settings-update'); ?>

		<?php include("navigation.php"); ?>
		
		<table class="form-table"> 
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="showcase-order">Shopp Updates</label>
				</th> 
				<td><?php _e('Currently running Shopp','Shopp'); ?> <?php echo SHOPP_VERSION; ?>
					<div id="update-info">
					<p><button type="button" id="check-update" name="check-update" class="button-secondary"><?php _e('Check for Updates','Shopp'); ?></button></p>	
					</div>
					<div id="ftp-credentials" class="hidden">
						<p><strong><?php _e('FTP Settings','Shopp'); ?></strong></p>
<p id="ftp-error" class="shopp error"><?php _e("Could not connect to your server over FTP, please check your settings and try again.","Shopp"); ?></p>
						<div class="stored"><input type="text" name="settings[ftp_credentials][hostname]" id="ftp-host" size="40" value="<?php echo attribute_escape($credentials['hostname']); ?>" /><br />
						<label for="ftp-host"><?php _e('Enter the FTP server/host name for this WordPress installation.','Shopp'); ?></label></div>
						<div class="stored"><input type="text" name="settings[ftp_credentials][username]" id="ftp-username" size="20"  value="<?php echo attribute_escape($credentials['username']); ?>" /><br />
						<label for="ftp-username"><?php _e('Enter your FTP username','Shopp'); ?></label></div>
						<div><input type="password" name="password" id="ftp-password" size="20" value="<?php echo attribute_escape($credentials['password']); ?>" /><br />
						<label for="ftp-password"><?php _e('Enter your FTP password','Shopp'); ?></label></div><br />
						<div><input type="submit" name="ftp-settings" id="ftp-continue" value="<?php _e('Continue Updates&hellip;','Shopp'); ?>" class="button-secondary" /></div>
					</div>
					</td>
			</tr>			
			<tr class="form-required"> 
				<th scope="row" valign="top"><label for="update-key"><?php _e('Update Key','Shopp'); ?></label></th> 
				<td>
					<?php if ($updatekey['status'] == "activated"): ?>
					<input type="<?php echo $type; ?>" name="updatekey" id="update-key" size="40" value="<?php echo $updatekey['key']; ?>" readonly="readonly" />
					<input type="hidden" name="process" value="deactivate-key" />
					<input type="submit" id="deactivate-button" name="activation" value="<?php _e('De-activate Key','Shopp'); ?>" class="button-secondary" />
					<?php else: ?>
					<input type="text" name="updatekey" id="update-key" size="40" value="<?php echo $updatekey['key']; ?>" />
					<input type="hidden" name="process" value="activate-key" />
					<input type="submit" id="activate-button" name="activation" value="<?php _e('Activate Key','Shopp'); ?>" class="button-secondary" />
					<?php endif; ?>
					<br /><?php echo $activation; ?>
	            </td>
			</tr>			
		</table>
		<br class="clear" />
	</form>
</div>

<script type="text/javascript">
(function($) {
	helpurl = "<?php echo SHOPP_DOCS; ?>Update_Settings";
	
	$(document).ready( function() {
	
	var purchase_url = '<?php echo SHOPP_HOME; ?>?buynow=true';
	var adminurl = '<?php echo wp_nonce_url($Shopp->wpadminurl."admin.php","shopp-wp_ajax_shopp_update"); ?>';
	var ajaxurl = '<?php echo wp_nonce_url($Shopp->wpadminurl."admin-ajax.php","shopp-wp_ajax_shopp_update"); ?>';
	
	var INSTALLING_MESSAGE = "<?php _e('Installing update %d of %d&hellip;','Shopp'); ?>";
	var CANCELLING_MESSAGE = "<?php _e('Cancelling updates&hellip;','Shopp'); ?>";
	
	
	var target = $('#update-info');
	var updating = -1;
	var updates = false;
	var queue = false;
	var ftphost = $('#ftp-host');
	var ftpusername = $('#ftp-username');
	var ftppassword = $('#ftp-password');
	
	$('#check-update').click(function () {

		$('<div id="status" class="updating"><?php _e("Checking"); ?>&hellip;</div>').appendTo(target);
		$.ajax({
			type:"GET",
			url:ajaxurl+"&action=wp_ajax_shopp_version_check",
			timeout:10000,
			dataType:'json',
			success:function (data) {
				if (data.updates) {
					updates = data.updates;
					showupdates();
				} else target.html("<strong><?php _e('There are no new updates.','Shopp'); ?></strong>");
			},
			error:function () {	$('#status').remove(); }
		});
		
	});

	function startupdates () {
		if (ftphost.val() != '' && ftpusername.val() != '' && ftppassword.val() == '') {
			ftphost.parent().hide();
			ftpusername.parent().hide();
			target.html('');
			ftpfailure();
			ftppassword.focus();
			return true;
		}
		
		ftphost.parent().show();
		ftpusername.parent().show();

		runupdate();
	}
	
	function runupdate () {
		updating++;
		if (updating >= queue.length) {
			window.location.href = adminurl+'&page=shopp-settings-update&updated=true';
			return true;
		}
		var notice = INSTALLING_MESSAGE.replace('%d',(updating+1)).replace('%d',queue.length);
		target.html('<div id="status" class="updating">'+notice+'</div>');
		
		var update = updates[queue[updating]];
		
		$.ajax({
			type:"POST",
			url:ajaxurl+"&action=wp_ajax_shopp_update",
			data:"update="+update.download+"&type="+update.type+"&password="+ftppassword.val(),
			timeout:30000,
			datatype:'text',
			success:function (result) {
				result = $.trim(result);
				if (result == "ftp-failed") {
					updating--;
					return ftpfailure(true);
				} else if (result == "updated") {
					if (updating+1 < queue.length) {
						runupdate(); // Continue processing more updates if needed
						return true; 
					}
					
					$('#update-info').html('<strong><?php _e("Update Complete!","Shopp"); ?></strong><br /><?php _e("Click continue to upgrade the Shopp database.","Shopp"); ?>');
					var wrap = $('<p></p>').appendTo('#update-info');
					var reload = $('<button type="button" name="reload" value="reload" class="button-secondary"><?php _e('Continue','Shopp'); ?>&hellip;</button>').appendTo('#update-info');
					reload.click(function () {
						window.location.href = adminurl+'&page=shopp-settings-update&updated=true';
					});
					
				} else {
					target.html('<div id="status" class="updating">'+CANCELLING_MESSAGE+'</div>');
					alert("<?php _e('An error occurred while trying to update.  The update failed.  This is what Shopp says happened:','Shopp'); ?>\n"+result);
					window.location.href = adminurl+'&page=shopp-settings-update&updated=true';
				}
			},
			error:function () {
				alert("<?php _e('The update timed out and was not successful.','Shopp'); ?>\n"+result);
				window.location.href = adminurl+'&page=shopp-settings-update&updated=true';
			}
		});
	}
	
	function showupdates () {
		$('#status').remove();
		var markup = '<p><strong>New updates are available:</strong></p><ul id="update-queue">';
		$(updates).each(function (id,update) {
			markup += '<li><input type="checkbox" id="update-'+update.download+'" name="queue" value="'+id+'" checked="checked" class="update" /><label for="update-'+update.download+'"> '+update.name+' <strong>'+update.version+'</strong></label></li>';
		});
		markup += '</ul>';
		
		<?php if ($updatekey['status'] == "activated"): ?>
			<?php if (!$ftpsupport): ?>
			markup += '<p class="shopp error"><?php _e("Your server does not have FTP support enabled. Automatic update not available.","Shopp"); ?></p>';
			<?php else: ?>
			markup += '<p><button type="button" name="update" id="update-button" class="button-secondary"><?php _e("Install Updates","Shopp"); ?></button></p>';
			<?php endif; ?>
		<?php else: ?>
		markup += '<p><button type="button" name="buykey" id="buykey-button" class="button-secondary"><?php _e("Buy an Update Key","Shopp"); ?></button></p>';
		<?php endif; ?>
		
		target.html(markup);
		
		if ($('#update-button').length) {
			$('#update-button').click(function () { 
				queue = new Array();
				$('#update-queue input').each(function() {
					if ($(this).attr('checked')) queue.push($(this).val());
				});
				if (queue.length > 0) startupdates();
			});
		}
		
		if ($('#buykey-button').length) {
			$('#buykey-button').click(function () { 
				window.location.href = purchase_url;
			});
		}
		
	}
	
	function ftpfailure (err) {
		$('#status').hide();
		$('#ftp-continue').click(function () { setftp(); return false; });
		$('#ftp-credentials').show();
		if (err) $('#ftp-error').show();
		return false;
	}
	
	function setftp () {
		$.ajax({
			type:"POST",
			url:ajaxurl+"&action=wp_ajax_shopp_setftp",
			data:$('#ftp-credentials input').serialize(),
			timeout:30000,
			datatype:'text',
			success:function (result) {
				$('#ftp-credentials').hide();
				startupdates();
			},
			error:function () {
				alert("<?php _e('The server did not respond. Your FTP settings could not be set.','Shopp'); ?>\n"+result);
				window.location.href = adminurl+'&page=shopp-settings&edit=update&updated=true';
			}
		});
	}
	
	});

})(jQuery)
</script>