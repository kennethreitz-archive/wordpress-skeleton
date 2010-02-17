<h2><?php _e('Further resources', 'more-fields'); ?></h2>

<p><?php printf(__('For more help see %s.', 'more-fields'), '<a href="http://labs.dagensskiva.com/plugins/more-fields/">http://labs.dagensskiva.com/plugins/more-fields/</a>'); ?></p>

<p><?php printf(__('If you find this plugin useful, <a href="%s">please donate</a>. Your contribution will ensure the future development of this plugin.', 'more-fields'), 'http://henrikmelin.se/plugins/'); ?></p>

<h2><?php _e('Definition of terms', 'more-fields'); ?></h2>

<dl>
	<dt><?php _e('Box', 'more-fields'); ?></dt>
	<dd>
		<?php _e('A box is one of those boxes you see on the <a href="post-new.php">Write/Edit pages</a>. By adding a new box with new fields, you can add valuable information to your posts. The new fields are stored as Custom Fields and can be accessed in the templates using e.g. <tt>get_post_meta</tt>.', ' more-fields') ; ?>
	</dd>

	<dt><?php _e('Post type', 'more-fields'); ?></dt>
	<dd>
		<?php _e("A post type is a Write/Edit page that contains a pre-defined set of boxes and/or categories and/or tags. For example, you might want to create a post type 'Record review' that includes the box 'Record information', but excludes all the default boxes that are not relevant when writing a record review. This way, you can un-clutter the writing process, making for a simpler interface for your writers.", 'more-fields'); ?>
	</dd>
</ul>