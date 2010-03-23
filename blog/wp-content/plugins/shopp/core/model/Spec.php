<?php
/**
 * Spec class
 * Catalog product spec table
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 26 July, 2008
 * @package shopp
 **/

class Spec extends DatabaseObject {
	static $table = "spec";
	
	function Spec ($id=false) {
		$this->init(self::$table);
		if ($this->load($id)) return true;
		else return false;
	}

} // end Spec class

?>