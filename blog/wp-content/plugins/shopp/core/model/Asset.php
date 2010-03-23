<?php
/**
 * Asset class
 * Catalog product assets (metadata, images, downloads)
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 28 March, 2008
 * @package shopp
 **/

class Asset extends DatabaseObject {
	static $table = "asset";
	
	var $storage = "db";
	var $path = "";
	
	function Asset ($id=false,$key=false) {
		$this->init(self::$table);
		if ($this->load($id,$key)) return true;
		else return false;
	}
	
	function setstorage ($type=false) {
		global $Shopp;
		if (!$type) $type = $this->datatype;
		switch ($type) {
			case "image":
			case "small":
			case "thumbnail":
				$this->storage = $Shopp->Settings->get('image_storage');
				$this->path = trailingslashit($Shopp->Settings->get('image_path'));
				break;
			case "download":
				$this->storage = $Shopp->Settings->get('product_storage');
				$this->path = trailingslashit($Shopp->Settings->get('products_path'));
				break;
		}
	}
	
	/**
	 * Save a record, updating when we have a value for the primary key,
	 * inserting a new record when we don't */
	function save () {
		$db =& DB::get();
		
		$data = $db->prepare($this);
		$id = $this->{$this->_key};

		$this->setstorage();
	
		// Hook for outputting files to filesystem
		if ($this->storage == "fs") {
			if (!$this->savefile()) return false;
			unset($data['data']); // Keep from duplicating data in DB
		}

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
			$this->id = $db->query("INSERT $this->_table SET $dataset");
			return $this->id;
		}
	}
	
	function savedata ($file) {
		$db =& DB::get();

		$id = $this->{$this->_key};
		if (!$id) return false;
		
		$handle = fopen($file, "r");
		while (!feof($handle)) {
			$buffer = mysql_real_escape_string(fread($handle, 65535));
			$query = "UPDATE $this->_table SET data=CONCAT(data,'$buffer') WHERE $this->_key=$id";
			$db->query($query);
		}
		fclose($handle);
	}
	
	function savefile () {
		if (empty($this->data)) return true;
		if (file_put_contents($this->path.$this->name,stripslashes($this->data)) > 0) return true;
		return false;
	}
	
	function deleteset ($keys,$type="image") {
		$db =& DB::get();

		if ($type == "image") $this->setstorage('image');
		if ($type == "download") $this->setstorage('download');

		if ($this->storage == "fs")	$this->deletefiles($keys);

		$selection = "";
		foreach ($keys as $value) 
			$selection .= ((!empty($selection))?" OR ":"")."{$this->_key}=$value OR src=$value";

		$query = "DELETE LOW_PRIORITY FROM $this->_table WHERE $selection";
		$db->query($query);
	}

	/** 
	 * deletefiles ()
	 * Remove files from the file system only when 1 reference to the file exists
	 * in file references in the database, otherwise, leave them **/
	function deletefiles ($keys) {
		$db =& DB::get();
		
		$selection = "";
		foreach ($keys as $value) 
			$selection .= ((!empty($selection))?" OR ":"")."f.{$this->_key}=$value OR f.src=$value";

		$query = "SELECT f.name,count(DISTINCT links.id) AS refs FROM $this->_table AS f LEFT JOIN $this->_table AS links ON f.name=links.name WHERE $selection GROUP BY links.name";
		$files = $db->query($query,AS_ARRAY);

		foreach ($files as $file)
			if ($file->refs == 1 && file_exists($this->path.$file->name))
				unlink($this->path.$file->name);

		return true;
	}
	
	function download ($dkey=false) {
		$this->setstorage('download');
		// Close the session in case of long download
		@session_write_close();

		// Don't want interference from the server
	    if (function_exists('apache_setenv')) @apache_setenv('no-gzip', 1);
	    @ini_set('zlib.output_compression', 0);
		
		set_time_limit(0);	// Don't timeout on long downloads
		ob_end_clean();		// End any automatic output buffering
		
		header("Pragma: public");
		header("Cache-Control: maxage=1");
		header("Content-type: application/octet-stream"); 
		header("Content-Disposition: attachment; filename=\"".$this->name."\""); 
		header("Content-Description: Delivered by WordPress/Shopp ".SHOPP_VERSION);

		// File System based download - handles very large files, supports resumable downloads
		if ($this->storage == "fs") {
			if (!empty($this->value)) $filepath = join("/",array($this->path,$this->value,$this->name));
			else $filepath = join("/",array($this->path,$this->name));

			if (!is_file($filepath)) {
				header("Status: 404 Forbidden");  // File not found?!
				return false;
			}

			$size = @filesize($filepath);
			
			// Handle resumable downloads
			if (isset($_SERVER['HTTP_RANGE'])) {
				list($units, $reqrange) = explode('=', $_SERVER['HTTP_RANGE'], 2);
				if ($units == 'bytes') {
					// Use first range - http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
					list($range, $extra) = explode(',', $reqrange, 2);
				} else $range = '';
			} else $range = '';
			
			// Determine download chunk to grab
		    list($start, $end) = explode('-', $range, 2);
			
		    // Set start and end based on range (if set), or set defaults
		    // also check for invalid ranges.
		    $end = (empty($end)) ? ($size - 1) : min(abs(intval($end)),($size - 1));
		    $start = (empty($start) || $end < abs(intval($start))) ? 0 : max(abs(intval($start)),0);

	        // Only send partial content header if downloading a piece of the file (IE workaround)
	        if ($start > 0 || $end < ($size - 1)) header('HTTP/1.1 206 Partial Content');

	        header('Accept-Ranges: bytes');
	        header('Content-Range: bytes '.$start.'-'.$end.'/'.$size);
		    header('Content-length: '.($end-$start+1)); 

			// WebKit/Safari resumable download support headers
		    header('Last-modified: '.date('D, d M Y H:i:s O',$this->modified)); 
			if (isset($dkey)) header('ETag: '.$dkey);

			$file = fopen($filepath, 'rb');
			fseek($file, $start);
			$packet = 1024*1024;
			while(!feof($file)) {
				if (connection_status() !== 0) return false;
				$buffer = fread($file,$packet);
				if (!empty($buffer)) echo $buffer;
				ob_flush(); flush();
			}
			fclose($file);
			return true;
		} else {
			// Database file download - short and sweet
			header ("Content-length: ".$this->size); 
			echo $this->data;
			return true;
		}
		
	}
	
} // end Asset class

?>