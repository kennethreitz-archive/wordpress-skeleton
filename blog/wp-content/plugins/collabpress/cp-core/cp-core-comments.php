<?php

// Add/View comments links
echo '<p><a href="#" class="comment_button" id="'.$my_task_count.'">Add Comment</a>';


// Don't display on single
if ($task_data->id) {
	
	echo '&nbsp;&middot;&nbsp;<a href="admin.php?page=cp-dashboard-page&view=task&task_id='.$task_data->id.'">View Comments (' . get_cp_comment_count($task_data->id) . ')</a></p>';
	
} else {
	
	echo '</p>';
	
}

?>

<!-- Add comment form -->
<div style="display:none; width:640px;" id="slidepanel<?php echo $my_task_count; ?>">
	<form method="post" action="">
		<?php wp_nonce_field('cp-add-task-comment'); ?>
		<input type="hidden" name="cp_task_id" value="<?php echo $task_data->id; ?>" />
		<input type="hidden" name="cp_author_id" value="<?php echo $task_data->users; ?>" />
		<textarea style="width:640px;height:150px" name="cp_task_comment"></textarea><br />
		<?php 
        //check if email option is enabled
        if (get_option('cp_email_config')) {
            $checked = 'checked="checked"';
        }            
        ?>
		<span style="float:left; margin:16px 0;"><?php _e('Notify via Email?', 'collabpress'); ?> <input type="checkbox" name="notify" <?php echo $checked; ?> /></span>
		<span style="float:right; margin:10px 0;"><input type="submit" name="cp_add_comment_button" value="<?php _e('Add Comment', 'collabpress'); ?>" /></span>
	</form>
	<div style="clear:both"></div>
</div>