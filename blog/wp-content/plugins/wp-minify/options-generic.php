<script type="text/javascript">
function confirm_reset() {
  var answer = confirm("<?php _e('All of options will return to default settings.  Are you sure you want to reset all settings?'); ?>");
  if(answer)
    return true;
  else
    return false;
}

jQuery(document).ready(function($){
  $("#wpm_options_toggle_advanced").click(function(e){
    e.preventDefault();
    state = $(this).attr("state");
    if(state == "visible"){
      $(".wpm_advanced").slideUp();
      $("#wpm_options_reset").fadeOut();
      $(this).attr("state", "hidden");
      $(this).attr("value", "<?php echo __('Show Advanced Options', $this->name); ?>" + " " + String.fromCharCode(187));
      $.ajax({
        type    : "POST",
        url     : "admin-ajax.php",
        data    : { action : "wpm", _ajax_nonce: "<?php echo wp_create_nonce($this->name); ?>", wpm_action : "hide_advanced" },
        success : function(resp){
          // do nothing visually
        },
        error   : function(resp){
          alert("Error:" + resp);
        }
      });
    }
    else{
      $(".wpm_advanced").slideDown();
      $("#wpm_options_reset").fadeIn();
      $(this).attr("state", "visible");
      $(this).attr("value", "<?php echo __('Hide Advanced Options', $this->name); ?>" + " " + String.fromCharCode(187));
      $.ajax({
        type    : "POST",
        url     : "admin-ajax.php",
        data    : { action : "wpm", _ajax_nonce: "<?php echo wp_create_nonce($this->name); ?>", wpm_action : "show_advanced" },
        success : function(resp){
          // do nothing visually
        },
        error   : function(resp){
          alert("Error:" + resp);
        }
      });
    }
  });
});
</script>
<form method="post"><fieldset>
<?php
    // take care of advanced options
    if ($wpm_options['show_advanced']) {
      $advanced_style = '';
      $advanced_toggle_text = __('Hide Advanced Options', $this->name);
      $advanced_toggle_state = 'visible';
    }
    else {
      $advanced_style = 'style="display:none"';
      $advanced_toggle_text = __('Show Advanced Options', $this->name);
      $advanced_toggle_state = 'hidden';
    }

    printf('
      <h2>%s</h2>
      <p><label>%s &nbsp; <input name="wpm_options_update[show_link]" value="on" type="radio" '.checked(true, $wpm_options['show_link'], false).'/></label></p>
      <p><label>%s <a href="http://omninoggin.com/donate">%s</a> &nbsp; <input name="wpm_options_update[show_link]" value="off" type="radio" '.checked(false, $wpm_options['show_link'], false).'/></label></p>
      ',
      __('Support this plugin!', $this->name),
      __('Display "Page optimized by WP Minify" link in the footer', $this->name),
      __('Do not display "Page optimized by WP Minify" link.', $this->name),
      __('I will donate and/or write about this plugin', $this->name)
    );

    printf('
      <h2>%s</h2>
      <p><label>%s &nbsp; <input name="wpm_options_update[enable_js]" type="checkbox" '.checked(true, $wpm_options['enable_js'], false).'/></label></p>
      <p><label>%s &nbsp; <input name="wpm_options_update[enable_css]" type="checkbox" '.checked(true, $wpm_options['enable_css'], false).'/></label></p>
      ',
      __('General Configuration', $this->name),
      __('Enable JavaScript Minification', $this->name),
      __('Enable CSS Minification', $this->name)
    );

    printf('
      <p class="wpm_advanced" '.$advanced_style.'><label>%s &nbsp; <input name="wpm_options_update[cache_external]" type="checkbox" '.checked(true, $wpm_options['cache_external'], false).'/> &nbsp; (%s)</label></p>
      <p class="wpm_advanced" '.$advanced_style.'><label>%s &nbsp; <input name="wpm_options_update[js_in_footer]" type="checkbox" '.checked(true, $wpm_options['js_in_footer'], false).'/> &nbsp; (%s)</label></p>
      ',
      __('Enable Minification on External Files', $this->name),
      __('Not recommended unless you want to excluding a bunch of external .js/.css files', $this->name),
      __('Place Minified JavaScript in footer', $this->name),
      __('Not recommended if you have embedded JS code referring to code that\'s been minified', $this->name)
    );

    printf('
      <p class="wpm_advanced" '.$advanced_style.'><label>%s &nbsp; <input name="wpm_options_update[debug]" type="checkbox" '.checked(true, $wpm_options['debug'], false).'/> &nbsp; (%s)</label></p>
      <p class="wpm_advanced" '.$advanced_style.'><label>%s<input name="wpm_options_update[cache_interval]" type="text" size="4" value="'.attribute_escape($wpm_options['cache_interval']).'"/>%s <span class="submit"><input type="submit" name="wpm_options_clear_cache_submit" value="%s"/></span></p></label>
      ',
      __('Combine files but do not minify', $this->name),
      __('Debug mode', $this->name),
      __('Cache expires after every', $this->name),
      __('seconds', $this->name),
      __('Manually Clear Cache', $this->name)
    );


    printf('
      <p class="wpm_advanced" '.$advanced_style.'><label>%s<br/><textarea name="wpm_options_update[js_include]" style="width:600px" rows="5">'.attribute_escape(implode(chr(10), $wpm_options['js_include'])).'</textarea></label></p>
      <p><label>%s<br/><textarea name="wpm_options_update[js_exclude]" style="width:600px" rows="5">'.attribute_escape(implode(chr(10), $wpm_options['js_exclude'])).'</textarea></label></p>
      <p class="wpm_advanced" '.$advanced_style.'><label>%s<br/><textarea name="wpm_options_update[css_include]" style="width:600px" rows="5">'.attribute_escape(implode(chr(10), $wpm_options['css_include'])).'</textarea></label></p>
      <p><label>%s<br/><textarea name="wpm_options_update[css_exclude]" style="width:600px" rows="5">'.attribute_escape(implode(chr(10), $wpm_options['css_exclude'])).'</textarea></label></p>
      <p class="wpm_advanced" '.$advanced_style.'><label>%s<br/><input name="wpm_options_update[extra_minify_options]" type="text" size="100" value="'.attribute_escape($wpm_options['extra_minify_options']).'"/><br/><em>%s</em></label></p>
      ',
      __('Additional Javascript files to minify (line delimited)', $this->name),
      __('Javascript files to exclude from minify (line delimited)', $this->name),
      __('Additional CSS files to minify (line delimited)', $this->name),
      __('CSS files to exclude from minify (line delimited)', $this->name),
      __('Extra arguments to pass to minify engine. This value will get append to calls to URL "wp-minify/min/?f=file1.js,file2.js,...,fileN.js".', $this->name),
      __('e.g. You can specify this value to be b=somepath to specify the base path for all files passed into Minify.', $this->name)
    );

    if ( function_exists( 'wp_nonce_field' ) && wp_nonce_field( $this->name ) ) {
      printf('
        <p class="submit">
          <input type="submit" name="wpm_options_update_submit" value="%s &#187;" />
          <input type="submit" name="wpm_options_reset_submit" id="wpm_options_reset" value="%s &#187;" onclick="return confirm_reset()" '.$advanced_style.'/>
          <input type="button" id="wpm_options_toggle_advanced" state="'.$advanced_toggle_state.'" value="'.$advanced_toggle_text.' &#187;"/>
        </p>
        ',
        __('Update Options', $this->name),
        __('Reset ALL Options', $this->name)
      );
    }
?>
</fieldset></form>
