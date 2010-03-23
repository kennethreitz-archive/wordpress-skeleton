<?php
/**
 * Purchased class
 * Purchased line items for orders
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 28 March, 2008
 * @package shopp
 **/

class Purchased extends DatabaseObject {
	static $table = "purchased";

	function Purchased ($id=false,$key=false) {
		$this->init(self::$table);
		if ($this->load($id,$key)) return true;
		else return false;
	}
	
	function keygen() {
		$message = $this->name.$this->purchase.$this->product.$this->price.$this->download;
		$key = sha1($message);
		if (empty($key)) $key = md5($message);
		$this->dkey = $key;
		do_action_ref_array('shopp_download_keygen',array(&$this));
	}
	
	function exportcolumns () {
		$prefix = "p.";
		return array(
			$prefix.'id' => __('Line Item ID','Shopp'),
			$prefix.'name' => __('Product Name','Shopp'),
			$prefix.'optionlabel' => __('Product Variation Name','Shopp'),
			$prefix.'description' => __('Product Description','Shopp'),
			$prefix.'sku' => __('Product SKU','Shopp'),
			$prefix.'quantity' => __('Product Quantity Purchased','Shopp'),
			$prefix.'unitprice' => __('Product Unit Price','Shopp'),
			$prefix.'total' => __('Product Total Price','Shopp'),
			$prefix.'data' => __('Product Data','Shopp'),
			$prefix.'downloads' => __('Product Downloads','Shopp')
			);
	}

} // end Purchased class

?>