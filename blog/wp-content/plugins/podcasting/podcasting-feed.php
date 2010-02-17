<?php

/**
 * Handles the Podcasting feed
 * @author Spiral Web Consulting
 */
class PodcastingFeed {
	
	/**
	 * Starts the Podcasting feed
	 */
	function PodcastingFeed()
	{
		add_action('init', array($this, 'init'));
	}
	
	/**
	 * Actions and filters to hook in after init
	 **/
	function init()
	{
		# Add the podcasting feed type
		add_feed('podcast', array($this, 'do_feed_podcast'));
		
		# Add the podcast feed
		add_filter('query_vars', array($this, 'queryVars'));
		add_filter('posts_join', array($this, 'feedJoin'));
		add_filter('posts_where', array($this, 'feedWhere'));
		add_filter('posts_groupby', array($this, 'feedGroupby'));
		add_action('wp_head', array($this, 'addFeedDiscovery'));
		add_action('template_redirect', array($this, 'preventFeedburner'), -10);

		# Add podcasting information to feeds
		add_action('rss2_ns', array($this, 'addItunesXML'));
		add_filter('option_blogname', array($this, 'blognameFilter'));
		add_filter('option_blogdescription', array($this, 'blogdescriptionFilter'));
		add_action('rss2_head', array($this, 'addItunesFeed'));
		add_filter('rss_enclosure', array($this, 'removeEnclosures'));
		add_action('rss2_item', array($this, 'addItunesItem'));
	}
	
	/**
	 * Create the podcast feed type
	 */
	function do_feed_podcast($withcomments) {
		global $wp_query;
		$wp_query->get_posts();
		do_feed_rss2($withcomments);
	}

	/**
	 * Adds the format option to the query vars
	 */
	function queryVars($vars) {
		$vars[] = 'format';
		return $vars;
	}

	/**
	 * The SQL join information for the feed
	 */
	function feedJoin($join) {
		global $wpdb;
		if ( 'podcast' == get_query_var('feed') ) {		
			$join .= " INNER JOIN {$wpdb->postmeta} pod_meta ON {$wpdb->posts}.ID = pod_meta.post_id";		
			$join .= " INNER JOIN {$wpdb->term_relationships} pod_rel ON (pod_meta.meta_id = pod_rel.object_id)";		
			$join .= " INNER JOIN {$wpdb->term_taxonomy} pod_tax ON (pod_rel.term_taxonomy_id = pod_tax.term_taxonomy_id)";
			$join .= " INNER JOIN {$wpdb->terms} pod_terms ON (pod_tax.term_id = pod_terms.term_id)";
		}
		return $join;
	}

	/**
	 * The SQL where information needed for the feed
	 */
	function feedWhere($where) {
		global $wpdb;
		if ( 'podcast' == get_query_var('feed') ) {
			$podcast_format = ( '' == get_query_var('format') ) ? 'default-format' : get_query_var('format');

			$where .= " AND pod_meta.meta_key = 'enclosure'";
			$where .= " AND pod_terms.slug = '{$podcast_format}'";
		}
		return $where;
	}

	/**
	 * The SQL groupby information needed for the feed
	 */
	function feedGroupby($groupby) {
		global $wpdb;
		if ( 'podcast' == get_query_var('feed') )
			$groupby = "{$wpdb->posts}.ID";
		return $groupby;
	}

	/**
	 * Adds auto-discovery functionality to the Podcasting feed
	 */
	function addFeedDiscovery() {
		global $wp_rewrite;
		$podcast_url = ($wp_rewrite->using_permalinks()) ? '/feed/podcast/' : '/?feed=podcast';
		$podcast_url = get_option('home') . $podcast_url;
		echo '	<link rel="alternate" type="application/rss+xml" title="Podcast: ' . htmlentities(stripslashes(get_option('pod_title')), ENT_COMPAT, "UTF-8") . '" href="' . $podcast_url . '" />' . "\n";

		// Formats
		$pod_formats = get_terms('podcast_format', 'get=all');
		if ( is_array($pod_formats) && count($pod_formats) > 0 ) {
			foreach ($pod_formats as $pod_format) {
				if ( 'default-format' != $pod_format->slug ) {
					$podcast_format_url = ($wp_rewrite->using_permalinks()) ? $podcast_url . "?format=$pod_format->slug" : $podcast_url . "&format=$pod_format->slug";
					echo '	<link rel="alternate" type="application/rss+xml" title="Podcast: ' . htmlentities(stripslashes(get_option('pod_title'))) . " ($pod_format->name)" . '" href="' . $podcast_format_url . '" />' . "\n";
				}
			}
		}
	}

