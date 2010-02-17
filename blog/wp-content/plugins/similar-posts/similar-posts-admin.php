<?php

// Admin stuff for Similar Posts Plugin, Version 2.6.2.0

function similar_posts_option_menu() {
	add_options_page(__('Similar Posts Options', 'similar_posts'), __('Similar Posts', 'similar_posts'), 8, 'similar-posts', 'similar_posts_options_page');
}

add_action('admin_menu', 'similar_posts_option_menu', 1);

function similar_posts_for_feed_option_menu() {
	add_options_page(__('Similar Posts Feed Options', 'similar_posts'), __('Similar Posts Feed', 'similar_posts'), 8, 'similar-posts-feed', 'similar_posts_for_feed_options_page');
}

// this sneaky piece of work lets the similar posts feed menu appear and disappear
function juggle_similar_posts_menus() {
	if (isset($_POST['feed_active'])) {
		$active = ($_POST['feed_active'] === 'true');
	} else {
		$options = get_option('similar-posts');
		$active = ($options['feed_active'] === 'true');
	}
	if ($active) {
		add_action('admin_menu', 'similar_posts_for_feed_option_menu', 2);
	} else {
		remove_action('admin_menu', 'similar_posts_for_feed_option_menu');
	}
}

add_action('plugins_loaded', 'juggle_similar_posts_menus');

function similar_posts_options_page(){
	echo '<div class="wrap"><h2>';
	_e('Similar Posts ', 'similar_posts'); 
	echo '<a href="http://rmarsh.com/plugins/post-options/" style="font-size: 0.8em;">';
	_e('help and instructions'); 
	echo '</a></h2></div>';
	if (!SimilarPosts::check_post_plugin_library('<h1>'.sprintf(__('Please install the %sPost Plugin Library%s plugin.'), '<a href="http://downloads.wordpress.org/plugin/post-plugin-library.zip">', '</a>').'</h1>')) return;
	$m = new admin_subpages();
	$m->add_subpage('General', 'general', 'similar_posts_general_options_subpage');
	$m->add_subpage('Output', 'output', 'similar_posts_output_options_subpage');
	$m->add_subpage('Filter', 'filter', 'similar_posts_filter_options_subpage');
	$m->add_subpage('Placement', 'placement', 'similar_posts_placement_options_subpage');
	$m->add_subpage('Other', 'other', 'similar_posts_other_options_subpage');
	$m->add_subpage('Manage the Index', 'index', 'similar_posts_index_options_subpage');
	$m->add_subpage('Report a Bug', 'bug', 'similar_posts_bug_subpage');
	$m->add_subpage('Remove this Plugin', 'remove', 'similar_posts_remove_subpage');
	$m->display();
	add_action('in_admin_footer', 'similar_posts_admin_footer');
}

function similar_posts_admin_footer() {
	ppl_admin_footer(str_replace('-admin', '', __FILE__), "similar-posts");
}

function similar_posts_general_options_subpage(){
	global $wpdb, $wp_version;
	$options = get_option('similar-posts');
	if (isset($_POST['update_options'])) {
		check_admin_referer('similar-posts-update-options'); 
		if (defined('POC_CACHE_4')) poc_cache_flush();
		// Fill up the options with the values chosen...
		$options = ppl_options_from_post($options, array('limit', 'skip', 'show_private', 'show_pages', 'show_attachments', 'status', 'age', 'omit_current_post', 'match_cat', 'match_tags', 'match_author'));
		update_option('similar-posts', $options);
		// Show a message to say we've done something
		echo '<div class="updated fade"><p>' . __('Options saved', 'similar_posts') . '</p></div>';
	} 
	//now we drop into html to display the option page form
	?>
		<div class="wrap">
		<h2><?php _e('General Settings', 'similar_posts'); ?></h2>
		<form method="post" action="">
		<div class="submit"><input type="submit" name="update_options" value="<?php _e('Save General Settings', 'similar_posts') ?>" /></div>
		<table class="optiontable form-table">
			<?php 
				ppl_display_limit($options['limit']); 
				ppl_display_skip($options['skip']); 
				ppl_display_show_private($options['show_private']); 
				ppl_display_show_pages($options['show_pages']); 
				ppl_display_show_attachments($options['show_attachments']); 
				ppl_display_status($options['status']);
				ppl_display_age($options['age']);
				ppl_display_omit_current_post($options['omit_current_post']); 
				ppl_display_match_cat($options['match_cat']); 
				ppl_display_match_tags($options['match_tags']); 
				ppl_display_match_author($options['match_author']); 
			?>
		</table>
		<div class="submit"><input type="submit" name="update_options" value="<?php _e('Save General Settings', 'similar_posts') ?>" /></div>
		<?php if (function_exists('wp_nonce_field')) wp_nonce_field('similar-posts-update-options'); ?>
		</form>  
	</div>
	<?php	
}

