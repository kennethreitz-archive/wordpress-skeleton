<?php
/**
 * @package WordPress
 * @subpackage Default_Theme
 */
?>
	<div id="sidebar">
		<ul>
			<?php 	/* Widgetized sidebar, if you have the plugin installed. */
					if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar() ) : ?>

			<?php wp_list_pages('title_li=' ); ?>

			<?php endif; ?>
			
			<li>
				
			</li>
			
		</ul>
	</div>

