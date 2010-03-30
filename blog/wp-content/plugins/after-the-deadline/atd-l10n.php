<?php

function AtD_load_l10n_domain() {
	if ( AtD_should_load_on_page() ) {
		$plugin_dir = basename(dirname(__FILE__));
		load_plugin_textdomain( 'after-the-deadline', 'wp-content/plugins/' . $plugin_dir . '/languages', $plugin_dir . '/languages' );
	}
}

/*
 * loads AtD localization strings (shared between Visual and HTML Editors)
 */
function AtD_init_l10n_js() {

	if ( AtD_should_load_on_page() ) {

		/* load localized strings for AtD */
		wp_localize_script('AtD_settings', 'AtD_l10n_r0ar', array
                (
			'menu_title_spelling' => __('Spelling', 'after-the-deadline'),
			'menu_title_repeated_word' => __('Repeated Word', 'after-the-deadline'),

			'menu_title_no_suggestions' => __('No suggestions', 'after-the-deadline'),

			'menu_option_explain' => __('Explain...', 'after-the-deadline'),
			'menu_option_ignore_once' => __('Ignore suggestion', 'after-the-deadline'),
			'menu_option_ignore_always' => __('Ignore always', 'after-the-deadline'),
			'menu_option_ignore_all' => __('Ignore all', 'after-the-deadline'),

			'menu_option_edit_selection' => __('Edit Selection...', 'after-the-deadline'),

			'button_proofread' => __('proofread', 'after-the-deadline'),
			'button_edit_text' => __('edit text', 'after-the-deadline'),
			'button_proofread_tooltip' => __('Proofread Writing', 'after-the-deadline'),

			'message_no_errors_found' => __('No writing errors were found.', 'after-the-deadline'),
			'message_server_error' => __('There was a problem communicating with the After the Deadline service. Try again in one minute.', 'after-the-deadline'),
			'message_server_error_short' => __('There was an error communicating with the proofreading service.', 'after-the-deadline'),

			'dialog_replace_selection' => __('Replace selection with:', 'after-the-deadline'),
			'dialog_confirm_post_publish' => __("The proofreader has suggestions for this post. Are you sure you want to publish it?\n\nPress OK to publish your post, or Cancel to view the suggestions and edit your post.", 'after-the-deadline'),
			'dialog_confirm_post_update' => __("The proofreader has suggestions for this post. Are you sure you want to update it?\n\nPress OK to update your post, or Cancel to view the suggestions and edit your post.", 'after-the-deadline'),
		));

		wp_enqueue_script( 'AtD_l10n', WP_PLUGIN_URL . '/after-the-deadline/install_atd_l10n.js', array('AtD_settings', 'jquery') );
	}
}

add_action( 'admin_print_scripts', 'AtD_init_l10n_js' );
add_action( 'init', 'AtD_load_l10n_domain');
