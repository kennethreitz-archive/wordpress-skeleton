<?php
/**
 * ShipCalcs class
 * Manages shipping method calculators
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 29 April, 2008
 * @package shopp
 **/

class ShipCalcs {
	var $modules = array();
	var $methods = array();
	var $path = "";
	
	function ShipCalcs ($basepath) {
		global $Shopp;

		$this->path = $basepath.DIRECTORY_SEPARATOR."shipping";
		$lastscan = $Shopp->Settings->get('shipcalc_lastscan');
		$lastupdate = filemtime($this->path);
		
		$modfiles = array();
		if ($lastupdate > $lastscan) $modfiles = $this->scanmodules();
		else {
			$modfiles = $Shopp->Settings->get('shipcalc_modules');
			if (empty($modfiles)) $modfiles = $this->scanmodules();
		}	
	
		if (!empty($modfiles)) {
			foreach ($modfiles as $ShipCalcClass => $file) {
				if (!file_exists($this->path.$file)) continue;
				include_once($this->path.$file);
				$this->modules[$ShipCalcClass] = new $ShipCalcClass();
				$this->modules[$ShipCalcClass]->methods($this);
			}
			
			if (count($this->modules) != count($modfiles))
				$modfiles = $this->scanmodules();			
			
		}
						
	}
	
	function readmeta ($modfile) {
		$metadata = array();

		$meta = get_filemeta($this->path.$modfile);

		if ($meta) {
			$lines = explode("\n",substr($meta,1));
			foreach($lines as $line) {
				preg_match("/^(?:[\s\*]*?\b([^@\*\/]*))/",$line,$match);
				if (!empty($match[1])) $data[] = $match[1];
				preg_match("/^(?:[\s\*]*?@([^\*\/]+?)\s(.+))/",$line,$match);
				if (!empty($match[1]) && !empty($match[2])) $tags[$match[1]] = $match[2];

			}
			$module = new stdClass();
			$module->file = $modfile;
			$module->name = $data[0];
			$module->description = (!empty($data[1]))?$data[1]:"";
			$module->tags = $tags;
			return $module;
		}
		return false;
	}
	
	function scanmodules ($path=false) {
		global $Shopp;
		if (!$path) $path = $this->path;
		$modfilescan = array();
		find_files(".php",$path,$path,$modfilescan);

		if (empty($modfilescan)) return $modfilescan;
		foreach ($modfilescan as $file) {
			if (! is_readable($path.$file)) continue;
			$ShipCalcClass = substr(basename($file),0,-4);
			$modfiles[$ShipCalcClass] = $file;
		}
		
		$Shopp->Settings->save('shipcalc_modules',addslashes(serialize($modfiles)));
		$Shopp->Settings->save('shipcalc_lastscan',mktime());

		return $modfiles;
	}
		
	function ui () {
		foreach ($this->modules as $ShipCalcClass => &$module) $module->ui();
	}

} // end ShipCalcs class

?>