<?php

/**
 * podPress importer for Podcasting plugin
 * @author Spiral Web Consulting
 */
class podPressImport {
	
	/**
	 * Display the header for the importer
	 */
	function header() {
		echo '<div class="wrap">';
		echo '<h2>'.__('Import podPress to Podcasting').'</h2>';
	}

	/**
	 * Display the footer
	 */
	function footer() {
		echo '</div>';
	}

	/**
	 * Start the importing process
	 */
	function greet() {
		$this->header();
?>
<div class="narrow">
<p><?php _e('Howdy! We&#8217;re about to begin importing all of your podPress podcasts into Podcasting. To begin, click "Start Importing".'); ?></p>

<form method="post" action="<?php echo add_query_arg('step', 1); ?>" class="import-upload-form">

<?php wp_nonce_field('import-podpress'); ?>
<p class="submit">
<input type="submit" value="<?php echo attribute_escape(__('Start Importing')); ?>" id="podpress-submit" />
</p>
</form>
<p><?php _e('The importer is smart enough not to import duplicates, so you can run this multiple times without worry if&#8212;for whatever reason&#8212;it doesn\'t finish.'); ?> </p>
</div>
<?php
		$this->footer();
	}
	
	/**
	 * Step one of the import process (displays importer output)
	 */
	function import() {
		$this->header();
		
		$podcasts = $this->retreive_podPress_podcasts();
		$this->import_podcasts($podcasts);
		
		$this->footer();
	}
	
	/**
	 * Grab the podPress podcasts
	 */
	function retreive_podPress_podcasts() {
		global $wpdb;
		
		$podcasts = array();
		
		$podpress_data = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'podPressMedia'", ARRAY_A);
		
		if ( $podpress_data != '' ) {
			foreach ( $podpress_data AS $data ) {
				$itunes_info = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'podPressPostSpecific' AND post_id = $data[post_id]", ARRAY_A);
			
				if ( $data['meta_value'] != '' ) {			
					$podcast = array();
					$podcast = unserialize(trim($data['meta_value']));
					$podcast = ( !is_array($podcast) ) ? unserialize($podcast) : $podcast;
					$podcast[0]['post_id'] = $data['post_id'];
					$podcast[0]['itunes'] = unserialize($itunes_info[0]['meta_value']);
					$podcast[0]['itunes'] = ( !is_array($podcast[0]['itunes']) ) ? unserialize($podcast[0]['itunes']) : $podcast;
			
					$podcasts[] = $podcast;
				}
			}
		}
		
		return $podcasts;
	}
	
	/**
	 * Actually import the podPress podcasts into Podcasting
	 */
	function import_podcasts($podcasts) {
		global $wpdb, $podcasting_metabox;
		$i = 0;
		
		$podPress_options = get_option('podPress_config');
		
		foreach ( $podcasts AS $podcast ) {
			$no_enclose = false;
			$enclosed = get_post_meta($podcast[0]['post_id'], 'enclosure');
			if ( $enclosed != '' ) {
				foreach ( $enclosed AS $enclose ) {
					$enclose = explode("\n", $enclose);
					if ( ( $enclose[0] == $podcast[0]['URI'] ) &&  ( $enclose[3] != '' ) )
						$no_enclose = true;
				}
			}

			if ( !$no_enclose ) {
			
				// Basic podcasting info
				$content = ( substr($podcast[0]['URI'], 0, 4) == 'http' ) ? $podcast[0]['URI'] : $podPress_options['mediaWebPath']  . $podcast[0]['URI'];
				$headers = $podcasting_metabox->getHttpHeaders($content);
				$length = (int) $headers['content-length'];
				$type = addslashes( $headers['content-type'] );
			
				// iTunes
				$itunes = serialize(array(
					'format' => 'default-format',
					'keywords' => ( substr($podcast[0]['itunes']['itunes:keywords'], 0, 1) != '#' ) ? $podcast[0]['itunes']['itunes:keywords'] : '',
					'author' => ( substr($podcast[0]['itunes']['itunes:author'], 0, 1) != '#' ) ? $podcast[0]['itunes']['itunes:author'] : '',
					'length' => $podcast[0]['duration'],
					'explicit' => strtolower($podcast[0]['itunes']['itunes:explicit'])
					));
			
				if ( $headers['response'] != '404' && is_array($headers) ) {
					add_post_meta($podcast[0]['post_id'], 'enclosure', "$content\n$length\n$type\n$itunes\n");
				
					$pod_enclosure_id2 = $wpdb->get_var("SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = {$podcast[0][post_id]} AND meta_key = 'enclosure' ORDER BY meta_id DESC"); // Find the enclosure we just added
					wp_set_object_terms($pod_enclosure_id2, 'default-format', 'podcast_format', false);
					
					$i++;
				}
			
			}
		}
		
		echo '<p><strong>Successfully imported ' . $i . ' podcasts.</strong></p>';
	}
	
	/**
	 * The switcher function for WordPress importers
	 */
	function dispatch() {
		if (empty ($_GET['step']))
			$step = 0;
		else
			$step = (int) $_GET['step'];

		switch ($step) {
			case 0 :
				$this->greet();
				break;
			case 1 :
				check_admin_referer('import-podpress');
				$this->import();
				break;
		}
	}

	function podPressImport() {
		// Nothing.
	}
}

# Start the podPress importer
$podPress_import = new podPressImport();

# Add the podPress importer to the array of importers
$wp_importers['podpress'] = array('PodPress', 'Import podPress podcasts into Podcasting.', array ($podPress_import, 'dispatch'));

?>