<?php
/**
 * @package WordPress
 * @subpackage involver
 */
?>
<?php if (function_exists('dynamic_sidebar')) { ?>
    
    <div id="sidebar" role="complementary">
    	<div class="cat">
        	<h2>Categories</h2>
    		<ul>
            	<?php if (is_home()) { ?>
                	<li class="current-cat"><a href="<?php bloginfo('url'); ?>/">Home</a></li>
                <?php } else { ?>
                	<li class="cat-item"><a href="<?php bloginfo('url'); ?>/">Home</a></li>
                <?php } ?>
				<?php wp_list_categories('title_li='); ?>
            </ul>
        </div>
	    <?php dynamic_sidebar('sidebar'); ?>
    </div>
           
<?php }?>
   
