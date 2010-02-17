        <div class="x-panel-bbar">
            <div class="x-toolbar x-small-editor">
                <table cellspacing="0"><tbody><tr><td>
                    <table cellspacing="0" cellpadding="0" border="0" class="x-btn-wrap x-btn" style="width: auto;"><tbody><tr><td class="x-btn-left"><i></i></td><td class="x-btn-center">
                        <em unselectable="on">
                            <?php the_tags('Tags: ', ', ', '<br />'); ?>
                        </em>
                    </td><td class="x-btn-right"><i></i></td></tr></tbody></table>
                    </td><td style="width: 100%;"><div class="ytb-spacer"></div></td><td>
                    <table cellspacing="0" cellpadding="0" border="0" class="x-btn-wrap x-btn" style="width: auto;"><tbody><tr><td class="x-btn-left"><i></i></td><td class="x-btn-center">
                        <em unselectable="on">
                            Posted in <?php the_category(', ') ?> | <?php edit_post_link('Edit', '', ' | '); ?>  <?php comments_popup_link('No Comments &#187;', '1 Comment &#187;', '% Comments &#187;'); ?>
                        </em>
                    </td><td class="x-btn-right"><i></i></td></tr></tbody></table>
                </td></tr></tbody></table>
            </div>
        </div>
