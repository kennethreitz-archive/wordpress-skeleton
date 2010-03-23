<?php
/**
 * Catalog class
 * 
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited,  9 April, 2008
 * @package shopp
 **/

require_once("Category.php");
require_once("Tag.php");

class Catalog extends DatabaseObject {
	static $table = "catalog";

	var $smarts = array("FeaturedProducts","BestsellerProducts","NewProducts","OnSaleProducts");
	var $categories = array();
	var $outofstock = false;
	
	function Catalog ($type="catalog") {
		global $Shopp;
		$this->init(self::$table);
		$this->type = $type;
		$this->outofstock = ($Shopp->Settings->get('outofstock_catalog') == "on");
	}
	
	function load_categories ($filtering=false,$showsmart=false,$results=false) {
		$db = DB::get();

		if (empty($filtering['columns'])) $filtering['columns'] = "cat.id,cat.parent,cat.name,cat.description,cat.uri,cat.slug,count(DISTINCT pd.id) AS total,IF(SUM(IF(pt.inventory='off',1,0) OR pt.inventory IS NULL)>0,'off','on') AS inventory, SUM(pt.stock) AS stock";
		if (!empty($filtering['limit'])) $filtering['limit'] = "LIMIT ".$filtering['limit'];
		else $filtering['limit'] = "";

		// if (!$this->outofstock) $filtering['where'] .= (empty($filtering['where'])?"":" AND ")."(pt.inventory='off' OR (pt.inventory='on' AND pt.stock > 0))";
		if (empty($filtering['where'])) $filtering['where'] = "true";
		
		if (empty($filtering['orderby'])) $filtering['orderby'] = "name";
		switch(strtolower($filtering['orderby'])) {
			case "id": $orderby = "cat.id"; break;
			case "slug": $orderby = "cat.slug"; break;
			case "count": $orderby = "total"; break;
			default: $orderby = "cat.name";
		}

		if (empty($filtering['order'])) $filtering['order'] = "ASC";
		switch(strtoupper($filtering['order'])) {
			case "DESC": $order = "DESC"; break;
			default: $order = "ASC";
		}
		
		$category_table = DatabaseObject::tablename(Category::$table);
		$product_table = DatabaseObject::tablename(Product::$table);
		$price_table = DatabaseObject::tablename(Price::$table);
		$query = "SELECT {$filtering['columns']} FROM $category_table AS cat LEFT JOIN $this->_table AS sc ON sc.category=cat.id LEFT JOIN $product_table AS pd ON sc.product=pd.id LEFT JOIN $price_table AS pt ON pt.product=pd.id AND pt.type != 'N/A' WHERE {$filtering['where']} GROUP BY cat.id ORDER BY cat.parent DESC,$orderby $order {$filtering['limit']}";
		$categories = $db->query($query,AS_ARRAY);

		if (count($categories) > 1) $categories = sort_tree($categories);		
		if ($results) return $categories;

		foreach ($categories as $category) {
			$category->outofstock = false;
			if (isset($category->inventory)) {
				if ($category->inventory == "on" && $category->stock == 0)
					$category->outofstock = true;

				if (!$this->outofstock && $category->outofstock) continue;
			}
			
			$this->categories[$category->id] = new Category();
			$this->categories[$category->id]->populate($category);

			if (isset($category->depth))
				$this->categories[$category->id]->depth = $category->depth;
			else $this->categories[$category->id]->depth = 0;

			if (isset($category->total))
				$this->categories[$category->id]->total = $category->total;
			else $this->categories[$category->id]->total = 0;

			if (isset($category->stock))
				$this->categories[$category->id]->stock = $category->stock;
			else $this->categories[$category->id]->stock = 0;


			if (isset($category->outofstock))
				$this->categories[$category->id]->outofstock = $category->outofstock;
			
			$this->categories[$category->id]->children = false;
			if ($category->total > 0 && isset($this->categories[$category->parent]))
				$this->categories[$category->parent]->children = true;
		}

		if ($showsmart == "before" || $showsmart == "after")
			$this->smart_categories($showsmart);
			
		return true;
	}
	
	function smart_categories ($method) {
		foreach ($this->smarts as $SmartCategory) {
			$category = new $SmartCategory(array("noload" => true));
			switch($method) {
				case "before": array_unshift($this->categories,$category); break; 
				default: array_push($this->categories,$category);
			}
		}
	}
	
