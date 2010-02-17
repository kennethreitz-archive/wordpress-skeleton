        <div class="x-panel" style="margin-bottom: 20px;;">
            <div class="x-panel-tl">
                <div class="x-panel-tr">
                    <div class="x-panel-tc">
                        <div class="x-panel-header x-unselectable" style="-moz-user-select: none;">
                            <span class="x-panel-header-text" id="comments">
                         	  <a class="post-title"  href="<?php echo $_SERVER['REQUEST_URI']; ?>">
                              <?php if (isset($_GET['s']) && !empty($_GET['s'])) { ?>
                        		Search Resultst for &#8216;<?php echo $_GET['s'];?>&#8217;
                         	  <?php } else {?>
                                Not Found 
                         	  <?php } ?>
                              </a>
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
                                <div class="entry">
                                    <p>Sorry, but you are looking for something that isn't here.</p>
                                </div>
                            </div>
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
