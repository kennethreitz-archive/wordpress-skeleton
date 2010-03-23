<?php
/**
 * ShoppTagCloudWidget class
 * A WordPress widget that shows a cloud of the most popular product tags
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 8 June, 2009
 * @package shopp
 **/

if (class_exists('WP_Widget')) {
	
class ShoppTagCloudWidget extends WP_Widget {

    function ShoppTagCloudWidget() {
        parent::WP_Widget(false, $name = 'Shopp Tag Cloud', array('description' => __('Popular product tags in a cloud format','Shopp')));
    }

    function widget($args, $options) {		
		global $Shopp;
		if (!empty($args)) extract($args);

		if (empty($options['title'])) $options['title'] = "Product Tags";
		$title = $before_title.$options['title'].$after_title;

		$tagcloud = $Shopp->Catalog->tag('tagcloud',$options);
		echo $before_widget.$title.$tagcloud.$after_widget;
    }

    function update($new_instance, $old_instance) {				
        return $new_instance;
    }

    function form($options) {				
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?></label>
		<input type="text" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" class="widefat" value="<?php echo $options['title']; ?>"></p>
		<?php
    }

} // class ShoppTagCloudWidget

register_widget('ShoppTagCloudWidget');

}

class LegacyShoppTagCloudWidget {

	function LegacyShoppTagCloudWidget () {
		wp_register_sidebar_widget('shopp-tagcloud',__('Shopp Tag Cloud','Shopp'),array(&$this,'widget'),'shopp tagcloud');
		wp_register_widget_control('shopp-tagcloud',__('Shopp Tag Cloud','Shopp'),array(&$this,'form'));
	}

	function widget ($args=null) {
		global $Shopp;
		if (!empty($args)) extract($args);

		$options = $Shopp->Settings->get('tagcloud_widget_options');

		if (empty($options['title'])) $options['title'] = "Product Tags";
		$options['title'] = $before_title.$options['title'].$after_title;

		$tagcloud = $Shopp->Catalog->tag('tagcloud',$options);
		echo $before_widget.$options['title'].$tagcloud.$after_widget;

	}	

	function form ($args=null) {
		global $Shopp;

		if (isset($_POST['shopp_tagcloud_widget_options'])) {
			$options = $_POST['tagcloud_widget_options'];
			$Shopp->Settings->save('tagcloud_widget_options',$options);
		}

		$options = $Shopp->Settings->get('tagcloud_widget_options');

		echo '<p><label>Title<input name="tagcloud_widget_options[title]" class="widefat" value="'.$options['title'].'"></label></p>';
		echo '<div><input type="hidden" name="shopp_tagcloud_widget_options" value="1" /></div>';
	}

}
