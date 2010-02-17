
	<div id="sidebar">
		<ul>
<?php 
$options  = get_option('ext_options');
if ($options['ext_mode'] != 'full') {
?>
			<li>
                <div style="margin-bottom: 20px; width: 200px;" class="x-panel widget" id="extizeme-switch">
                    <div class="x-panel-tl">
                        <div class="x-panel-tr">
                            <div class="x-panel-tc">
                                <div style="-moz-user-select: none;" class="x-panel-header x-unselectable">
                                    <span class="x-panel-header-text widgettitle">ExtizeMe</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="x-panel-bwrap">
                        <div class="x-panel-ml">
                            <div class="x-panel-mr">
                                <div class="x-panel-mc">
                                    <div style="width: 188px;" class="x-panel-body widgetbody">
                                        <a href="?ext"><div id="extizeme-switch-btn">
                                            Switch to <font color="#1860a8">Ext</font><font color="#464646">ize</font><font color="#789030">Me</font>
                                        </div></a>
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
            </li>
<?php
}
?>
            <?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('Left Sidebar') ) : ?>
			<li>
				<?php get_search_form(); ?>
			</li>
			<?php endif; ?>
		</ul>
	</div>