function similar_posts_output_options_subpage(){
	global $wpdb, $wp_version;
	$options = get_option('similar-posts');
	if (isset($_POST['update_options'])) {
		check_admin_referer('similar-posts-update-options'); 
		if (defined('POC_CACHE_4')) poc_cache_flush();
		// Fill up the options with the values chosen...
		$options = ppl_options_from_post($options, array('output_template', 'prefix', 'suffix', 'none_text', 'no_text', 'divider', 'sort', 'group_template'));
		update_option('similar-posts', $options);
		// Show a message to say we've done something
		echo '<div class="updated fade"><p>' . __('Options saved', 'similar_posts') . '</p></div>';
	} 
	//now we drop into html to display the option page form
	?>
		<div class="wrap">
		<h2><?php _e('Output Settings', 'similar_posts'); ?></h2>
		<form method="post" action="">
		<div class="submit"><input type="submit" name="update_options" value="<?php _e('Save Output Settings', 'similar_posts') ?>" /></div>
		<table class="optiontable form-table">
			<tr>
			<td>
			<table>
			<?php 
				ppl_display_output_template($options['output_template']); 
				ppl_display_prefix($options['prefix']); 
				ppl_display_suffix($options['suffix']); 
				ppl_display_none_text($options['none_text']); 
				ppl_display_no_text($options['no_text']); 
				ppl_display_divider($options['divider']); 
				ppl_display_sort($options['sort']);
				ppl_display_group_template($options['group_template']); 
			?>
			</table>
			</td>
			<td>
			<?php ppl_display_available_tags('similar-posts'); ?>
			</td></tr>
		</table>
		<div class="submit"><input type="submit" name="update_options" value="<?php _e('Save Output Settings', 'similar_posts') ?>" /></div>
		<?php if (function_exists('wp_nonce_field')) wp_nonce_field('similar-posts-update-options'); ?>
		</form>  
	</div>
	<?php	
}

function similar_posts_filter_options_subpage(){
	global $wpdb, $wp_version;
	$options = get_option('similar-posts');
	if (isset($_POST['update_options'])) {
		check_admin_referer('similar-posts-update-options'); 
		if (defined('POC_CACHE_4')) poc_cache_flush();
		// Fill up the options with the values chosen...
		$options = ppl_options_from_post($options, array('excluded_posts', 'included_posts', 'excluded_authors', 'included_authors', 'excluded_cats', 'included_cats', 'tag_str', 'custom'));
		update_option('similar-posts', $options);
		// Show a message to say we've done something
		echo '<div class="updated fade"><p>' . __('Options saved', 'similar_posts') . '</p></div>';
	} 
	//now we drop into html to display the option page form
	?>
		<div class="wrap">
		<h2><?php _e('Filter Settings', 'similar_posts'); ?></h2>
		<form method="post" action="">
		<div class="submit"><input type="submit" name="update_options" value="<?php _e('Save Filter Settings', 'similar_posts') ?>" /></div>
		<table class="optiontable form-table">
			<?php 
				ppl_display_excluded_posts($options['excluded_posts']); 
				ppl_display_included_posts($options['included_posts']); 
				ppl_display_authors($options['excluded_authors'], $options['included_authors']); 
				ppl_display_cats($options['excluded_cats'], $options['included_cats']); 
				ppl_display_tag_str($options['tag_str']); 
				ppl_display_custom($options['custom']); 
			?>
		</table>
		<div class="submit"><input type="submit" name="update_options" value="<?php _e('Save Filter Settings', 'similar_posts') ?>" /></div>
		<?php if (function_exists('wp_nonce_field')) wp_nonce_field('similar-posts-update-options'); ?>
		</form>  
	</div>
	<?php	
}

function similar_posts_placement_options_subpage(){
	global $wpdb, $wp_version;
	$options = get_option('similar-posts');
	if (isset($_POST['update_options'])) {
		check_admin_referer('similar-posts-update-options'); 
		if (defined('POC_CACHE_4')) poc_cache_flush();
		// Fill up the options with the values chosen...
		$options = ppl_options_from_post($options, array('content_filter', 'widget_parameters', 'widget_condition', 'feed_on', 'feed_priority', 'feed_parameters', 'append_on', 'append_priority', 'append_parameters', 'append_condition'));
		update_option('similar-posts', $options);
		// Show a message to say we've done something
		echo '<div class="updated fade"><p>' . __('Options saved', 'similar_posts') . '</p></div>';
	} 
	//now we drop into html to display the option page form
	?>
		<div class="wrap">
		<h2><?php _e('Placement Settings', 'similar_posts'); ?></h2>
		<form method="post" action="">
		<div class="submit"><input type="submit" name="update_options" value="<?php _e('Save Placement Settings', 'similar_posts') ?>" /></div>
		<table class="optiontable form-table">
			<?php 
				ppl_display_append($options); 
				ppl_display_feed($options); 
				ppl_display_widget($options); 
				ppl_display_content_filter($options['content_filter']);
			?>
		</table>
		<div class="submit"><input type="submit" name="update_options" value="<?php _e('Save Placement Settings', 'similar_posts') ?>" /></div>
		<?php if (function_exists('wp_nonce_field')) wp_nonce_field('similar-posts-update-options'); ?>
		</form>  
	</div>
	<?php	
}

