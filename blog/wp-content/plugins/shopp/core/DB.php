<?php
/**
 * DB classes
 * Database management classes
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 28 March, 2008
 * @package Shopp
 **/

define("AS_ARRAY",false);
define("SHOPP_DBPREFIX","shopp_");
if (!defined('SHOPP_QUERY_DEBUG')) define('SHOPP_QUERY_DEBUG',false);

// Make sure that compatibility mode is not enabled
if (ini_get('zend.ze1_compatibility_mode'))
	ini_set('zend.ze1_compatibility_mode','Off');

class DB {
	private static $instance;
	// Define datatypes for MySQL
	var $_datatypes = array("int" => array("int", "bit", "bool", "boolean"),
							"float" => array("float", "double", "decimal", "real"),
							"string" => array("char", "binary", "varbinary", "text", "blob"),
							"list" => array("enum","set"),
							"date" => array("date", "time", "year")
							);
	var $results = array();
	var $queries = array();
	var $dbh = false;


	protected function DB () {
		global $wpdb;
		$this->dbh = $wpdb->dbh;
		$this->version = mysql_get_server_info();
	}

	function __clone() { trigger_error('Clone is not allowed.', E_USER_ERROR); }
	static function &get() {
		if (!self::$instance instanceof self)
			self::$instance = new self;
		return self::$instance;
	}

	
	/* 
	 * Connects to the database server */
	function connect($user, $password, $database, $host) {
		$this->dbh = @mysql_connect($host, $user, $password);
		if (!$this->dbh) trigger_error("Could not connect to the database server '$host'.");
		else $this->select($database);
	}
	
	/**
	 * Select the database to use for our connection */
	function select($database) {
		if(!@mysql_select_db($database,$this->dbh)) 
			trigger_error("Could not select the '$database' database.");
	}
	
	/**
	 * Escape contents of data for safe insertion into the db */
	function escape($data) {
		// Prevent double escaping by stripping any existing escapes out
		return addslashes(stripslashes($data));
	}

	function clean($data) {
		if (is_array($data)) array_map(array(&$this,'clean'), $data);
		if (is_string($data)) rtrim($data);
		return $data;
	}
	
	/**
	 * Send a query to the database */
	function query($query, $output=true, $errors=true) {
		if (SHOPP_QUERY_DEBUG) $this->queries[] = $query;
		$result = @mysql_query($query, $this->dbh);
		if (SHOPP_QUERY_DEBUG && class_exists('ShoppError')) new ShoppError($query,'shopp_query_debug',SHOPP_DEBUG_ERR);

		// Error handling
		if ($this->dbh && $error = mysql_error($this->dbh)) 
			new ShoppError(sprintf(__('Query failed: %s - DB Query: %s','Shopp'),$error, str_replace("\n","",$query)),'shopp_query_error',SHOPP_DB_ERR);
				
		// Results handling
		if ( preg_match("/^\\s*(create|drop|insert|delete|update|replace) /i",$query) ) {
			$this->affected = mysql_affected_rows();
			if ( preg_match("/^\\s*(insert|replace) /i",$query) ) {
				$insert = @mysql_fetch_object(@mysql_query("SELECT LAST_INSERT_ID() AS id", $this->dbh));
				return $insert->id;
			}
			
			if ($this->affected > 0) return $this->affected;
			else return true;
		} else {
			if ($result === true) return true;
			$this->results = array();
			while ($row = @mysql_fetch_object($result)) {
				$this->results[] = $row;
			}
			@mysql_free_result($result);
			if ($output && sizeof($this->results) == 1) $this->results = $this->results[0];
			return $this->results;
		}
	
	}
		
	function datatype($type) {
		foreach((array)$this->_datatypes as $datatype => $patterns) {
			foreach((array)$patterns as $pattern) {				
				if (strpos($type,$pattern) !== false) return $datatype;
			}
		}
		return false;
	}
	
