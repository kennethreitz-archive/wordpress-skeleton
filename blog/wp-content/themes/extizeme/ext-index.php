<?php get_header(); ?>

<?php
$options = get_option('ext_options');
$extPath = get_bloginfo('template_url').'/ext-2.2.1/';
$jsPath  = get_bloginfo('template_url').'/js/';
$imgPath  = get_bloginfo('template_url').'/images/';
?>
<script type="text/javascript">
var myEl = document.createElement('div');
myEl.innerHTML = '<div id="loading" style="color:#1A5C9A;font-family:Georgia, serif;font-weight:bold;font-size:40px;margin:40px;"><h1><a>Patience is gold...</a></h1></div>';
document.body.appendChild(myEl); 
</script>
<?php if (!empty($options['ext_cdn']) && $options['ext_cdn'] == 'yes') {?>
<script src="http://extjs.cachefly.net/builds/ext-cdn-771.js"></script>
<?php } else { ?>
<script src="<?php echo $extPath?>ext.js"></script>
<?php }  ?>
<script src="<?php echo $jsPath;?>ux/ext.util.md5.js"></script>
<script src="<?php echo $jsPath;?>ux/TabCloseMenu.js"></script>
<script src="<?php echo $jsPath;?>ux/miframe-min.js"></script>
<script src="<?php echo $jsPath;?>ux/iframe-proxy.js"></script>
<script src="<?php echo $jsPath;?>overrides.js"></script>
<script src="<?php echo $jsPath;?>wp.js"></script>
<script src="<?php echo $jsPath;?>module-livesearch.js"></script>
<script src="<?php echo $jsPath;?>module-commentform.js"></script>
<script src="<?php echo $jsPath;?>module-galery.js"></script>
<script src="<?php echo $jsPath;?>module-page.js"></script>
<script src="<?php echo $jsPath;?>module-widget.js"></script>
<script src="<?php echo $jsPath;?>module-sidebar.js"></script>

<script src="<?php echo $jsPath;?>viewport.js"></script>
<script src="<?php echo $jsPath;?>app.js"></script>

<script type="text/javascript">


WP.config = {
    sidebar: '<?php echo (!empty($options['ext_sidebar_style']) ? $options['ext_sidebar_style'] : 'accordion'); ?>',
    linkTarget: null,
    entriesRSSBtn: {
        url: '<?php echo get_feed_link('rss2'); ?>/',
        name: 'Entries RSS'
    },
    homeBtn: {
        url :'<?php echo get_option('home'); ?>/',
        name:'<?php echo (!empty($options['ext_home_name']) ? $options['ext_home_name'] : 'Home'); ?>'
    },
    viewport: {
        west: 0,
        east: 270
    }, 
    URL:document.location.href.replace('?ext', '') + (document.location.href.replace('?ext', '').indexOf('?') == -1 ? '?noext' : '&noext')
};
Ext.onReady(WP.App.init, WP.App);

Ext.onReady(function() {
    Ext.getBody().show();
    Ext.get('loading').remove();

});
</script>

<noscript>
<?php include_once('tpl/site_header.php'); ?>
<div id="content">
	<?php if (have_posts()) : ?>
    <?php include_once('tpl/details.php'); ?>
	<?php else : ?>
    <?php include_once('tpl/notfound.php'); ?>
	<?php endif; ?>
</div>
<?php get_sidebar(); ?>
<?php include_once('tpl/site_footer.php'); ?>
</div>
</noscript>

</body>
</html>