	/**
	 * Prevents the podcasting feed from being redirected by Feedburner
	 */
	function preventFeedburner() {
		if ( 'podcast' == get_query_var('feed') )
			remove_action('template_redirect', 'ol_feed_redirect');
	}

	/**
	 * Add iTunes' XML information to the feed
	 */
	function addItunesXML() {
		if ( 'podcast' == get_query_var('feed') ) {
			echo 'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"';
		}
	}

	/**
	 * Change the feed title for podcasting feeds
	 */
	function blognameFilter($title) {
		if ( 'podcast' == get_query_var('feed') ) {
			$podcast_format = get_term_by('slug', get_query_var('format'), 'podcast_format');
			$title = $this->getOption('pod_title');
			if ( 'default-format' != get_query_var('format') && '' != get_query_var('format') && !empty($podcast_format) )
				$title .= " ($podcast_format->name)";
		}
		return $title;
	}

	/**
	 * Change the feed tagline
	 */
	function blogdescriptionFilter($tagline) {
		if ( 'podcast' == get_query_var('feed') )
			$tagline = $this->getOption('pod_tagline');
		return $tagline;
	}

	/**
	 * Adds the main iTunes feed information
	 */
	function addItunesFeed() {
		if ( 'podcast' == get_query_var('feed') ) {
			// iTunes summary
			if ( '' != get_option('pod_itunes_summary') )
				echo '<itunes:summary>' . $this->flatTextEncode(get_option('pod_itunes_summary')) . '</itunes:summary>' . "\n	";
			// iTunes subtitle
			if ( '' != get_option('pod_tagline') )
				echo '<itunes:subtitle>' . $this->flatTextEncode(get_option('pod_tagline')) . '</itunes:subtitle>' . "\n	";
			// iTunes author
			if ( '' != get_option('pod_itunes_author') )
				echo '<itunes:author>' . $this->getOption('pod_itunes_author') . '</itunes:author>' . "\n	";
			// iTunes image
			if ( '' != get_option('pod_itunes_image') ) {
				echo '<itunes:image href="' . stripslashes(get_option('pod_itunes_image')) . '" />' . "\n	";
				echo '<image><url>' . stripslashes(get_option('pod_itunes_image')) . '</url><title>' . $this->getOption('pod_title') . '</title><link>' . get_option('home') . '</link></image>' . "\n	";
			}
			// iTunes categories
			for ($i = 1; $i <= 3; $i++) {
				$pod_cat_option = 'pod_itunes_cat' . $i;
				if ( '' != get_option($pod_cat_option) ) {
					$pod_category = explode('||', htmlspecialchars(stripslashes(get_option($pod_cat_option))));
					if ( $pod_category[1] ) {
						echo '<itunes:category text="' . $pod_category[0] . '">' . "\n		";
						echo '<itunes:category text="' . $pod_category[1] . '" />' . "\n	";
						echo '</itunes:category>' . "\n	";
					} else
						echo '<itunes:category text="' . $pod_category[0] . '" />' . "\n	";
				}
			}
			// iTunes keywords
			if ( '' != get_option('pod_itunes_keywords') )
				echo '<itunes:keywords>' . $this->getOption('pod_itunes_keywords') . '</itunes:keywords>' . "\n	";
			// iTunes keywords
			if ( '' != get_option('pod_itunes_explicit') )
				echo '<itunes:explicit>' . get_option('pod_itunes_explicit') . '</itunes:explicit>' . "\n	";
			else
				echo '<itunes:explicit>no</itunes:explicit>' . "\n	";
			// iTunes owner information
			if ( ( '' != get_option('pod_itunes_ownername') ) || ( '' != get_option('pod_itunes_owneremail') ) ) {
				echo '<itunes:owner>' . "\n	";
				if ( '' != get_option('pod_itunes_ownername') )
					echo '	<itunes:name>' . $this->getOption('pod_itunes_ownername') . '</itunes:name>' . "\n	";
				if ( '' != get_option('pod_itunes_owneremail') )
					echo '	<itunes:email>' . $this->getOption('pod_itunes_owneremail') . '</itunes:email>' . "\n	";
				echo '</itunes:owner>' . "\n	";
			}
		}
	} // podcasting_add_itunes_feed()

	/**
	 * Remove enclosures from other podcasting formats
	 */
	function removeEnclosures($enclosure) {
		if ( 'podcast' == get_query_var('feed') ) {
			$podcast_format = ( '' == get_query_var('format') ) ? 'default-format' : get_query_var('format');
			$enclosures = get_post_custom_values('enclosure');
			$podcast_urlformats = array();

			// Check if the enclosure should be displayed
			foreach ($enclosures as $enclose) {
				$enclose = explode("\n", $enclose);
				$enclosure_itunes = unserialize($enclose[3]);
				$enclosure_url = explode('"', $enclosure);
				if ( ( $enclosure_url[1] == trim(htmlspecialchars($enclose[0])) ) && ( $enclosure_itunes['format'] == $podcast_format ) )
					return $enclosure;
			}
		} else	
			return $enclosure;
	}

