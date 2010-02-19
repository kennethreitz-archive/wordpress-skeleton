<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

require_once('lib/agapetry_config_items.php');

class WP_Scoped_Capabilities extends AGP_Config_Items {
	// defining_module_name could be a plugin name, theme name, etc.
	// args: status, base_cap, owner_privilege, anon_user_has, is_taxonomy_cap
	function &add($name, $defining_module_name, $src_name, $object_type, $op_type, $args = '') {
		if ( $this->locked ) {
			$notice = sprintf('A plugin or theme (%1$s) is too late in its attempt to define a capability (%2$s).', $defining_module_name, $name)
					. '<br /><br />' . 'This must be done via the define_capabilities_rs hook.';
			rs_notice($notice);
			return;
		}
	
		if ( isset($this->members[$name]) )
			unset($this->members[$name]);
		
		$this->members[$name] = new WP_Scoped_Capability($name, $defining_module_name, $src_name, $object_type, $op_type, $args);
		$this->process($this->members[$name]);

		return $this->members[$name];
	}
		
	function process( &$cap_def ) {
		if ( ! isset($cap_def->attributes) )
			$cap_def->attributes = '';
			
		if ( ! isset($cap_def->status) )
			$cap_def->status = '';
	}
	
	//returns array[src_name][object_type] = 1
	function object_types_from_caps($reqd_caps) {
		if ( ! is_array($reqd_caps) )
			$reqd_caps = ($reqd_caps) ? array($reqd_caps) : array();
	
		$object_types = array();
		foreach( $reqd_caps as $cap_name) {
			if ( isset($this->members[$cap_name]) ) {
				$cap_def = $this->members[$cap_name];
				
				if ( isset($cap_def->src_name) && isset($cap_def->object_type) )
					$object_types[$cap_def->src_name][$cap_def->object_type] = 1;
			}
		}
		
		return $object_types;
	}
	
	// returns caps array[op_type] = array of cap names
	function organize_caps_by_op($caps, $include_undefined_caps = false ) {
		$opcaps = array();
		
		foreach ($caps as $cap_name) {
			if ( isset($this->members[$cap_name]) )
				$opcaps[$this->members[$cap_name]->op_type] []= $cap_name;
		
			elseif ( $include_undefined_caps )
				$opcaps[''] []= $cap_name;
		}
				
		return $opcaps;
	}
	
	// returns caps array[src_name][object_type] = array of cap names
	function organize_caps_by_otype( $caps, $include_undefined_caps = false, $required_src_name = '', $default_object_type = '' ) {
		$otype_caps = array();
		
		foreach ($caps as $cap_name) {
			if ( isset($this->members[$cap_name]) ) {
				$src_name = $this->members[$cap_name]->src_name;
				
				if ( $required_src_name && ( $src_name != $required_src_name ) ) {
					$src_name =  '';
					$object_type = '';
				} else
					$object_type = $this->members[$cap_name]->object_type;
				
				if ( ! $object_type )
					$object_type = $default_object_type;
				
				$otype_caps[$src_name][$object_type] []= $cap_name;
				
			} elseif ( $include_undefined_caps )
				$otype_caps[''][''] []= $cap_name;
		}
		
		// if an otype-indeterminate cap (i.e. 'read') is present alongside otype-specific caps, combine them into the otype-specfic array(s) 
		foreach ( array_keys($otype_caps) as $src_name ) {
			if ( (count($otype_caps[$src_name]) > 1) && isset($otype_caps[$src_name]['']) ) {
				foreach ( array_keys($otype_caps[$src_name]) as $otype ) {
					if ( $otype )
						$otype_caps[$src_name][$otype] = array_merge($otype_caps[$src_name][$otype], $otype_caps[$src_name][''] );
				}
				unset ($otype_caps[$src_name]['']);
			}
		} 
		
		return $otype_caps;
	}
	
	function get_base_cap($cap) {
		if ( isset($this->members[$cap]->base_cap) )
			return $this->members[$cap]->base_cap;
	}
	
