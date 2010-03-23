<?php
/**
 * Product class
 * Catalog products
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 28 March, 2008
 * @package shopp
 **/

require("Asset.php");
require("Spec.php");
require("Price.php");
require("Promotion.php");

class Product extends DatabaseObject {
	static $table = "product";
	var $prices = array();
	var $pricekey = array();
	var $priceid = array();
	var $pricerange = array('max'=>array(),'min'=>array());
	var $onsale = false;
	var $categories = array();
	var $tags = array();
	var $images = array();
	var $imagesets = array();
	var $imageset = false;
	var $specs = array();
	var $ranges = array('max'=>array(),'min'=>array());
	var $freeshipping = false;
	var $priceloop = false;
	var $specloop = false;
	var $categoryloop = false;
	var $imageloop = false;
	var $tagloop = false;	
	var $outofstock = false;
	var $stock = 0;
	var $options = 0;
	
	function Product ($id=false,$key=false) {
		$this->init(self::$table);
		if ($this->load($id,$key)) {			
			add_filter('shopp_product_description', 'wptexturize');
			add_filter('shopp_product_description', 'convert_chars');
			add_filter('shopp_product_description', 'wpautop');
			add_filter('shopp_product_description', 'do_shortcode', 11); // AFTER wpautop()	

			add_filter('shopp_product_spec', 'wptexturize');
			add_filter('shopp_product_spec', 'convert_chars');
			add_filter('shopp_product_spec', 'do_shortcode', 11); // AFTER wpautop()	

			return true;
		}
		return false;
	}
	
	function load_data ($options=false,&$products=false) {
		global $Shopp;
		$db =& DB::get();

		// Load object schemas on request
		
		$Dataset = array();
		if (in_array('prices',$options)) {
			$promotable = DatabaseObject::tablename(Promotion::$table);
			$discounttable = DatabaseObject::tablename(Discount::$table);
			$assettable = DatabaseObject::tablename(Asset::$table);

			$Dataset['prices'] = new Price();
			$Dataset['prices']->_datatypes['promos'] = "MAX(promo.status)";
			$Dataset['prices']->_datatypes['promotions'] = "group_concat(promo.name)";
			$Dataset['prices']->_datatypes['percentoff'] = "SUM(IF (promo.type='Percentage Off',promo.discount,0))";
			$Dataset['prices']->_datatypes['amountoff'] = "SUM(IF (promo.type='Amount Off',promo.discount,0))";
			$Dataset['prices']->_datatypes['freeshipping'] = "SUM(IF (promo.type='Free Shipping',1,0))";
			$Dataset['prices']->_datatypes['buyqty'] = "IF (promo.type='Buy X Get Y Free',promo.buyqty,0)";
			$Dataset['prices']->_datatypes['getqty'] = "IF (promo.type='Buy X Get Y Free',promo.getqty,0)";
			$Dataset['prices']->_datatypes['download'] = "download.id";
			$Dataset['prices']->_datatypes['filename'] = "download.name";
			$Dataset['prices']->_datatypes['filedata'] = "download.properties";
			$Dataset['prices']->_datatypes['filesize'] = "download.size";
		}

		if (in_array('images',$options)) {
			$Dataset['images'] = new Asset();
			unset($Dataset['images']->_datatypes['data']);	
		}

		if (in_array('categories',$options)) {
			$Dataset['categories'] = new Category();
			unset($Dataset['categories']->_datatypes['priceranges']);
			unset($Dataset['categories']->_datatypes['specs']);
			unset($Dataset['categories']->_datatypes['options']);
			unset($Dataset['categories']->_datatypes['prices']);
		}

		if (in_array('specs',$options)) $Dataset['specs'] = new Spec();
		if (in_array('tags',$options)) $Dataset['tags'] = new Tag();

		// Determine the maximum columns to allocate
		$maxcols = 0;
		foreach ($Dataset as $set) {
			$cols = count($set->_datatypes);
			if ($cols > $maxcols) $maxcols = $cols;
		}
		
		// Prepare product list depending on single product or entire list
		$ids = array();
		if (isset($products) && is_array($products)) {
			foreach ($products as $product) $ids[] = $product->id;
		} else $ids[0] = $this->id;
		
		// Skip if there are no product ids
		if (empty($ids) || empty($ids[0])) return false;
		
		// Build the mega-query	
		foreach ($Dataset as $rtype => $set) {

			// Allocate generic columns for record data
			$columns = array(); $i = 0;
			foreach ($set->_datatypes as $key => $datatype)
				$columns[] = ((strpos($datatype,'.')!==false)?"$datatype":"{$set->_table}.$key")." AS c".($i++);
			for ($i = $i; $i < $maxcols; $i++) 
				$columns[] = "'' AS c$i";
			
			$cols = join(',',$columns);

			// Build object-specific selects and UNION them
			$where = "";
			if (isset($query)) $query .= " UNION ";
			else $query = "";
			switch($rtype) {
				case "prices":
					foreach ($ids as $id) $where .= ((!empty($where))?" OR ":"")."$set->_table.product=$id";
					$query .= "(SELECT '$set->_table' as dataset,$set->_table.product AS product,'$rtype' AS rtype,'' AS alphaorder,$set->_table.sortorder AS sortorder,$cols FROM $set->_table 
								LEFT JOIN $assettable AS download ON $set->_table.id=download.parent AND download.context='price' AND download.datatype='download' 
								LEFT JOIN $discounttable AS discount ON discount.product=$set->_table.product AND discount.price=$set->_table.id
								LEFT JOIN $promotable AS promo ON promo.id=discount.promo AND (promo.status='enabled' AND ((UNIX_TIMESTAMP(starts)=1 AND UNIX_TIMESTAMP(ends)=1) OR (UNIX_TIMESTAMP(now()) > UNIX_TIMESTAMP(starts) AND UNIX_TIMESTAMP(now()) < UNIX_TIMESTAMP(ends)) ))
								WHERE $where GROUP BY $set->_table.id)";
					break;
				case "images":
					$ordering = $Shopp->Settings->get('product_image_order');
					if (empty($ordering)) $ordering = "ASC";
					$orderby = $Shopp->Settings->get('product_image_orderby');

					$sortorder = "0";
					if ($orderby == "sortorder" || $orderby == "created") {
						if ($orderby == "created") $orderby = "UNIX_TIMESTAMP(created)";
						switch ($ordering) {
							case "DESC": $sortorder = "$orderby*-1"; break;
							case "RAND": $sortorder = "RAND()"; break;
							default: $sortorder = "$orderby";
						}
					}

					$alphaorder = "''";
					if ($orderby == "name") {
						switch ($ordering) {
							case "DESC": $alphaorder = "$orderby"; break;
							case "RAND": $alphaorder = "RAND()"; break;
							default: $alphaorder = "$orderby";
						}
					}

					foreach ($ids as $id) $where .= ((!empty($where))?" OR ":"")."parent=$id";
					$where = "($where) AND context='product'";
					$query .= "(SELECT '$set->_table' as dataset,parent AS product,'$rtype' AS rtype,$alphaorder AS alphaorder,$sortorder AS sortorder,$cols FROM $set->_table WHERE $where ORDER BY $orderby)";
					break;
				case "specs":
					foreach ($ids as $id) $where .= ((!empty($where))?" OR ":"")."product=$id";
					$query .= "(SELECT '$set->_table' as dataset,product,'$rtype' AS rtype,'' AS alphaorder,sortorder AS sortorder,$cols FROM $set->_table WHERE $where)";
					break;
				case "categories":
					foreach ($ids as $id) $where .= ((!empty($where))?" OR ":"")."catalog.product=$id";
					$where = "($where) AND catalog.category > 0";
					$query .= "(SELECT '$set->_table' as dataset,catalog.product AS product,'$rtype' AS rtype,$set->_table.name AS alphaorder,0 AS sortorder,$cols FROM {$Shopp->Catalog->_table} AS catalog LEFT JOIN $set->_table ON catalog.category=$set->_table.id WHERE $where)";
					break;
				case "tags":
					foreach ($ids as $id) $where .= ((!empty($where))?" OR ":"")."catalog.product=$id";
					$where = "($where) AND catalog.tag > 0";
					$query .= "(SELECT '$set->_table' as dataset,catalog.product AS product,'$rtype' AS rtype,$set->_table.name AS alphaorder,0 AS sortorder,$cols FROM {$Shopp->Catalog->_table} AS catalog LEFT JOIN $set->_table ON catalog.tag=$set->_table.id WHERE $where)";
					break;
			}
		}
		