	/**
	 * Prepare the data properties for entry into
	 * the database */
	function prepare($object) {
		$data = array();
		
		// Go through each data property of the object
		foreach(get_object_vars($object) as $property => $value) {
			if (!isset($object->_datatypes[$property])) continue;				

			// If the property is has a _datatype
			// it belongs in the database and needs
			// to be prepared

			// Process the data
			switch ($object->_datatypes[$property]) {
				case "string":
					// Escape characters in strings as needed
					if (is_array($value)) $value = serialize($value);
					$data[$property] = "'".$this->escape($value)."'";
					break;	
				case "list":
					// If value is empty, skip setting the field
					// so it inherits the default value in the db
					if (!empty($value)) 
						$data[$property] = "'$value'";
					break;
				case "date":
					// If it's an empty date, set it to now()'s timestamp
					if (is_null($value)) {
						$data[$property] = "now()";
					// If the date is an integer, convert it to an
					// sql YYYY-MM-DD HH:MM:SS format
					} elseif (!empty($value) && is_int(intval($value))) {
						$data[$property] = "'".mkdatetime(intval($value))."'";
					// Otherwise it's already ready, so pass it through
					} else {
						$data[$property] = "'$value'";
					}
					break;
				case "int":
				case "float":					
					$value = floatnum($value);
					
					$data[$property] = "'$value'";
					
					if (empty($value)) $data[$property] = "'0'";

					// Special exception for id fields
					if ($property == "id" && empty($value)) $data[$property] = "NULL";
					
					break;
				default:
					// Anything not needing processing
					// passes through into the object
					$data[$property] = "'$value'";
			}
			
		}
				
		return $data;
	}
	
	/**
	 * Get the list of possible values for an enum() or set() column */
	function column_options($table = null, $column = null) {
		if ( ! ($table && $column)) return array();
		$r = $this->query("SHOW COLUMNS FROM $table LIKE '$column'");
		if ( strpos($r[0]->Type,"enum('") )
			$list = substr($r[0]->Type, 6, strlen($r[0]->Type) - 8);
			
		if ( strpos($r[0]->Type,"set('") )
			$list = substr($r[0]->Type, 5, strlen($r[0]->Type) - 7);
	
		return explode("','",$list);
	}
	
	/**
	 * Send a large set of queries to the database. */
	function loaddata ($queries) {
		$queries = explode(";\n", $queries);
		array_pop($queries);
		foreach ($queries as $query) if (!empty($query)) $this->query($query);
		return true;
	}
	
	
} // END class DB


/* class DatabaseObject
 * Generic database glueware between the database and the active data model */

class DatabaseObject {
	
	function DatabaseObject () {
		// Constructor	
	}
	
	/**
	 * Initializes the db object by grabbing table schema
	 * so we know how to work with this table */
	function init ($table,$key="id") {
		global $Shopp;
		$db = DB::get();
		
		$this->_table = $this->tablename($table); // So we know what the table name is
		$this->_key = $key;				// So we know what the primary key is
		$this->_datatypes = array();	// So we know the format of the table
		$this->_lists = array();		// So we know the options for each list
		$this->_defaults = array();		// So we know the default values for each field

		if (isset($Shopp->Settings)) {
			$Tables = $Shopp->Settings->get('data_model');
			if (isset($Tables[$this->_table])) {
				$this->_datatypes = $Tables[$this->_table]->_datatypes;
				$this->_lists = $Tables[$this->_table]->_lists;
				foreach($this->_datatypes as $property => $type) {
					$this->{$property} = (isset($this->_defaults[$property]))?
						$this->_defaults[$property]:'';
					if (empty($this->{$property}) && $type == "date")
						$this->{$property} = null;
				}
				return true;
			}
		}

		if (!$r = $db->query("SHOW COLUMNS FROM $this->_table",true,false)) return false;
		
		// Map out the table definition into our data structure
		foreach($r as $object) {	
			$property = $object->Field;
			$this->{$property} = $object->Default;
			$this->_datatypes[$property] = $db->datatype($object->Type);
			$this->_defaults[$property] = $object->Default;
			
			// Grab out options from list fields
			if ($db->datatype($object->Type) == "list") {
				$values = str_replace("','", ",", substr($object->Type,strpos($object->Type,"'")+1,-2));
				$this->_lists[$property] = explode(",",$values);
			}
		}
		
		if (isset($Shopp->Settings)) {
			$Tables[$this->_table] = new StdClass();
			$Tables[$this->_table]->_datatypes = $this->_datatypes;
			$Tables[$this->_table]->_lists = $this->_lists;
			$Tables[$this->_table]->_defaults = $this->_defaults;
			$Shopp->Settings->save('data_model',$Tables);
		}
	}
	
