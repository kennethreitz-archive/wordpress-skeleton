<?php
/**
 * ShoppProductWidget class
 * A WordPress widget that provides a navigation menu of a Shopp category section (branch)
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 8 June, 2009
 * @package shopp
 **/

if (class_exists('WP_Widget')) {
	
class ShoppProductWidget extends WP_Widget {

    function ShoppProductWidget() {
        parent::WP_Widget(false, $name = 'Shopp Product', array('description' => __('Highlight specific store products','Shopp')));	
    }

    function widget($args, $options) {		
		global $Shopp;
		extract($args);

		$title = $before_title.$options['title'].$after_title;
		unset($options['title']);

		$content = $Shopp->Catalog->tag('sideproduct',$options);
		echo $before_widget.$title.$content.$after_widget;
    }

    function update($new_instance, $old_instance) {				
        return $new_instance;
    }

    function form($options) {				
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?></label>
		<input type="text" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" class="widefat" value="<?php echo $options['title']; ?>"></p>
		
		<p><select id="<?php echo $this->get_field_id('source'); ?>" name="<?php echo $this->get_field_name('source'); ?>" class="widefat"><option value="category"<?php echo $options['source'] == "category"?' selected="selected"':''; ?>>From a category</option><option value="product"<?php echo $options['source'] == "product"?' selected="selected"':''; ?>>By product</option></select></p>

		<?php 
			if (SHOPP_PERMALINKS) $label = __('Category Slug/ID','Shopp');
			else $label = __('Category ID','Shopp');
		 ?>
		<p id="<?php echo $this->get_field_id('category-fields'); ?>" class="hidden">
			<label for="<?php echo $this->get_field_id('category'); ?>"><?php echo $label; ?></label>
			<input type="text" name="<?php echo $this->get_field_name('category'); ?>" id="<?php echo $this->get_field_id('category'); ?>" class="widefat" value="<?php echo $options['category']; ?>">
			<br /><br />
			<select id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>">
				<?php $limits = array(1,2,3,4,5,6,7,8,9,10,15,20,25);
					echo menuoptions($limits,$options['limit']); ?>
			</select>
			<select id="<?php echo $this->get_field_id('order'); ?>" name="<?php echo $this->get_field_name('order'); ?>">
				<?php
					$sortoptions = array(
						"bestselling" => __('Bestselling','Shopp'),
						"highprice" => __('Highest Price','Shopp'),
						"lowprice" => __('Lowest Price','Shopp'),
						"newest" => __('Newest','Shopp'),
						"oldest" => __('Oldest','Shopp'),
						"random" => __('Random','Shopp')
					);
					echo menuoptions($sortoptions,$options['order'],true);
				?>
			</select>
			<label for="<?php echo $this->get_field_id('order'); ?>"><?php _e('products','Shopp'); ?></label>
		</p>
		
		<?php 
			if (SHOPP_PERMALINKS) $label = __('Product Slug/ID(s)','Shopp');
			else $label = __('Product ID(s)','Shopp');
		 ?>
		<p id="<?php echo $this->get_field_id('product-fields'); ?>" class="hidden">
			<label for="<?php echo $this->get_field_id('product'); ?>"><?php echo $label; ?></label>
			<input type="text" name="<?php echo $this->get_field_name('product'); ?>" id="<?php echo $this->get_field_id('product'); ?>" class="widefat" value="<?php echo $options['product']; ?>">
			<small><?php _e('Use commas to specify multiple products','Shopp')?></small></p>
		
		<script type="text/javascript">
		(function($) {
			$(window).ready(function () {
				var categoryui = $('#<?php echo $this->get_field_id("category-fields"); ?>');
				var productui = $('#<?php echo $this->get_field_id("product-fields"); ?>');
				$('#<?php echo $this->get_field_id("source"); ?>').change(function () {
					if ($(this).val() == "category") {
						productui.hide();
						categoryui.show();
					}
					if ($(this).val() == "product") {
						categoryui.hide();
						productui.show();
					}
				}).change();
			});
		})(jQuery)
		</script>
		<?php
    }

} // class ShoppProductWidget

register_widget('ShoppProductWidget');

}

class LegacyShoppProductWidget {

	function LegacyShoppProductWidget () {
		wp_register_sidebar_widget('shopp-product',__('Shopp Category Section','Shopp'),array(&$this,'widget'),'shopp categories');
		wp_register_widget_control('shopp-product',__('Shopp Category Section','Shopp'),array(&$this,'form'));
	}
	
	function widget ($args=null) {
		global $Shopp;
		extract($args);

		$options = array();
		$options = $Shopp->Settings->get('product_widget_options');

		$title = $before_title.$options['title'].$after_title;
		unset($options['title']);

		$content = $Shopp->Catalog->tag('feature-product',$options);
		echo $before_widget.$title.$content.$after_widget;
	}

	function form ($args=null) {
		global $Shopp;
		
		if (isset($_POST['product_widget_options'])) {
			$options = $_POST['shopp_product_options'];
			$Shopp->Settings->save('product_widget_options',$options);
		}

		$options = $Shopp->Settings->get('product_widget_options');
		
		echo '<p><label>Title<input name="shopp_product_options[title]" class="widefat" value="'.$options['title'].'"></label></p>';
		if (SHOPP_PERMALINKS):
		echo '<p><label>Product Slug<input name="shopp_product_options[slug]" class="widefat" value="'.$options['slug'].'"></label></p>';
		else:
		echo '<p><label>Product ID<input name="shopp_product_options[id]" class="widefat" value="'.$options['id'].'"></label></p>';
		endif;
		echo '<div><input type="hidden" name="product_widget_options" value="1" /></div>';
	}
	
}
