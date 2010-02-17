<div id="wrap">

<div id="header" class="x-panel" style="margin-top: 5px;margin-bottom:20px;">
    <div class="x-panel-tl">
        <div class="x-panel-tr">
            <div class="x-panel-tc">
                <div class="x-panel-header x-unselectable" style="-moz-user-select: none;">
                    <div class="feed-icon">
                        <a href="<?php bloginfo('rss2_url'); ?>""><img src="<? bloginfo('template_url') ?>/images/feed-icon2.png" width="47" height="35" alt="" border="0" /></a>
                    </div>
                    <span class="x-panel-header-text">
	                    <h1><a class="blog-title" href="<?php echo get_option('home'); ?>/"><?php bloginfo('name'); ?></a></h1>
                        <div class="description">
                            <?php bloginfo('description'); ?>
                        </div>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="x-panel-bwrap">
        <div class="x-panel-ml">
            <div class="x-panel-mr">
                <div class="x-panel-mc">
                    <div class="x-panel-body">
                        <ul class="ext-page-nav">
                            <li class="home">
                                <a href="<?php echo get_option('home'); ?>/"><?php echo (isset($options['ext_home_name']) ? $options['ext_home_name'] : 'Home'); ?></a>
                            </li>
                            <?php wp_list_pages('title_li=&depth=1&' ); ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="x-panel-bl x-panel-nofooter">
            <div class="x-panel-br">
                <div class="x-panel-bc"></div>
            </div>
        </div>
    </div>
</div>

<hr />

<!-- List of pages for Ext {{{ -->
<div id="wp-tb-items" style="display:none">
<ul><?php wp_list_pages('title_li=&' ); ?></ul>
</div>
<!-- }}}  -->