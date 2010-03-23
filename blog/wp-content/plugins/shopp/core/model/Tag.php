<?php
/**
 * Tag class
 * Catalog product tag table
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 9 October, 2008
 * @package shopp
 **/

class Tag extends DatabaseObject {
	static $table = "tag";
	
	function Tag ($id=false,$key=false) {
		$this->init(self::$table);
		if ($this->load($id,$key)) return true;
		else return false;
	}

} // end Tag class

?>