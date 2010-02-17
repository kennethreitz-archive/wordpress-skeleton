<?php

$podcasting_excerpt_check = false;

/**
 * Handle's the various format players for Podcasting
 * @author Spiral Web Consulting
 **/
class PodcastingPlayer
{
	var $_id = 0;
	var $_playerAdded = array();
	
	/**
	 * Setup the player for use
	 */
	function PodcastingPlayer() {
		add_shortcode('podcast', array($this, 'shortcode'));
		add_action('wp_print_scripts', array($this, 'addPlayerScripts'));
		add_action('wp_head', array($this, 'addPlayerJavascript'));
		add_filter('the_content', array($this, 'theContent'), 50);
		add_filter('get_the_excerpt', array($this, 'checkExcerpt'), 1);
	}
	
	/**
	 * Handle's the podcast shortcode
	 */
	function shortcode( $atts, $content = null ) {
		global $post, $podcasting_excerpt_check;
		
		# Don't process the excerpt
		if ( $podcasting_excerpt_check )
			return '';

		# Mark the player added so it doesn't happen automatically
		$this->_playerAdded[$post->ID] = true;

		# Extract the information from the shortcode
		extract( shortcode_atts( array(
			'format' => 'mp3',
			'width' => get_option('pod_player_width'),
			'height' => get_option('pod_player_height'),
			'flashvars' => ''
			), $atts ) );
		
		# Increase the id count
		$this->_id++;

		# Display the correct player
		if ( 'mp3' == $format && get_option('pod_player_use_video') == 'no' )
			return $this->audioPlayer($content, $width, $height, $flashvars);
		elseif ( 'video' == $format || get_option('pod_player_use_video') == 'yes' )
			return $this->videoPlayer($content, $width, $height, $flashvars);
	}
	
	/**
	 * The audio player
	 * @return the HTML for the audio player
	 **/
	function audioPlayer($content, $width, $height, $flashvars)
	{
		$podcasting_player_url = plugins_url('/podcasting/player/player.swf');

		# Grab the player's surrounding text
		$podcasting_text_above = stripslashes(get_option('pod_player_text_above'));
		$podcasting_text_before = stripslashes(get_option('pod_player_text_before'));
		$podcasting_text_below = stripslashes(get_option('pod_player_text_below'));
		$podcasting_text_link = get_option('pod_player_text_link');

		# Text above the player
		if ( $podcasting_text_above != '' ) {
			if ( 'above' == $podcasting_text_link )
				$podcasting_text_above = "<p><a href='$content'>$podcasting_text_above</a></p>";
			else
				$podcasting_text_above = "<p>$podcasting_text_above</p>";
		}

		# Text immeaditely before the player
		if ( $podcasting_text_before != '' ) {
			if ( 'before' == $podcasting_text_link )
				$podcasting_text_before = "<a href='$content'>$podcasting_text_before</a> ";
			else
				$podcasting_text_before .= ' ';
		}

		# Text below the player
		if ( $podcasting_text_below != '' ) {
			if ( 'below' == $podcasting_text_link )
				$podcasting_text_below = "<p><a href='$content'>$podcasting_text_below</a></p>";
			else
				$podcasting_text_below = "<p>$podcasting_text_below</p>";
		}

		# Add the flashvars if any
		if ( $flashvars != '' )
			$flashvars = ', ' . $flashvars;

		# Check if is a feed
		if ( is_feed() ) {
			return $podcasting_text_above . $podcasting_text_before . '<a href="' . $content . '">' . $content . '</a>' . $podcasting_text_below;
		} else {
			return $podcasting_text_above . $podcasting_text_before . '<span id="pod_audio_' . $this->_id . '">&nbsp;</span>
		<script type="text/javascript">  
			AudioPlayer.embed("pod_audio_' . $this->_id . '", {soundFile: "' . rawurlencode($content) . '"' . $flashvars . '});  
		</script>
		' . $podcasting_text_below;
		}
	}
	
