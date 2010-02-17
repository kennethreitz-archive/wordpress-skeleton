<?php require_once( dirname(__FILE__) . '/../include/icons.php' ); ?>
<?php global $wptouch_settings; ?>

<div class="metabox-holder" id="available_icons">
	<div class="postbox">
		<h3><span class="icon-options">&nbsp;</span><?php _e( "Default &amp; Custom Icon Pool", "wptouch" ); ?></h3>

			<div class="left-content">
				<p><?php _e( "You can select which icons will be displayed beside the pages you enable in the next section.", "wptouch" ); ?></p>
				<strong><?php _e( "Adding Icons", "wptouch" ); ?></strong>
				<p><?php _e( "To add icons to the pool, simply upload a 32x32.png, .jpeg or .gif image from your computer.", "wptouch" ); ?></p>
				<strong><?php _e( "Logo/Bookmark Icons", "wptouch" ); ?></strong>
				<p><?php _e( "If you're adding a logo icon, the best dimensions for it are 59x60px when used as a bookmark icon.", "wptouch" ); ?></p>
				<p><?php echo sprintf( __( "Need help? You can use %sthis easy online icon generator%s to make one.", "wptouch"), "<a href='http://www.flavorstudios.com/iphone-icon-generator' target='_blank'>", "</a>" ); ?></p>
				<p><?php echo sprintf( __( "These files will be stored in the<br />%s%s/wptouch/custom-icons%s<br />folder we create.", "wptouch"), "<strong>", str_replace( ABSPATH, "", compat_get_upload_dir() ), "</strong>" ); ?></p>
				<p><?php echo sprintf( __( "If an upload fails (usually it's a permission problem) create the folder yourself using FTP and try again.", "wptouch"), "<strong>", "</strong>" ); ?></p>
						
				<div id="upload_button"></div>

				<!-- <div id="extras_button">
					<a href="#" onclick="alert('This does nothing yet');return false;"><img src="<?php echo compat_get_plugin_url( 'wptouch' ) . '/images/extras.png'; ?>" alt="extras" /></a>
				</div> --> 

			<div id="upload_response"></div>
				<div id="upload_progress" style="display:none">
					<p><img src="<?php echo compat_get_plugin_url( 'wptouch' ) . '/images/progress.gif'; ?>" alt="" /> <?php _e( "Uploading..."); ?></p>
				</div>
								
			</div><!-- left-content -->
		
	<div class="right-content">	
		<?php bnc_show_icons(); ?>
	</div>
	
	<div class="bnc-clearer"></div>
	</div><!-- postbox -->
</div><!-- metabox -->