	function load_tags ($limits=false) {
		$db = DB::get();
		
		if ($limits) $limit = " LIMIT {$limits[0]},{$limits[1]}";
		else $limit = "";
		
		$tagtable = DatabaseObject::tablename(Tag::$table);
		$query = "SELECT t.*,count(sc.product) AS products FROM $this->_table AS sc LEFT JOIN $tagtable AS t ON sc.tag=t.id WHERE sc.tag != 0 GROUP BY t.id ORDER BY t.name ASC$limit";
		$this->tags = $db->query($query,AS_ARRAY);
		return true;
	}
	
	function load_category ($category,$options=array()) {
		switch ($category) {
			case SearchResults::$_slug: return new SearchResults($options); break;
			case TagProducts::$_slug: return new TagProducts($options); break;
			case BestsellerProducts::$_slug: return new BestsellerProducts(); break;
			case CatalogProducts::$_slug: return new CatalogProducts(); break;
			case NewProducts::$_slug: return new NewProducts(); break;
			case FeaturedProducts::$_slug: return new FeaturedProducts(); break;
			case OnSaleProducts::$_slug: return new OnSaleProducts(); break;
			case RandomProducts::$_slug: return new RandomProducts(); break;
			default:
				$key = "id";
				if (!preg_match("/^\d+$/",$category)) $key = "uri";
				return new Category($category,$key);
		}
	}
	
