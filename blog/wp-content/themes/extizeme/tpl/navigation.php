<?php 
$previous_posts_link = get_previous_posts_link('
<div class="prev_posts" style="text-align:right;float:right;padding:5px;padding-right:20px;width:250px;">
    Newer Entries
    <div class="x-tab-scroller-right x-unselectable" style="height: 22px; -moz-user-select: none;"></div>
</div>');
$next_posts_link = get_next_posts_link('
<div class="next_posts">
    <div class="x-tab-scroller-left x-unselectable" style="height: 22px; -moz-user-select: none;"></div>
    Older Entries
</div>');

if ( !empty($next_posts_link) || !empty($previous_posts_link) ) :  
?>
    <div class="posts_navigation x-panel" style="margin-top: -20px; width: auto;">
        <div class="x-tab-panel-header x-unselectable x-tab-scrolling x-tab-scrolling-top" style="-moz-user-select: none; width: auto;height:22px;border-top:0">
            <?php if ( !empty($next_posts_link) ) { echo $next_posts_link; } ?>
            <?php if ( !empty($previous_posts_link) ) { echo $previous_posts_link; } ?>
        </div>
    </div>
    <hr />
<?php 
endif;
?>
