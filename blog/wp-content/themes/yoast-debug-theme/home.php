<?php
get_header();

?>
	<div class="box">
		<h2>Database Constants &amp; Variables</h2>
		<table>
			<tbody>
<?php
	foreach (array('DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD','DB_CHARSET','DB_COLLATE') as $const) {
		show_constant($const);
	}
?>
				<tr>
					<th>$table_prefix</th>
					<td><?php echo $table_prefix; ?></td>
				</tr>
			</tbody>
		</table>
	</div>
	
	<div class="box">
		<h2>Security Constants</h2>
		<table>
			<tbody>
<?php
	foreach (array('AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY') as $const) {
		show_constant($const);
	}
?>
			</tbody>
		</table>
		<p>Yes <a href="http://codex.wordpress.org/Editing_wp-config.php#Security_Keys">these are needed</a> and they should be unique, generate new ones <a href="https://api.wordpress.org/secret-key/1.1/">here</a>.</p>
	</div>
	
	<div class="box">
		<h2>Editor settings</h2>
		<table>
			<tbody>
<?php
	foreach (array('AUTOSAVE_INTERVAL', 'WP_POST_REVISIONS') as $const) {
		show_constant($const);
	}
?>
			</tbody>
		</table>
	</div>

	<div class="box">
		<h2>Cache and memory</h2>
		<table>
			<tbody>
<?php
	foreach (array('WP_MEMORY_LIMIT', 'WP_CACHE') as $const) {
		show_constant($const);
	}
?>
			</tbody>
		</table>
	</div>	
	
	<br class="clear"/>
	
	<div class="box double">
		<h2>URL's and directories</h2>
		<table>
			<tbody>
<?php
	foreach (array('WP_SITEURL', 'WP_HOME', 'WP_CONTENT_DIR', 'WP_CONTENT_URL', 'WP_PLUGIN_DIR', 'WP_PLUGIN_URL', 'PLUGINDIR', 'COOKIE_DOMAIN', 'TEMPLATEPATH', 'STYLESHEETPATH') as $const) {
		show_constant($const);
	}
?>
			</tbody>
		</table>
	</div>
<?php
get_footer();
?>