function similar_posts_other_options_subpage(){
	global $wpdb, $wp_version;
	$options = get_option('similar-posts');
	if (isset($_POST['update_options'])) {
		check_admin_referer('similar-posts-update-options'); 
		if (defined('POC_CACHE_4')) poc_cache_flush();
		// Fill up the options with the values chosen...
		$options = ppl_options_from_post($options, array('stripcodes', 'feed_active', 'term_extraction', 'num_terms', 'weight_title', 'weight_content', 'weight_tags', 'hand_links'));
		$wcontent = $options['weight_content'] + 0.0001; 
		$wtitle = $options['weight_title'] + 0.0001;
		$wtags = $options['weight_tags'] + 0.0001;
		$wcombined = $wcontent + $wtitle + $wtags;
		$options['weight_content'] = $wcontent / $wcombined; 
		$options['weight_title'] = $wtitle / $wcombined; 
		$options['weight_tags'] = $wtags / $wcombined; 
		update_option('similar-posts', $options);
		// Show a message to say we've done something
		echo '<div class="updated fade"><p>' . __('Options saved', 'similar_posts') . '</p></div>';
	} 
	//now we drop into html to display the option page form
	?>
		<div class="wrap">
		<h2><?php _e('Other Settings', 'similar_posts'); ?></h2>
		<form method="post" action="">
		<div class="submit"><input type="submit" name="update_options" value="<?php _e('Save Other Settings', 'similar_posts') ?>" /></div>
		<table class="optiontable form-table">
			<?php 
				ppl_display_weights($options); 
				ppl_display_num_terms($options['num_terms']); 
				ppl_display_term_extraction($options['term_extraction']); 
				ppl_display_hand_links($options['hand_links']);
				ppl_display_feed_active($options['feed_active']);
				ppl_display_stripcodes($options['stripcodes']); 
			?>
		</table>
		<div class="submit"><input type="submit" name="update_options" value="<?php _e('Save Other Settings', 'similar_posts') ?>" /></div>
		<?php if (function_exists('wp_nonce_field')) wp_nonce_field('similar-posts-update-options'); ?>
		</form>  
	</div>
	<?php	
}

function similar_posts_index_options_subpage(){
	if (isset($_POST['reindex_all'])) {
		check_admin_referer('similar-posts-manage-update-options'); 
		if (defined('POC_CACHE_4')) poc_cache_flush();
		$options = get_option('similar-posts');
		$options['utf8'] = $_POST['utf8'];
		if (!function_exists('mb_split')) {
			$options['utf8'] = 'false';
		}
		$options['cjk'] = $_POST['cjk'];
		if (!function_exists('mb_internal_encoding')) {
			$options['cjk'] = 'false';
		}
		if ($options['cjk'] === 'true') $options['utf8'] = 'true';
		$options['use_stemmer'] = $_POST['use_stemmer'];
		$options['batch'] = ppl_check_cardinal($_POST['batch']);
		if ($options['batch'] === 0) $options['batch'] = 100;
		flush();
		$termcount = save_index_entries (($options['utf8']==='true'), $options['use_stemmer'], $options['batch'], ($options['cjk']==='true'));
		update_option('similar-posts', $options);
		//show a message
		printf('<div class="updated fade"><p>'.__('Indexed %d posts.').'</p></div>', $termcount);
	} else {
		$options = get_option('similar-posts');
	}
	?>
    <div class="wrap"> 
		<?php 
		echo '<h2>'.__('Manage Index', 'similar_posts').'</h2>'; 
		echo '<p>'.__('Similar Posts maintains a special index to help search for related posts. The index is created when the plugin is activated and then kept up-to-date  automatically when posts are added, edited, or deleted.', 'similar_posts').'</p>';
		echo '<p>'.__('The options that affect the index can be set below.', 'similar_posts').'</p>';
		echo '<p>'.__('If you are using a language other than english you may find that the plugin mangles some characters since PHP is normally blind to multibyte characters. You 	can force the plugin to interpret extended characters as UTF-8 at the expense of a little speed but this facility is only available if your installation of PHP supports the mbstring functions.', 'similar_posts').'</p>';
		echo '<p>'.__('Languages like Chinese, Korean and Japanese pose a special difficulty for the full-text search algorithm. As an experiment I have introduced an option below to work around some of these issues. The text must be encoded as UTF-8. I would be very grateful for feedback from any users knowledgeable in these languages.', 'similar_posts').'</p>';
		echo '<p>'.__('Some related word forms should really be counted together, e.g., "follow", "follows", and "following". By default, Similar Posts treats such differences strictly but has two other algorithms which are more relaxed: <em>stemming</em> and <em>fuzzy matching</em>. The stemming algorithm tries to reduce related forms to their root stem. Stemming algorithms are provided for english, german, spanish, french and italian but stemmers for other languages can be created: see the help for instructions. Fuzzy matching uses the "metaphone" algorithm to handle word variations. Note: both stemming and fuzzy matching slow down the indexing more than a little. It is worth experimenting with the three possibilities to see what improves the similarity of posts in your particular circumstances.', 'similar_posts').'</p>'; 
		echo '<p>'.__('The indexing routine processes posts in batches of 100 by default. If you run into problems with limited memory you can opt to make the batches smaller.', 'similar_posts').'</p>'; 
		echo '<p>'.__('Note: the process of indexing may take a little while. On my modest machine 500 posts take between 5 seconds and 20 seconds (with stemming and utf-8 support). Don\'t worry if the screen fails to update until finished.', 'similar_posts').'</p>'; 
		?>
		<form method="post" action="">		
		<table class="optiontable form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Handle extended characters?', 'similar_posts') ?></th>
				<td>
					<select name="utf8" id="utf8" <?php if (!function_exists('mb_split')) echo 'disabled="true"'; ?> >
					<option <?php if($options['utf8'] == 'false') { echo 'selected="selected"'; } ?> value="false">No</option>
					<option <?php if($options['utf8'] == 'true') { echo 'selected="selected"'; } ?> value="true">Yes</option>
					</select>
				</td> 
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Treat as Chinese, Korean, or Japanese?', 'similar_posts') ?></th>
				<td>
					<select name="cjk" id="cjk" <?php if (!function_exists('mb_split')) echo 'disabled="true"'; ?> >
					<option <?php if($options['cjk'] == 'false') { echo 'selected="selected"'; } ?> value="false">No</option>
					<option <?php if($options['cjk'] == 'true') { echo 'selected="selected"'; } ?> value="true">Yes</option>
					</select>
				</td> 
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Treat Related Word Variations:', 'similar_posts') ?></th>
				<td>
					<select name="use_stemmer" id="use_stemmer">
					<option <?php if($options['use_stemmer'] == 'false') { echo 'selected="selected"'; } ?> value="false">Strictly</option>
					<option <?php if($options['use_stemmer'] == 'true') { echo 'selected="selected"'; } ?> value="true">By Stem</option>
					<option <?php if($options['use_stemmer'] == 'fuzzy') { echo 'selected="selected"'; } ?> value="fuzzy">Fuzzily</option>
					</select>
				</td> 
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Batch size:', 'similar_posts') ?></th>
				<td><input name="batch" type="text" id="batch" value="<?php echo $options['batch']; ?>" size="3" /></td>
			</tr>
		</table>
		<div class="submit">
		<input type="submit" name="reindex_all" value="<?php _e('Recreate Index', 'similar_posts') ?>" />
		<?php if (function_exists('wp_nonce_field')) wp_nonce_field('similar-posts-manage-update-options'); ?>
		</div>   
		</form>       
    </div>
	<?php
}


