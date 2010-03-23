<?php
/**
 * XMLdata
 * Reads XML data into associative arrays and outputs them back to valid XML 
 *
 * Credits for the parsing, markup and insert functions:
 * http://mysrc.blogspot.com/2007/02/php-xml-to-array-and-backwards.html
 * 
 * Adapted by Jon Davis, August 21, 2008
 * Navigation functions developed by Jon Davis, August 21, 2008
 */

class XMLdata {
	var $data = array();

	function XMLdata ($data=false) {
		if (!is_array($data)) $this->parse($data);
		else $this->data = $data;
		return true;
	}

	/**
	 * parse()
	 * Parses a string of XML markup into an associative array */
	function parse (&$string) {
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parse_into_struct($parser, $string, $vals, $index);
		xml_parser_free($parser);

		$data = array();
		$working = &$data;
		foreach ($vals as $r) {
			$t=$r['tag'];
			if ($r['type'] == 'open') {
				if (isset($working[$t])) {
					if (isset($working[$t][0])) $working[$t][] = array(); 
					else $working[$t]=array($working[$t], array());
					$cv = &$working[$t][count($working[$t])-1];
				} else $cv = &$working[$t];
				if (isset($r['attributes'])) { foreach ($r['attributes'] as $k => $v) $cv['ATTRS'][$k] = $v; }
				$cv['CHILDREN'] = array();
				$cv['CHILDREN']['_p'] = &$working;
				$working = &$cv['CHILDREN'];

			} elseif ($r['type']=='complete') {
				if (isset($working[$t])) { // same as open
					if (isset($working[$t][0])) $working[$t][] = array();
					else $working[$t] = array($working[$t], array());
					$cv = &$working[$t][count($working[$t])-1];
				} else $cv = &$working[$t];
				if (isset($r['attributes'])) { foreach ($r['attributes'] as $k => $v) $cv['ATTRS'][$k] = $v; }
				$cv['CONTENT'] = (isset($r['value']) ? $r['value'] : '');

			} elseif ($r['type'] == 'close') {
				$working = &$working['_p'];
			}
		}    

		$this->remove_p($data);
		$this->data = $data;
		return true;
	}

	/**
	 * remove_p()
	 * Removes recursive results in the tree */
	private function remove_p(&$data) {
		foreach ($data as $k => $v) {
			if ($k === '_p') unset($data[$k]);
			elseif (is_array($data[$k])) $this->remove_p($data[$k]);
		}
	}

	/**
	 * markup()
	 * Uses recursion to build and returns XML-markup */
	function markup ($data=false, $depth=0, $forcetag='') {
		if (!$data) $data = $this->data;
		$res=array('<?xml version="1.0" encoding="utf-8"?>'."\n");
		foreach ($data as $tag=>$r) {
			if (isset($r[0])) {
				$res[]=$this->markup($r, $depth, $tag);
		} else {
				if ($forcetag) $tag=$forcetag;
				$sp=str_repeat("\t", $depth);
				$res[] = "$sp<$tag";
				if (isset($r['ATTRS'])) { foreach ($r['ATTRS'] as $at => $av) $res[] = ' '.$at.'="'.htmlentities($av).'"'; }
				$res[] = ">".((isset($r['CHILDREN'])) ? "\n" : '');
				if (isset($r['CHILDREN'])) $res[] = $this->markup($r['CHILDREN'], $depth+1);
				elseif (isset($r['CONTENT'])) $res[] = htmlentities($r['CONTENT']);
				$res[] = (isset($r['CHILDREN']) ? $sp : '')."</$tag>\n";
			}
        
		}
		return implode('', $res);
	}

	/**
	 * insert()
	 * Inserts a new element into the data tree */
	function insert ($element, $pos) {
		$working = array_slice($this->data, 0, $pos); $working[] = $element;
		$this->data = array_merge($working, array_slice($this->data, $pos));
	}
	
	/**
	 * add()
	 * Adds a new element to the data tree as a child of the $target element */
	function &add ($element,$target=false,$attrs=array(),$content=false) {
		$working = array();
		$working[$element] = array();
		if (!empty($attrs) && is_array($attrs)) $working[$element]['ATTRS'] = $attrs;
		if ($content) $working[$element]['CONTENT'] = $content;
		if ($target) {
			if (is_array($target)) $node = &$target;
			else $node =& $this->search($target,false,true);
			if (!isset($node['CHILDREN'])) $node['CHILDREN'][$element] = $working[$element];
			else $node['CHILDREN'][$element] = $working[$element];
			return $node['CHILDREN'][$element];
		} else $this->data[$element] = $working[$element];
		return $this->data[$element];
	}
	
	/**
	 * getRootElement()
	 * Returns the root element of the tree */
	function getRootElement () {
		reset($this->data);
		return current($this->data);
	}
	
	/**
	 * getElementContent()
	 * Searches the tree for the target $element and returns 
	 * the contents (the value between the tags) */
	function getElementContent ($element) {
		$found = $this->search($element);
		if (!empty($found)) return $found[0]['CONTENT'];
		else return false;
	}

	/**
	 * getElementAttrs()
	 * Searches the tree for the target $element and returns 
	 * an associative array of attribute names and values (<tag attribute="value">) */
	function getElementAttrs ($element) {
		$found = $this->search($element);
		if (!empty($found)) return $found[0]['ATTRS'];
		else return false;
	}

	/**
	 * getElementAttr()
	 * Searches the tree for the target $element and returns 
	 * value of a specific attribute for a specific element tag (<tag attribute="value">) */
	function getElementAttr ($element,$attr) {
		$found = $this->search($element);
		if (!empty($found)) return $found[0]['ATTRS'][$attr];
		else return false;
	}
	
	/**
	 * getElement()
	 * Searches the tree for the target $element and returns 
	 * an array of the element attributes, content and any children */
	function getElement ($element) {
		$found = $this->search($element);
		if (!empty($found)) return $found[0];
		else return false;
	}
	
	/**
	 * getElements()
	 * Searches the tree for the target $element and returns 
	 * an indexed array with each indice including matched elements
	 * as associative arrays including the element attribtues, content
	 * and any children */
	function getElements($element) {
		return $this->search($element);
	}
	
	/**
	 * search()
	 * Helper function to perform recursive searches in the tree 
	 * for a $target and returns the structure */
	private function search ($target,&$dom=false,$ref=false) {
		if (!$dom) $dom = &$this->data;
		if (!is_array($dom)) $dom = array($dom);

		$results = array();
		foreach($dom as $key => &$element) {
			if (is_array($element) && $key == $target && $ref) return $element;
			if (is_array($element) && $key == $target) array_push($results,$element);
			if (isset($element['CHILDREN'])) {
				$found = &$this->search($target,$element['CHILDREN'],$ref);
				if ($ref) return $found;
				else $results += $found;
			}
		}
		return $results;
	}

}

?>