	function get_base_caps($caps, $require_owner_privilege = false) {
		if ( ! is_array($caps) )
			$caps = array($caps);
	
		foreach ($caps as $key => $cap_name) {
			if ( isset($this->members[$cap_name]) ) {
				$capdef = $this->members[$cap_name];
				if ( isset($capdef->base_cap) && ( ! $require_owner_privilege || ! empty($this->members[$capdef->base_cap]->owner_privilege) ) ) {
					unset($caps[$key]);
					$caps []= $capdef->base_cap;
				}
			}
		}
		return array_unique($caps);
	}
	
	// remove caps which do not apply to owners, or are granted to them automatically
	function remove_owner_caps($caps) {
		if ( ! is_array($caps) )
			$caps = array($caps);
	
		foreach ($caps as $key => $cap_name)
			if ( ! empty($this->members[$cap_name]->owner_privilege) || ! empty($this->members[$cap_name]->base_cap) )
				unset($caps[$key]);	

		return $caps;
	}
	
	function get_matching($src_name, $object_type = '', $op_type = '', $status = '', $base_caps_only = false, $args = '' ) {
		$arr = array();
	
		$defaults = array( 'strict_op_match' => false);
		$args = array_intersect_key( $defaults, (array) $args );
		extract($args);
		
		// disregard a status arg which is not present in any cap
		if ( $status && ( $status != STATUS_ANY_RS ) ) {
			$status_present = false;
			foreach ( $this->members as $cap_name => $capdef )
				if ( $capdef->status == $status ) {
					$status_present = true;
					break;
				}
			
			if ( ! $status_present )
				$status = '';
		}
					
		// first narrow to specified source name, object type, status and baseness
		foreach ( $this->members as $cap_name => $capdef )
			if ( ( $capdef->src_name == $src_name )
			&& ( ! $object_type || empty($capdef->object_type) || ( $object_type == $capdef->object_type ) )
			&& ( (STATUS_ANY_RS == $status) || ( ! $status && empty($capdef->status) ) || ( isset($capdef->status) && ($status == $capdef->status) ) )
			&& ( (BASE_CAPS_RS != $base_caps_only) || empty($capdef->base_cap) ) 
			)
				$arr[$cap_name] = $capdef;
						
		// Narrow to specified op type.  
		// But if no cap of this op type is defined, sustitute a cap of higher op level (which also met the other criteria)
		if ( $arr && $op_type ) {
			$sustitute_ops = array( OP_EDIT_RS, OP_PUBLISH_RS, OP_DELETE_RS, OP_ADMIN_RS );
			if ( ! $op_level = array_search($op_type, $sustitute_ops) )
				$op_level = -1;
			
			$op_caps = array();
			do {
				if ( $op_level >= 0 )
					$op_type = $sustitute_ops[$op_level];
			
				foreach ( $arr as $cap_name => $capdef )
					if ( $capdef->op_type == $op_type )
						$op_caps[$cap_name] = $capdef;
				
				$op_level++;
			} while ( ! $op_caps && ($op_level < count($sustitute_ops) ) && empty($strict_op_match) );
			
			return $op_caps;	
		} else
			return $arr;
	}
}

class WP_Scoped_Capability extends AGP_Config_Item {
	var $src_name;			// required
	var $object_type;		// required
	var $op_type;			// required
	var $status = '';			
	var $attributes;		// array of attribute names: 'others', etc.
	var $base_cap;			// documentation not finished - see scoper_core_cap_defs()
	var $owner_privilege;	// 		''
	var $anon_user_has;		// 		''
	var $is_taxonomy_cap;	// 		''
	
	// args: status, base_cap, owner_privilege, anon_user_has, is_taxonomy_cap 
	function WP_Scoped_Capability($name, $defining_module_name, $src_name, $object_type = '', $op_type = '', $args) {
		$this->AGP_Config_Item($name, $defining_module_name, $args);
		
		$this->src_name = $src_name;
		$this->object_type = $object_type;
		$this->op_type = $op_type;
	}
}
?>