function similar_posts_bug_subpage(){
	ppl_bug_form('similar-posts'); 
}

function similar_posts_remove_subpage(){
	ppl_plugin_eradicate_form(str_replace('-admin', '', __FILE__)); 
}	

function similar_posts_for_feed_options_page(){
	echo '<div class="wrap"><h2>';
	_e('Similar Posts Feed ', 'similar_posts'); 
	echo '<a href="http://rmarsh.com/plugins/post-options/" style="font-size: 0.8em;">';
	_e('help and instructions'); 
	echo '</a></h2></div>';
	$m = new admin_subpages();
	$m->add_subpage('General', 'general', 'similar_posts_feed_general_options_subpage');
	$m->add_subpage('Output', 'output', 'similar_posts_feed_output_options_subpage');
	$m->add_subpage('Filter', 'filter', 'similar_posts_feed_filter_options_subpage');
	$m->add_subpage('Other', 'other', 'similar_posts_feed_other_options_subpage');
	$m->add_subpage('Report a Bug', 'bug', 'similar_posts_feed_bug_subpage');
	$m->add_subpage('Remove this Plugin', 'remove', 'similar_posts_feed_remove_subpage');
	$m->display();
}

function similar_posts_feed_general_options_subpage(){
	global $wpdb, $wp_version;
	$options = get_option('similar-posts-feed');
	if (isset($_POST['update_options'])) {
		check_admin_referer('similar-posts-feed-update-options'); 
		if (defined('POC_CACHE_4')) poc_cache_flush();
		// Fill up the options with the values chosen...
		$options = ppl_options_from_post($options, array('limit', 'skip', 'show_private', 'show_pages', 'show_attachments', 'status', 'age', 'omit_current_post', 'match_cat', 'match_tags', 'match_author'));
		update_option('similar-posts-feed', $options);
		// Show a message to say we've done something
		echo '<div class="updated fade"><p>' . __('Options saved', 'similar_posts') . '</p></div>';
	} 
	//now we drop into html to display the option page form
	?>
		<div class="wrap">
		<h2><?php _e('General Settings', 'similar_posts'); ?></h2>
		<form method="post" action="">
		<div class="submit"><input type="submit" name="update_options" value="<?php _e('Save General Settings', 'similar_posts') ?>" /></div>
		<table class="optiontable form-table">
			<?php 
				ppl_display_limit($options['limit']); 
				ppl_display_skip($options['skip']); 
				ppl_display_show_private($options['show_private']); 
				ppl_display_show_pages($options['show_pages']); 
				ppl_display_show_attachments($options['show_attachments']); 
				ppl_display_status($options['status']);
				ppl_display_age($options['age']);
				ppl_display_omit_current_post($options['omit_current_post']); 
				ppl_display_match_cat($options['match_cat']); 
				ppl_display_match_tags($options['match_tags']); 
				ppl_display_match_author($options['match_author']); 
			?>
		</table>
		<div class="submit"><input type="submit" name="update_options" value="<?php _e('Save General Settings', 'similar_posts') ?>" /></div>
		<?php if (function_exists('wp_nonce_field')) wp_nonce_field('similar-posts-feed-update-options'); ?>
		</form>  
	</div>
	<?php	
}

