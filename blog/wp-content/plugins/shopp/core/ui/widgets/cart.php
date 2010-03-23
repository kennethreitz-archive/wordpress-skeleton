<?php
/**
 * ShoppCartWidget class
 * A WordPress widget for showing a drilldown search menu for category products
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 8 June, 2009
 * @package shopp
 **/

if (class_exists('WP_Widget')) {
	
class ShoppCartWidget extends WP_Widget {

    function ShoppCartWidget() {
        parent::WP_Widget(false, $name = 'Shopp Cart', array('description' => __('The customer\'s shopping cart','Shopp')));
    }

    function widget($args, $options) {		
		global $Shopp;
		if (!empty($args)) extract($args);

		if (empty($options['title'])) $options['title'] = "Your Cart";
		$title = $before_title.$options['title'].$after_title;

		$sidecart = $Shopp->Cart->tag('sidecart',$options);
		echo $before_widget.$title.$sidecart.$after_widget;
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

} // class ShoppCartWidget

register_widget('ShoppCartWidget');

}

class LegacyShoppCartWidget {

	function LegacyShoppCartWidget () {
		wp_register_sidebar_widget('shopp-cart',__('Shopp Cart','Shopp'),array(&$this,'widget'),'shopp cart');
		wp_register_widget_control('shopp-cart',__('Shopp Cart','Shopp'),array(&$this,'form'));
	}

	function widget ($args=null) {
		global $Shopp;
		if (!empty($args)) extract($args);

		$options = $Shopp->Settings->get('cart_widget_options');

		if (empty($options['title'])) $options['title'] = "Your Cart";
		$options['title'] = $before_title.$options['title'].$after_title;

		$sidecart = $Shopp->Cart->tag('sidecart',$options);
		echo $before_widget.$options['title'].$sidecart.$after_widget;
	}

	function form ($args=null) {
		global $Shopp;

		if (isset($_POST['shopp_cart_widget_options'])) {
			$options = $_POST['shopp_cart_options'];
			$Shopp->Settings->save('cart_widget_options',$options);
		}

		$options = $Shopp->Settings->get('cart_widget_options');

		echo '<p><label>Title<input name="shopp_cart_options[title]" class="widefat" value="'.$options['title'].'"></label></p>';
		echo '<div><input type="hidden" name="shopp_cart_widget_options" value="1" /></div>';
	}

}
