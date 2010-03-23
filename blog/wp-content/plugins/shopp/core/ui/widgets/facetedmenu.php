<?php
/**
 * ShoppFacetedMenuWidget class
 * A WordPress widget for showing a drilldown search menu for category products
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 8 June, 2009
 * @package shopp
 **/

if (class_exists('WP_Widget')) {
	
class ShoppFacetedMenuWidget extends WP_Widget {

    function ShoppFacetedMenuWidget() {
        parent::WP_Widget(false, $name = 'Shopp Faceted Menu', array('description' => __('Category products drill-down search menu','Shopp')));
    }

    function widget($args, $options) {		
		global $Shopp;
		if (!empty($args)) extract($args);

		if (empty($options['title'])) $options['title'] = __('Product Filters','Shopp');
		$title = $before_title.$options['title'].$after_title;

		if (!empty($Shopp->Category->id) && $Shopp->Category->facetedmenus == "on") {
			$menu = $Shopp->Category->tag('faceted-menu',$options);
			echo $before_widget.$title.$menu.$after_widget;			
		}
    }

    function update($new_instance, $old_instance) {				
        return $new_instance;
    }

    function form($options) {				
		?>
		<p><?php _e('There are no options for this widget.'); ?></p>
		<?php
    }

} // class ShoppFacetedMenuWidget

register_widget('ShoppFacetedMenuWidget');

}

class LegacyShoppFacetedMenuWidget {

	function LegacyShoppFacetedMenuWidget () {
		wp_register_sidebar_widget('shopp-facetedmenu',__('Shopp Faceted Menu','Shopp'),array(&$this,'widget'),'shopp facetedmenu');
		wp_register_widget_control('shopp-facetedmenu',__('Shopp Faceted Menu','Shopp'),array(&$this,'form'));
	}

	function widget ($args=null) {
		global $Shopp;
		if (!empty($args)) extract($args);

		$options = $Shopp->Settings->get('facetedmenu_widget_options');

		if (empty($options['title'])) $options['title'] = __('Product Filters','Shopp');
		$options['title'] = $before_title.$options['title'].$after_title;
		global $wp_registered_widgets;

		if (!empty($Shopp->Category->id) && $Shopp->Category->facetedmenus == "on") {
			$menu = $Shopp->Category->tag('faceted-menu',$options);
			echo $before_widget.$options['title'].$menu.$after_widget;			
		}
	}

	function form ($args=null) {
		global $Shopp;

		if (isset($_POST['shopp_facetedmenu_widget_options'])) {
			$options = $_POST['facetedmenu_widget_options'];
			$Shopp->Settings->save('facetedmenu_widget_options',$options);
		}

		$options = $Shopp->Settings->get('facetedmenu_widget_options');

	}

}