function similar_posts_feed_output_options_subpage(){
	global $wpdb, $wp_version;
	$options = get_option('similar-posts-feed');
	if (isset($_POST['update_options'])) {
		check_admin_referer('similar-posts-feed-update-options'); 
		if (defined('POC_CACHE_4')) poc_cache_flush();
		// Fill up the options with the values chosen...
		$options = ppl_options_from_post($options, array('output_template', 'prefix', 'suffix', 'none_text', 'no_text', 'divider', 'sort', 'group_template'));
		update_option('similar-posts-feed', $options);
		// Show a message to say we've done something
		echo '<div class="updated fade"><p>' . __('Options saved', 'similar_posts') . '</p></div>';
	} 
	//now we drop into html to display the option page form
	?>
		<div class="wrap">
		<h2><?php _e('Output Settings', 'similar_posts'); ?></h2>
		<form method="post" action="">
		<div class="submit"><input type="submit" name="update_options" value="<?php _e('Save Output Settings', 'similar_posts') ?>" /></div>
		<table class="optiontable form-table">
			<tr>
			<td>
			<table>
			<?php 
				ppl_display_output_template($options['output_template']); 
				ppl_display_prefix($options['prefix']); 
				ppl_display_suffix($options['suffix']); 
				ppl_display_none_text($options['none_text']); 
				ppl_display_no_text($options['no_text']); 
				ppl_display_divider($options['divider']); 
				ppl_display_sort($options['sort']);
				ppl_display_group_template($options['group_template']); 
			?>
			</table>
			</td>
			<td>
			<?php ppl_display_available_tags('similar-posts'); ?>
			</td></tr>
		</table>
		<div class="submit"><input type="submit" name="update_options" value="<?php _e('Save Output Settings', 'similar_posts') ?>" /></div>
		<?php if (function_exists('wp_nonce_field')) wp_nonce_field('similar-posts-feed-update-options'); ?>
		</form>  
	</div>
	<?php	
}

function similar_posts_feed_filter_options_subpage(){
	global $wpdb, $wp_version;
	$options = get_option('similar-posts-feed');
	if (isset($_POST['update_options'])) {
		check_admin_referer('similar-posts-feed-update-options'); 
		if (defined('POC_CACHE_4')) poc_cache_flush();
		// Fill up the options with the values chosen...
		$options = ppl_options_from_post($options, array('excluded_posts', 'included_posts', 'excluded_authors', 'included_authors', 'excluded_cats', 'included_cats', 'tag_str', 'custom'));
		update_option('similar-posts-feed', $options);
		// Show a message to say we've done something
		echo '<div class="updated fade"><p>' . __('Options saved', 'similar_posts') . '</p></div>';
	} 
	//now we drop into html to display the option page form
	?>
		<div class="wrap">
		<h2><?php _e('Filter Settings', 'similar_posts'); ?></h2>
		<form method="post" action="">
		<div class="submit"><input type="submit" name="update_options" value="<?php _e('Save Filter Settings', 'similar_posts') ?>" /></div>
		<table class="optiontable form-table">
			<?php 
				ppl_display_excluded_posts($options['excluded_posts']); 
				ppl_display_included_posts($options['included_posts']); 
				ppl_display_authors($options['excluded_authors'], $options['included_authors']); 
				ppl_display_cats($options['excluded_cats'], $options['included_cats']); 
				ppl_display_tag_str($options['tag_str']); 
				ppl_display_custom($options['custom']); 
			?>
		</table>
		<div class="submit"><input type="submit" name="update_options" value="<?php _e('Save Filter Settings', 'similar_posts') ?>" /></div>
		<?php if (function_exists('wp_nonce_field')) wp_nonce_field('similar-posts-feed-update-options'); ?>
		</form>  
	</div>
	<?php	
}

function similar_posts_feed_other_options_subpage(){
	global $wpdb, $wp_version;
	$options = get_option('similar-posts-feed');
	if (isset($_POST['update_options'])) {
		check_admin_referer('similar-posts-feed-update-options'); 
		if (defined('POC_CACHE_4')) poc_cache_flush();
		// Fill up the options with the values chosen...
		$options = ppl_options_from_post($options, array('stripcodes', 'term_extraction', 'num_terms', 'weight_title', 'weight_content', 'weight_tags', 'hand_links'));
		$wcontent = $options['weight_content'] + 0.0001; 
		$wtitle = $options['weight_title'] + 0.0001;
		$wtags = $options['weight_tags'] + 0.0001;
		$wcombined = $wcontent + $wtitle + $wtags;
		$options['weight_content'] = $wcontent / $wcombined; 
		$options['weight_title'] = $wtitle / $wcombined; 
		$options['weight_tags'] = $wtags / $wcombined; 
		update_option('similar-posts-feed', $options);
		// Show a message to say we've done something
		echo '<div class="updated fade"><p>' . __('Options saved', 'similar_posts') . '</p></div>';
	} 
	//now we drop into html to display the option page form
	?>
		<div class="wrap">
		<h2><?php _e('Other Settings', 'similar_posts'); ?></h2>
		<form method="post" action="">
		<div class="submit"><input type="submit" name="update_options" value="<?php _e('Save Other Settings', 'similar_posts') ?>" /></div>
		<table class="optiontable form-table">
			<?php 
				ppl_display_weights($options); 
				ppl_display_num_terms($options['num_terms']); 
				ppl_display_term_extraction($options['term_extraction']); 
				ppl_display_hand_links($options['hand_links']);
				ppl_display_stripcodes($options['stripcodes']); 
			?>
		</table>
		<div class="submit"><input type="submit" name="update_options" value="<?php _e('Save Other Settings', 'similar_posts') ?>" /></div>
		<?php if (function_exists('wp_nonce_field')) wp_nonce_field('similar-posts-feed-update-options'); ?>
		</form>  
	</div>
	<?php	
}

