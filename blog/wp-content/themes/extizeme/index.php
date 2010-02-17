<?php get_header(); ?>
<?php include('tpl/site_header.php'); ?>
<div id="content">
	<?php if (have_posts()) : ?>
        <?php if (is_archive() || is_search()) : ?>
            <? include('tpl/summary.php'); ?>
        <?php else : ?>
	        <?php include('tpl/details.php'); ?>
        <?php endif; ?>
    <?php else : ?>
        <?php include('tpl/notfound.php'); ?>
	<?php endif; ?>
</div>
<?php get_sidebar(); ?>
<?php include('tpl/site_footer.php'); ?>
<?php get_footer(); ?>
