<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>

<head profile="http://gmpg.org/xfn/11">
<!-- <meta http-equiv="Page-Enter" content="RevealTrans(Transition=12,Duration=0)" /> -->
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />

<title><?php wp_title('&laquo;', true, 'right'); ?> <?php bloginfo('name'); ?></title>

<?php 
$options  = get_option('ext_options');
if ($options['ext_mode']=== 'full') {
    $isExtized = true;
} else {
    if (isset($_GET['ext'])) {
        $isExtized = true;
    } else {
        $isExtized = false;
    }
}

if (isset($_REQUEST['noext'])) {
    siteCssOptions('ext_css');
} else {
    siteCssOptions('ext_css_outer');
}
?>

<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>" />
<link rel="alternate" type="application/atom+xml" title="<?php bloginfo('name'); ?> Atom Feed" href="<?php bloginfo('atom_url'); ?>" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
<?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' ); ?>
<?php wp_head(); ?>
<script>
var dA = document.domain.split('.');document.domain = [dA[dA.length-2],dA[dA.length-1]].join('.');
</script>
</head>
<body>