function similar_posts_feed_bug_subpage(){
	ppl_bug_form('similar-posts-feed'); 
}

function similar_posts_feed_remove_subpage(){
	function eradicate() {
		global $wpdb, $table_prefix;
		delete_option('similar-posts');
		delete_option('similar-posts-feed');
		$table_name = $table_prefix . 'similar_posts_feed';
		$wpdb->query("DROP TABLE `$table_name`");
	}
	ppl_plugin_eradicate_form('eradicate', str_replace('-admin', '', __FILE__)); 
}	

// sets up the index for the blog
function save_index_entries ($utf8=false, $use_stemmer='false', $batch=100, $cjk=false) {
	global $wpdb, $table_prefix;
	//$t0 = microtime(true);
	$table_name = $table_prefix.'similar_posts';
	$wpdb->query("TRUNCATE `$table_name`");
	$termcount = 0;
	$start = 0;
	// in batches to conserve memory
	while ($posts = $wpdb->get_results("SELECT `ID`, `post_title`, `post_content`, `post_type` FROM $wpdb->posts LIMIT $start, $batch", ARRAY_A)) {
		reset($posts);
		while (list($dummy, $post) = each($posts)) {
			if ($post['post_type'] === 'revision') continue;
			$content = sp_get_post_terms($post['post_content'], $utf8, $use_stemmer, $cjk);
			$title = sp_get_title_terms($post['post_title'], $utf8, $use_stemmer, $cjk);
			$postID = $post['ID'];
			$tags = sp_get_tag_terms($postID, $utf8);
			$wpdb->query("INSERT INTO `$table_name` (pID, content, title, tags) VALUES ($postID, \"$content\", \"$title\", \"$tags\")");
			$termcount = $termcount + 1;
		}
		$start += $batch;
		if (!ini_get('safe_mode')) set_time_limit(30);
	}
	unset($posts);
	//$t = microtime(true) - $t0; echo "t = $t<br>";
	return $termcount;
}