		// Add order by columns
		$query .= " ORDER BY sortorder";
		// echo $query;
		// Execute the query
		$data = $db->query($query,AS_ARRAY);
		
		// Process the results into specific product object data in a product set
		
		foreach ($data as $row) {
			if (is_array($products) && isset($products[$row->product])) 
				$target = $products[$row->product];
			else $target = $this;

			$record = new stdClass(); $i = 0; $name = "";
			foreach ($Dataset[$row->rtype]->_datatypes AS $key => $datatype) {
				$column = 'c'.$i++;
				$record->{$key} = '';
				if ($key == "name") $name = $row->{$column};
				if (!empty($row->{$column})) {
					if (preg_match("/^[sibNaO](?:\:.+?\{.*\}$|\:.+;$|;$)/",$row->{$column}))
						$row->{$column} = unserialize($row->{$column});
					$record->{$key} = $row->{$column};
				}
			}
			$target->{$row->rtype}[] = $record;
			if (!empty($name)) {
				if (isset($target->{$row->rtype.'key'}[$name]))
					$target->{$row->rtype.'key'}[$name] = array($target->{$row->rtype.'key'}[$name],$record);
				else $target->{$row->rtype.'key'}[$name] = $record;
			}
		}
		
		if (is_array($products)) {
			foreach ($products as $product) if (!empty($product->prices)) $product->pricing();
			foreach ($products as $product) if (count($product->images) >= 3 && count($product->imagesets) <= 1)
					$product->imageset();
		} else {
			if (!empty($this->prices)) $this->pricing($options);
			if (count($this->images) >= 3 && count($this->imagesets) <= 1) $this->imageset();
		}
		
	} // end load_data()
		
	function pricing ($options = false) {
		global $Shopp;

		$variations = ($this->variations == "on");
		$freeshipping = true;
		$this->inventory = false;
		foreach ($this->prices as $i => &$price) {
			// Build secondary lookup table using the combined optionkey
			$this->pricekey[$price->optionkey] = $price;
			
			// Build third lookup table using the price id as the key
			$this->priceid[$price->id] = $price;
			if ($price->type == "N/A" || ($i > 0 && !$variations)) continue;
			
			// Boolean flag for custom product sales
			$price->onsale = false;
			if ($price->sale == "on" && $price->type != "N/A")
				$this->onsale = $price->onsale = true;
			
			$price->stocked = false;
			if ($price->inventory == "on" && $price->type != "N/A") {
				$this->stock += $price->stock;
				$this->inventory = $price->stocked = true;
			}
			
			if ($price->freeshipping == 0) $freeshipping = false;

			if ($price->onsale) $price->promoprice = (float)$price->saleprice;
			else $price->promoprice = (float)$price->price;

			if ((isset($price->promos) && $price->promos == 'enabled')) {
				if ($price->percentoff > 0) {
					$price->promoprice = $price->promoprice - ($price->promoprice * ($price->percentoff/100));
					$this->onsale = $price->onsale = true;
				}
				if ($price->amountoff > 0) {
					$price->promoprice = $price->promoprice - $price->amountoff;
					$this->onsale = $price->onsale = true;;
				}
			}

			// Grab price and saleprice ranges (minimum - maximum)
			if ($price->type != "N/A") {
				if (!$price->price) $price->price = 0;
					if (!isset($this->pricerange['min']['price'])) 
						$this->pricerange['min']['price'] = $this->pricerange['max']['price'] = $price->price;
					if ($this->pricerange['min']['price'] > $price->price) 
						$this->pricerange['min']['price'] = $price->price;
					if ($this->pricerange['max']['price'] < $price->price) 
						$this->pricerange['max']['price'] = $price->price;

					if (!isset($this->pricerange['min']['saleprice'])) 
						$this->pricerange['min']['saleprice'] = $this->pricerange['max']['saleprice'] = $price->promoprice;
					if ($this->pricerange['min']['saleprice'] > $price->promoprice) 
						$this->pricerange['min']['saleprice'] = $price->promoprice;
					if ($this->pricerange['max']['saleprice'] < $price->promoprice) 
						$this->pricerange['max']['saleprice'] = $price->promoprice;
				
				if ($price->stocked) {
					if (!isset($this->pricerange['min']['stock']))
						$this->pricerange['min']['stock'] = $this->pricerange['max']['stock'] = $price->stock;
					if ($this->pricerange['min']['stock'] > $price->stock) 
						$this->pricerange['min']['stock'] = $price->stock;
					if ($this->pricerange['max']['stock'] < $price->stock) 
						$this->pricerange['max']['stock'] = $price->stock;
				}
				
			}
			
			// Determine savings ranges
			if ($price->onsale 
				&& isset($this->pricerange['min']['price']) 
				&& isset($this->pricerange['min']['saleprice'])) {

				if (!isset($this->pricerange['min']['saved'])) {
					$this->pricerange['min']['saved'] = $price->price;
					$this->pricerange['min']['savings'] = 100;
					$this->pricerange['max']['saved'] = 0;
					$this->pricerange['max']['savings'] = 0;
				}

				if ($price->price - $price->promoprice < $this->pricerange['min']['saved'])
						$this->pricerange['min']['saved'] =
							$price->price - $price->promoprice;

				if ($price->price - $price->promoprice > $this->pricerange['max']['saved'])
						$this->pricerange['max']['saved'] =
							$price->price - $price->promoprice;
				
				// Find lowest savings percentage
				if ($price->onsale) {
					if ($this->pricerange['min']['saved']/$price->price < $this->pricerange['min']['savings'])
						$this->pricerange['min']['savings'] = ($this->pricerange['min']['saved']/$price->price)*100;
					if ($this->pricerange['max']['saved']/$price->price < $this->pricerange['min']['savings'])
						$this->pricerange['min']['savings'] = ($this->pricerange['max']['saved']/$price->price)*100;
				
					// Find highest savings percentage
					if ($this->pricerange['min']['saved']/$price->price > $this->pricerange['max']['savings'])
						$this->pricerange['max']['savings'] = ($this->pricerange['min']['saved']/$price->price)*100;
					if ($this->pricerange['max']['saved']/$price->price > $this->pricerange['max']['savings'])
						$this->pricerange['max']['savings'] = ($this->pricerange['max']['saved']/$price->price)*100;
				}
			}
			
			// Determine weight ranges
			if(!isset($this->weightrange['min'])) $this->weightrange = array('min'=>0,'max'=>0);
			if($price->weight && $price->weight > 0){
				if(!$this->weightrange['min'] || $price->weight < $this->weightrange['min']) 
					$this->weightrange['min'] = $price->weight; 
				if(!$this->weightrange['max'] || $price->weight > $this->weightrange['max']) 
					$this->weightrange['max'] = $price->weight;
			}

			if (defined('WP_ADMIN') && !isset($options['taxes'])) $options['taxes'] = true;
			if (defined('WP_ADMIN') && value_is_true($options['taxes']) && $price->tax == "on") { 
				$base = $Shopp->Settings->get('base_operations');
				if ($base['vat']) {
					$taxrate = $Shopp->Cart->taxrate();
					$price->price += $price->price*$taxrate;
					$price->saleprice += $price->saleprice*$taxrate;
				}
			}
			
		} // end foreach($price)
		if ($this->inventory && $this->stock <= 0) $this->outofstock = true;
		if ($freeshipping) $this->freeshipping = true;
	}
	
	function imageset () {
		global $Shopp;
		// Organize images into groupings by type
		$this->imagesets = array();
		foreach ($this->images as $key => &$image) {
			if (empty($this->imagesets[$image->datatype])) $this->imagesets[$image->datatype] = array();
			if ($image->id) {
				if (SHOPP_PERMALINKS) $image->uri = $Shopp->imguri.$image->id;
				else $image->uri = add_query_arg('shopp_image',$image->id,$Shopp->imguri);
			}
			$this->imagesets[$image->datatype][] = $image;
		}
		$this->thumbnail = $this->imagesets['thumbnail'][0];
		return true;
	}
	
	function merge_specs () {
		$merged = array();
		foreach ($this->specs as $key => $spec) {
			if (!isset($merged[$spec->name])) $merged[$spec->name] = $spec;
			else {
				if (!is_array($merged[$spec->name]->content)) 
					$merged[$spec->name]->content = array($merged[$spec->name]->content);
				$merged[$spec->name]->content[] = $spec->content;
			}
		}
		$this->specs = $merged;
	}
	
	function save_categories ($updates) {
		$db = DB::get();
		
		if (empty($updates)) $updates = array();
		
		$current = array();
		foreach ($this->categories as $category) $current[] = $category->id;

		$added = array_diff($updates,$current);
		$removed = array_diff($current,$updates);

		$table = DatabaseObject::tablename(Catalog::$table);

		if (!empty($added)) {
			foreach ($added as $id) {
				if (empty($id)) continue;
				$db->query("INSERT $table SET category='$id',product='$this->id',created=now(),modified=now()");
			}
		}
		
		if (!empty($removed)) {
			foreach ($removed as $id) {
				if (empty($id)) continue;
				$db->query("DELETE LOW_PRIORITY FROM $table WHERE category='$id' AND product='$this->id'"); 
			}
			
		}
		
	}

	function save_tags ($updates) {
		$db = DB::get();
		
		if (empty($updates)) $updates = array();
		$updates = stripslashes_deep($updates);
		
		$current = array();
		foreach ($this->tags as $tag) $current[] = $tag->name;
		
		$added = array_diff($updates,$current);
		$removed = array_diff($current,$updates);

		if (!empty($added)) {
			$catalog = DatabaseObject::tablename(Catalog::$table);
			$tagtable = DatabaseObject::tablename(Tag::$table);
			$where = "";
			foreach ($added as $tag) $where .= ($where == ""?"":" OR ")."name='".$db->escape($tag)."'";
			$results = $db->query("SELECT id,name FROM $tagtable WHERE $where",AS_ARRAY);
			$exists = array();
			foreach ($results as $tag) $exists[$tag->id] = $tag->name;

			foreach ($added as $tag) {
				if (empty($tag)) continue; // No empty tags
				$tagid = array_search($tag,$exists);

				if (!$tagid) {
					$Tag = new Tag();
					$Tag->name = $tag;
					$Tag->save();
					$tagid = $Tag->id;
				}

				if (!empty($tagid))
					$db->query("INSERT $catalog SET tag='$tagid',product='$this->id',created=now(),modified=now()");
					
			}
		}

		if (!empty($removed)) {
			$catalog = DatabaseObject::tablename(Catalog::$table);
			foreach ($removed as $tag) {
				$Tag = new Tag($tag,'name');
				if (!empty($Tag->id))
					$db->query("DELETE LOW_PRIORITY FROM $catalog WHERE tag='$Tag->id' AND product='$this->id'"); 
			}
		}

	}
			
	/**
	 * optionkey
	 * There is no Zul only XOR! */
	function optionkey ($ids=array(),$deprecated=false) {
		if ($deprecated) $factor = 101;
		else $factor = 7001;
		if (empty($ids)) return 0;
		foreach ($ids as $set => $id) 
			$key = $key ^ ($id*$factor);
		return $key;
	}
	
	/**
	 * save_imageorder()
	 * Updates the sortorder of image assets (source, featured and thumbnails)
	 * based on the provided array of image ids */
	function save_imageorder ($ordering) {
		$db = DB::get();
		$table = DatabaseObject::tablename(Asset::$table);
		foreach ($ordering as $i => $id) 
			$db->query("UPDATE LOW_PRIORITY $table SET sortorder='$i' WHERE id='$id' OR src='$id'");
		return true;
	}
	
	/**
	 * link_images()
	 * Updates the product id of the images to link to the product 
	 * when the product being saved is new (has no previous id assigned) */
	function link_images ($images) {
		$db = DB::get();
		$table = DatabaseObject::tablename(Asset::$table);
		
		$query = "";
		foreach ($images as $i => $id) {
			if (empty($id)) continue;
			if ($i > 0) $query .= " OR ";
			$query .= "id=$id OR src=$id";
		}
		
		if (empty($query)) return false;
		else $query = "UPDATE $table SET parent='$this->id',context='product' WHERE ".$query;
		
		$db->query($query);
		
		return true;
	}
	
	/**
	 * update_images()
	 * Updates the image details for an entire image set (thumbnail, small, image) */
	function update_images ($images) {
		if (!is_array($images)) return false;

		$db = DB::get();
		$table = DatabaseObject::tablename(Asset::$table);

		foreach($images as $i => $img) {
			$query = "SELECT imgs.id FROM $table AS thumb LEFT JOIN $table AS imgs ON thumb.src=imgs.src OR thumb.src=imgs.id WHERE thumb.id={$img['id']}";
			$imageset = $db->query($query);
			foreach ($imageset as $is) {
				$Image = new Asset();
				unset($Image->_datatypes['data'],$Image->data);
				$Image->load($is->id);
				$Image->properties['title'] = $img['title'];
				$Image->properties['alt'] = $img['alt'];
				$Image->save();
			}
		}			
		
		return true;
	}
	
	
	/**
	 * delete_images()
	 * Delete provided array of image ids, removing the source image and
	 * all related images (small and thumbnails) */
	function delete_images ($images) {
		$Images = new Asset();
		$Images->deleteset($images,'image');
		return true;
	}
	
	/**
	 * Deletes the record associated with this object */
	function delete () {
		$db = DB::get();
		$id = $this->{$this->_key};
		if (empty($id)) return false;
		
		// Delete from categories
		$table = DatabaseObject::tablename(Catalog::$table);
		$db->query("DELETE LOW_PRIORITY FROM $table WHERE product='$id'");

		// Delete prices
		$table = DatabaseObject::tablename(Price::$table);
		$db->query("DELETE LOW_PRIORITY FROM $table WHERE product='$id'");

		// Delete specs
		$table = DatabaseObject::tablename(Spec::$table);
		$db->query("DELETE LOW_PRIORITY FROM $table WHERE product='$id'");
		
		// Delete images/files
		$table = DatabaseObject::tablename(Asset::$table);

		// Delete images
		$images = array();
		$src = $db->query("SELECT id FROM $table WHERE parent='$id' AND context='product' AND datatype='image'",AS_ARRAY);
		foreach ($src as $img) $images[] = $img->id;
		$this->delete_images($images);
		
		// Delete product downloads (but keep the file if on file system)
		$db->query("DELETE LOW_PRIORITY FROM $table WHERE parent='$id' AND context='product'");

		// Delete record
		$db->query("DELETE FROM $this->_table WHERE $this->_key='$id'");

	}
	
	function duplicate () {
		$db =& DB::get();
		
		$this->load_data(array('prices','specs','categories','tags','images','taxes'=>'false'));
		$this->id = '';
		$this->name = $this->name.' '.__('copy','Shopp');
		$this->slug = sanitize_title_with_dashes($this->name);

		// Check for an existing product slug
		$existing = $db->query("SELECT slug FROM $this->_table WHERE slug='$this->slug' LIMIT 1");
		if ($existing) {
			$suffix = 2;
			while($existing) {
				$altslug = substr($this->slug, 0, 200-(strlen($suffix)+1)). "-$suffix";
				$existing = $db->query("SELECT slug FROM $this->_table WHERE slug='$altslug' LIMIT 1");
				$suffix++;
			}
			$this->slug = $altslug;
		}
		$this->created = '';
		$this->modified = '';
		
		$this->save();
		
		// Copy prices
		foreach ($this->prices as $price) {
			$Price = new Price();
			$Price->updates($price,array('id','product','created','modified'));
			$Price->product = $this->id;
			$Price->save();
		}
		
		// Copy sepcs
		foreach ($this->specs as $spec) {
			$Spec = new Spec();
			$Spec->updates($spec,array('id','product','created','modified'));
			$Spec->product = $this->id;
			$Spec->save();
		}
		
		// Copy categories
		$categories = array();
		foreach ($this->categories as $category) $categories[] = $category->id;
		$this->categories = array();
		$this->save_categories($categories);

		// Copy tags
		$taglist = array();
		foreach ($this->tags as $tag) $taglist[] = $tag->name;
		$this->tags = array();
		$this->save_tags($taglist);

		// // Copy product images
		$template = new Asset();
		$columns = array(); $values = array();
		foreach ($template->_datatypes as $name => $type) {
			$colname = $name;
			$columns[$colname] = $name;
			if ($name == "id") $name = "''";
			if ($name == "parent") $name = "'$this->id'";
			if ($name == "created" || $name == "modified") $name = "now()";
			$values[$colname] = $name;
		}
		$sets = array('image','small','thumbnail');
		$images = array();
		foreach ($sets as $set) {
			foreach ($this->imagesets[$set] as $image) {
				if (isset($images[$image->src])) $values['src'] = $images[$image->src];
				$id = $db->query("INSERT $template->_table (".join(',',$columns).") SELECT ".join(",",$values)." FROM $template->_table WHERE id=$image->id");
				if ($set == "image") {
					$images[$image->id] = $id;
					$db->query("UPDATE $template->_table SET src=$id WHERE id=$id LIMIT 1");
				}
			}
		}
		
	}
	
	function tag ($property,$options=array()) {
		global $Shopp;
				
		switch ($property) {
			case "link": 
			case "url": 
				if (SHOPP_PERMALINKS) $url = esc_url(add_query_arg($_GET,$Shopp->shopuri.urldecode($this->slug)."/"));
				else $url = add_query_arg('shopp_pid',$this->id,$Shopp->shopuri);
				return $url;
				break;
			case "found": 
				if (empty($this->id)) return false;
				$load = array('prices','images','specs');
				if (isset($options['load'])) $load = explode(",",$options['load']);
				$this->load_data($load);
				return true;
				break;
			case "id": return $this->id; break;
			case "name": return $this->name; break;
			case "slug": return $this->slug; break;
			case "summary": return $this->summary; break;
			case "description": 
				return apply_filters('shopp_product_description',$this->description);
			case "isfeatured": 
			case "is-featured":
				return ($this->featured == "on"); break;
			case "price":
				if (empty($this->prices)) $this->load_data(array('prices'));
				if (!isset($options['taxes'])) $options['taxes'] = null;

				// $taxrate = 0;
				// $taxes = false;
				// $base = $Shopp->Settings->get('base_operations');
				// if ($base['vat']) $taxes = true;
				// if (isset($options['taxes'])) $taxes = (value_is_true($options['taxes']));
				// if ($taxes) $taxrate = $Shopp->Cart->taxrate();
				
				if (count($this->options) > 0) {
					$taxrate = shopp_taxrate($options['taxes']);
					if ($this->pricerange['min']['price'] == $this->pricerange['max']['price'])
						return money($this->pricerange['min']['price'] + ($this->pricerange['min']['price']*$taxrate));
					else {
						if (!empty($options['starting'])) return $options['starting']." ".money($this->pricerange['min']['price']+($this->pricerange['min']['price']*$taxrate));
						return money($this->pricerange['min']['price']+($this->pricerange['min']['price']*$taxrate))." &mdash; ".money($this->pricerange['max']['price'] + ($this->pricerange['max']['price']*$taxrate));
					}
				} else {
					$taxrate = shopp_taxrate($options['taxes'],$this->prices[0]->tax);
					return money($this->prices[0]->price + ($this->prices[0]->price*$taxrate));
				}
				break;
			case "weight":
				if(empty($this->prices)) $this->load_data(array('prices'));
				$unit = (isset($options['units']) && !value_is_true($options['units'])? 
					false : $Shopp->Settings->get('weight_unit'));
				if(!$this->weightrange['min']) return false;
				
				$string = ($this->weightrange['min'] == $this->weightrange['max']) ? 
					round($this->weightrange['min'],3) :  
					round($this->weightrange['min'],3) . " - " . round($this->weightrange['max'],3);
				$string .= ($unit) ? " $unit" : "";
				return $string;
				break;
			case "onsale":
				if (empty($this->prices)) $this->load_data(array('prices'));
				if (empty($this->prices)) return false;
				return $this->onsale;
				
				// if (empty($this->prices)) $this->load_prices();
				$sale = false;
				if (count($this->prices) > 1) {
					foreach($this->prices as $pricetag) 
						if (isset($pricetag->onsale) && $pricetag->onsale == "on") $sale = true;
					return $sale;
				} else return ($this->prices[0]->onsale == "on")?true:false;
				break;
			case "saleprice":
				if (empty($this->prices)) $this->load_data(array('prices'));
				if (!isset($options['taxes'])) $options['taxes'] = null;
				$pricetag = 'price';

				if ($this->onsale) $pricetag = 'saleprice';
				if (count($this->options) > 0) {
					$taxrate = shopp_taxrate($options['taxes']);
					if ($this->pricerange['min'][$pricetag] == $this->pricerange['max'][$pricetag])
						return money($this->pricerange['min'][$pricetag]+($this->pricerange['min'][$pricetag]*$taxrate)); // No price range
					else {
						if (!empty($options['starting'])) return $options['starting']." ".money($this->pricerange['min'][$pricetag]+($this->pricerange['min'][$pricetag]*$taxrate));
						return money($this->pricerange['min'][$pricetag]+($this->pricerange['min'][$pricetag]*$taxrate))." &mdash; ".money($this->pricerange['max'][$pricetag]+($this->pricerange['max'][$pricetag]*$taxrate));
					}
				} else {
					$taxrate = shopp_taxrate($options['taxes'],$this->prices[0]->tax);
					return money($this->prices[0]->promoprice+($this->prices[0]->promoprice*$taxrate));
				}
				break;
			case "has-savings": return ($this->onsale && $this->pricerange['min']['saved'] > 0)?true:false; break;
			case "savings":
				if (empty($this->prices)) $this->load_data(array('prices'));
				if (!isset($options['taxes'])) $options['taxes'] = null;

				$taxrate = shopp_taxrate($options['taxes']);

				if (!isset($options['show'])) $options['show'] = '';
				if ($options['show'] == "%" || $options['show'] == "percent") {
					if ($this->options > 1) {
						if (round($this->pricerange['min']['savings']) == round($this->pricerange['max']['savings']))
							return percentage($this->pricerange['min']['savings']); // No price range
						else return percentage($this->pricerange['min']['savings'])." &mdash; ".percentage($this->pricerange['max']['savings']);
					} else return percentage($this->pricerange['max']['savings']);
				} else {
					if ($this->options > 1) {
						if ($this->pricerange['min']['saved'] == $this->pricerange['max']['saved'])
							return money($this->pricerange['min']['saved']+($this->pricerange['min']['saved']*$taxrate)); // No price range
						else return money($this->pricerange['min']['saved']+($this->pricerange['min']['saved']*$taxrate))." &mdash; ".money($this->pricerange['max']['saved']+($this->pricerange['max']['saved']*$taxrate));
					} else return money($this->pricerange['max']['saved']+($this->pricerange['max']['saved']*$taxrate));
				}
				break;
			case "freeshipping":
				if (empty($this->prices)) $this->load_data(array('prices'));
				// if (empty($this->prices)) $this->load_prices();
				return $this->freeshipping;
			case "thumbnail":
				if (empty($this->imagesets)) $this->load_data(array('images'));
				if (empty($options['class'])) $options['class'] = '';
				else $options['class'] = ' class="'.$options['class'].'"';
				if (isset($this->thumbnail)) {
					$img = $this->thumbnail;
					$title = !empty($img->properties['title'])?' title="'.attribute_escape($img->properties['title']).'"':'';

					$width = (isset($options['width']))?$options['width']:$img->properties['width'];
					$height = (isset($options['height']))?$options['height']:$img->properties['height'];

					if (isset($options['width']) && !isset($options['height'])) {
						$scale = $width/$img->properties['width'];
						$height = round($img->properties['height']*$scale);
					}
					if (isset($options['height']) && !isset($options['width'])) {
						$scale = $height/$img->properties['height'];
						$width = round($img->properties['width']*$scale);
					}
					
					if (!empty($options['title'])) $title = ' title="'.attribute_escape($options['title']).'"';
					$alt = attribute_escape(!empty($img->properties['alt'])?$img->properties['alt']:$this->name);
					return '<img src="'.$img->uri.'"'.$title.' alt="'.$alt.'"  width="'.$width.'" height="'.$height.'" '.$options['class'].' />'; break;
				}
				break;
			case "hasimages": 
			case "has-images": 
				if (empty($options['type'])) $options['type'] = "thumbnail";
				if (empty($this->images)) $this->load_data(array('images'));
				if (!empty($this->imagesets[$options['type']])) {
					$this->imageset = &$this->imagesets[$options['type']];
					return true;
				} else return false;
				break;
			case "images":
				if (!$this->imageset) return false;
				if (!$this->imageloop) {
					reset($this->imageset);
					$this->imageloop = true;
				} else next($this->imageset);

				if (current($this->imageset)) return true;
				else {
					$this->imageloop = false;
					$this->imageset = false;
					return false;
				}
				break;
			case "image":			
				$img = current($this->imageset);
				if (isset($options['property'])) {
					switch (strtolower($options['property'])) {
						case "url": return $img->uri;
						case "width": return $img->properties['width'];
						case "height": return $img->properties['height'];
						case "title": return attribute_escape($img->properties['title']);
						case "alt": return attribute_escape($img->properties['alt']);
						default: return $img->id;
					}
				}
				if (!isset($options['class'])) $options['class'] = false;
				if (!empty($options['class'])) $options['class'] = ' class="'.$options['class'].'"';

				$title = !empty($img->properties['title'])?' title="'.attribute_escape($img->properties['title']).'"':'';

				$width = (isset($options['width']))?$options['width']:$img->properties['width'];
				$height = (isset($options['height']))?$options['height']:$img->properties['height'];

				if (isset($options['width']) && !isset($options['height'])) {
					$scale = $width/$img->properties['width'];
					$height = round($img->properties['height']*$scale);
				}
				if (isset($options['height']) && !isset($options['width'])) {
					$scale = $height/$img->properties['height'];
					$width = round($img->properties['width']*$scale);
				}

				if (!empty($options['title'])) $title = ' title="'.attribute_escape($options['title']).'"';
				$alt = attribute_escape(!empty($img->properties['alt'])?$img->properties['alt']:$this->name);

				$string = "";
				if (!isset($options['zoomfx'])) $options['zoomfx'] = "shopp-thickbox";
				if (!empty($options['zoom'])) $string .= '<a href="'.$Shopp->imguri.$img->src.'/'.str_replace('small_','',$img->name).'" class="'.$options['zoomfx'].'" rel="product-gallery">';
				$string .= '<img src="'.$img->uri.'"'.$title.' alt="'.$alt.'" width="'.$width.'" height="'.$height.'" '.$options['class'].' />';
				if (!empty($options['zoom'])) $string .= "</a>";
				return $string;
				break;
			case "gallery":
				if (empty($this->images)) $this->load_data(array('images'));
				if (!isset($options['zoomfx'])) $options['zoomfx'] = "shopp-thickbox";
				if (!isset($options['preview'])) $options['preview'] = "click";
				
				$previews = '<ul class="previews">';
				$firstPreview = true;
				if (!empty($this->imagesets['small'])) {
					foreach ($this->imagesets['small'] as $img) {
						if ($firstPreview) {
							$previews .= '<li id="preview-fill"'.(($firstPreview)?' class="fill"':'').'>';
							$previews .= '<img src="'.$Shopp->uri.'/core/ui/icons/clear.png'.'" alt="'.$img->datatype.'" width="'.$img->properties['width'].'" height="'.$img->properties['height'].'" />';
							$previews .= '</li>';
						}
						$title = !empty($img->properties['title'])?' title="'.attribute_escape($img->properties['title']).'"':'';
						$alt = attribute_escape(!empty($img->properties['alt'])?$img->properties['alt']:$img->name);
						$rel = (isset($options['rel']) && $options['rel'])?' rel="product_'.$this->id.'_gallery"':'';
						
						$previews .= '<li id="preview-'.$img->src.'"'.(($firstPreview)?' class="active"':'').'>';
						$previews .= '<a href="'.$Shopp->imguri.$img->src.'/'.str_replace('small_','',$img->name).'" class="product_'.$this->id.'_gallery '.$options['zoomfx'].'"'.$rel.'>';
						$previews .= '<img src="'.$Shopp->imguri.$img->id.'"'.$title.' alt="'.$alt.'" width="'.$img->properties['width'].'" height="'.$img->properties['height'].'" />';
						$previews .= '</a>';
						$previews .= '</li>';
						$firstPreview = false;
					}
				}
				$previews .= '</ul>';

				$thumbs = "";
				if (isset($this->imagesets['thumbnail']) && count($this->imagesets['thumbnail']) > 1) {
					$thumbsize = 32;
					if (isset($options['thumbsize'])) $thumbsize = $options['thumbsize'];
					$thumbwidth = $thumbsize;
					$thumbheight = $thumbsize;
					if (isset($options['thumbwidth'])) $thumbwidth = $options['thumbwidth'];
					if (isset($options['thumbheight'])) $thumbheight = $options['thumbheight'];

					$firstThumb = true;
					$thumbs = '<ul class="thumbnails">';
					foreach ($this->imagesets['thumbnail'] as $img) {
						if (isset($options['thumbwidth']) && !isset($options['thumbheight'])) {
							$scale = $thumbwidth/$img->properties['width'];
							$thumbheight = round($img->properties['height']*$scale);
						}

						if (isset($options['thumbheight']) && !isset($options['thumbwidth'])) {
							$scale = $thumbheight/$img->properties['height'];
							$thumbwidth = round($img->properties['width']*$scale);
						}

						$title = !empty($img->properties['title'])?' title="'.attribute_escape($img->properties['title']).'"':'';
						$alt = attribute_escape(!empty($img->properties['alt'])?$img->properties['alt']:$img->name);

						$thumbs .= '<li id="thumbnail-'.$img->src.'" class="preview-'.$img->src.(($firstThumb)?' first':' test').'">';
						$thumbs .= '<img src="'.$Shopp->imguri.$img->id.'"'.$title.' alt="'.$alt.'" width="'.$thumbwidth.'" height="'.$thumbheight.'" />';
						$thumbs .= '</li>';
						$firstThumb = false;						
					}
					$thumbs .= '</ul>';
				}

				$result = '<div id="gallery-'.$this->id.'" class="gallery">'.$previews.$thumbs.'</div>';
				$result .= '<script type="text/javascript"><!--
					jQuery(document).ready( function() {  shopp_gallery("#gallery-'.$this->id.'","'.$options['preview'].'"); }); 
					// --></script>';
				return $result;
				break;
			case "has-categories": 
				if (empty($this->categories)) $this->load_data(array('categories'));
				if (count($this->categories) > 0) return true; else return false; break;
			case "categories":			
				if (!$this->categoryloop) {
					reset($this->categories);
					$this->categoryloop = true;
				} else next($this->categories);

				if (current($this->categories)) return true;
				else {
					$this->categoryloop = false;
					return false;
				}
				break;
			case "in-category": 
				if (empty($this->categories)) $this->load_data(array('categories'));
				if (isset($options['id'])) $field = "id";
				if (isset($options['name'])) $field = "name";
				if (isset($options['slug'])) $field = "slug";
				foreach ($this->categories as $category)
					if ($category->{$field} == $options[$field]) return true;
				return false;
			case "category":
				$category = current($this->categories);
				if (isset($options['show'])) {
					if ($options['show'] == "id") return $category->id;
					if ($options['show'] == "slug") return $category->slug;
				}
				return $category->name;
				break;
			case "has-tags": 
				if (empty($this->tags)) $this->load_data(array('tags'));
				if (count($this->tags) > 0) return true; else return false; break;
			case "tags":
				if (!$this->tagloop) {
					reset($this->tags);
					$this->tagloop = true;
				} else next($this->tags);

				if (current($this->tags)) return true;
				else {
					$this->tagloop = false;
					return false;
				}
				break;
			case "tagged": 
				if (empty($this->tags)) $this->load_data(array('tags'));
				if (isset($options['id'])) $field = "id";
				if (isset($options['name'])) $field = "name";
				foreach ($this->tags as $tag)
					if ($tag->{$field} == $options[$field]) return true;
				return false;
			case "tag":
				$tag = current($this->tags);
				if (isset($options['show'])) {
					if ($options['show'] == "id") return $tag->id;
				}
				return $tag->name;
				break;
			case "has-specs": 
				if (empty($this->specs)) $this->load_data(array('specs'));
				if (count($this->specs) > 0) {
					$this->merge_specs();
					return true;
				} else return false; break;
			case "specs":			
				if (!$this->specloop) {
					reset($this->specs);
					$this->specloop = true;
				} else next($this->specs);
				
				if (current($this->specs)) return true;
				else {
					$this->specloop = false;
					return false;
				}
				break;
			case "spec":
				$string = "";
				$separator = ": ";
				$delimiter = ", ";
				if (isset($options['separator'])) $separator = $options['separator'];
				if (isset($options['delimiter'])) $separator = $options['delimiter'];

				$spec = current($this->specs);
				if (is_array($spec->content)) $spec->content = join($delimiter,$spec->content);
				
				if (isset($options['name']) 
					&& !empty($options['name']) 
					&& isset($this->specskey[$options['name']])) {
						$spec = $this->specskey[$options['name']];
						if (is_array($spec)) {
							if (isset($options['index'])) {
								foreach ($spec as $index => $entry) 
									if ($index+1 == $options['index']) 
										$content = $entry->content;
							} else {
								foreach ($spec as $entry) $contents[] = $entry->content;
								$content = join($delimiter,$contents);
							}
						} else $content = $spec->content;
					$string = apply_filters('shopp_product_spec',$content);
					return $string;
				}
				
				if (isset($options['name']) && isset($options['content']))
					$string = "{$spec->name}{$separator}".apply_filters('shopp_product_spec',$spec->content);
				elseif (isset($options['name'])) $string = $spec->name;
				elseif (isset($options['content'])) $string = apply_filters('shopp_product_spec',$spec->content);
				else $string = "{$spec->name}{$separator}".apply_filters('shopp_product_spec',$spec->content);
				return $string;
				break;
			case "has-variations":
				return ($this->variations == "on" && !empty($this->options)); break;
			case "variations":
				
				$string = "";

				if (!isset($options['mode'])) {
					if (!$this->priceloop) {
						reset($this->prices);
						$this->priceloop = true;
					} else next($this->prices);
					$thisprice = current($this->prices);

					if ($thisprice && $thisprice->type == "N/A")
						next($this->prices);

					if (current($this->prices)) return true;
					else {
						$this->priceloop = false;
						return false;
					}
					return true;
				}

				if ($this->outofstock) return false; // Completely out of stock, hide menus
				if (!isset($options['taxes'])) $options['taxes'] = null;
				
				$defaults = array(
					'defaults' => '',
					'disabled' => 'show',
					'before_menu' => '',
					'after_menu' => ''
					);
					
				$options = array_merge($defaults,$options);

				if (!isset($options['label'])) $options['label'] = "on";
				if (!isset($options['required'])) $options['required'] = __('You must select the options for this item before you can add it to your shopping cart.','Shopp');
				if ($options['mode'] == "single") {
					if (!empty($options['before_menu'])) $string .= $options['before_menu']."\n";
					if (value_is_true($options['label'])) $string .= '<label for="product-options'.$this->id.'">Options: </label> '."\n";

					$string .= '<select name="products['.$this->id.'][price]" id="product-options'.$this->id.'">';
					if (!empty($options['defaults'])) $string .= '<option value="">'.$options['defaults'].'</option>'."\n";

					foreach ($this->prices as $pricetag) {
						if ($pricetag->context != "variation") continue;
						
						$taxrate = shopp_taxrate($options['taxes'],$pricetag->tax);
						$currently = ($pricetag->sale == "on")?$pricetag->promoprice:$pricetag->price;
						$disabled = ($pricetag->inventory == "on" && $pricetag->stock == 0)?' disabled="disabled"':'';

						$price = '  ('.money($currently).')';
						if ($pricetag->type != "N/A")
							$string .= '<option value="'.$pricetag->id.'"'.$disabled.'>'.$pricetag->label.$price.'</option>'."\n";
					}

					$string .= '</select>';
					if (!empty($options['after_menu'])) $string .= $options['after_menu']."\n";

				} else {
					$taxrate = shopp_taxrate($options['taxes'],true);
					ob_start();
					?>
					<script type="text/javascript">
					<!--
					(function($) {
						$(document).ready(function () {
							productOptions[<?php echo $this->id; ?>] = new Array();
							productOptions[<?php echo $this->id; ?>]['pricing'] = <?php echo json_encode($this->pricekey); ?>;
							options_default = <?php echo (!empty($options['defaults']))?'true':'false'; ?>;
							options_required = "<?php echo $options['required']; ?>";
							
							productOptions[<?php echo $this->id; ?>]['menu'] = new ProductOptionsMenus('select<?php if (isset($Shopp->Category->slug)) echo ".category-".$Shopp->Category->slug; ?>.product<?php echo $this->id; ?>',<?php echo ($options['disabled'] == "hide")?"true":"false"; ?>,productOptions[<?php echo $this->id; ?>]['pricing'],<?php echo empty($taxrate)?'0':$taxrate; ?>);
						});
					})(jQuery)
					//-->
					</script>
					<?php
					$script = ob_get_contents();
					ob_end_clean();

					$options['after_menu'] = $script.$options['after_menu'];
					if (isset($this->options['variations'])) {
						foreach ($this->options['variations'] as $id => $menu) {
							if (!empty($options['before_menu'])) $string .= $options['before_menu']."\n";
							if (value_is_true($options['label'])) $string .= '<label for="options-'.$id.'">'.$menu['menu'].'</label> '."\n";

							$string .= '<select name="products['.$this->id.'][options][]" class="product'.$this->id.' options">';
							if (!empty($options['defaults'])) $string .= '<option value="">'.$options['defaults'].'</option>'."\n";
							foreach ($menu['label'] as $key => $option)
								$string .= '<option value="'.$menu['id'][$key].'">'.$option.'</option>'."\n";

							$string .= '</select>';
							if (!empty($options['after_menu'])) $string .= $options['after_menu']."\n";
						}
					} else {
						foreach ($this->options as $id => $menu) {
							if (!empty($options['before_menu'])) $string .= $options['before_menu']."\n";
							if (value_is_true($options['label'])) $string .= '<label for="options-'.$menu['id'].'">'.$menu['name'].'</label> '."\n";
							$category_class = isset($Shopp->Category->slug)?'category-'.$Shopp->Category->slug:'';
							$string .= '<select name="products['.$this->id.'][options][]" class="'.$category_class.' product'.$this->id.' options" id="options-'.$menu['id'].'">';
							if (!empty($options['defaults'])) $string .= '<option value="">'.$options['defaults'].'</option>'."\n";
							foreach ($menu['options'] as $key => $option)
								$string .= '<option value="'.$option['id'].'">'.$option['name'].'</option>'."\n";

							$string .= '</select>';
							if (!empty($options['after_menu'])) $string .= $options['after_menu']."\n";
						}
					}
					
				}

				return $string;
				break;
			case "variation":
				$variation = current($this->prices);
				if (!isset($options['taxes'])) $options['taxes'] = null;
				$taxrate = shopp_taxrate($options['taxes'],$variation->tax);
				
				$weightunit = (isset($options['units']) && !value_is_true($options['units']) ) ? false : $Shopp->Settings->get('weight_unit');
				
				$string = '';
				if (array_key_exists('id',$options)) $string .= $variation->id;
				if (array_key_exists('label',$options)) $string .= $variation->label;
				if (array_key_exists('type',$options)) $string .= $variation->type;
				if (array_key_exists('sku',$options)) $string .= $variation->sku;
				if (array_key_exists('price',$options)) $string .= money($variation->price+($variation->price*$taxrate));
				if (array_key_exists('saleprice',$options)) $string .= money($variation->saleprice+($variation->saleprice*$taxrate));
				if (array_key_exists('stock',$options)) $string .= $variation->stock;
				if (array_key_exists('weight',$options)) $string .= round($variation->weight, 3) . ($weightunit ? " $weightunit" : false);
				if (array_key_exists('shipfee',$options)) $string .= money(floatvalue($variation->shipfee));
				if (array_key_exists('sale',$options)) return ($variation->sale == "on");
				if (array_key_exists('shipping',$options)) return ($variation->shipping == "on");
				if (array_key_exists('tax',$options)) return ($variation->tax == "on");
				if (array_key_exists('inventory',$options)) return ($variation->inventory == "on");
				return $string;
				break;
			case "has-addons":
				if (isset($this->options['addons'])) return true; else return false; break;
				break;
			case "donation":
			case "amount":
			case "quantity":
				if ($this->outofstock) return false;
				if (!isset($options['value'])) $options['value'] = 1;
				if (!isset($options['input'])) $options['input'] = "text";
				if (!isset($options['labelpos'])) $options['labelpos'] = "before";
				if (!isset($options['label'])) $label ="";
				else $label = '<label for="quantity'.$this->id.'">'.$options['label'].'</label>';
				
				$result = "";
				if ($options['labelpos'] == "before") $result .= "$label ";
				
				if (!$this->priceloop) reset($this->prices);
				$variation = current($this->prices);

				if (isset($options['input']) && $options['input'] == "menu") {
					if (!isset($options['options'])) 
						$values = "1-15,20,25,30,40,50,75,100";
					else $values = $options['options'];
					if ($this->inventory && $this->pricerange['max']['stock'] == 0) return "";	
				
					if (strpos($values,",") !== false) $values = explode(",",$values);
					else $values = array($values);
					$qtys = array();
					foreach ($values as $value) {
						if (strpos($value,"-") !== false) {
							$value = explode("-",$value);
							if ($value[0] >= $value[1]) $qtys[] = $value[0];
							else for ($i = $value[0]; $i < $value[1]+1; $i++) $qtys[] = $i;
						} else $qtys[] = $value;
					}
					$result .= '<select name="products['.$this->id.'][quantity]" id="quantity-'.$this->id.'">';
					foreach ($qtys as $qty) {
						$amount = $qty;
						$selected = (isset($this->quantity))?$this->quantity:1;
						if ($variation->type == "Donation" && $variation->donation['var'] == "on") {
							if ($variation->donation['min'] == "on" && $amount < $variation->price) continue;
							$amount = money($amount);
							$selected = $variation->price;
						} else {
							if ($this->inventory && $amount > $this->pricerange['max']['stock']) continue;	
						}
						$result .= '<option'.(($qty == $selected)?' selected="selected"':'').' value="'.$qty.'">'.$amount.'</option>';
					}
					$result .= '</select>';
					if ($options['labelpos'] == "after") $result .= " $label";
					return $result;
				}
				if (valid_input($options['input'])) {
					if (!isset($options['size'])) $options['size'] = 3;
					if ($variation->type == "Donation" && $variation->donation['var'] == "on") {
						if ($variation->donation['min']) $options['value'] = $variation->price;
						$options['class'] .= " currency";
					}
					$result = '<input type="'.$options['input'].'" name="products['.$this->id.'][quantity]" id="quantity-'.$this->id.'"'.inputattrs($options).' />';
				}
				if ($options['labelpos'] == "after") $result .= " $label";
				return $result;
				break;
			case "input":
				if (!isset($options['type']) || 
					($options['type'] != "menu" && $options['type'] != "textarea" && !valid_input($options['type']))) $options['type'] = "text";
				if (!isset($options['name'])) return "";
				if ($options['type'] == "menu") {
					$result = '<select name="products['.$this->id.'][data]['.$options['name'].']" id="data-'.$options['name'].'-'.$this->id.'">';
					if (isset($options['options'])) 
						$menuoptions = preg_split('/,(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/',$options['options']);
					if (is_array($menuoptions)) {
						foreach($menuoptions as $option) {
							$selected = "";
							$option = trim($option,'"');
							if (isset($options['default']) && $options['default'] == $option) 
								$selected = ' selected="selected"';
							$result .= '<option value="'.$option.'"'.$selected.'>'.$option.'</option>';
						}
					}
					$result .= '</select>';
				} elseif ($options['type'] == "textarea") {
					if (isset($options['cols'])) $cols = ' cols="'.$options['cols'].'"';
					if (isset($options['rows'])) $rows = ' rows="'.$options['rows'].'"';
					$result .= '<textarea  name="products['.$this->id.'][data]['.$options['name'].']" id="data-'.$options['name'].'-'.$this->id.'"'.$cols.$rows.'>'.$options['value'].'</textarea>';
				} else {
					$result = '<input type="'.$options['type'].'" name="products['.$this->id.'][data]['.$options['name'].']" id="data-'.$options['name'].'-'.$this->id.'"'.inputattrs($options).' />';
				}
				
				return $result;
				break;
			case "outofstock":
				if ($this->outofstock) {
					$label = isset($options['label'])?$options['label']:$Shopp->Settings->get('outofstock_text');
					$string = '<span class="outofstock">'.$label.'</span>';
					return $string;
				} else return false;
				break;
			case "buynow":
				if (!isset($options['value'])) $options['value'] = __("Buy Now","Shopp");
			case "addtocart":
				if (!isset($options['class'])) $options['class'] = "addtocart";
				else $options['class'] .= " addtocart";
				if (!isset($options['value'])) $options['value'] = __("Add to Cart","Shopp");
				$string = "";
				
				if ($this->outofstock) {
					$string .= '<span class="outofstock">'.$Shopp->Settings->get('outofstock_text').'</span>';
					return $string;
				}
				
				$string .= '<input type="hidden" name="products['.$this->id.'][product]" value="'.$this->id.'" />';

				if (!empty($this->prices[0]) && $this->prices[0]->type != "N/A") 
					$string .= '<input type="hidden" name="products['.$this->id.'][price]" value="'.$this->prices[0]->id.'" />';

				if (!empty($Shopp->Category)) {
					if (SHOPP_PERMALINKS)
						$string .= '<input type="hidden" name="products['.$this->id.'][category]" value="'.$Shopp->Category->uri.'" />';
					else
						$string .= '<input type="hidden" name="products['.$this->id.'][category]" value="'.((!empty($Shopp->Category->id))?$Shopp->Category->id:$Shopp->Category->slug).'" />';
				}

				$string .= '<input type="hidden" name="cart" value="add" />';
				if (isset($options['ajax'])) {
					$options['class'] .= " ajax";
					$string .= '<input type="hidden" name="ajax" value="true" />';
					$string .= '<input type="button" name="addtocart" '.inputattrs($options).' />';					
				} else {
					$string .= '<input type="submit" name="addtocart" '.inputattrs($options).' />';					
				}
					
				return $string;
		}
		
		
	}

} // end Product class

?>