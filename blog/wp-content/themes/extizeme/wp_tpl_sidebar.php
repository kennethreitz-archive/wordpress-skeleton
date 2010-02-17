<?php 
/* 
Template Name: Generic Template With Sidebar 
*/
?>
<?php get_header(); ?>
<?php include('tpl/site_header.php'); ?>

<div id="content">

    <?php while (have_posts()) : the_post(); ?>

    <div class="x-panel" style="margin-bottom: 20px; width: 748px;">
        <?include('tpl/post_title.php')?>
        <div class="x-panel-bwrap">
            <?include('tpl/post_tbar.php')?>
            <div class="x-panel-body" style="width: auto;">
                <div class="entry">
                <?php the_content(); ?>
                <div style="clear: both;"></div>
                </div>
            </div>
            <?include('tpl/post_bbar.php')?>
        </div>
    </div>

	<?php endwhile; ?>

</div>

<?php get_sidebar(); ?>

<?php include('tpl/site_footer.php'); ?>
<?php get_footer(); ?>
