<?php

// Version 0.6

class scbOptions_06 {
	public $key;
	public $defaults;
	private $data;

	public function __construct($key) {
		$this->key = $key;
		$this->data = get_option($this->key);
	}

	public function setup($file, $defaults) {
		$this->defaults = $defaults;
		register_activation_hook($file, array($this, 'reset'), false);
		register_uninstall_hook($file, array($this, 'delete'));
	}

	// Get all data or a certain field
	public function get($field = '') {
		if ( empty($field) === true )
			return $this->data;

		return @$this->data[$field];
	}

	// Update a portion of the data
	public function update_part($newdata) {
		if ( !is_array($this->data) || !is_array($newdata) )
			return false;

		$this->update(array_merge($this->data, $newdata));
	}

	// Update option
	public function update($newdata) {
		if ( $this->data === $newdata )
			return;

		$this->data = $newdata;

		   add_option($this->key, $this->data) or
		update_option($this->key, $this->data);
	}

	// Reset option to defaults
	public function reset($override = true) {
		if ( !$override && is_array($this->defaults) && is_array($this->data) )
			$newdata = array_merge($this->defaults, $this->data);
		else
			$newdata = $this->defaults;

		$this->update($newdata);
	}

	// Delete option
	public function delete() {
		delete_option($this->key);
	}
}

// < WP 2.7
if ( !function_exists('register_uninstall_hook') ) :
function register_uninstall_hook() {}
endif;
