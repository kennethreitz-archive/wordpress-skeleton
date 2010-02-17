<?php
	require_once(dirname(__FILE__).'/../../../wp-config.php');
	header("Content-Type: text/javascript");
	global $mf0;
?>

/*
**	Only show values field for when it's relevant
**
*/
jQuery(document).ready(function(){
	more_fields_show_values_fields();
	jQuery('#type').bind("change", function(e){
		more_fields_show_values_fields();
    });
    
    jQuery('.delete_me').bind("click", function(e){
    	var deleteme = confirm('<?php _e('Are you sure you want to delete this?', 'more-fields'); ?>');
		if (deleteme == true) return true;
		else return false;    
    });
    
   	jQuery('.resetall').bind("click", function(e){
    	var deleteme = confirm('<?php _e('Are you sure you want to reset More-Fields?', 'more-fields'); ?>');
		if (deleteme == true) return true;
		else return false;    
    });
    
});

/*
**	Checks to see if the input allows multiple values
**
*/
function more_fields_show_values_fields() {
	<?php foreach ((array) $mf0->field_types as $type) : ?>
		<?php if ($type->values) : ?>
			if (jQuery('#type').val() == '<?php echo sanitize_title($type->title); ?>') {
				jQuery('#values_container').show();
			}
		<?php else : ?>
			if (jQuery('#type').val() == '<?php echo sanitize_title($type->title); ?>') {
				jQuery('#values_container').hide();
			}
		<?php endif; ?>
	<?php endforeach; ?>
}