	function tag ($property,$options=array()) {
		global $Shopp;

		$pages = $Shopp->Settings->get('pages');
		if (SHOPP_PERMALINKS) $path = $Shopp->shopuri;
		else $page = add_query_arg('page_id',$pages['catalog']['id'],$Shopp->shopuri);		
		
		switch ($property) {
			case "url": return $Shopp->link('catalog'); break;
			case "display":
			case "type": return $this->type; break;
			case "is-landing": 
			case "is-catalog": return (is_shopp_page('catalog') && $this->type == "catalog"); break;
			case "is-category": return (is_shopp_page('catalog') && $this->type == "category"); break;
			case "is-product": return (is_shopp_page('catalog') && $this->type == "product"); break;
			case "is-cart": return (is_shopp_page('cart')); break;
			case "is-checkout": return (is_shopp_page('checkout')); break;
			case "is-account": return (is_shopp_page('account')); break;
			case "tagcloud":
				if (!empty($options['levels'])) $levels = $options['levels'];
				else $levels = 7;
				if (empty($this->tags)) $this->load_tags();
				$min = -1; $max = -1;
				foreach ($this->tags as $tag) {
					if ($min == -1 || $tag->products < $min) $min = $tag->products;
					if ($max == -1 || $tag->products > $max) $max = $tag->products;
				}
				if ($max == 0) $max = 1;
				$string = '<ul class="shopp tagcloud">';
				foreach ($this->tags as $tag) {
					$level = floor((1-$tag->products/$max)*$levels)+1;
					if (SHOPP_PERMALINKS) $link = $path.'tag/'.urlencode($tag->name).'/';
					else $link = add_query_arg('shopp_tag',urlencode($tag->name),$page);
					$string .= '<li class="level-'.$level.'"><a href="'.$link.'" rel="tag">'.$tag->name.'</a></li> ';
				}
				$string .= '</ul>';
				return $string;
				break;
			case "has-categories": 
				if (empty($this->categories)) $this->load_categories(array('where'=>'true'),$options['showsmart']);
				if (count($this->categories) > 0) return true; else return false; break;
			case "categories":
				if (!$this->categoryloop) {
					reset($this->categories);
					$Shopp->Category = current($this->categories);
					$this->categoryloop = true;
				} else {
					$Shopp->Category = next($this->categories);
				}

				if (current($this->categories)) {
					$Shopp->Category = current($this->categories);
					return true;
				} else {
					$this->categoryloop = false;
					return false;
				}
				break;
			case "category-list":
				$defaults = array(
					'title' => '',
					'before' => '',
					'after' => '',
					'class' => '',
					'exclude' => '',
					'orderby' => 'name',
					'order' => 'ASC',
					'depth' => 0,
					'childof' => 0,
					'parent' => false,
					'showall' => false,
					'linkall' => false,
					'linkcount' => false,
					'dropdown' => false,
					'hierarchy' => false,
					'products' => false,
					'wraplist' => true,
					'showsmart' => false
					);
			
				$options = array_merge($defaults,$options);
				extract($options, EXTR_SKIP);

				$this->load_categories(array("where"=>"(pd.published='on' OR pd.id IS NULL)","orderby"=>$orderby,"order"=>$order),$showsmart);

				$string = "";
				$depthlimit = $depth;
				$depth = 0;
				$exclude = explode(",",$exclude);
				$classes = ' class="shopp_categories'.(empty($class)?'':' '.$class).'"';
				$wraplist = value_is_true($wraplist);
				
				if (value_is_true($dropdown)) {
					if (!isset($default)) $default = __('Select category&hellip;','Shopp');
					$string .= $title;
					$string .= '<form><select name="shopp_cats" id="shopp-categories-menu"'.$classes.'>';
					$string .= '<option value="">'.$default.'</option>';
					foreach ($this->categories as &$category) {
						if (!empty($category->id) && in_array($category->id,$exclude)) continue; // Skip excluded categories
						if ($category->total == 0 && !isset($category->smart)) continue; // Only show categories with products
						if ($depthlimit && $category->depth >= $depthlimit) continue;

						if (value_is_true($hierarchy) && $category->depth > $depth) {
							$parent = &$previous;
							if (!isset($parent->path)) $parent->path = '/'.$parent->slug;
						}
						
						if (value_is_true($hierarchy))
							$padding = str_repeat("&nbsp;",$category->depth*3);

						if (SHOPP_PERMALINKS) $link = $Shopp->shopuri.'category/'.$category->uri;
						else $link = add_query_arg('shopp_category',$category->id,$Shopp->shopuri);

						$total = '';
						if (value_is_true($products) && $category->total > 0) $total = ' ('.$category->total.')';

						$string .= '<option value="'.$link.'">'.$padding.$category->name.$total.'</option>';
						$previous = &$category;
						$depth = $category->depth;
						
					}
					$string .= '</select></form>';
					$string .= '<script type="text/javascript">';
					$string .= 'var menu = document.getElementById(\'shopp-categories-menu\');';
					$string .= 'if (menu) {';
					$string .= '	menu.onchange = function () {';
					$string .= '		document.location.href = this.options[this.selectedIndex].value;';
					$string .= '	}';
					$string .= '}';
					$string .= '</script>';
					
				} else {
					$string .= $title;
					if ($wraplist) $string .= '<ul'.$classes.'>';
					foreach ($this->categories as &$category) {
						if (!isset($category->total)) $category->total = 0;
						if (!isset($category->depth)) $category->depth = 0;
						if (!empty($category->id) && in_array($category->id,$exclude)) continue; // Skip excluded categories
						if ($depthlimit && $category->depth >= $depthlimit) continue;
						if (value_is_true($hierarchy) && $category->depth > $depth) {
							$parent = &$previous;
							if (!isset($parent->path)) $parent->path = $parent->slug;
							$string = substr($string,0,-5); // Remove the previous </li>
							$active = '';

							if (isset($Shopp->Category) && !empty($parent->slug)
									&& preg_match('/(^|\/)'.$parent->path.'(\/|$)/',$Shopp->Category->uri)) {
								$active = ' active';
							}
							
							$subcategories = '<ul class="children'.$active.'">';
							$string .= $subcategories;
						}

						if (value_is_true($hierarchy) && $category->depth < $depth) {
							for ($i = $depth; $i > $category->depth; $i--) {
								if (substr($string,strlen($subcategories)*-1) == $subcategories) {
									// If the child menu is empty, remove the <ul> to avoid breaking standards
									$string = substr($string,0,strlen($subcategories)*-1).'</li>';
								} else $string .= '</ul></li>';
							}
						}
					
						if (SHOPP_PERMALINKS) $link = $Shopp->shopuri.'category/'.$category->uri;
						else $link = add_query_arg('shopp_category',(!empty($category->id)?$category->id:$category->uri),$Shopp->shopuri);
					
						$total = '';
						if (value_is_true($products) && $category->total > 0) $total = ' <span>('.$category->total.')</span>';
					
						$current = '';
						if (isset($Shopp->Category) && $Shopp->Category->slug == $category->slug) 
							$current = ' class="current"';
						
						$listing = '';
						if ($category->total > 0 || isset($category->smart) || $linkall) 
							$listing = '<a href="'.$link.'"'.$current.'>'.$category->name.($linkcount?$total:'').'</a>'.(!$linkcount?$total:'');
						else $listing = $category->name;
						
						if (value_is_true($showall) || 
							$category->total > 0 || 
							isset($category->smart) || 
							$category->children) 
							$string .= '<li'.$current.'>'.$listing.'</li>';

						$previous = &$category;
						$depth = $category->depth;
					}
					if (value_is_true($hierarchy) && $depth > 0) 
						for ($i = $depth; $i > 0; $i--) {
							if (substr($string,strlen($subcategories)*-1) == $subcategories) {
								// If the child menu is empty, remove the <ul> to avoid breaking standards
								$string = substr($string,0,strlen($subcategories)*-1).'</li>';
							} else $string .= '</ul></li>';
						}
					if ($wraplist) $string .= '</ul>';
				}
				return $string;
				break;
			case "views":
				if (isset($Shopp->Category->controls)) return false;
				$string = "";
				$string .= '<ul class="views">';
				if (isset($options['label'])) $string .= '<li>'.$options['label'].'</li>';
				$string .= '<li><button type="button" class="grid"></button></li>';
				$string .= '<li><button type="button" class="list"></button></li>';
				$string .= '</ul>';
				return $string;
			case "orderby-list":
				if (isset($Shopp->Category->controls)) return false;
				if (isset($Shopp->Category->smart)) return false;
				$menuoptions = Category::sortoptions();
				$title = "";
				$string = "";
				$default = $Shopp->Settings->get('default_product_order');
				if (empty($default)) $default = "title";
				
				if (isset($options['default'])) $default = $options['default'];
				if (isset($options['title'])) $title = $options['title'];

				if (value_is_true($options['dropdown'])) {
					if (isset($Shopp->Cart->data->Category['orderby'])) 
						$default = $Shopp->Cart->data->Category['orderby'];
					$string .= $title;
					$string .= '<form action="'.esc_url($_SERVER['REQUEST_URI']).'" method="get" id="shopp-'.$Shopp->Category->slug.'-orderby-menu">';
					if (!SHOPP_PERMALINKS) {
						foreach ($_GET as $key => $value)
							if ($key != 'shopp_orderby') $string .= '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
					}
					$string .= '<select name="shopp_orderby" class="shopp-orderby-menu">';
					$string .= menuoptions($menuoptions,$default,true);
					$string .= '</select>';
					$string .= '</form>';
					$string .= '<script type="text/javascript">';
					$string .= "jQuery('#shopp-".$Shopp->Category->slug."-orderby-menu select.shopp-orderby-menu').change(function () { this.form.submit(); });";
					$string .= '</script>';
				} else {
					if (strpos($_SERVER['REQUEST_URI'],"?") !== false) 
						list($link,$query) = explode("\?",$_SERVER['REQUEST_URI']);
					$query = $_GET;
					unset($query['shopp_orderby']);
 					$query = http_build_query($query);
					if (!empty($query)) $query .= '&';
					
					foreach($menuoptions as $value => $option) {
						$label = $option;
						$href = esc_url($link.'?'.$query.'shopp_orderby='.$value);
						$string .= '<li><a href="'.$href.'">'.$label.'</a></li>';
					}
					
				}
				return $string;
				break;
			case "breadcrumb":
				if (isset($Shopp->Category->controls)) return false;
				if (empty($this->categories)) $this->load_categories();
				$separator = "&nbsp;&raquo; ";
				if (isset($options['separator'])) $separator = $options['separator'];
				
				$category = false;
				if (isset($Shopp->Cart->data->breadcrumb))
					$category = $Shopp->Cart->data->breadcrumb;
				
				$trail = false;
				$search = array();
				if (isset($Shopp->Cart->data->Search)) $search = array('search'=>$Shopp->Cart->data->Search);
				$path = explode("/",$category);
				if ($path[0] == "tag") {
					$category = "tag";
					$search = array('tag'=>urldecode($path[1]));
				}
				$Category = Catalog::load_category($category,$search);

				if (!empty($Category->uri)) {
					$type = "category";
					if (isset($Category->tag)) $type = "tag";
					
					if (SHOPP_PERMALINKS)
						$link = esc_url(add_query_arg($_GET,$Shopp->shopuri.$type.'/'.$Category->uri));
					else {
						if (isset($Category->smart)) 
							$link = esc_url(add_query_arg(array_merge($_GET,
								array('shopp_category'=>$Category->slug,'shopp_pid'=>null)),
								$Shopp->shopuri));
						else 
							$link = esc_url(add_query_arg(array_merge($_GET,
								array('shopp_category'=>$Category->id,'shopp_pid'=>null)), 
								$Shopp->shopuri));
					}

					$filters = false;
					if (!empty($Shopp->Cart->data->Category[$Category->slug]))
						$filters = ' (<a href="?shopp_catfilters=cancel">'.__('Clear Filters','Shopp').'</a>)';
					
					if (!empty($Shopp->Product)) 
						$trail .= '<li><a href="'.$link.'">'.$Category->name.(!$trail?'':$separator).'</a></li>';
					elseif (!empty($Category->name)) 
						$trail .= '<li>'.$Category->name.$filters.(!$trail?'':$separator).'</li>';
					

					// Build category names path by going from the target category up the parent chain
					$parentkey = (!empty($Category->id))?$this->categories[$Category->id]->parent:0;
					while ($parentkey != 0) {
						$tree_category = $this->categories[$parentkey];
					
						if (SHOPP_PERMALINKS) $link = $Shopp->shopuri.'category/'.$tree_category->uri;
						else $link = esc_url(add_query_arg(array_merge($_GET,
							array('shopp_category'=>$tree_category->id,'shopp_pid'=>null)),
							$Shopp->shopuri));
					
						$trail = '<li><a href="'.$link.'">'.$tree_category->name.'</a>'.
							(empty($trail)?'':$separator).'</li>'.$trail;
					
						$parentkey = $tree_category->parent;
					}
				}

				$trail = '<li><a href="'.$Shopp->link('catalog').'">'.$pages['catalog']['title'].'</a>'.(empty($trail)?'':$separator).'</li>'.$trail;
				return '<ul class="breadcrumb">'.$trail.'</ul>';
				break;
			case "search":
				global $wp;
				$type = "hidden";
				if (isset($options['type'])) $type = $options['type'];
				if ($type == "radio") {
					$option = "shopp";
					if (isset($options['option'])) $option = $options['option'];
					$default = false;
					if (isset($options['default'])) $default = value_is_true($options['default']);
					$selected = '';
					if ($default) $selected = ' checked="checked"';
					if (!empty($wp->query_vars['st'])) {
						$selected = '';
						if ($wp->query_vars['st'] == $option) $selected = ' checked="checked"';
					}
					if ($option == "blog") return '<input type="radio" name="st" value="blog"'.$selected.' />';
					else return '<input type="radio" name="st" value="shopp"'.$selected.' />';
				} elseif ($type == "menu") {
					if (empty($options['store'])) $options['store'] = __('Search the store','Shopp');
					if (empty($options['blog'])) $options['blog'] = __('Search the blog','Shopp');
					if (isset($wp->query_vars['st'])) $selected = $wp->query_vars['st'];
					$menu = '<select name="st">';
					if (isset($options['default']) && $options['default'] == "blog") {
						$menu .= '<option value="blog"'.($selected == "blog"?' selected="selected"':'').'>'.$options['blog'].'</option>';
						$menu .= '<option value="shopp"'.($selected == "shopp"?' selected="selected"':'').'>'.$options['store'].'</option>';
					} else {
						$menu .= '<option value="shopp"'.($selected == "shopp"?' selected="selected"':'').'>'.$options['store'].'</option>';
						$menu .= '<option value="blog"'.($selected == "blog"?' selected="selected"':'').'>'.$options['blog'].'</option>';
					}
					$menu .= '</select>';
					return $menu;
				} else return '<input type="hidden" name="st" value="shopp" />';
				break;
			case "catalog-products":
				if ($property == "catalog-products") $Shopp->Category = new CatalogProducts($options);
			case "new-products":
				if ($property == "new-products") $Shopp->Category = new NewProducts($options);
			case "featured-products":
				if ($property == "featured-products") $Shopp->Category = new FeaturedProducts($options);
			case "onsale-products":
				if ($property == "onsale-products") $Shopp->Category = new OnSaleProducts($options);
			case "bestseller-products":
				if ($property == "bestseller-products") $Shopp->Category = new BestsellerProducts($options);
			case "random-products":
				if ($property == "random-products") $Shopp->Category = new RandomProducts($options);
			case "tag-products":
				if ($property == "tag-products") $Shopp->Category = new TagProducts($options);
			case "related-products":
				if ($property == "related-products") $Shopp->Category = new RelatedProducts($options);
			case "search-products":
				if ($property == "search-products") $Shopp->Category = new SearchResults($options);
			case "category":
				if ($property == "category") {
					if (isset($options['name'])) $Shopp->Category = new Category($options['name'],'name');
					else if (isset($options['slug'])) $Shopp->Category = new Category($options['slug'],'slug');
					else if (isset($options['id'])) $Shopp->Category = new Category($options['id']);
				}
				if (isset($options['reset'])) return ($Shopp->Category = false);
				if (isset($options['title'])) $Shopp->Category->name = $options['title'];
				if (isset($options['show'])) $Shopp->Category->loading['limit'] = $options['show'];
				if (isset($options['pagination'])) $Shopp->Category->loading['pagination'] = $options['pagination'];
				if (isset($options['order'])) $Shopp->Category->loading['order'] = $options['order'];

				if (isset($options['load'])) return true;
				if (isset($options['controls']) && !value_is_true($options['controls'])) 
					$Shopp->Category->controls = false;
				if (isset($options['view'])) {
					if ($options['view'] == "grid") $Shopp->Category->view = "grid";
					else $Shopp->Category->view = "list";
				} 
				ob_start();
				if (isset($Shopp->Category->smart) && 
						file_exists(SHOPP_TEMPLATES."/category-{$Shopp->Category->slug}.php"))
					include(SHOPP_TEMPLATES."/category-{$Shopp->Category->slug}.php");
				elseif (isset($Shopp->Category->id) && 
					file_exists(SHOPP_TEMPLATES."/category-{$Shopp->Category->id}.php"))
					include(SHOPP_TEMPLATES."/category-{$Shopp->Category->id}.php");
				else include(SHOPP_TEMPLATES."/category.php");
				$content = ob_get_contents();
				ob_end_clean();
				$Shopp->Category = false; // Reset the current category
				return $content;
				break;
			case "product":
				if (isset($options['name'])) $Shopp->Product = new Product($options['name'],'name');
				else if (isset($options['slug'])) $Shopp->Product = new Product($options['slug'],'slug');
				else if (isset($options['id'])) $Shopp->Product = new Product($options['id']);
				if (isset($options['load'])) return true;
				ob_start();
				if (file_exists(SHOPP_TEMPLATES."/product-{$Shopp->Product->id}.php"))
					include(SHOPP_TEMPLATES."/product-{$Shopp->Product->id}.php");
				else include(SHOPP_TEMPLATES."/product.php");
				$content = ob_get_contents();
				ob_end_clean();
				return $content;
				break;
			case "sideproduct":
				$content = false;
				$source = $options['source'];
				if ($source == "product" && isset($options['product'])) {
					 // Save original requested product
					if ($Shopp->Product) $Requested = $Shopp->Product;
					$products = explode(",",$options['product']);
					if (!is_array($products)) $products = array($products);
					foreach ($products as $product) {
						$product = trim($product);
						if (empty($product)) continue;
						if (preg_match('/^[\d+]$/',$product)) 
							$Shopp->Product = new Product($product);
						else $Shopp->Product = new Product($product,'slug');
						
						if (empty($Shopp->Product->id)) continue;
						if (isset($options['load'])) return true;
						ob_start();
						if (file_exists(SHOPP_TEMPLATES."/sideproduct-{$Shopp->Product->id}.php"))
							include(SHOPP_TEMPLATES."/sideproduct-{$Shopp->Product->id}.php");
						else include(SHOPP_TEMPLATES."/sideproduct.php");
						$content .= ob_get_contents();
						ob_end_clean();
					}
					 // Restore original requested category
					if (!empty($Requested)) $Shopp->Product = $Requested;
					else $Shopp->Product = false;
				}
				
				if ($source == "category" && isset($options['category'])) {
					 // Save original requested category
					if ($Shopp->Category) $Requested = $Shopp->Category;
					if (empty($options['category'])) return false;
					$Shopp->Category = Catalog::load_category($options['category']);
					$Shopp->Category->load_products($options);
					if (isset($options['load'])) return true;
					foreach ($Shopp->Category->products as $product) {
						$Shopp->Product = $product;
						ob_start();
						if (file_exists(SHOPP_TEMPLATES."/sideproduct-{$Shopp->Product->id}.php"))
							include(SHOPP_TEMPLATES."/sideproduct-{$Shopp->Product->id}.php");
						else include(SHOPP_TEMPLATES."/sideproduct.php");
						$content .= ob_get_contents();
						ob_end_clean();
					}
					 // Restore original requested category
					if (!empty($Requested)) $Shopp->Category = $Requested;
					else $Shopp->Category = false;
				}
				
				return $content;
				break;
		}
	}

} // end Catalog class

?>