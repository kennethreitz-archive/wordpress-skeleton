<?php
/**
 * Setting class
 * Shopp settings
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 28 March, 2008
 * @package shopp
 **/

class Settings extends DatabaseObject {
	static $table = "setting";
	var $registry = array();
	var $unavailable = false;
	var $_table = "";
	
	function Settings () {
		$this->_table = $this->tablename(self::$table);
		if (!$this->load()) {
			if (!$this->init('setting')) {
				$this->unavailable = true;
				return false;
			}
		}
	}
	
	/**
	 * Load all settings from the database */
	function load ($name="") {
		$db = DB::get();
		if (!empty($name)) $results = $db->query("SELECT name,value FROM $this->_table WHERE name='$name'",AS_ARRAY,false);
		else $results = $db->query("SELECT name,value FROM $this->_table WHERE autoload='on'",AS_ARRAY,false);
		
		if (!is_array($results) || sizeof($results) == 0) return false;
		while(list($key,$entry) = each($results)) $settings[$entry->name] = $this->restore($entry->value);

		if (!empty($settings)) $this->registry = array_merge($this->registry,$settings);
		return true;
	}
	
	/**
	 * Add a new setting to the registry and store it in the database */
	function add ($name, $value,$autoload = true) {
		$db = DB::get();
		$Setting = $this->setting();
		$Setting->name = $name;
		$Setting->value = $db->clean($value);
		$Setting->autoload = ($autoload)?'on':'off';

		$data = $db->prepare($Setting);
		$dataset = DatabaseObject::dataset($data);
		if ($db->query("INSERT $this->_table SET $dataset"))
		 	$this->registry[$name] = $this->restore($db->clean($value));
		else return false;
		return true;
	}

	/**
	 * Updates the setting in the registry and the database */
	function update ($name,$value) {
		$db = DB::get();

		if ($this->get($name) == $value) return true;

		$Setting = $this->setting();
		$Setting->name = $name;
		$Setting->value = $db->clean($value);
		unset($Setting->autoload);
		$data = $db->prepare($Setting);				// Prepare the data for db entry
		$dataset = DatabaseObject::dataset($data);	// Format the data in SQL
		
		if ($db->query("UPDATE $this->_table SET $dataset WHERE name='$Setting->name'")) 
			$this->registry[$name] = $this->restore($value); // Update the value in the registry
		else return false;
		return true;
	}
	
	function save ($name,$value,$autoload=true) {
		// Update or Insert as needed
		if ($this->get($name) === false) $this->add($name,$value,$autoload);
		else $this->update($name,$value);
	}
	
	/**
	 * Remove a setting from the registry and the database */
	function delete ($name) {
		$db = DB::get();
		unset($this->registry[$name]);
		if (!$db->query("DELETE FROM $this->_table WHERE name='$name'")) return false;
		return true;
	}	
	
	/**
	 * Get a specific setting from the registry */
	function get ($name) {
		global $Shopp;

		$value = false;
		if (isset($this->registry[$name])) {
			return $this->registry[$name];
		} elseif ($this->load($name)) {			
			$value = $this->registry[$name];
		}
		
		// Return false and add an entry to the registry
		// to avoid repeat database queries
		if (!isset($this->registry[$name])) {
			$this->registry[$name] = false;
			return false;
		}
		
		return $value;
	}
	
	function restore ($value) {
		if (!is_string($value)) return $value;
		// Return unserialized, if serialized value
		if (preg_match("/^[sibNaO](?:\:.+?\{.*\}$|\:.+;$|;$)/s",$value)) {
			$restored = unserialize($value);
			if (!empty($restored)) return $restored;
			$restored = unserialize(stripslashes($value));
			if (!empty($restored)) return $restored;
		}
		return $value;
	}
	
	/**
	 * Return a blank setting object */
	function setting () {
		$setting->_datatypes = array("name" => "string", "value" => "string", "autoload" => "list", 
			"created" => "date", "modified" => "date");
		$setting->name = null;
		$setting->value = null;
		$setting->autoload = null;
		$setting->created = null;
		$setting->modified = null;
		return $setting;
	}
	
} // END class Settings

?>