<?php global $wptouch_settings; ?>

<div class="metabox-holder" id="wptouch-head">
	<div class="postbox">
		<div id="wptouch-head-colour">
			<div id="wptouch-head-title">
				<?php WPtouch(); ?>
				<img class="ajax-load" src="<?php echo compat_get_plugin_url('wptouch'); ?>/images/admin-ajax-loader.gif" alt="ajax"/>
			</div>
				<div id="wptouch-head-links">
					<ul>
						<!-- <li><?php echo sprintf(__( "%sSupport Forums%s", "wptouch" ), '<a href="http://support.bravenewcode.com/forum/wptouch" target="_blank">','</a>'); ?> | </li> -->
						<li><?php echo sprintf(__( "%sWPtouch Homepage%s", "wptouch" ), '<a href="http://www.bravenewcode.com/wptouch" target="_blank">','</a>'); ?> | </li>
						<li><?php echo sprintf(__( "%sNewsletter%s", "wordtwit" ), '<a href="http://www.bravenewcode.com/newsletter" target="_blank">','</a>'); ?> | </li>
						<li><?php echo sprintf(__( "%sDonate%s", "wptouch" ), '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=paypal%40bravenewcode%2ecom&amp;item_name=WPtouch%20Beer%20Fund&amp;no_shipping=1&amp;tax=0&amp;currency_code=CAD&amp;lc=CA&amp;bn=PP%2dDonationsBF&amp;charset=UTF%2d8" target="_blank">','</a>'); ?></li>
					</ul>
				</div>
	<div class="bnc-clearer"></div>
			</div>	
	
		<div id="wptouch-news-support">

			<div id="wptouch-news-wrap">
			<h3><span class="rss-head">&nbsp;</span><?php _e( "WPtouch Wire", "wptouch" ); ?></h3>
				<div id="wptouch-news-content" style="display:none">
					<?php require_once (ABSPATH . WPINC . '/rss.php');
					$rss = @fetch_rss('http://www.bravenewcode.com/tag/wptouch/feed/');						
					if ( isset($rss->items) && 0 != count($rss->items) ) { ?>
					<ul>
						<?php $rss->items = array_slice($rss->items, 0, 5); foreach ($rss->items as $item ) { ?>
						<li><a target="_blank" class="orange-link" href='<?php echo wp_filter_kses($item['link']); ?>'><?php echo wp_specialchars($item['title']); ?></a></li>
						<?php } ?>
					</ul>
					<?php } ?>				
				</div>
			</div>

			<div id="wptouch-support-wrap">			
			<h3><span class="rss-head">&nbsp;</span><?php _e( "Twitter Topics", "wptouch" ); ?></h3>
				<div id="wptouch-support-content" style="display:none">
					<?php require_once (ABSPATH . WPINC . '/rss.php');
					$rss = @fetch_rss('http://search.twitter.com/search.atom?q=wptouch');						
					if ( isset($rss->items) && 0 != count($rss->items) ) { ?>
					<ul>
						<?php $rss->items = array_slice($rss->items, 0, 5); foreach ($rss->items as $item ) { ?>
						<li><a target="_blank" class="orange-link" href='<?php echo wp_filter_kses($item['link']); ?>'><?php echo wp_specialchars($item['title']); ?></a></li>
						<?php } ?>
					</ul>
					<?php } ?>
				</div>
			</div>
			
		</div><!-- wptouch-news-support -->

	<div class="bnc-clearer"></div>
	</div><!-- postbox -->
</div><!-- wptouch-head -->