	/**
	 * Load a single record by the primary key or a custom query 
	 * @param $where - An array of key/values to be built into an SQL where clause
	 * or
	 * @param $id - A string containing the id for db object's predefined primary key
	 * or
	 * @param $id - A string containing the object's id value
	 * @param $key - A string of the name of the db object's primary key
	 **/
	function load () {
		$db = DB::get();

		$args = func_get_args();
		if (empty($args[0])) return false;
		
		$where = "";
		if (is_array($args[0])) 
			foreach ($args[0] as $key => $id) 
				$where .= ($where == ""?"":" AND ")."$key='".$db->escape($id)."'";
		else {
			$id = $args[0];
			$key = $this->_key;
			if (!empty($args[1])) $key = $args[1];
			$where = $key."='".$db->escape($id)."'";
		}

		$r = $db->query("SELECT * FROM $this->_table WHERE $where LIMIT 1");
		$this->populate($r);
		
		if (!empty($this->id)) return true;
		return false;
	}

	/**
	 * Processes a bulk string of semi-colon separated SQL queries */
	function loaddata ($queries) {
		$queries = explode(";\n", $queries);
		array_pop($queries);
		foreach ($queries as $query) if (!empty($query)) $this->query($query);
		return true;
	}
	
	function tablename ($table) {
		global $table_prefix;
		return $table_prefix.SHOPP_DBPREFIX.$table;
	}
	
	/**
	 * Save a record, updating when we have a value for the primary key,
	 * inserting a new record when we don't */
	function save () {
		$db = DB::get();
		
		$data = $db->prepare($this);
		$id = $this->{$this->_key};
		// Update record
		if (!empty($id)) {
			if (isset($data['modified'])) $data['modified'] = "now()";
			$dataset = $this->dataset($data);
			$db->query("UPDATE $this->_table SET $dataset WHERE $this->_key=$id");
			return true;
		// Insert new record
		} else {
			if (isset($data['created'])) $data['created'] = "now()";
			if (isset($data['modified'])) $data['modified'] = "now()";
			$dataset = $this->dataset($data);
			//print "INSERT $this->_table SET $dataset";
			$this->id = $db->query("INSERT $this->_table SET $dataset");
			return $this->id;
		}

	}
		
	/**
	 * Deletes the record associated with this object */
	function delete () {
		$db = DB::get();
		// Delete record
		$id = $this->{$this->_key};
		if (!empty($id)) $db->query("DELETE FROM $this->_table WHERE $this->_key='$id'");
		else return false;
	}

	/**
	 * Populate the object properties from a set of 
	 * loaded results  */
	function populate($data) {
		if(empty($data)) return false;
		foreach(get_object_vars($data) as $property => $value) {
			if (empty($this->_datatypes[$property])) continue;
			// Process the data
			switch ($this->_datatypes[$property]) {
				case "date":
					$this->{$property} = mktimestamp($value);
					break;
				case "int":
				case "float":
				case "string":
					// If string has been serialized, unserialize it
					if (preg_match("/^[sibNaO](?:\:.+?\{.*\}$|\:.+;$|;$)/s",$value)) $value = unserialize($value);
				default:
					// Anything not needing processing
					// passes through into the object
					$this->{$property} = $value;
			}			
		}
	}
	
	/**
	 * Build an SQL-ready string of the prepared data
	 * for entry into the database  */
	function dataset($data) {
		$query = "";
		foreach($data as $property => $value) {
			if (!empty($query)) $query .= ", ";
			$query .= "$property=$value";
		}
		return $query;
	}
	
	/**
	 * Populate the object properties from a set of 
	 * form post inputs  */
	function updates($data,$ignores = array()) {
		$db = DB::get();
		
		foreach ($data as $key => $value) {
			if (!is_null($value) && 
				!in_array($key,$ignores) && 
				property_exists($this, $key) ) {
				$this->{$key} = $db->clean($value);
			}	
		}
	}
	
	/**
	 * Copy property values from a given (like) object to this object
	 * where the property names match */
	function copydata ($Object,$prefix="") {
		$db = DB::get();

		$ignores = array("_datatypes","_table","_key","_lists","id","created","modified");
		foreach(get_object_vars($Object) as $property => $value) {
			$property = $prefix.$property;
			if (property_exists($this,$property) && 
				!in_array($property,$ignores)) 
				$this->{$property} = $db->clean($value);
		}
	}

}  // END class DatabaseObject

?>