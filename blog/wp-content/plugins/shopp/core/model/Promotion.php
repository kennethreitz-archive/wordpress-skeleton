<?php
/**
 * Promotion class
 * Handles special promotion deals
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 2 September, 2008
 * @package shopp
 **/

class Promotion extends DatabaseObject {
	static $table = "promo";
	
	var $values = array(
		"Name" => "text",
		"Category" => "text",
		"Variation" => "text",
		"Price" => "price",
		"Sale price" => "price",
		"Type" => "text",
		"In stock" => "text",
		"Item name" => "text",
		"Item quantity" => "text",
		"Item amount" => "price",
		"Total quantity" => "text",
		"Shipping amount" => "price",
		"Subtotal amount" => "price",
		"Promo code" => "text"
	);

	function Promotion ($id=false) {
		$this->init(self::$table);
		if ($this->load($id)) return true;
		else return false;
	}
	
	function build_discounts () {
		$db = DB::get();
		
		$discount_table = DatabaseObject::tablename(Discount::$table);
		$product_table = DatabaseObject::tablename(Product::$table);
		$price_table = DatabaseObject::tablename(Price::$table);
		$catalog_table = DatabaseObject::tablename(Catalog::$table);
		$category_table = DatabaseObject::tablename(Category::$table);
		
		$where = "";
		// Go through each rule to construct an SQL query 
		// that gets all applicable product & price ids
		if (!empty($this->rules) && is_array($this->rules)) {
			foreach ($this->rules as $rule) {
				
				if ($this->values[$rule['property']] == "price") 
					$value = floatnum($rule['value']);
				else $value = "'".$rule['value']."'";
				
				switch($rule['logic']) {
					case "Is equal to": $match = "=$value"; break;
					case "Is not equal to": $match = "!=$value"; break;
					case "Contains": $match = " LIKE '%$value%'"; break;
					case "Does not contain": $match = " NOT LIKE '%$value%'"; break;
					case "Begins with": $match = " LIKE '$value%'"; break;
					case "Ends with": $match = " LIKE '%$value'"; break;
					case "Is greater than": $match = "> $value"; break;
					case "Is greater than or equal to": $match = ">= $value"; break;
					case "Is less than": $match = "< $value"; break;
					case "Is less than or equal to": $match = "<= $value"; break;
				}
			
				$where .= "AND ";
				switch($rule['property']) {
					case "Name": $where .= "p.name$match"; break;
					case "Category": $where .= "cat.name$match"; break;
					case "Variation": $where .= "prc.label$match"; break;
					case "Price": $where .= "prc.price$match"; break;
					case "Sale price": $where .= "(prc.onsale='on' AND prc.saleprice$match)"; break;
					case "Type": $where .= "prc.type$match"; break;
					case "In stock": $where .= "(prc.inventory='on' AND prc.stock$match)"; break;
				}
			
			}
			
		}
		
		$type = ($this->type == "Item")?'catalog':'cart';
		// Delete previous discount records
		$db->query("DELETE FROM $discount_table WHERE promo=$this->id");
		$query = "INSERT INTO $discount_table (promo,product,price) 
					SELECT '$this->id' as promo,p.id AS product,prc.id AS price
					FROM $product_table as p 
					LEFT JOIN $price_table AS prc ON prc.product=p.id 
					LEFT JOIN $catalog_table AS clog ON clog.product=p.id 
					LEFT JOIN $category_table AS cat ON clog.category=cat.id 
					WHERE TRUE $where 
					GROUP BY prc.id";

		$db->query($query);
		
	}

	/**
	 * match_rule ()
	 * Determines if the value of a given subject matches the rule based 
	 * on the specified operation */
	function match_rule ($subject,$op,$value,$property=false) {
		switch($op) {
			// String or Numeric operations
			case "Is equal to":
			 	if($property && $this->values[$property] == 'price'){
					return ( floatvalue($subject) != 0 
					&& floatvalue($value) != 0 
					&& floatvalue($subject) == floatvalue($value));
				} else {
					return ($subject === $value);
				}		 
					break;
			case "Is not equal to": 		
				return ($subject !== $value 
						|| (floatvalue($subject) != 0 
						&& floatvalue($value) != 0 
						&& floatvalue($subject) != floatvalue($value))); 
						break;

			// String operations
			case "Contains": return (stripos($subject,$value) !== false); break;
			case "Does not contain": return (stripos($subject,$value) === false); break;
			case "Begins with": return (stripos($subject,$value) === 0); break;
			case "Ends with": return  (stripos($subject,$value) === strlen($subject) - strlen($value)); break;
			
			// Numeric operations
			case "Is greater than":
				return (floatvalue($subject,false) > floatvalue($value,false)); 
				break;
			case "Is greater than or equal to": 
				return (floatvalue($subject,false) >= floatvalue($value,false)); 
				break;
			case "Is less than": 
				return (floatvalue($subject,false) < floatvalue($value,false)); 
				break;
			case "Is less than or equal to": 
				return (floatvalue($subject,false) <= floatvalue($value,false)); 
				break;
		}
		
		return false;
	}

} // end Promotion class


// Discount table provides discount index for faster, efficient discount lookups
class Discount extends DatabaseObject {
	static $table = "discount";
	
	function Promotion ($id=false) {
		$this->init(self::$table);
		if ($this->load($id)) return true;
		else return false;
	}
	
	function delete () {
		$db = DB::get();
		// Delete record
		$id = $this->{$this->_key};

		// Delete related discounts
		$discount_table = DatabaseObject::tablename(Discount::$table);
		if (!empty($id)) $db->query("DELETE LOW_PRIORITY FROM $discount_table WHERE promo='$id'");
		
		if (!empty($id)) $db->query("DELETE FROM $this->_table WHERE $this->_key='$id'");
		else return false;
	}

} // end Discount class


?>