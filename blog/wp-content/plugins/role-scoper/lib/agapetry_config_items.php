<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();
	
require_once('agapetry_lib.php');

class AGP_Config_Items {
	var $members = array();		// collection array used by each base class	
	var $locked = 0;			// used to prevent inappropriate calls to the add method
	
	// accepts array of objects - either an instance the class collected by calling child class, or stdObject objects with matching properties 
	function &add( $name, $defining_module_name, $args = '' ) {
		if ( ! empty($this->locked) ) {
			$notice = sprintf(__('%1$s attempted to define a configuration item (%2$s) after the collection was locked.'), $defining_module_name, $name)
			. '<br /><br />' . sprintf(__('The calling function probably needs to be registered to a hook.  Consult %s developer documentation.', 'scoper'), $defining_module_name);
			rs_notice($notice);
			return;
		}
		
		// Restrict characters in member key / object name.  A display_name property is available where applicable.
		$name = preg_replace( '/[^0-9_a-zA-Z]/', '_', $name );
	
		if ( ! isset($this->members[$name]) )
			$this->members[$name] = new AGP_Config_Item($name, $defining_module_name, $args);
		
		return $this->members[$name];
	}
	
	function process( &$src ) {		
		return;
	}
	
	function remove($name) {
		if ( isset($this->members[$name]) )
			unset ($this->members[$name]);
	}
	
	function add_member_objects($arr) {
		if ( ! is_array($arr) )
			return;
			
		if ( ! empty($this->locked) ) {
			rs_notice('Config items cannot not be added at this time.  Maybe the calling function must be registered to a hook.  Consult developer documentation.');
			return;
		}
			
		foreach ( array_keys($arr) as $key ) {
			// Restrict characters in member key / object name.  A display_name property is available where applicable.
			$key = preg_replace( '/[^0-9_a-zA-Z]/', '_', $key );
		
			if ( ! isset($arr[$key]->name) )
				// copy key into name property
				$arr[$key]->name = $key;
			else
				$arr[$key]->name = preg_replace('/[^0-9_a-zA-Z]/', '_', $arr[$key]->name);
		}
		
		$this->members = array_merge($this->members, $arr);
		
		$this->process_added_members($arr);
	}
	
	function process_added_members($arr) {
		if ( method_exists($this, 'process') )	// call descendant method, if it exists
			foreach (array_keys($arr) as $name )
				$this->process($this->members[$name]);
	}
	
	// accepts object or name as argument, returns valid object or null
	function get($obj_or_name) {
		if ( is_object($obj_or_name) )
			return $obj_or_name;
		
		// $obj_or_name must actually be the object name
		if ( isset($this->members[$obj_or_name]) )
			return $this->members[$obj_or_name];
	}
	
	// accepts object or name as argument, returns valid object or null
	function &get_ref($obj_or_name) {
		if ( is_object($obj_or_name) )
			return $obj_or_name;
		
		// $obj_or_name must actually be the object name
		if ( isset($this->members[$obj_or_name]) )
			return $this->members[$obj_or_name];
	}
	
	function get_all() {
		return $this->members;
	}
	
	function get_all_keys() {
		return array_keys($this->members);
	}
	
	function is_member($name) {
		return isset($this->members[$name]);
	}
	
	// Potential use of alias property in RS Data Source definition to indicate where 
	// a 3rd party plugin uses a taxonomy->object_type property different from the src_name we define
	function is_member_alias($alias) {
		foreach ( array_keys($this->members) as $name )
			if ( isset($this->members[$name]->alias) && ( $alias == $this->members[$name]->alias ) )
				return $name;
	}
	
	function member_property() { // $name, $property, $key1 = '', $key2 = '', $key3 = '' ...
		$args = func_get_args();
		
		if ( ! is_string($args[0]) ) {
			// todo: confirm this isn't needed anymore
			return;
		}
		
		if ( ! isset( $this->members[$args[0]] ) )
			return;
			
		if ( ! isset( $this->members[$args[0]]->$args[1] ) )
			return;
			
		$val = $this->members[$args[0]]->$args[1];
		
		// if additional args were passed in, treat them as array or object keys
		for ( $i = 2; $i < count($args); $i++ ) {
			if ( is_array($val) ) {
				if ( isset($val[ $args[$i] ]) )
					$val = $val[ $args[$i] ];
				else
					return;
			
			} elseif ( is_object($val) ) {
				if ( isset($val->$args[$i]) )
					$val = $val->$args[$i];
				else
					return;
			}
		}
		
		return $val;
	}

	function remove_members_by_key($disabled, $require_value = false) {
		if ( ! is_array($disabled) )
			return;
		
		if ( $require_value ) { 
			foreach ( array_keys($disabled) as $key )
				if ( ! $disabled[$key] )
					unset($disabled[$key]);
			
			if ( ! $disabled )
				return;
		}
					
		$this->members = array_diff_key($this->members, $disabled);
	}
	
	function remove_members($disabled) {
		$this->members = array_diff_key($this->members, array_flip($disabled) );
	}
		
	function lock() {
		$this->locked = true;
	}
}

class AGP_Config_Item {
	var $name;
	var $defining_module_name;
	
	function AGP_Config_Item ( $name, $defining_module_name, $args = '' ) {
		$this->name = $name;
		$this->defining_module_name = $defining_module_name;
		
		if ( is_array($args) )
			foreach($args as $key => $val)
				$this->$key = $val;
	}
}
?>