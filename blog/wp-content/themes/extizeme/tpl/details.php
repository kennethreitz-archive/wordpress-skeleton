<?php 
while (have_posts()) : the_post(); 
?>

<div <?php post_class("x-panel ") ?> id="post-<?php the_ID(); ?>" style="margin-bottom: 20px;">
    <?php include('post_title.php')?>
    <div class="x-panel-bwrap">
        <?php include('post_tbar.php')?>                
        <div class="x-panel-body">
            <div class="entry">
            <?php if (is_attachment()) :?>
                <div class="wp-caption aligncenter">
                <a href="<?php echo wp_get_attachment_url($post->ID); ?>"><?php echo wp_get_attachment_image( $post->ID, array(708, 950) ); ?></a>
				<p class="wp-caption-text"><?php if ( !empty($post->post_excerpt) ) the_excerpt(); // this is the "caption" ?></p>
                </div>
                <?php //the_content('Read the rest of this entry &raquo;'); ?>
				<div class="navigation">
					<div class="alignleft"><?php previous_image_link() ?></div>
					<div class="alignright"><?php next_image_link() ?></div>
				</div>
            <?php else :?>
                <?php the_content('Read the rest of this entry &raquo;'); ?>
            <?php endif;?>
                <div style="clear: both;"></div>
            
                <!-- PAGES LKINKS -->
                <div class="pages_navigation">
                    <?php wp_link_pages(array('before' => '<strong>Pages:</strong> ', 'after' => '', 'next_or_number' => 'number')); ?>
                </div>

            </div>
        </div>
        <?php include('post_meta.php')?>                
        <?php include('post_bbar.php')?>                
    </div>
</div>
<?php 
        if ( is_singular() ) {
            comments_template(); 
        }
endwhile; ?>


        <?include('navigation.php')?>
