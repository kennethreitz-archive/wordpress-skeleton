<?php

// Usage: http://scribu.net/wordpress/scb-framework/scb-options.html

class scbOptions {
	protected $defaults;	// the default value(s)

	protected $key;			// the option name
	protected $data;		// the option value

	public $wp_filter_id;	// used by WP hooks

	/**
	 * Create a new set of options
	 *
	 * @param key Option name
	 * @param string Reference to main plugin file
	 * @param array An associative array of default values
	 */
	function __construct($key, $file, $defaults = '') {
		$this->key = $key;
		$this->defaults = $defaults;
		$this->data = get_option($this->key);

		if ( is_array($this->defaults) ) {
			$this->data = (array) $this->data;

			register_activation_hook($file, array($this, '_update_reset'));
		}

		scbUtil::add_uninstall_hook($file, array($this, '_delete'));
	}

	/**
	 * Get all data fields, certain fields or a single field
	 *
	 * @param string|array $field The field(s) to get
	 * @return mixed Whatever is in those fields
	 */
	function get($field = '') {
		return $this->_get($field, $this->data);
	}

	/**
	 * Get all default fields, certain fields or a single field
	 *
	 * @param string|array $field The field(s) to get
	 * @return mixed Whatever is in those fields
	 */
	function get_defaults($field = '') {
		return $this->_get($field, $this->defaults);
	}

	/**
	 * Set all data fields, certain fields or a single field
	 *
	 * @param string|array $field The field to update or an associative array
	 * @param mixed $value The new value (ignored if $field is array)
	 * @return null
	 */
	function set($field, $value = '') {
		if ( is_array($field) )
			$newdata = $field;
		else
			$newdata = array($field => $value);

		$this->update(array_merge($this->data, $newdata));
	}

	/**
	 * Remove any keys that are not in the defaults array
	 */
	function cleanup() {
		$r = array();

		if ( ! is_array($this->defaults) )
			return false;

		foreach ( array_keys($this->defaults) as $key )
			$r[$key] = $this->data[$key];

		$this->update($r);

		return true;
	}

	/**
	 * Update raw data
	 *
	 * @param mixed $newdata
	 * @return null
	 */
	function update($newdata) {
		if ( $this->data === $newdata )
			return;

		$this->data = $newdata;

		update_option($this->key, $this->data);
	}

	/**
	 * Reset option to defaults
	 *
	 * @return null
	 */
	function reset() {
		$this->update($this->defaults);
	}


//_____INTERNAL METHODS_____


	// Get one, more or all fields from an array
	private function _get($field, $data) {
		if ( empty($field) )
			return $data;

		if ( is_string($field) )
			return $data[$field];

		foreach ( $field as $key )
			if ( isset($data[$key]) )
				$result[] = $data[$key];

		return $result;
	}

	// Magic method: $options->field
	function __get($field) {
		return $this->data[$field];
	}

	// Magic method: $options->field = $value
	function __set($field, $value) {
		$this->set($field, $value);
	}

	// Magic method: isset($options->field)
	function __isset($field) {
		return isset($this->data[$field]);
	}

	// Add new fields with their default values
	function _update_reset() {
		$this->update(array_merge($this->defaults, $this->data));
	}

	// Delete option
	function _delete() {
		delete_option($this->key);
	}

	// DEPRECATED
	function update_part($data) {
		$this->set($data);
	}
}

