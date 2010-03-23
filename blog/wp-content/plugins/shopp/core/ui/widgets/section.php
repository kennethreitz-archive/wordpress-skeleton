<?php
/**
 * ShoppCategorySectionWidget class
 * A WordPress widget that provides a navigation menu of a Shopp category section (branch)
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 8 June, 2009
 * @package shopp
 **/

if (class_exists('WP_Widget')) {
	
class ShoppCategorySectionWidget extends WP_Widget {

    function ShoppCategorySectionWidget() {
        parent::WP_Widget(false, $name = 'Shopp Category Section', array('description' => __('A list or dropdown of store categories')));	
    }

    function widget($args, $options) {		
		global $Shopp;
		extract($args);

		$title = $before_title.$options['title'].$after_title;
		unset($options['title']);
		if (empty($Shopp->Category->id)) return false;
		$menu = $Shopp->Category->tag('section-list',$options);
		echo $before_widget.$title.$menu.$after_widget;
    }

    function update($new_instance, $old_instance) {				
        return $new_instance;
    }

    function form($options) {				
		global $Shopp;
		
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?></label>
		<input type="text" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" class="widefat" value="<?php echo $options['title']; ?>"></p>
		
		<p>
		<input type="hidden" name="<?php echo $this->get_field_name('dropdown'); ?>" value="off" /><input type="checkbox" id="<?php echo $this->get_field_id('dropdown'); ?>" name="<?php echo $this->get_field_name('dropdown'); ?>" value="on"<?php echo $options['dropdown'] == "on"?' checked="checked"':''; ?> /><label for="<?php echo $this->get_field_id('dropdown'); ?>"> <?php _e('Show as dropdown','Shopp'); ?></label><br />
		<input type="hidden" name="<?php echo $this->get_field_name('products'); ?>" value="off" /><input type="checkbox" id="<?php echo $this->get_field_id('products'); ?>" name="<?php echo $this->get_field_name('products'); ?>" value="on"<?php echo $options['products'] == "on"?' checked="checked"':''; ?> /><label for="<?php echo $this->get_field_id('products'); ?>"> <?php _e('Show product counts','Shopp'); ?></label><br />
		<input type="hidden" name="<?php echo $this->get_field_name('hierarchy'); ?>" value="off" /><input type="checkbox" id="<?php echo $this->get_field_id('hierarchy'); ?>" name="<?php echo $this->get_field_name('hierarchy'); ?>" value="on"<?php echo $options['hierarchy'] == "on"?' checked="checked"':''; ?> /><label for="<?php echo $this->get_field_id('hierarchy'); ?>"> <?php _e('Show hierarchy','Shopp'); ?></label><br />
		</p>
		<?php
    }

} // class ShoppCategorySectionWidget

register_widget('ShoppCategorySectionWidget');

}

class LegacyShoppCategorySectionWidget {

	function LegacyShoppCategorySectionWidget () {
		wp_register_sidebar_widget('shopp-section',__('Shopp Category Section','Shopp'),array(&$this,'widget'),'shopp categories');
		wp_register_widget_control('shopp-section',__('Shopp Category Section','Shopp'),array(&$this,'form'));
	}
	
	function widget ($args=null) {
		global $Shopp;
		extract($args);

		$options = array();
		$options = $Shopp->Settings->get('section_widget_options');

		$title = $before_title.$options['title'].$after_title;
		unset($options['title']);
		
		if (!isset($Shopp->Category->parent) || $Shopp->Category->parent == 0) return false;
		$menu = $Shopp->Category->tag('section-list',$options);
		echo $before_widget.$title.$menu.$after_widget;		
	}

	function form ($args=null) {
		global $Shopp;
		
		if (isset($_POST['section_widget_options'])) {
			$options = $_POST['shopp_section_options'];
			$Shopp->Settings->save('section_widget_options',$options);
		}

		$options = $Shopp->Settings->get('section_widget_options');
		
		echo '<p><label>Title<input name="shopp_section_options[title]" class="widefat" value="'.$options['title'].'"></label></p>';
		echo '<p>';
		echo '<label><input type="hidden" name="shopp_section_options[dropdown]" value="off" /><input type="checkbox" name="shopp_section_options[dropdown]" value="on"'.(($options['dropdown'] == "on")?' checked="checked"':'').' /> Show as dropdown</label><br />';
		echo '<label><input type="hidden" name="shopp_section_options[products]" value="off" /><input type="checkbox" name="shopp_section_options[products]" value="on"'.(($options['products'] == "on")?' checked="checked"':'').' /> Show product counts</label><br />';
		echo '<label><input type="hidden" name="shopp_section_options[hierarchy]" value="off" /><input type="checkbox" name="shopp_section_options[hierarchy]" value="on"'.(($options['hierarchy'] == "on")?' checked="checked"':'').' /> Show hierarchy</label><br />';
		echo '</p>';
		echo '<p><label for="pages-sortby">Smart Categories:<select name="shopp_section_options[showsmart]" class="widefat"><option value="false">Hide</option><option value="before"'.(($options['showsmart'] == "before")?' selected="selected"':'').'>Include before custom categories</option><option value="after"'.(($options['showsmart'] == "after")?' selected="selected"':'').'>Include after custom categories</option></select></label></p>';
		echo '<div><input type="hidden" name="section_widget_options" value="1" /></div>';
	}
	
}