// this function gets called when the plugin is installed to set up the index and default options
function similar_posts_install() {
   	global $wpdb, $table_prefix;
	
	$table_name = $table_prefix . 'similar_posts';
	$errorlevel = error_reporting(0);
	$suppress = $wpdb->hide_errors();
	$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
			`pID` bigint( 20 ) unsigned NOT NULL ,
			`content` longtext NOT NULL ,
			`title` text NOT NULL ,
			`tags` text NOT NULL ,
			FULLTEXT KEY `title` ( `title` ) ,
			FULLTEXT KEY `content` ( `content` ) ,
			FULLTEXT KEY `tags` ( `tags` )
			) ENGINE = MyISAM CHARSET = utf8;";
	$wpdb->query($sql);
	// MySQL before 4.1 doesn't recognise the character set properly, so if there's an error we can try without
	if ($wpdb->last_error !== '') {
		$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
				`pID` bigint( 20 ) unsigned NOT NULL ,
				`content` longtext NOT NULL ,
				`title` text NOT NULL ,
				`tags` text NOT NULL ,
				FULLTEXT KEY `title` ( `title` ) ,
				FULLTEXT KEY `content` ( `content` ) ,
				FULLTEXT KEY `tags` ( `tags` )
				) ENGINE = MyISAM;";
		$wpdb->query($sql);
	}
	$options = (array) get_option('similar-posts-feed');
	// check each of the option values and, if empty, assign a default (doing it this long way
	// lets us add new options in later versions)
	if (!isset($options['limit'])) $options['limit'] = 5;
	if (!isset($options['skip'])) $options['skip'] = 0;
	if (!isset($options['age'])) {$options['age']['direction'] = 'none'; $options['age']['length'] = '0'; $options['age']['duration'] = 'month';}
	if (!isset($options['divider'])) $options['divider'] = '';
	if (!isset($options['omit_current_post'])) $options['omit_current_post'] = 'true';
	if (!isset($options['show_private'])) $options['show_private'] = 'false';
	if (!isset($options['show_pages'])) $options['show_pages'] = 'false';
	if (!isset($options['show_attachments'])) $options['show_attachments'] = 'false';
	// show_static is now show_pages
	if ( isset($options['show_static'])) {$options['show_pages'] = $options['show_static']; unset($options['show_static']);};
	if (!isset($options['none_text'])) $options['none_text'] = __('None Found', 'similar_posts');
	if (!isset($options['no_text'])) $options['no_text'] = 'false';
	if (!isset($options['tag_str'])) $options['tag_str'] = '';
	if (!isset($options['excluded_cats'])) $options['excluded_cats'] = '';
	if ($options['excluded_cats'] === '9999') $options['excluded_cats'] = '';
	if (!isset($options['included_cats'])) $options['included_cats'] = '';
	if ($options['included_cats'] === '9999') $options['included_cats'] = '';
	if (!isset($options['excluded_authors'])) $options['excluded_authors'] = '';
	if ($options['excluded_authors'] === '9999') $options['excluded_authors'] = '';
	if (!isset($options['included_authors'])) $options['included_authors'] = '';
	if ($options['included_authors'] === '9999') $options['included_authors'] = '';
	if (!isset($options['included_posts'])) $options['included_posts'] = '';
	if (!isset($options['excluded_posts'])) $options['excluded_posts'] = '';
	if ($options['excluded_posts'] === '9999') $options['excluded_posts'] = '';
	if (!isset($options['stripcodes'])) $options['stripcodes'] = array(array());
	if (!isset($options['prefix'])) $options['prefix'] = 'Similar Posts:<ul>';
	if (!isset($options['suffix'])) $options['suffix'] = '</ul>';
	if (!isset($options['output_template'])) $options['output_template'] = '<li>{link}</li>';
	if (!isset($options['match_cat'])) $options['match_cat'] = 'false';
	if (!isset($options['match_tags'])) $options['match_tags'] = 'false';
	if (!isset($options['match_author'])) $options['match_author'] = 'false';
	if (!isset($options['custom'])) {$options['custom']['key'] = ''; $options['custom']['op'] = '='; $options['custom']['value'] = '';}
	if (!isset($options['sort'])) {$options['sort']['by1'] = ''; $options['sort']['order1'] = SORT_ASC; $options['sort']['case1'] = 'false';$options['sort']['by2'] = ''; $options['sort']['order2'] = SORT_ASC; $options['sort']['case2'] = 'false';}
	if (!isset($options['status'])) {$options['status']['publish'] = 'true'; $options['status']['private'] = 'false'; $options['status']['draft'] = 'false'; $options['status']['future'] = 'false';}
	if (!isset($options['group_template'])) $options['group_template'] = '';
	if (!isset($options['weight_content'])) $options['weight_content'] = 0.9;
	if (!isset($options['weight_title'])) $options['weight_title'] = 0.1;
	if (!isset($options['weight_tags'])) $options['weight_tags'] = 0.0;	
	if (!isset($options['num_terms'])) $options['num_terms'] = 20;
	if (!isset($options['term_extraction'])) $options['term_extraction'] = 'frequency';
	if (!isset($options['hand_links'])) $options['hand_links'] = 'false';
	update_option('similar-posts-feed', $options);
	
	$options = (array) get_option('similar-posts');
	// check each of the option values and, if empty, assign a default (doing it this long way
	// lets us add new options in later versions)
	if (!isset($options['feed_active'])) $options['feed_active'] = 'false'; // deprecated
	if (!isset($options['widget_condition'])) $options['widget_condition'] = '';
	if (!isset($options['widget_parameters'])) $options['widget_parameters'] = '';
	if (!isset($options['feed_on'])) $options['feed_on'] = 'false';
	if (!isset($options['feed_priority'])) $options['feed_priority'] = '10';
	if (!isset($options['feed_parameters'])) $options['feed_parameters'] = 'prefix=<strong>'.__('Similar Posts', 'similar-posts').':</strong><ul class="similar-posts">&suffix=</ul>';
	if (!isset($options['append_on'])) $options['append_on'] = 'false';
	if (!isset($options['append_priority'])) $options['append_priority'] = '10';
	if (!isset($options['append_parameters'])) $options['append_parameters'] = 'prefix=<h3>'.__('Similar Posts', 'similar-posts').':</h3><ul class="similar-posts">&suffix=</ul>';
	if (!isset($options['append_condition'])) $options['append_condition'] = 'is_single()';
	if (!isset($options['limit'])) $options['limit'] = 5;
	if (!isset($options['skip'])) $options['skip'] = 0;
	if (!isset($options['age'])) {$options['age']['direction'] = 'none'; $options['age']['length'] = '0'; $options['age']['duration'] = 'month';}
	if (!isset($options['divider'])) $options['divider'] = '';
	if (!isset($options['omit_current_post'])) $options['omit_current_post'] = 'true';
	if (!isset($options['show_private'])) $options['show_private'] = 'false';
	if (!isset($options['show_pages'])) $options['show_pages'] = 'false';
	if (!isset($options['show_attachments'])) $options['show_attachments'] = 'false';
	// show_static is now show_pages
	if ( isset($options['show_static'])) {$options['show_pages'] = $options['show_static']; unset($options['show_static']);};
	if (!isset($options['none_text'])) $options['none_text'] = __('None Found', 'similar_posts');
	if (!isset($options['no_text'])) $options['no_text'] = 'false';
	if (!isset($options['tag_str'])) $options['tag_str'] = '';
	if (!isset($options['excluded_cats'])) $options['excluded_cats'] = '';
	if ($options['excluded_cats'] === '9999') $options['excluded_cats'] = '';
	if (!isset($options['included_cats'])) $options['included_cats'] = '';
	if ($options['included_cats'] === '9999') $options['included_cats'] = '';
	if (!isset($options['excluded_authors'])) $options['excluded_authors'] = '';
	if ($options['excluded_authors'] === '9999') $options['excluded_authors'] = '';
	if (!isset($options['included_authors'])) $options['included_authors'] = '';
	if ($options['included_authors'] === '9999') $options['included_authors'] = '';
	if (!isset($options['included_posts'])) $options['included_posts'] = '';
	if (!isset($options['excluded_posts'])) $options['excluded_posts'] = '';
	if ($options['excluded_posts'] === '9999') $options['excluded_posts'] = '';
	if (!isset($options['stripcodes'])) $options['stripcodes'] = array(array());
	if (!isset($options['prefix'])) $options['prefix'] = '<ul>';
	if (!isset($options['suffix'])) $options['suffix'] = '</ul>';
	if (!isset($options['output_template'])) $options['output_template'] = '<li>{link}</li>';
	if (!isset($options['match_cat'])) $options['match_cat'] = 'false';
	if (!isset($options['match_tags'])) $options['match_tags'] = 'false';
	if (!isset($options['match_author'])) $options['match_author'] = 'false';
	if (!isset($options['content_filter'])) $options['content_filter'] = 'false';
	if (!isset($options['custom'])) {$options['custom']['key'] = ''; $options['custom']['op'] = '='; $options['custom']['value'] = '';}
	if (!isset($options['sort'])) {$options['sort']['by1'] = ''; $options['sort']['order1'] = SORT_ASC; $options['sort']['case1'] = 'false';$options['sort']['by2'] = ''; $options['sort']['order2'] = SORT_ASC; $options['sort']['case2'] = 'false';}
	if (!isset($options['status'])) {$options['status']['publish'] = 'true'; $options['status']['private'] = 'false'; $options['status']['draft'] = 'false'; $options['status']['future'] = 'false';}
	if (!isset($options['group_template'])) $options['group_template'] = '';
	if (!isset($options['weight_content'])) $options['weight_content'] = 0.9;
	if (!isset($options['weight_title'])) $options['weight_title'] = 0.1;
	if (!isset($options['weight_tags'])) $options['weight_tags'] = 0.0;	
	if (!isset($options['num_terms'])) $options['num_terms'] = 20;
	if (!isset($options['term_extraction'])) $options['term_extraction'] = 'frequency';
	if (!isset($options['hand_links'])) $options['hand_links'] = 'false';
	if (!isset($options['utf8'])) $options['utf8'] = 'false';
	if (!function_exists('mb_internal_encoding')) $options['utf8'] = 'false';
	if (!isset($options['cjk'])) $options['cjk'] = 'false';
	if (!function_exists('mb_internal_encoding')) $options['cjk'] = 'false';
	if (!isset($options['use_stemmer'])) $options['use_stemmer'] = 'false';
	if (!isset($options['batch'])) $options['batch'] = '100';
	
	update_option('similar-posts', $options);

 	// initial creation of the index, if the table is empty
	$num_index_posts = $wpdb->get_var("SELECT COUNT(*) FROM `$table_name`");
	if ($num_index_posts == 0) save_index_entries (($options['utf8'] === 'true'), 'false', $options['batch'], ($options['cjk'] === 'true'));	

	// deactivate legacy Similar Posts Feed if present
	$current = get_option('active_plugins');
	if (in_array('Similar_Posts_Feed/similar-posts-feed.php', $current)) {
		array_splice($current, array_search('Similar_Posts_Feed/similar-posts-feed.php', $current), 1); 
		update_option('active_plugins', $current);	
	}
	unset($current);
	
 	// clear legacy custom fields
	$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = 'similarterms'");
	
	// clear legacy index
	$indices = $wpdb->get_results("SHOW INDEX FROM $wpdb->posts", ARRAY_A);
	foreach ($indices as $index) {
		if ($index['Key_name'] === 'post_similar') {
			$wpdb->query("ALTER TABLE $wpdb->posts DROP INDEX post_similar");
			break;
		}	
	}
	
	$wpdb->show_errors($suppress);
	error_reporting($errorlevel);
}



if (!function_exists('ppl_plugin_basename')) {
	if ( !defined('WP_PLUGIN_DIR') ) define( 'WP_PLUGIN_DIR', ABSPATH . 'wp-content/plugins' ); 
	function ppl_plugin_basename($file) {
		$file = str_replace('\\','/',$file); // sanitize for Win32 installs
		$file = preg_replace('|/+|','/', $file); // remove any duplicate slash
		$plugin_dir = str_replace('\\','/',WP_PLUGIN_DIR); // sanitize for Win32 installs
		$plugin_dir = preg_replace('|/+|','/', $plugin_dir); // remove any duplicate slash
		$file = preg_replace('|^' . preg_quote($plugin_dir, '|') . '/|','',$file); // get relative path from plugins dir
		return $file;
	}
}

add_action('activate_'.str_replace('-admin', '', ppl_plugin_basename(__FILE__)), 'similar_posts_install');

?>