	/**
	 * Add the iTunes information to feed items
	 */
	function addItunesItem() {
		if ( 'podcast' == get_query_var('feed') ) {
			$podcast_format = ( '' == get_query_var('format') ) ? 'default-format' : get_query_var('format');
			$enclosures = get_post_custom_values('enclosure');
			foreach ($enclosures as $enclosure) {
				$enclosure_itunes = explode("\n", $enclosure);
				$enclosure_itunes = unserialize($enclosure_itunes[3]);
				if ($enclosure_itunes['format'] == $podcast_format) break;
			}

			// iTunes summary
			ob_start(); the_content(); $itunes_summary = ob_get_contents(); ob_end_clean();
			$itunes_summary = $this->limitStringLength($this->flatTextEncode($itunes_summary), 4000);
			echo '<itunes:summary>' . $itunes_summary . '</itunes:summary>' . "\n";
			// iTunes subtitle
			ob_start(); the_excerpt_rss(); $itunes_subtitle = ob_get_contents(); ob_end_clean();
			$itunes_subtitle = $this->limitStringLength($this->flatTextEncode($itunes_subtitle), 255);
			echo '<itunes:subtitle>' . $itunes_subtitle . '</itunes:subtitle>' . "\n";
			// iTunes author
			if ( '' != $enclosure_itunes['author'] )
				echo '<itunes:author>' . $this->utf8Encode($enclosure_itunes['author']) . '</itunes:author>' . "\n";
			// iTunes duration
			if ( '' != $enclosure_itunes['length'] )
				echo '<itunes:duration>' . $this->utf8Encode($enclosure_itunes['length']) . '</itunes:duration>' . "\n";
			// iTunes keywords
			if ( '' != $enclosure_itunes['keywords'] )
				echo '<itunes:keywords>' . $this->utf8Encode($enclosure_itunes['keywords']) . '</itunes:keywords>' . "\n";
			// iTunes explicit
			if ( '' != $enclosure_itunes['explicit'] )
				echo '<itunes:explicit>' . $enclosure_itunes['explicit'] . '</itunes:explicit>' . "\n";
		}
	} // podcasting_add_itunes_item()

	/**
	 * Limit the length of a string
	 * @param string - the string to limit
	 * @param limit - the number of characters to limit by
	 * @return the limited string
	 */
	function limitStringLength($string, $limit) {
		if ( strlen($string) > $limit )
			$string = substr($string, 0, strrpos(substr($string, 0, $limit-6), ' ')) . ' [...]';

		return $string;
	}

	/**
	 * Retrieves an iTunes feed value and formats it for the feed
	 *
	 * @param value - the WordPress option to retrieve
	 * @return formatted data for itunes (UTF8)
	 * @author Ronald Heft
	 **/
	function getOption($value)
	{
		return $this->utf8Encode(get_option($value));
	}

	/**
	 * Encode data in UTF8
	 *
	 * @param value - the data to format
	 * @return utf8 formatted data
	 * @author Ronald Heft
	 **/
	function utf8Encode($value)
	{
		return utf8_encode(remove_accents(htmlspecialchars(stripslashes($value))));
	}
	
	/**
	 * Encode specific iTunes fields to flat text
	 *
	 *
	 **/
	function flatTextEncode($value)
	{
		if( DB_CHARSET != 'utf8' ) // Check if the string is UTF-8
			$value = utf8_encode($value); // If it is not, convert to UTF-8 then decode it...

		// Code added to solve issue with KimiliFlashEmbed plugin and also remove the shortcode for the WP Audio Player
		// 99.9% of the time this code will not be necessary
		$value = preg_replace("/\[(kml_(flash|swf)embed|audio\:)\b(.*?)(?:(\/))?(\]|$)/isu", '', $value);

		if(version_compare("5", phpversion(), ">"))
			$value = preg_replace( '/&nbsp;/ui' , ' ', $value); // Best we can do for PHP4
		else
			$value = @html_entity_decode($value, ENT_COMPAT, 'UTF-8'); // Remove any additional entities such as &nbsp;
		$value = preg_replace( '/&amp;/ui' , '&', $value); // Best we can do for PHP4. precaution in case it didn't get removed from function above.

		return wp_specialchars( $value );
	}
	
}

# Start the feed
$podcasting_feed = new PodcastingFeed();

?>