	/**
	 * The video player
	 */
	function videoPlayer($content, $width, $height, $flashvars) {
		$podcasting_player_url = plugins_url('/podcasting/player/mediaplayer.swf');

		# Check to make sure the width and height have values
		$width = ( $width == '' ) ? '400' : $width;
		$height = ( $height == '' ) ? '300' : $height;

		# Add the flashvars, if any
		$global_flashvars = stripslashes(get_option('pod_video_flashvars'));
		$global_flashvars = ( $global_flashvars != '' ) ? ', ' . $global_flashvars : '';
		$flashvars = ( $flashvars != '' ) ? ', ' . $flashvars : '';

		# Grab the player's surrounding text
		$podcasting_text_above = stripslashes(get_option('pod_player_text_above'));
		$podcasting_text_before = stripslashes(get_option('pod_player_text_before'));
		$podcasting_text_below = stripslashes(get_option('pod_player_text_below'));
		$podcasting_text_link = get_option('pod_player_text_link');

		# Above the player
		if ( $podcasting_text_above != '' ) {
			if ( 'above' == $podcasting_text_link )
				$podcasting_text_above = "<p><a href='$content'>$podcasting_text_above</a></p>";
			else
				$podcasting_text_above = "<p>$podcasting_text_above</p>";
		}

		# Text right before the player
		if ( $podcasting_text_before != '' ) {
			if ( 'before' == $podcasting_text_link )
				$podcasting_text_before = "<a href='$content'>$podcasting_text_before</a> ";
			else
				$podcasting_text_before .= ' ';
		}

		# Text below the player
		if ( $podcasting_text_below != '' ) {
			if ( 'below' == $podcasting_text_link )
				$podcasting_text_below = "<p><a href='$content'>$podcasting_text_below</a></p>";
			else
				$podcasting_text_below = "<p>$podcasting_text_below</p>";
		}

		# Check if is a feed
		if ( is_feed() ) {
			return $podcasting_text_above . '<a href="' . $content . '">' . $content . '</a>' . $podcasting_text_below;
		} else {
			return $podcasting_text_above . '<span id="pod_video_' . $this->_id . '">&nbsp;</span>' . $podcasting_text_below . '
		<script type="text/javascript">
			var pod_video_flashvars_' . $this->_id . ' = { file: "' . rawurlencode($content) . '"' . $global_flashvars . $flashvars . ' };
			var pod_video_params_' . $this->_id . ' = { allowfullscreen: "true", allowscriptaccess: "always" };
			swfobject.embedSWF("' . $podcasting_player_url . '", "pod_video_' . $this->_id . '", "' . $width . '", "' . $height . '", "9.0.0", "", pod_video_flashvars_' . $this->_id . ', pod_video_params_' . $this->_id . ');
		</script>';
		}
	}
	
	/**
	 * Adds the player's javascript to the page
	 */
	function addPlayerScripts() {
		wp_enqueue_script('swfobject', plugins_url('/podcasting/player/swfobject.js'), false, '2.1');
		wp_enqueue_script('audio-player', plugins_url('/podcasting/player/audio-player-noswfobject.js'), false, '2.0');
	}
	
	/**
	 * Add the Javascript needed to control the various players
	 */
	function addPlayerJavascript() {
		# Grab the audio player's global flashvars
		$global_flashvars = stripslashes(get_option('pod_player_flashvars'));
		
		# Add the global flashvars, if any
		if ( get_option('pod_player_flashvars') != '' )
			$global_flashvars = ', ' . $global_flashvars;
		
		# Adjust the audio player's width
		$pod_player_width = stripslashes(get_option('pod_audio_width'));
		if ( $pod_player_width == '' )
			$pod_player_width = 290;
		
		# Initialize the audio player
		?>
		<!-- Podcasting <?php echo PODCASTING_VERSION; ?>: http://plugins.spiralwebconsulting.com/podcasting.html -->
		<script type="text/javascript">
			AudioPlayer.setup("<?php echo plugins_url('/podcasting/player/player.swf'); ?>", {  
				width: <?php echo $pod_player_width . $global_flashvars; ?>
			});
		</script>
		<?php
	}
	
	/**
	 * Add the player automatically to a post
	 */
	function theContent($content) {
		global $wpdb, $post, $podcasting_excerpt_check;
		
		# Don't process the excerpt
		if ( $podcasting_excerpt_check )
			return $content;
		
		# Don't automatically add the player if the page is a feed, the player has already been added, or the user has the automatic player option disabled
		if ( is_feed() || $this->_playerAdded[$post->ID] || get_option('pod_player_location') == '' )
			return $content;

		# If there is a post id, grab the enclosures
		if ($post->ID)
			$enclosures = $wpdb->get_results("SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE post_id = {$post->ID} AND meta_key = 'enclosure' ORDER BY meta_id", ARRAY_A);

		# Stop if no enclosures
		if ( $enclosures == '' )
			return $content;

		# For each enclosure
		foreach ($enclosures as $enclosure) {
			# Parse out the enclosure information
			$enclosure_value = explode("\n", $enclosure['meta_value']);
			$enclosure_itunes = unserialize($enclosure_value[3]);
			
			# Check if the enclosure is an audio format
			$podcast_player = ( 'mp3' == substr(trim($enclosure_value[0]), -3) ) ? true : false;
			
			# Check if the enclosure is a video format
			$podcast_video_player_formats = array('m4v', 'mp4', 'mov', 'flv');
			$podcast_video_player = ( in_array(substr(trim($enclosure_value[0]), -3), $podcast_video_player_formats) ) ? true : false;
			
			# Place the player in correct spot on the page
			if ( $podcast_player )
				if ( get_option('pod_player_location') == 'top' )
					$content = $this->shortcode(array('format'=>'mp3'), trim($enclosure_value[0])) . $content;
				else
					$content .= $this->shortcode(array('format'=>'mp3'), trim($enclosure_value[0]));
			elseif ( $podcast_video_player )
				if ( get_option('pod_player_location') == 'top' )
					$content = $this->shortcode(array('format'=>'video'), trim($enclosure_value[0])) . $content;
				else
					$content .= $this->shortcode(array('format'=>'video'), trim($enclosure_value[0]));
		}
		
		return $content;
	}
	
	/**
	 * Checks for the excerpt
	 **/
	function checkExcerpt($content)
	{
		global $podcasting_excerpt_check;
		$podcasting_excerpt_check = true;
		return $content;
	}
	
}

# Start the player
$podcasting_player = new PodcastingPlayer();

?>