<?php
/**
 * Shipping class
 * Shipping addresses
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 28 March, 2008
 * @package shopp
 **/

class Shipping extends DatabaseObject {
	static $table = "shipping";
	
	function Shipping ($id=false,$key=false) {
		$this->init(self::$table);
		if ($id && $this->load($id,$key)) return true;
		else return false;
	}
	
	function exportcolumns () {
		$prefix = "s.";
		return array(
			$prefix.'address' => __('Shipping Street Address','Shopp'),
			$prefix.'xaddress' => __('Shipping Street Address 2','Shopp'),
			$prefix.'city' => __('Shipping City','Shopp'),
			$prefix.'state' => __('Shipping State/Province','Shopp'),
			$prefix.'country' => __('Shipping Country','Shopp'),
			$prefix.'postcode' => __('Shipping Postal Code','Shopp'),
			);
	}
	
	/**
	 * postarea()
	 * Determines the domestic area name from a 
	 * U.S. zip code or Canadian postal code */
	function postarea () {
		global $Shopp;
		$code = $this->postcode;
		$areas = $Shopp->Settings->get('areas');
		
		// Skip if there are no areas for this country
		if (!isset($areas[$this->country])) return false;

		// If no postcode is provided, return the first regional column
		if (empty($this->postcode)) return key($areas[$this->country]);
		
		// Lookup US area name
		if (preg_match("/\d{5}(\-\d{4})?/",$code)) {
			
			foreach ($areas['US'] as $name => $states) {
				foreach ($states as $id => $coderange) {
					for($i = 0; $i<count($coderange); $i+=2) {
						if ($code >= (int)$coderange[$i] && $code <= (int)$coderange[$i+1]) {
							$this->state = $id;
							return $name;
						}
					}
				}
			}
		}
		
		// Lookup Canadian area name
		if (preg_match("/\w\d\w\s*\d\w\d/",$code)) {
			
			foreach ($areas['CA'] as $name => $provinces) {
				foreach ($provinces as $id => $fsas) {
					if (in_array(substr($code,0,1),$fsas)) return $name;
				}
			}
			return $name;
			
		}
		
		return false;
	}

} // end Shipping class

?>