<div class="archives x-panel" style="margin-bottom: 20px; width: auto;">
    <?include('post_title.php')?>                
    <div class="x-panel-bwrap">
        <div class="x-panel-body" style="width: auto;">

<div class="x-grid3" hidefocus="true" style="width: width: auto;">
<div class="x-grid3-viewport">

<div class="x-grid3-header">
<div class="x-grid3-header-inner" style="width: auto;">
<table cellspacing="0" cellpadding="0" border="0" style="width: 100%;height: 22px;">
<thead>
<tr class="x-grid3-hd-row">
<td style="width: 45%" class="x-grid3-hd x-grid3-cell x-grid3-td-topic x-grid3-cell-first">
<div style="" unselectable="on" class="x-grid3-hd-inner x-grid3-hd-topic">
Title
</div>
</td>
<td style="width: 20%;" class="x-grid3-hd x-grid3-cell x-grid3-td-1">
<div style="" unselectable="on" class="x-grid3-hd-inner x-grid3-hd-1">
Date
</div>
</td>
<td style="width: 20%;" class="x-grid3-hd x-grid3-cell x-grid3-td-2">
<div style="padding-right: 16px;" unselectable="on" class="x-grid3-hd-inner x-grid3-hd-2">
Author
</div>
</td>
<td style="width: 15%;border-right-width:0px" class="x-grid3-hd x-grid3-cell x-grid3-td-last x-grid3-cell-last">
<div unselectable="on" class="x-grid3-hd-inner x-grid3-hd-last">
Comments
</div>
</td>
</tr>
</thead>
</table>
</div>
<div class="x-clear"></div>
</div>

<div class="x-grid3-body">
		<?php while (have_posts()) : the_post(); ?>
<div style="width: auto;border-width: 1px 0 1px 0;" class="x-grid3-row x-grid3-row-expanded x-grid3-row-first">
<table cellspacing="0" cellpadding="0" border="0" style="width: 100%;" class="x-grid3-row-table">
<tbody>
<tr>
<td tabindex="0" style="width: 45%;" class="x-grid3-col x-grid3-cell">
<div unselectable="on" class="x-grid3-cell-inner">
<b><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></b>
</div>
</td>
<td tabindex="0" style="width: 20%;" class="x-grid3-col x-grid3-cell">
<div unselectable="on" class="x-grid3-cell-inner"><?php the_time('F jS, Y') ?></div>
</td>
<td tabindex="0" style="width: 20%;" class="x-grid3-col x-grid3-cell">
<div unselectable="on" class="x-grid3-cell-inner"><?php the_author() ?></div>
</td>
<td tabindex="0" style="width: 15%;" class="x-grid3-col x-grid3-cell">
<div unselectable="on" class="x-grid3-cell-inner"><?php comments_popup_link('No Comments &#187;', '1 Comment &#187;', '% Comments &#187;'); ?></div>
</td>
</tr>
<tr style="" class="x-grid3-row-body-tr">
<td hidefocus="on" tabindex="0" class="x-grid3-body-cell" colspan="4">
<div class="x-grid3-row-body excerpt">
    <p><?php the_excerpt(); ?></p>
</div>
</td>
</tr>
</tbody>
</table>
</div>
        <?php endwhile; ?>
</div>
</div>
</div>

        </div>
    </div>
</div>
<?include('navigation.php')?>
