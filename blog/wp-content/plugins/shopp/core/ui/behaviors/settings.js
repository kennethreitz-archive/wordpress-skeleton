var currencyFormat = <?php echo json_encode($base_operations['currency']['format']); ?>;
var tb_pathToImage = '<?php echo force_ssl(WP_PLUGIN_URL); ?>/<?php echo $dir; ?>/core/ui/icons/loading.gif';
var UNSAVED_CHANGES_WARNING = '<?php echo addslashes(__('There are unsaved changes that will be lost if you continue.','Shopp')); ?>';
var CHECKOUT_LOGIN_NAME = '<?php echo addslashes(__('You did not enter a login.','Shopp')); ?>';
var CHECKOUT_LOGIN_PASSWORD = '<?php echo addslashes(__('You did not enter a password to login with.','Shopp')); ?>';
var CHECKOUT_REQUIRED_FIELD = '<?php echo addslashes(__('Your %s is required.','Shopp')); ?>';
var CHECKOUT_INVALID_EMAIL = '<?php echo addslashes(__('The e-mail address you provided does not appear to be a valid address.','Shopp')); ?>';
var CHECKOUT_MIN_LENGTH = '<?php echo addslashes(__('The %s you entered is too short. It must be at least %d characters long.','Shopp')); ?>';
var CHECKOUT_PASSWORD_MISMATCH = '<?php echo addslashes(__('The passwords you entered do not match. They must match in order to confirm you are correctly entering the password you want to use.','Shopp')); ?>';
var CHECKOUT_CHECKBOX_CHECKED = '<?php echo addslashes(__('%s must be checked before you can proceed.','Shopp')); ?>';

var SHOPP_TB_CLOSE = '<?php echo addslashes(__('Press Esc Key or','Shopp')); ?>';
var SHOPP_TB_IMAGE = '<?php echo addslashes(__('Image %d of %d','Shopp')); ?>';
var SHOPP_TB_NEXT = '<?php echo addslashes(__('Next','Shopp')); ?>';
var SHOPP_TB_BACK = '<?php echo addslashes(__('Back','Shopp')); ?>';
var MONTH_NAMES = new Array('','<?php echo addslashes(__('January','Shopp')) ?>','<?php echo addslashes(__('February','Shopp')) ?>','<?php echo addslashes(__('March','Shopp')) ?>','<?php echo addslashes(__('April','Shopp')) ?>','<?php echo addslashes(__('May','Shopp')) ?>','<?php echo addslashes(__('June','Shopp')) ?>','<?php echo addslashes(__('July','Shopp')) ?>','<?php echo addslashes(__('August','Shopp')) ?>','<?php echo addslashes(__('September','Shopp')) ?>','<?php echo addslashes(__('October','Shopp')) ?>','<?php echo addslashes(__('November','Shopp')) ?>','<?php echo addslashes(__('December','Shopp')) ?>');
var WEEK_DAYS = new Array('<?php echo addslashes(__('Sun','Shopp')) ?>','<?php echo addslashes(__('Mon','Shopp')) ?>','<?php echo addslashes(__('Tue','Shopp')) ?>','<?php echo addslashes(__('Wed','Shopp')) ?>','<?php echo addslashes(__('Thu','Shopp')) ?>','<?php echo addslashes(__('Fri','Shopp')) ?>','<?php echo addslashes(__('Sat','Shopp')) ?>');
