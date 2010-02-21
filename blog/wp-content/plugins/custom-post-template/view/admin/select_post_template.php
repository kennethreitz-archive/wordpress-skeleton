<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?>
<label class="hidden" for="page_template"><?php _e( 'Post Template' ); ?></label>
<input type="hidden" name="custom_post_template_present" value="1" />
<select name="custom_post_template" id="custom_post_template">
	<option 
		value='default'
		<?php
			if ( ! $custom_template ) {
				echo "selected='selected'";
			}
		?>><?php _e( 'Default Template' ); ?></option>
	<?php foreach( $templates AS $name => $filename ) { ?>
		<option 
			value='<?php echo $filename; ?>'
			<?php
				if ( $custom_template == $filename ) {
					echo "selected='selected'";
				}
			?>><?php echo $name; ?></option>
	<?php } ?>
</select>
<p><?php _e( 'Some themes have custom templates you can use for certain posts that might have additional features or custom layouts. If so, you&#8217;ll see them above.' ); ?></p>
