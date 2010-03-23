<?php
/**
 * Flow handlers
 * Main flow handling for all request processing/handling
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 2 April, 2008
 * @package Shopp
 **/

class Flow {
	var $Admin;
	var $Settings;

	var $basepath;
	var $baseuri;
	var $secureuri;
	
	function Flow (&$Core) {
		global $wp_version;
		$this->Settings = $Core->Settings;
		$this->Cart = $Core->Cart;

		$langpath = array(PLUGINDIR,$Core->directory,'lang');
		load_plugin_textdomain('Shopp',join(DIRECTORY_SEPARATOR,$langpath));

		$this->basepath = dirname(dirname(__FILE__));
		$this->uri = ((!empty($_SERVER['HTTPS']))?"https://":"http://").
					$_SERVER['SERVER_NAME'].str_replace("?".$_SERVER['QUERY_STRING'],"",$_SERVER['REQUEST_URI']);
		$this->secureuri = 'https://'.$_SERVER['SERVER_NAME'].$this->uri;
		
		$this->Admin = new stdClass();
		$this->Admin->orders = $Core->directory."-orders";
		$this->Admin->customers = $Core->directory."-customers";
		$this->Admin->editcustomer = $Core->directory."-customers-edit";
		$this->Admin->categories = $Core->directory."-categories";
		$this->Admin->editcategory = $Core->directory."-categories-edit";
		$this->Admin->products = $Core->directory."-products";
		$this->Admin->editproduct = $Core->directory."-products-edit";
		$this->Admin->promotions = $Core->directory."-promotions";
		$this->Admin->editpromo = $Core->directory."-promotions-edit";
		$this->Admin->settings = array(
			'settings' => array($Core->directory."-settings",__('General','Shopp')),
			'checkout' => array($Core->directory."-settings-checkout",__('Checkout','Shopp')),
			'payments' => array($Core->directory."-settings-payments",__('Payments','Shopp')),
			'shipping' => array($Core->directory."-settings-shipping",__('Shipping','Shopp')),
			'taxes' => array($Core->directory."-settings-taxes",__('Taxes','Shopp')),
			'presentation' => array($Core->directory."-settings-presentation",__('Presentation','Shopp')),
			'system' => array($Core->directory."-settings-system",__('System','Shopp')),
			'update' => array($Core->directory."-settings-update",__('Update','Shopp'))
		);
		$this->Admin->help = $Core->directory."-help";
		$this->Admin->welcome = $Core->directory."-welcome";
		$this->Admin->default = $this->Admin->orders;
		
		$this->Pages = $Core->Settings->get('pages');
		if (empty($this->Pages)) {
			$this->Pages = array();
			$this->Pages['catalog'] = array('name'=>'shop','title'=>'Shop','content'=>'[catalog]');
			$this->Pages['cart'] = array('name'=>'cart','title'=>'Cart','content'=>'[cart]');
			$this->Pages['checkout'] = array('name'=>'checkout','title'=>'Checkout','content'=>'[checkout]');
			$this->Pages['account'] = array('name'=>'account','title'=>'Your Orders','content'=>'[account]');
		}
		$this->Docs = array(
			'orders' => 'Managing Orders',
			'customers' => 'Managing Customers',
			'promotions' => 'Running Sales & Promotions',
			'editpromos' => 'Running Sales & Promotions',
			'products' => 'Editing a Product',
			'editproducts' => 'Editing a Product',
			'categories' => 'Editing a Category',
			'editcategory' => 'Editing a Category',
			'settings' => 'General Settings',
			'checkout' => 'Checkout Settings',
			'payments' => 'Payments Settings',
			'shipping' => 'Shipping Settings',
			'taxes' => 'Taxes Settings',
			'presentation' => 'Presentation Settings',
			'system' => 'System Settings',
			'update' => 'Update Settings'
		);
		
		$this->coremods = array("GoogleCheckout.php", "PayPalExpress.php", 
								"TestMode.php", "FlatRates.php", "ItemQuantity.php", 
								"OrderAmount.php", "OrderWeight.php");

		if (!defined('BR')) define('BR','<br />');

		// Overrideable macros
		if (!defined('SHOPP_USERLEVEL')) define('SHOPP_USERLEVEL',8);
		if (!defined('SHOPP_NOSSL')) define('SHOPP_NOSSL',false);
		if (!defined('SHOPP_PREPAYMENT_DOWNLOADS')) define('SHOPP_PREPAYMENT_DOWNLOADS',false);
		if (!defined('SHOPP_SESSION_TIMEOUT')) define('SHOPP_SESSION_TIMEOUT',7200);
		if (!defined('SHOPP_QUERY_DEBUG')) define('SHOPP_QUERY_DEBUG',false);
		
		define("SHOPP_WP27",(!version_compare($wp_version,"2.7","<")));
		define("SHOPP_DEBUG",($Core->Settings->get('error_logging') == 2048));
		define("SHOPP_PATH",$this->basepath);
		define("SHOPP_ADMINPATH",SHOPP_PATH."/core/ui");
		define("SHOPP_PLUGINURI",$Core->uri);
		define("SHOPP_DBSCHEMA",SHOPP_PATH."/core/model/schema.sql");

		define("SHOPP_TEMPLATES",($Core->Settings->get('theme_templates') != "off" && 
		 							is_dir($Core->Settings->get('theme_templates')))?
									$Core->Settings->get('theme_templates'):
									SHOPP_PATH.DIRECTORY_SEPARATOR."templates");
		define("SHOPP_TEMPLATES_URI",($Core->Settings->get('theme_templates') != "off" && 
			 							is_dir($Core->Settings->get('theme_templates')))?
										get_bloginfo('stylesheet_directory')."/shopp":
										$Core->uri."/templates");

		define("SHOPP_GATEWAYS",SHOPP_PATH.DIRECTORY_SEPARATOR."gateways".DIRECTORY_SEPARATOR);

		define("SHOPP_PERMALINKS",(get_option('permalink_structure') == "")?false:true);
		
		define("SHOPP_LOOKUP",(strpos($_SERVER['REQUEST_URI'],"images/") !== false || 
								strpos($_SERVER['REQUEST_URI'],"lookup=") !== false)?true:false);

		$this->uploadErrors = array(
			UPLOAD_ERR_INI_SIZE => __('The uploaded file exceeds the upload_max_filesize directive in PHP\'s configuration file','Shopp'),
			UPLOAD_ERR_FORM_SIZE => __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.','Shopp'),
			UPLOAD_ERR_PARTIAL => __('The uploaded file was only partially uploaded.','Shopp'),
			UPLOAD_ERR_NO_FILE => __('No file was uploaded.','Shopp'),
			UPLOAD_ERR_NO_TMP_DIR => __('The server\'s temporary folder is missing.','Shopp'),
			UPLOAD_ERR_CANT_WRITE => __('Failed to write the file to disk.','Shopp'),
			UPLOAD_ERR_EXTENSION => __('File upload stopped by extension.','Shopp'),
		);

	}

	function admin () {
		global $Shopp;
		$db =& DB::get();
		if (!defined('WP_ADMIN') || !isset($_GET['page'])) return;
		$Admin = $Shopp->Flow->Admin;
		$adminurl = $Shopp->wpadminurl."admin.php";
		
		$defaults = array(
			'page' => false,
			'deleting' => false,
			'delete' => false,
			'id' => false,
			'save' => false,
			'duplicate' => false,
			'next' => false
			);
		$args = array_merge($defaults,$_REQUEST);
		extract($args,EXTR_SKIP);

		if (strstr($page,$Admin->categories)) {
			
			if ($page == "shopp-categories"
					&& !empty($deleting) 
					&& !empty($delete) 
					&& is_array($delete)) {
				foreach($delete as $deletion) {
					$Category = new Category($deletion);
					$db->query("UPDATE $Category->_table SET parent=0 WHERE parent=$Category->id");
					$Category->delete();
				}
				$redirect = esc_url(add_query_arg(array_merge($_GET,array('delete[]'=>null,'deleting'=>null)),$adminurl));
				shopp_redirect($redirect);
			}
			
			if ($id && $id != "new")
				$Shopp->Category = new Category($id);
			else $Shopp->Category = new Category();
			
			if ($save) {
				$this->save_category($Shopp->Category);
				$this->Notice = '<strong>'.stripslashes($Shopp->Category->name).'</strong> '.__('has been saved.','Shopp');

				if ($next) {
					if ($next != "new") 
						$Shopp->Category = new Category($next);
					else $Shopp->Category = new Category();
				} else {
					if (empty($id)) $id = $Shopp->Category->id;
					$Shopp->Category = new Category($id);
				}
					
			}
			
		} // end $Admin->categories

		if (strstr($page,$Admin->products)) {
			if ($page == "shopp-products"
					&& !empty($deleting) 
					&& !empty($delete) 
					&& is_array($delete)) {
				foreach($delete as $deletion) {
					$Product = new Product($deletion);
					$Product->delete();
				}
				$redirect = esc_url(add_query_arg(array_merge($_GET,array('delete'=>null,'deleting'=>null)),$adminurl));
				shopp_redirect($redirect);
				exit();
			}
			
			if ($duplicate) {
				$Product = new Product();
				$Product->load($duplicate);
				$Product->duplicate();
				shopp_redirect(add_query_arg('page',$Admin->products,$adminurl));
			}

			if ($id && $id != "new") {
				$Shopp->Product = new Product($id);
				$Shopp->Product->load_data(array('prices','specs','categories','tags'));
			} else {
				$Shopp->Product = new Product();
				$Shopp->Product->published = "on";  
			}
			
			if ($save) {
				$this->save_product($Shopp->Product);
				$this->Notice = '<strong>'.stripslashes($Shopp->Product->name).'</strong> '.__('has been saved.','Shopp');
				
				if ($next) {
					if ($next == "new") {
						$Shopp->Product = new Product();
						$Shopp->Product->published = "on";  
					} else {
						$Shopp->Product = new Product($next);
						$Shopp->Product->load_data(array('prices','specs','categories','tags'));
					}
				} else {
					if (empty($id)) $id = $Shopp->Product->id;
					$Shopp->Product = new Product($id);
					$Shopp->Product->load_data(array('prices','specs','categories','tags'));					
				}
			}
		} // end $Admin->products
		
	}

	function helpdoc ($menu,$page) {
		if (!isset($this->Docs[$menu])) return;
		$url = SHOPP_DOCS.str_replace("+","_",urlencode($this->Docs[$menu]));
		$link = htmlspecialchars($this->Docs[$menu]);
		$content = '<a href="'.$url.'" target="_blank">'.$link.'</a>';

		if ($menu == "orders" || $menu == "customers") {
			ob_start();
			include("{$this->basepath}/core/ui/help/$menu.php");
			$help = ob_get_contents();
			ob_end_clean();
			$content .= $help;
		}
		
		add_contextual_help($page,$content);
	}

	/**
	 * Catalog flow handlers
	 **/
	function catalog () {
		global $Shopp;
		
		if (SHOPP_DEBUG) new ShoppError('Displaying catalog page request: '.$_SERVER['REQUEST_URI'],'shopp_catalog',SHOPP_DEBUG_ERR);
		
		ob_start();
		switch ($Shopp->Catalog->type) {
			case "product": 
				if (file_exists(SHOPP_TEMPLATES."/product-{$Shopp->Product->id}.php"))
					include(SHOPP_TEMPLATES."/product-{$Shopp->Product->id}.php");
				else include(SHOPP_TEMPLATES."/product.php"); break;

			case "category":
				if (isset($Shopp->Category->smart) && 
						file_exists(SHOPP_TEMPLATES."/category-{$Shopp->Category->slug}.php"))
					include(SHOPP_TEMPLATES."/category-{$Shopp->Category->slug}.php");
				elseif (isset($Shopp->Category->id) && 
					file_exists(SHOPP_TEMPLATES."/category-{$Shopp->Category->id}.php"))
					include(SHOPP_TEMPLATES."/category-{$Shopp->Category->id}.php");
				else include(SHOPP_TEMPLATES."/category.php"); break;

			default: include(SHOPP_TEMPLATES."/catalog.php"); break;
		}
		$content = ob_get_contents();
		ob_end_clean();
		
		$classes = $Shopp->Catalog->type;
		if (!isset($_COOKIE['shopp_catalog_view'])) {
			// No cookie preference exists, use shopp default setting
			$view = $Shopp->Settings->get('default_catalog_view');
			if ($view == "list") $classes .= " list";
			if ($view == "grid") $classes .= " grid";
		} else {
			if ($_COOKIE['shopp_catalog_view'] == "list") $classes .= " list";
			if ($_COOKIE['shopp_catalog_view'] == "grid") $classes .= " grid";
		}			
		
		return apply_filters('shopp_catalog','<div id="shopp" class="'.$classes.'">'.$content.'<div class="clear"></div></div>');
	}
	
	/**
	 * Shopping Cart flow handlers
	 **/
	function cart ($attrs=array()) {
		$Cart = $this->Cart;

		ob_start();
		include(SHOPP_TEMPLATES."/cart.php");
		$content = ob_get_contents();
		ob_end_clean();

		return apply_filters('shopp_cart_template','<div id="shopp">'.$content.'</div>');
	}

	function shipping_estimate ($attrs) {
		$Cart = $this->Cart;

		ob_start();
		include(SHOPP_TEMPLATES."/shipping.php");
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}
	
	/**
	 * Checkout flow handlers
	 **/
	function checkout () {
		global $Shopp;
		$Cart = $Shopp->Cart;

		$process = get_query_var('shopp_proc');
		$xco = get_query_var('shopp_xco');
		if (!empty($xco)) {
			$Shopp->gateway($xco);
			$Shopp->Gateway->actions();
		}

		switch ($process) {
			case "confirm-order": $content = $this->order_confirmation(); break;
			case "receipt": $content = $this->order_receipt(); break;
			default:
				ob_start();
				if ($Cart->data->Errors->exist(SHOPP_COMM_ERR)) {
					include(SHOPP_TEMPLATES."/errors.php");
					$Cart->data->Errors->reset();
				}
				if (!empty($xco)) {
					
					if (!empty($Shopp->Gateway)) {
						if ($Shopp->Gateway->checkout) include(SHOPP_TEMPLATES."/checkout.php");
						else {
							if ($Cart->data->Errors->exist(SHOPP_COMM_ERR))
								include(SHOPP_TEMPLATES."/errors.php");
							include(SHOPP_TEMPLATES."/summary.php");
							echo $Shopp->Gateway->tag('button');
						}
					} else include(SHOPP_TEMPLATES."/summary.php");
					
				} else include(SHOPP_TEMPLATES."/checkout.php");
				$content = ob_get_contents();
				ob_end_clean();

				unset($Cart->data->OrderError);
		}
		
		// Wrap with #shopp if not already wrapped
		if (strpos($content,'<div id="shopp">') === false) 
			$content = '<div id="shopp">'.$content.'</div>';
		
		return apply_filters('shopp_checkout',$content);
	}
	
	function checkout_order_summary () {
		global $Shopp;
		$Cart = $Shopp->Cart;

		ob_start();
		include(SHOPP_TEMPLATES."/summary.php");
		$content = ob_get_contents();
		ob_end_clean();
		
		return apply_filters('shopp_order_summary',$content);
	}
	
	function secure_page_links ($linklist) {
		global $Shopp;
		$gateway = $Shopp->Settings->get('payment_gateway');
		if (strpos($gateway,"TestMode.php") !== false) return $linklist;
		$hrefs = array(
			'checkout' => $Shopp->link('checkout'),
			'account' => $Shopp->link('account')
		);
		if (empty($gateway)) return str_replace($hrefs['checkout'],$Shopp->link('cart'),$linklist);

		foreach ($hrefs as $href) {
			$secure_href = str_replace("http://","https://",$href);
			$linklist = str_replace($href,$secure_href,$linklist);
		}
		return $linklist;
	}	
	
	/**
	 * order()
	 * Processes orders by passing transaction information to the active
	 * payment gateway */
	function order ($gateway = false) {
		global $Shopp;
		$Cart = $Shopp->Cart;
		$db = DB::get();
		
		do_action('shopp_order_preprocessing');

		$Order = $Shopp->Cart->data->Order;
		$Order->Totals = $Shopp->Cart->data->Totals;
		$Order->Items = $Shopp->Cart->contents;
		$Order->Cart = $Shopp->Cart->session;
		
		if ($Shopp->Gateway && !$Cart->orderisfree()) {
			// Use an external checkout payment gateway
			if (SHOPP_DEBUG) new ShoppError('Processing order through a remote-payment gateway service.',false,SHOPP_DEBUG_ERR);
			$Purchase = $Shopp->Gateway->process();
			if (!$Purchase) {
				if (SHOPP_DEBUG) new ShoppError('The remote-payment gateway encountered an error.',false,SHOPP_DEBUG_ERR);
				$Shopp->Gateway->error();
				return false;
			}
			if (SHOPP_DEBUG) new ShoppError('Transaction successfully processed by remote-payment gateway service.',false,SHOPP_DEBUG_ERR);
		} else {
			// Use local payment gateway set in payment settings
			
			$gateway = $Shopp->Settings->get('payment_gateway');
			
			// Process a transaction if the order has a cost (is not free)
			if (!$Cart->orderisfree()) {

				if (!$Shopp->gateway($gateway)) return false;

				// Process the transaction through the payment gateway
				if (SHOPP_DEBUG) new ShoppError('Processing order through local-payment gateway service.',false,SHOPP_DEBUG_ERR);
				$processed = $Shopp->Gateway->process();

				// exit();
				// There was a problem processing the transaction, 
				// grab the error response from the gateway so we can report it
				if (!$processed) {
					if (SHOPP_DEBUG) new ShoppError('The local-payment gateway encountered an error.',false,SHOPP_DEBUG_ERR);
					$Shopp->Gateway->error();
					return false;
				}
				$gatewaymeta = $this->scan_gateway_meta(SHOPP_GATEWAYS.$gateway);
				$gatewayname = $gatewaymeta->name;
				$transactionid = $Shopp->Gateway->transactionid();
				
				if (SHOPP_DEBUG) new ShoppError('Transaction '.$transactionid.' successfully processed by local-payment gateway service '.$gatewayname.'.',false,SHOPP_DEBUG_ERR);
				
			} else { 
				if(!$Cart->validorder()){
					new ShoppError(__('There is not enough customer information to process the order.','Shopp'),'invalid_order',SHOPP_TRXN_ERR);
					return false;	
				}
				$gatewayname = __('N/A','Shopp');
				$transactionid = __('(Free Order)','Shopp');
			}

			$authentication = $Shopp->Settings->get('account_system');

			// Transaction successful, save the order

			if ($authentication == "wordpress") {
				// Check if they've logged in
				// If the shopper is already logged-in, save their updated customer info
				if ($Shopp->Cart->data->login) {
					if (SHOPP_DEBUG) new ShoppError('Customer logged in, linking Shopp customer account to existing WordPress account.',false,SHOPP_DEBUG_ERR);
					get_currentuserinfo();
					global $user_ID;
					$Order->Customer->wpuser = $user_ID;
				}
				
				// Create WordPress account (if necessary)
				if (!$Order->Customer->wpuser) {
					if (SHOPP_DEBUG) new ShoppError('Creating a new WordPress account for this customer.',false,SHOPP_DEBUG_ERR);
					if(!$Order->Customer->new_wpuser()) new ShoppError(__('Account creation failed on order for customer id:' . $Order->Customer->id, "Shopp"), false,SHOPP_TRXN_ERR);
				}
			}

			// Create a WP-compatible password hash to go in the db
			if (empty($Order->Customer->id))
				$Order->Customer->password = wp_hash_password($Order->Customer->password);
			$Order->Customer->save();

			$Order->Billing->customer = $Order->Customer->id;
			$Order->Billing->card = substr($Order->Billing->card,-4);
			$Order->Billing->save();

			// Card data is truncated, switch the cart to normal mode
			if ($Shopp->Cart->secured() && is_shopp_secure())
				$Shopp->Cart->secured(false);

			if (!empty($Order->Shipping->address)) {
				$Order->Shipping->customer = $Order->Customer->id;
				$Order->Shipping->save();
			}
			
			$Promos = array();
			foreach ($Shopp->Cart->data->PromosApplied as $promo)
				$Promos[$promo->id] = $promo->name;

			if ($Shopp->Cart->orderisfree()) $orderisfree = true;
			else $orderisfree = false;

			$Purchase = new Purchase();
			$Purchase->customer = $Order->Customer->id;
			$Purchase->billing = $Order->Billing->id;
			$Purchase->shipping = $Order->Shipping->id;
			$Purchase->copydata($Order->Customer);
			$Purchase->copydata($Order->Billing);
			$Purchase->copydata($Order->Shipping,'ship');
			$Purchase->copydata($Shopp->Cart->data->Totals);
			$Purchase->data = $Order->data;
			$Purchase->promos = $Promos;
			$Purchase->freight = $Shopp->Cart->data->Totals->shipping;
			$Purchase->gateway = $gatewayname;
			$Purchase->transactionid = $transactionid;
			$Purchase->transtatus = "CHARGED";
			$Purchase->ip = $Shopp->Cart->ip;
			$Purchase->save();
			// echo "<pre>"; print_r($Purchase); echo "</pre>";

			foreach($Shopp->Cart->contents as $Item) {
				$Purchased = new Purchased();
				$Purchased->copydata($Item);
				$Purchased->purchase = $Purchase->id;
				if (!empty($Purchased->download)) $Purchased->keygen();
				$Purchased->save();
				if ($Item->inventory) $Item->unstock();
			}

			if (SHOPP_DEBUG) new ShoppError('Purchase '.$Purchase->id.' was successfully saved to the database.',false,SHOPP_DEBUG_ERR);
		}
		
		// Skip post order if no Purchase ID exists
		if (empty($Purchase->id)) return true;

		// Empty cart on successful order
		$Shopp->Cart->unload();
		session_destroy();
		
		// Start new cart session
		$Shopp->Cart = new Cart();
		session_start();
		
		// Keep the user logged in or log them in if they are a new customer
		if ($Shopp->Cart->data->login || $authentication != "none")
			$Shopp->Cart->loggedin($Order->Customer);
		
		// Save the purchase ID for later lookup
		$Shopp->Cart->data->Purchase = new Purchase($Purchase->id);
		$Shopp->Cart->data->Purchase->load_purchased();
		// // $Shopp->Cart->save();
		
		// Allow other WordPress plugins access to Purchase data to extend
		// what Shopp does after a successful transaction
		do_action_ref_array('shopp_order_success',array(&$Shopp->Cart->data->Purchase));
		
		// Send email notifications
		// notification(addressee name, email, subject, email template, receipt template)
		$Purchase->notification(
			"$Purchase->firstname $Purchase->lastname",
			$Purchase->email,
			__('Order Receipt','Shopp')
		);

		if ($Shopp->Settings->get('receipt_copy') == 1) {
			$Purchase->notification(
				'',
				$Shopp->Settings->get('merchant_email'),
				__('New Order','Shopp')
			);
		}

		$ssl = true;
		// Test Mode will not require encrypted checkout
		if (strpos($gateway,"TestMode.php") !== false 
				|| isset($_GET['shopp_xco']) 
				|| $orderisfree
				|| SHOPP_NOSSL) 
			$ssl = false;
		shopp_redirect($Shopp->link('receipt',$ssl));
	}
	
	// Display the confirm order screen
	function order_confirmation () {
		global $Shopp;
		$Cart = $Shopp->Cart;
		
		ob_start();
		include(SHOPP_TEMPLATES."/confirm.php");
		$content = ob_get_contents();
		ob_end_clean();
		return apply_filters('shopp_order_confirmation','<div id="shopp">'.$content.'</div>');
	}

	// Display a sales receipt
	function order_receipt ($template="receipt.php") {
		global $Shopp;
		$Cart = $Shopp->Cart;
		
		ob_start();
		include(trailingslashit(SHOPP_TEMPLATES).$template);
		$content = ob_get_contents();
		ob_end_clean();
		return apply_filters('shopp_order_receipt','<div id="shopp">'.$content.'</div>');
	}
	
	// Display an error page
	function error_page ($template="errors.php") {
		global $Shopp;
		$Cart = $Shopp->Cart;
		
		ob_start();
		include(trailingslashit(SHOPP_TEMPLATES).$template);
		$content = ob_get_contents();
		ob_end_clean();
		return apply_filters('shopp_errors_page','<div id="shopp">'.$content.'</div>');
	}
	

	/**
	 * Orders admin flow handlers
	 */
	function orders_list() {
		global $Shopp,$Orders;
		$db = DB::get();
		
		$defaults = array(
			'page' => false,
			'deleting' => false,
			'selected' => false,
			'update' => false,
			'newstatus' => false,
			'pagenum' => 1,
			'per_page' => false,
			'start' => '',
			'end' => '',
			'status' => false,
			's' => '',
			'range' => '',
			'startdate' => '',
			'enddate' => '',
		);
		
		$args = array_merge($defaults,$_GET);
		extract($args, EXTR_SKIP);
		
		if ( !current_user_can(SHOPP_USERLEVEL) )
			wp_die(__('You do not have sufficient permissions to access this page.','Shopp'));

		if ($page == "shopp-orders"
						&& !empty($deleting)
						&& !empty($selected) 
						&& is_array($selected)) {
			foreach($selected as $selection) {
				$Purchase = new Purchase($selection);
				$Purchase->load_purchased();
				foreach ($Purchase->purchased as $purchased) {
					$Purchased = new Purchased($purchased->id);
					$Purchased->delete();
				}
				$Purchase->delete();
			}
		}

		$statusLabels = $this->Settings->get('order_status');
		if (empty($statusLabels)) $statusLabels = array('');
		$txnStatusLabels = array(
			'PENDING' => __('Pending','Shopp'),
			'CHARGED' => __('Charged','Shopp'),
			'REFUNDED' => __('Refunded','Shopp'),
			'VOID' => __('Void','Shopp')
			);

		if ($update == "order"
						&& !empty($selected) 
						&& is_array($selected)) {
			foreach($selected as $selection) {
				$Purchase = new Purchase($selection);
				$Purchase->status = $newstatus;
				$Purchase->save();
			}
		}

		$Purchase = new Purchase();
		
		if (!empty($start)) {
			$startdate = $start;
			list($month,$day,$year) = explode("/",$startdate);
			$starts = mktime(0,0,0,$month,$day,$year);
		}
		if (!empty($end)) {
			$enddate = $end;
			list($month,$day,$year) = explode("/",$enddate);
			$ends = mktime(23,59,59,$month,$day,$year);
		}

		$pagenum = absint( $pagenum );
		if ( empty($pagenum) )
			$pagenum = 1;
		if( !$per_page || $per_page < 0 )
			$per_page = 20;
		$start = ($per_page * ($pagenum-1)); 
		
		$where = '';
		if (!empty($status) || $status === '0') $where = "WHERE status='$status'";
		
		if (!empty($s)) {
			$s = stripslashes($s);
			if (preg_match_all('/(\w+?)\:(?="(.+?)"|(.+?)\b)/',$s,$props,PREG_SET_ORDER) > 0) {
				foreach ($props as $search) {
					$keyword = !empty($search[2])?$search[2]:$search[3];
					switch(strtolower($search[1])) {
						case "txn": $where .= (empty($where)?"WHERE ":" AND ")."transactionid='$keyword'"; break;
						case "gateway": $where .= (empty($where)?"WHERE ":" AND ")."gateway LIKE '%$keyword%'"; break;
						case "cardtype": $where .= ((empty($where))?"WHERE ":" AND ")."cardtype LIKE '%$keyword%'"; break;
						case "address": $where .= ((empty($where))?"WHERE ":" AND ")."(address LIKE '%$keyword%' OR xaddress='%$keyword%')"; break;
						case "city": $where .= ((empty($where))?"WHERE ":" AND ")."city LIKE '%$keyword%'"; break;
						case "province":
						case "state": $where .= ((empty($where))?"WHERE ":" AND ")."state='$keyword'"; break;
						case "zip":
						case "zipcode":
						case "postcode": $where .= ((empty($where))?"WHERE ":" AND ")."postcode='$keyword'"; break;
						case "country": $where .= ((empty($where))?"WHERE ":" AND ")."country='$keyword'"; break;
					}
				}
				if (empty($where)) $where .= ((empty($where))?"WHERE ":" AND ")." (id='$s' OR CONCAT(firstname,' ',lastname) LIKE '%$s%')";	
			} elseif (strpos($s,'@') !== false) {
				 $where .= ((empty($where))?"WHERE ":" AND ")." email='$s'";	
			} else $where .= ((empty($where))?"WHERE ":" AND ")." (id='$s' OR CONCAT(firstname,' ',lastname) LIKE '%$s%')";	
		}
		if (!empty($starts) && !empty($ends)) $where .= ((empty($where))?"WHERE ":" AND ").' (UNIX_TIMESTAMP(created) >= '.$starts.' AND UNIX_TIMESTAMP(created) <= '.$ends.')';
		$ordercount = $db->query("SELECT count(*) as total,SUM(total) AS sales,AVG(total) AS avgsale FROM $Purchase->_table $where ORDER BY created DESC");
		$query = "SELECT * FROM $Purchase->_table $where ORDER BY created DESC LIMIT $start,$per_page";
		$Orders = $db->query($query,AS_ARRAY);

		$num_pages = ceil($ordercount->total / $per_page);
		$page_links = paginate_links( array(
			'base' => add_query_arg( 'pagenum', '%#%' ),
			'format' => '',
			'total' => $num_pages,
			'current' => $pagenum
		));
		
		$ranges = array(
			'all' => __('Show All Orders','Shopp'),
			'today' => __('Today','Shopp'),
			'week' => __('This Week','Shopp'),
			'month' => __('This Month','Shopp'),
			'quarter' => __('This Quarter','Shopp'),
			'year' => __('This Year','Shopp'),
			'yesterday' => __('Yesterday','Shopp'),
			'lastweek' => __('Last Week','Shopp'),
			'last30' => __('Last 30 Days','Shopp'),
			'last90' => __('Last 3 Months','Shopp'),
			'lastmonth' => __('Last Month','Shopp'),
			'lastquarter' => __('Last Quarter','Shopp'),
			'lastyear' => __('Last Year','Shopp'),
			'lastexport' => __('Last Export','Shopp'),
			'custom' => __('Custom Dates','Shopp')
			);
		
		$exports = array(
			'tab' => __('Tab-separated.txt','Shopp'),
			'csv' => __('Comma-separated.csv','Shopp'),
			'xls' => __('Microsoft&reg; Excel.xls','Shopp'),
			'iif' => __('Intuit&reg; QuickBooks.iif','Shopp')
			);
		
		$formatPref = $Shopp->Settings->get('purchaselog_format');
		if (!$formatPref) $formatPref = 'tab';
		
		$columns = array_merge(Purchase::exportcolumns(),Purchased::exportcolumns());
		$selected = $Shopp->Settings->get('purchaselog_columns');
		if (empty($selected)) $selected = array_keys($columns);
		
		include("{$this->basepath}/core/ui/orders/orders.php");
	}      
	
	function orders_list_columns () {
		shopp_register_column_headers('toplevel_page_shopp-orders', array(
			'cb'=>'<input type="checkbox" />',
			'order'=>__('Order','Shopp'),
			'name'=>__('Name','Shopp'),
			'destination'=>__('Destination','Shopp'),
			'total'=>__('Total','Shopp'),
			'txn'=>__('Transaction','Shopp'),
			'date'=>__('Date','Shopp'))
		);
	}
	
	function order_manager () {
		global $Shopp;
		global $is_IIS;

		if ( !current_user_can(SHOPP_USERLEVEL) )
			wp_die(__('You do not have sufficient permissions to access this page.','Shopp'));

		if (preg_match("/\d+/",$_GET['id'])) {
			$Shopp->Cart->data->Purchase = new Purchase($_GET['id']);
			$Shopp->Cart->data->Purchase->load_purchased();
		} else $Shopp->Cart->data->Purchase = new Purchase();
		
		$Purchase = $Shopp->Cart->data->Purchase;
		$Customer = new Customer($Purchase->customer);
		
		if (!empty($_POST['update'])) {
			check_admin_referer('shopp-save-order');
			
			if ($_POST['transtatus'] != $Purchase->transtatus)
				do_action_ref_array('shopp_order_txnstatus_update',array(&$_POST['transtatus'],&$Purchase));
			
			$Purchase->updates($_POST);
			if ($_POST['notify'] == "yes") {
				$labels = $this->Settings->get('order_status');
				
				// Send the e-mail notification
				$notification = array();
				$notification['from'] = $Shopp->Settings->get('merchant_email');
				if($is_IIS) $notification['to'] = $Purchase->email;
				else $notification['to'] = "\"{$Purchase->firstname} {$Purchase->lastname}\" <{$Purchase->email}>";
				$notification['subject'] = __('Order Updated','Shopp');
				$notification['url'] = get_bloginfo('siteurl');
				$notification['sitename'] = get_bloginfo('name');

				if ($_POST['receipt'] == "yes")
					$notification['receipt'] = $this->order_receipt();

				$notification['status'] = strtoupper($labels[$Purchase->status]);
				$notification['message'] = wpautop($_POST['message']);

				shopp_email(SHOPP_TEMPLATES."/notification.html",$notification);
				
			}
			
			$Purchase->save();
			$updated = __('Order status updated.','Shopp');
		}

		$targets = $this->Settings->get('target_markets');
		$txnStatusLabels = array(
			'PENDING' => __('Pending','Shopp'),
			'CHARGED' => __('Charged','Shopp'),
			'REFUNDED' => __('Refunded','Shopp'),
			'VOID' => __('Void','Shopp')
			);
		
		$statusLabels = $this->Settings->get('order_status');
		if (empty($statusLabels)) $statusLabels = array('');
		
		
		$taxrate = 0;
		$base = $Shopp->Settings->get('base_operations');
		if ($base['vat']) $taxrate = $Shopp->Cart->taxrate();
		
				
		include("{$this->basepath}/core/ui/orders/order.php");
	}
	
	function order_status_counts () {
		$db = DB::get();
		
		$purchase_table = DatabaseObject::tablename(Purchase::$table);
		$labels = $this->Settings->get('order_status');
		
		if (empty($labels)) return false;

		$r = $db->query("SELECT status,COUNT(status) AS total FROM $purchase_table GROUP BY status ORDER BY status ASC",AS_ARRAY);

		$status = array();
		foreach ($r as $count) $status[$count->status] = $count->total;
		foreach ($labels as $id => $label) if (empty($status[$id])) $status[$id] = 0;
		return $status;
	}
		
	function account () {
		global $Shopp,$wp;
		
		if ($Shopp->Cart->data->login 
				&& isset($Shopp->Cart->data->Order->Customer)) 
			$Shopp->Cart->data->Order->Customer->management();
		
		if (isset($_GET['acct']) && $_GET['acct'] == "rp") $Shopp->Cart->data->Order->Customer->reset_password($_GET['key']);
		if (isset($_POST['recover-login'])) $Shopp->Cart->data->Order->Customer->recovery();
				
		ob_start();
		if (isset($wp->query_vars['shopp_download'])) include(SHOPP_TEMPLATES."/errors.php");
		elseif ($Shopp->Cart->data->login) include(SHOPP_TEMPLATES."/account.php");
		else include(SHOPP_TEMPLATES."/login.php");
		$content = ob_get_contents();
		ob_end_clean();
		
		return apply_filters('shopp_account_template','<div id="shopp">'.$content.'</div>');
		
	}
	
	function customers_list () {
		global $Shopp,$Customers,$wpdb;
		$db = DB::get();
		
		$defaults = array(
			'page' => false,
			'deleting' => false,
			'selected' => false,
			'update' => false,
			'newstatus' => false,
			'pagenum' => 1,
			'per_page' => false,
			'start' => '',
			'end' => '',
			'status' => false,
			's' => '',
			'range' => '',
			'startdate' => '',
			'enddate' => '',
		);
		
		$args = array_merge($defaults,$_GET);
		extract($args, EXTR_SKIP);
		
		if ($page == "shopp-customers"
				&& !empty($deleting) 
				&& !empty($selected) 
				&& is_array($selected)) {
			foreach($selected as $deletion) {
				$Customer = new Customer($deletion);
				$Billing = new Billing($Customer->id,'customer');
				$Billing->delete();
				$Shipping = new Shipping($Customer->id,'customer');
				$Shipping->delete();
				$Customer->delete();
			}
		}
		
		if (!empty($_POST['save'])) {
			check_admin_referer('shopp-save-customer');

			if ($_POST['id'] != "new") {
				$Customer = new Customer($_POST['id']);
				$Billing = new Billing($Customer->id,'customer');
				$Shipping = new Shipping($Customer->id,'customer');
			} else $Customer = new Customer();
			
			$Customer->updates($_POST);
			
			if (!empty($_POST['new-password']) && !empty($_POST['confirm-password'])
				&& $_POST['new-password'] == $_POST['confirm-password']) {
					$Customer->password = wp_hash_password($_POST['new-password']);
					if (!empty($Customer->wpuser)) wp_set_password($_POST['new-password'], $Customer->wpuser);
				}
			
			$Customer->save();
			
			$Billing->updates($_POST['billing']);
			$Billing->save();

			$Shipping->updates($_POST['shipping']);
			$Shipping->save();

		}

		$pagenum = absint( $pagenum );
		if ( empty($pagenum) )
			$pagenum = 1;
		if( !$per_page || $per_page < 0 )
			$per_page = 20;
		$index = ($per_page * ($pagenum-1)); 
		
		if (!empty($start)) {
			$startdate = $start;
			list($month,$day,$year) = explode("/",$startdate);
			$starts = mktime(0,0,0,$month,$day,$year);
		}
		if (!empty($end)) {
			$enddate = $end;
			list($month,$day,$year) = explode("/",$enddate);
			$ends = mktime(23,59,59,$month,$day,$year);
		}
		
		$customer_table = DatabaseObject::tablename(Customer::$table);
		$billing_table = DatabaseObject::tablename(Billing::$table);
		$purchase_table = DatabaseObject::tablename(Purchase::$table);
		$users_table = $wpdb->users;
		
		$where = '';
		if (!empty($s)) {
			$s = stripslashes($s);
			if (preg_match_all('/(\w+?)\:(?="(.+?)"|(.+?)\b)/',$s,$props,PREG_SET_ORDER)) {
				foreach ($props as $search) {
					$keyword = !empty($search[2])?$search[2]:$search[3];
					switch(strtolower($search[1])) {
						case "company": $where .= ((empty($where))?"WHERE ":" AND ")."c.company LIKE '%$keyword%'"; break;
						case "login": $where .= ((empty($where))?"WHERE ":" AND ")."u.user_login LIKE '%$keyword%'"; break;
						case "address": $where .= ((empty($where))?"WHERE ":" AND ")."(b.address LIKE '%$keyword%' OR b.xaddress='%$keyword%')"; break;
						case "city": $where .= ((empty($where))?"WHERE ":" AND ")."b.city LIKE '%$keyword%'"; break;
						case "province":
						case "state": $where .= ((empty($where))?"WHERE ":" AND ")."b.state='$keyword'"; break;
						case "zip":
						case "zipcode":
						case "postcode": $where .= ((empty($where))?"WHERE ":" AND ")."b.postcode='$keyword'"; break;
						case "country": $where .= ((empty($where))?"WHERE ":" AND ")."b.country='$keyword'"; break;
					}
				}
			} elseif (strpos($s,'@') !== false) {
				 $where .= ((empty($where))?"WHERE ":" AND ")."c.email='$s'";	
			} else $where .= ((empty($where))?"WHERE ":" AND ")." (c.id='$s' OR CONCAT(c.firstname,' ',c.lastname) LIKE '%$s%' OR c.company LIKE '%$s%')";

		}
		if (!empty($starts) && !empty($ends)) $where .= ((empty($where))?"WHERE ":" AND ").' (UNIX_TIMESTAMP(c.created) >= '.$starts.' AND UNIX_TIMESTAMP(c.created) <= '.$ends.')';

		$customercount = $db->query("SELECT count(*) as total FROM $customer_table AS c $where");
		$query = "SELECT c.*,b.city,b.state,b.country, u.user_login, SUM(p.total) AS total,count(distinct p.id) AS orders FROM $customer_table AS c LEFT JOIN $purchase_table AS p ON p.customer=c.id LEFT JOIN $billing_table AS b ON b.customer=c.id LEFT JOIN $users_table AS u ON u.ID=c.wpuser AND (c.wpuser IS NULL OR c.wpuser !=0) $where GROUP BY c.id ORDER BY c.created DESC LIMIT $index,$per_page";
		$Customers = $db->query($query,AS_ARRAY);

		$num_pages = ceil($customercount->total / $per_page);
		$page_links = paginate_links( array(
			'base' => add_query_arg( 'pagenum', '%#%' ),
			'format' => '',
			'total' => $num_pages,
			'current' => $pagenum
		));
		
		$ranges = array(
			'all' => __('Show New Customers','Shopp'),
			'today' => __('Today','Shopp'),
			'week' => __('This Week','Shopp'),
			'month' => __('This Month','Shopp'),
			'quarter' => __('This Quarter','Shopp'),
			'year' => __('This Year','Shopp'),
			'yesterday' => __('Yesterday','Shopp'),
			'lastweek' => __('Last Week','Shopp'),
			'last30' => __('Last 30 Days','Shopp'),
			'last90' => __('Last 3 Months','Shopp'),
			'lastmonth' => __('Last Month','Shopp'),
			'lastquarter' => __('Last Quarter','Shopp'),
			'lastyear' => __('Last Year','Shopp'),
			'lastexport' => __('Last Export','Shopp'),
			'custom' => __('Custom Dates','Shopp')
			);
		
		$exports = array(
			'tab' => __('Tab-separated.txt','Shopp'),
			'csv' => __('Comma-separated.csv','Shopp'),
			'xls' => __('Microsoft&reg; Excel.xls','Shopp')			
			);
		
		
		$formatPref = $Shopp->Settings->get('customerexport_format');
		if (!$formatPref) $formatPref = 'tab';
		
		$columns = array_merge(Customer::exportcolumns(),Billing::exportcolumns(),Shipping::exportcolumns());
		$selected = $Shopp->Settings->get('customerexport_columns');
		if (empty($selected)) $selected = array_keys($columns);
		
		$authentication = $Shopp->Settings->get('account_system');
		
		include("{$this->basepath}/core/ui/customers/customers.php");
		
	}
	
	function customers_list_columns () {
		shopp_register_column_headers('shopp_page_shopp-customers', array(
			'cb'=>'<input type="checkbox" />',
			'name'=>__('Name','Shopp'),
			'login'=>__('Login','Shopp'),
			'email'=>__('Email','Shopp'),
			'location'=>__('Location','Shopp'),
			'orders'=>__('Orders','Shopp'),
			'joined'=>__('Joined','Shopp'))
		);
		
	}

	function customer_editor_ui () {
		global $Shopp;
		include("{$this->basepath}/core/ui/customers/ui.php");
	}
	
	function customer_editor () {
		global $Shopp,$Customer;
		
		if ( !current_user_can(SHOPP_USERLEVEL) )
			wp_die(__('You do not have sufficient permissions to access this page.'));


		if ($_GET['id'] != "new") {
			$Customer = new Customer($_GET['id']);
			$Customer->Billing = new Billing($Customer->id,'customer');
			$Customer->Shipping = new Shipping($Customer->id,'customer');
			if (empty($Customer->id)) 
				wp_die(__('The requested customer record does not exist.','Shopp'));
		} else $Customer = new Customer();

		$countries = array(''=>'');
		$countrydata = $Shopp->Settings->get('countries');
		foreach ($countrydata as $iso => $c) {
			if (isset($_POST['settings']) && $_POST['settings']['base_operations']['country'] == $iso) 
				$base_region = $c['region'];
			$countries[$iso] = $c['name'];
		}
		$Customer->countries = $countries;

		$regions = $Shopp->Settings->get('zones');
		$Customer->billing_states = array_merge(array(''),(array)$regions[$Customer->Billing->country]);
		$Customer->shipping_states = array_merge(array(''),(array)$regions[$Customer->Shipping->country]);

		include("{$this->basepath}/core/ui/customers/editor.php");
	}
	
	/**
	 * Products admin flow handlers
	 **/
	function products_list($workflow=false) {
		global $Products,$Shopp;
		$db = DB::get();

		if ( !current_user_can(SHOPP_USERLEVEL) )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$defaults = array(
			'cat' => false,
			'pagenum' => 1,
			'per_page' => 20,
			's' => '',
			'sl' => '',
			'matchcol' => ''
			);
		
		$args = array_merge($defaults,$_GET);
		extract($args,EXTR_SKIP);

		if (!$workflow) {		
			if (empty($categories)) $categories = array('');
		
			$category_table = DatabaseObject::tablename(Category::$table);
			$query = "SELECT id,name,parent FROM $category_table ORDER BY parent,name";
			$categories = $db->query($query,AS_ARRAY);
			$categories = sort_tree($categories);
			if (empty($categories)) $categories = array();
		
			$categories_menu = '<option value="">'.__('View all categories','Shopp').'</option>';
			$categories_menu .= '<option value="-"'.($cat=='-'?' selected="selected"':'').'>'.__('Uncategorized','Shopp').'</option>';
			foreach ($categories as $category) {
				$padding = str_repeat("&nbsp;",$category->depth*3);
				if ($cat == $category->id) $categories_menu .= '<option value="'.$category->id.'" selected="selected">'.$padding.$category->name.'</option>';
				else $categories_menu .= '<option value="'.$category->id.'">'.$padding.$category->name.'</option>';
			}
			$inventory_filters = array(
				'all' => __('View all products','Shopp'),
				'is' => __('In stock','Shopp'),
				'ls' => __('Low stock','Shopp'),
				'oos' => __('Out-of-stock','Shopp'),
				'ns' => __('Not stocked','Shopp')
			);
			$inventory_menu = menuoptions($inventory_filters,$sl,true);
		}
		
		$pagenum = absint( $pagenum );
		if ( empty($pagenum) )
			$pagenum = 1;
		if( !$per_page || $per_page < 0 )
			$per_page = 20;
		$start = ($per_page * ($pagenum-1)); 
		
		$pd = DatabaseObject::tablename(Product::$table);
		$pt = DatabaseObject::tablename(Price::$table);
		$catt = DatabaseObject::tablename(Category::$table);
		$clog = DatabaseObject::tablename(Catalog::$table);

		$orderby = "pd.created DESC";
		
		$where = "true";
		$having = "";
		if (!empty($s)) {
			if (strpos($s,"sku:") !== false) { // SKU search
				$where .= ' AND pt.sku="'.substr($s,4).'"';
				$orderby = "pd.name";
			} else {                                   // keyword search
				$interference = array("'s","'",".","\"");
				$search = preg_replace('/(\s?)(\w+)(\s?)/','\1*\2*\3',str_replace($interference,"", stripslashes($s)));
				$match = "MATCH(pd.name,pd.summary,pd.description) AGAINST ('$search' IN BOOLEAN MODE)";
				$where .= " AND $match";
				$matchcol = ", $match AS score";
				$orderby = "score DESC";         
			}
		}
		// if (!empty($cat)) $where .= " AND cat.id='$cat' AND (clog.category != 0 OR clog.id IS NULL)";
		if (!empty($cat)) {
			if ($cat == "-") {
				$having = "HAVING COUNT(cat.id) = 0";
			} else {
				$matchcol .= ", GROUP_CONCAT(DISTINCT cat.id ORDER BY cat.id SEPARATOR ',') AS catids";
				$where .= " AND (clog.category != 0 OR clog.id IS NULL)";	
				$having = "HAVING FIND_IN_SET('$cat',catids) > 0";
			}
		}
		if (!empty($sl)) {
			switch($sl) {
				case "ns": $where .= " AND pt.inventory='off'"; break;
				case "oos": 
					$where .= " AND (pt.inventory='on')"; 
					$having .= (empty($having)?"HAVING ":" AND ")."SUM(pt.stock) = 0";
					break;
				case "ls":
					$ls = $Shopp->Settings->get('lowstock_level');
					if (empty($ls)) $ls = '0';
					$where .= " AND (pt.inventory='on' AND pt.stock <= $ls AND pt.stock > 0)"; 
					break;
				case "is": $where .= " AND (pt.inventory='on' AND pt.stock > 0)";
			}
		}
		
		$base = $Shopp->Settings->get('base_operations');
		if ($base['vat']) $taxrate = $Shopp->Cart->taxrate();
		if (empty($taxrate)) $taxrate = 0;
		
		$columns = "SQL_CALC_FOUND_ROWS pd.id,pd.name,pd.slug,pd.featured,pd.variations,GROUP_CONCAT(DISTINCT cat.name ORDER BY cat.name SEPARATOR ', ') AS categories,if(pt.options=0,IF(pt.tax='off',pt.price,pt.price+(pt.price*$taxrate)),-1) AS mainprice,IF(MAX(pt.tax)='off',MAX(pt.price),MAX(pt.price+(pt.price*$taxrate))) AS maxprice,IF(MAX(pt.tax)='off',MIN(pt.price),MIN(pt.price+(pt.price*$taxrate))) AS minprice,IF(pt.inventory='on','on','off') AS inventory,ROUND(SUM(pt.stock)/count(DISTINCT clog.id),0) AS stock";
		if ($workflow) $columns = "pd.id";
		// Load the products
		$query = "SELECT $columns $matchcol FROM $pd AS pd LEFT JOIN $pt AS pt ON pd.id=pt.product AND pt.type != 'N/A' LEFT JOIN $clog AS clog ON pd.id=clog.product LEFT JOIN $catt AS cat ON cat.id=clog.category WHERE $where GROUP BY pd.id $having ORDER BY $orderby LIMIT $start,$per_page";
		$Products = $db->query($query,AS_ARRAY);
		$productcount = $db->query("SELECT FOUND_ROWS() as total");

		$num_pages = ceil($productcount->total / $per_page);
		$page_links = paginate_links( array(
			'base' => add_query_arg(array("edit"=>null,'pagenum' => '%#%')),
			'format' => '',
			'total' => $num_pages,
			'current' => $pagenum,
		));

		if ($workflow) return $Products;
		
		include("{$this->basepath}/core/ui/products/products.php");
	}

	function products_list_columns () {
		shopp_register_column_headers('shopp_page_shopp-products', array(
			'cb'=>'<input type="checkbox" />',
			'name'=>__('Name','Shopp'),
			'category'=>__('Category','Shopp'),
			'price'=>__('Price','Shopp'),
			'inventory'=>__('Inventory','Shopp'),
			'featured'=>__('Featured','Shopp'))
		);
	}	
	
	function product_shortcode ($atts) {
		global $Shopp;

		if (isset($atts['name'])) {
			$Shopp->Product = new Product($atts['name'],'name');
		} elseif (isset($atts['slug'])) {
			$Shopp->Product = new Product($atts['slug'],'slug');
		} elseif (isset($atts['id'])) {
			$Shopp->Product = new Product($atts['id']);
		} else return "";
		
		if (isset($atts['nowrap']) && value_is_true($atts['nowrap']))
			return $Shopp->Catalog->tag('product',$atts);
		else return '<div id="shopp">'.$Shopp->Catalog->tag('product',$atts).'<div class="clear"></div></div>';
	}
	
	function category_shortcode ($atts) {
		global $Shopp;
		
		$tag = 'category';
		if (isset($atts['name'])) {
			$Shopp->Category = new Category($atts['name'],'name');
			unset($atts['name']);
		} elseif (isset($atts['slug'])) {
			switch ($atts['slug']) {
				case SearchResults::$_slug: $tag = 'search-products'; unset($atts['slug']);
				 break;
				case TagProducts::$_slug: $tag = 'tag-products'; unset($atts['slug']);
				 break;
				case BestsellerProducts::$_slug: $tag = 'bestseller-products'; unset($atts['slug']);
				 break;
				case CatalogProducts::$_slug: $tag = 'catalog-products'; unset($atts['slug']);
				 break;
				case NewProducts::$_slug: $tag = 'new-products'; unset($atts['slug']);
				 break;
				case FeaturedProducts::$_slug: $tag = 'featured-products'; unset($atts['slug']);
				 break;
				case OnSaleProducts::$_slug: $tag = 'onsale-products'; unset($atts['slug']);
				 break;
				case RandomProducts::$_slug: $tag = 'random-products'; unset($atts['slug']);
				 break;
			}
		} elseif (isset($atts['id'])) {
			$Shopp->Category = new Category($atts['id']);
			unset($atts['id']);
		} else return "";
		
		if (isset($atts['nowrap']) && value_is_true($atts['nowrap']))
			return $Shopp->Catalog->tag($tag,$atts);
		else return '<div id="shopp">'.$Shopp->Catalog->tag($tag,$atts).'<div class="clear"></div></div>';
		
	}
	
	function maintenance_shortcode ($atts) {
		return '<div id="shopp" class="update"><p>The store is currently down for maintenance.  We\'ll be back soon!</p><div class="clear"></div></div>';
	}
	
	function product_editor_ui () {
		global $Shopp;
		include("{$this->basepath}/core/ui/products/ui.php");
	}

	function product_editor() {
		global $Shopp;
		$db = DB::get();
		
		if ( !current_user_can(SHOPP_USERLEVEL) )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		if (empty($Shopp->Product)) {
			$Product = new Product();
			$Product->published = "on";
		} else $Product = $Shopp->Product;
		
		// $Product->load_data(array('images'));
		// echo "<pre>"; print_r($Product->imagesets); echo "</pre>";
		
		$Product->slug = apply_filters('editable_slug',$Product->slug);
		$permalink = $Shopp->shopuri;

		require_once("{$this->basepath}/core/model/Asset.php");
		require_once("{$this->basepath}/core/model/Category.php");

		$Price = new Price();
		$priceTypes = array(
			array('value'=>'Shipped','label'=>__('Shipped','Shopp')),
			array('value'=>'Virtual','label'=>__('Virtual','Shopp')),
			array('value'=>'Download','label'=>__('Download','Shopp')),
			array('value'=>'Donation','label'=>__('Donation','Shopp')),
			array('value'=>'N/A','label'=>__('Disabled','Shopp')),
		);
		
		$workflows = array(
			"continue" => __('Continue Editing','Shopp'),
			"close" => __('Products Manager','Shopp'),
			"new" => __('New Product','Shopp'),
			"next" => __('Edit Next','Shopp'),
			"previous" => __('Edit Previous','Shopp')
			);
		
		$taglist = array();
		foreach ($Product->tags as $tag) $taglist[] = $tag->name;

		if ($Product->id) {
			$Assets = new Asset();
			$Images = $db->query("SELECT id,src,properties FROM $Assets->_table WHERE context='product' AND parent=$Product->id AND datatype='thumbnail' ORDER BY sortorder",AS_ARRAY);
			unset($Assets);			
		}

		$shiprates = $this->Settings->get('shipping_rates');
		if (!empty($shiprates)) ksort($shiprates);

		$uploader = $Shopp->Settings->get('uploader_pref');
		if (!$uploader) $uploader = 'flash';

		$process = (!empty($Product->id)?$Product->id:'new');
		$_POST['action'] = add_query_arg(array_merge($_GET,array('page'=>$this->Admin->products)),$Shopp->wpadminurl."admin.php");
		
		include("{$this->basepath}/core/ui/products/editor.php");

	}

	function save_product($Product) {
		global $Shopp;
		$db = DB::get();
		check_admin_referer('shopp-save-product');

		if ( !current_user_can(SHOPP_USERLEVEL) )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$this->settings_save(); // Save workflow setting

		$base = $Shopp->Settings->get('base_operations');
		$taxrate = 0;
		if ($base['vat']) $taxrate = $Shopp->Cart->taxrate();

		if (!$_POST['options']) $Product->options = array();
		else $_POST['options'] = stripslashes_deep($_POST['options']);

		if (empty($Product->slug)) $Product->slug = sanitize_title_with_dashes($_POST['name']);	

		// Check for an existing product slug
		$exclude_product = !empty($Product->id)?"AND id != $Product->id":"";
		$existing = $db->query("SELECT slug FROM $Product->_table WHERE slug='$Product->slug' $exclude_product LIMIT 1");
		if ($existing) {
			$suffix = 2;
			while($existing) {
				$altslug = substr($Product->slug, 0, 200-(strlen($suffix)+1)). "-$suffix";
				$existing = $db->query("SELECT slug FROM $Product->_table WHERE slug='$altslug' $exclude_product LIMIT 1");
				$suffix++;
			}
			$Product->slug = $altslug;
		}
		
		if (isset($_POST['content'])) $_POST['description'] = $_POST['content'];
		$Product->updates($_POST,array('categories'));
		$Product->save();

		$Product->save_categories($_POST['categories']);
		$Product->save_tags(explode(",",$_POST['taglist']));
		
		if (!empty($_POST['price']) && is_array($_POST['price'])) {

			// Delete prices that were marked for removal
			if (!empty($_POST['deletePrices'])) {
				$deletes = array();
				if (strpos($_POST['deletePrices'],","))	$deletes = explode(',',$_POST['deletePrices']);
				else $deletes = array($_POST['deletePrices']);
			
				foreach($deletes as $option) {
					$Price = new Price($option);
					$Price->delete();
				}
			}

			// Save prices that there are updates for
			foreach($_POST['price'] as $i => $option) {
				if (empty($option['id'])) {
					$Price = new Price();
					$option['product'] = $Product->id;
				} else $Price = new Price($option['id']);
				$option['sortorder'] = array_search($i,$_POST['sortorder'])+1;
				
				// Remove VAT amount to save in DB
				if ($base['vat'] && $option['tax'] == "on") {
					$option['price'] = number_format(floatnum($option['price'])/(1+$taxrate),2);
					$option['saleprice'] = number_format(floatnum($option['saleprice'])/(1+$taxrate),2);
				}
				
				$Price->updates($option);
				$Price->save();
				if (!empty($option['download'])) $Price->attach_download($option['download']);
				if (!empty($option['downloadpath'])) {
					$basepath = trailingslashit($Shopp->Settings->get('products_path'));
					$download = $basepath.ltrim($option['downloadpath'],"/");
					if (file_exists($download)) {
						$File = new Asset();
						$File->parent = 0;
						$File->context = "price";
						$File->datatype = "download";
						$File->name = basename($download);
						$File->value = substr(dirname($download),strlen($basepath));
						$File->size = filesize($download);
						$File->properties = array("mimetype" => file_mimetype($download,$File->name));
						$File->save();
						$Price->attach_download($File->id);
					}
				}
			}
			unset($Price);
		}
		
		// No variation options at all, delete all variation-pricelines
		if (empty($Product->options) && !empty($Product->prices) && is_array($Product->prices)) { 
			foreach ($Product->prices as $priceline) {
				// Skip if not tied to variation options
				if ($priceline->optionkey == 0) continue; 
				$Price = new Price($priceline->id);
				$Price->delete();
			}
		}
			
		if (!empty($_POST['details']) || !empty($_POST['deletedSpecs'])) {
			$deletes = array();
			if (!empty($_POST['deletedSpecs'])) {
				if (strpos($_POST['deletedSpecs'],","))	$deletes = explode(',',$_POST['deletedSpecs']);
				else $deletes = array($_POST['deletedSpecs']);
				foreach($deletes as $option) {
					$Spec = new Spec($option);
					$Spec->delete();
				}
				unset($Spec);
			}

			if (is_array($_POST['details'])) {
				foreach ($_POST['details'] as $i => $spec) {
					if (in_array($spec['id'],$deletes)) continue;
					if (isset($spec['new'])) {
						$Spec = new Spec();
						$spec['id'] = '';
						$spec['product'] = $Product->id;
					} else $Spec = new Spec($spec['id']);
					$spec['sortorder'] = array_search($i,$_POST['details-sortorder'])+1;
					
					$Spec->updates($spec);
					if (preg_match('/^.*?(\d+[\.\,\d]*).*$/',$spec['content']))
						$Spec->numeral = preg_replace('/^.*?(\d+[\.\,\d]*).*$/','$1',$spec['content']);
					
					$Spec->save();
				}
			}
		}
		
		if (!empty($_POST['deleteImages'])) {			
			$deletes = array();
			if (strpos($_POST['deleteImages'],","))	$deletes = explode(',',$_POST['deleteImages']);
			else $deletes = array($_POST['deleteImages']);
			$Product->delete_images($deletes);
		}
		
		if (!empty($_POST['images']) && is_array($_POST['images'])) {
			$Product->link_images($_POST['images']);
			$Product->save_imageorder($_POST['images']);
			if (!empty($_POST['imagedetails']))
				$Product->update_images($_POST['imagedetails']);
		}
		
		do_action_ref_array('shopp_product_saved',array(&$Product));
		
		unset($Product);
		return true;
	}
	
	function product_downloads () {
		$error = false;
		if (isset($_FILES['Filedata']['error'])) $error = $_FILES['Filedata']['error'];
		if ($error) die(json_encode(array("error" => $this->uploadErrors[$error])));
		
		// Save the uploaded file
		$File = new Asset();
		$File->parent = 0;
		$File->context = "price";
		$File->datatype = "download";
		$File->name = $_FILES['Filedata']['name'];
		$File->size = filesize($_FILES['Filedata']['tmp_name']);
		$File->properties = array("mimetype" => file_mimetype($_FILES['Filedata']['tmp_name'],$File->name));
		$File->data = addslashes(file_get_contents($_FILES['Filedata']['tmp_name']));
		$File->save();
		unset($File->data); // Remove file contents from memory
		
		do_action('add_product_download',$File,$_FILES['Filedata']);
		
		echo json_encode(array("id"=>$File->id,"name"=>stripslashes($File->name),"type"=>$File->properties['mimetype'],"size"=>$File->size));
	}
	
	function add_images () {
			$QualityValue = array(100,92,80,70,60);
			
			$error = false;
			if (isset($_FILES['Filedata']['error'])) $error = $_FILES['Filedata']['error'];
			if ($error) die(json_encode(array("error" => $this->uploadErrors[$error])));

			require("{$this->basepath}/core/model/Image.php");
			
			if (isset($_POST['product'])) {
				$parent = $_POST['product'];
				$context = "product";
			}

			if (isset($_POST['category'])) {
				$parent = $_POST['category'];
				$context = "category";
			}
			
			// Save the source image
			$Image = new Asset();
			$Image->parent = $parent;
			$Image->context = $context;
			$Image->datatype = "image";
			$Image->name = $_FILES['Filedata']['name'];
			list($width, $height, $mimetype, $attr) = getimagesize($_FILES['Filedata']['tmp_name']);
			$Image->properties = array(
				"width" => $width,
				"height" => $height,
				"mimetype" => image_type_to_mime_type($mimetype),
				"attr" => $attr);
			$Image->data = addslashes(file_get_contents($_FILES['Filedata']['tmp_name']));
			$Image->save();
			unset($Image->data); // Save memory for small image & thumbnail processing

			// Generate Small Size
			$SmallSettings = array();
			$SmallSettings['width'] = $this->Settings->get('gallery_small_width');
			$SmallSettings['height'] = $this->Settings->get('gallery_small_height');
			$SmallSettings['sizing'] = $this->Settings->get('gallery_small_sizing');
			$SmallSettings['quality'] = $this->Settings->get('gallery_small_quality');
			
			$Small = new Asset();
			$Small->parent = $Image->parent;
			$Small->context = $context;
			$Small->datatype = "small";
			$Small->src = $Image->id;
			$Small->name = "small_".$Image->name;
			$Small->data = file_get_contents($_FILES['Filedata']['tmp_name']);
			$SmallSizing = new ImageProcessor($Small->data,$width,$height);
			
			switch ($SmallSettings['sizing']) {
				// case "0": $SmallSizing->scaleToWidth($SmallSettings['width']); break;
				// case "1": $SmallSizing->scaleToHeight($SmallSettings['height']); break;
				case "0": $SmallSizing->scaleToFit($SmallSettings['width'],$SmallSettings['height']); break;
				case "1": $SmallSizing->scaleCrop($SmallSettings['width'],$SmallSettings['height']); break;
			}
			$SmallSizing->UnsharpMask(75);
			$Small->data = addslashes($SmallSizing->imagefile($QualityValue[$SmallSettings['quality']]));
			$Small->properties = array();
			$Small->properties['width'] = $SmallSizing->Processed->width;
			$Small->properties['height'] = $SmallSizing->Processed->height;
			$Small->properties['mimetype'] = "image/jpeg";
			unset($SmallSizing);
			$Small->save();
			unset($Small);
			
			// Generate Thumbnail
			$ThumbnailSettings = array();
			$ThumbnailSettings['width'] = $this->Settings->get('gallery_thumbnail_width');
			$ThumbnailSettings['height'] = $this->Settings->get('gallery_thumbnail_height');
			$ThumbnailSettings['sizing'] = $this->Settings->get('gallery_thumbnail_sizing');
			$ThumbnailSettings['quality'] = $this->Settings->get('gallery_thumbnail_quality');

			$Thumbnail = new Asset();
			$Thumbnail->parent = $Image->parent;
			$Thumbnail->context = $context;
			$Thumbnail->datatype = "thumbnail";
			$Thumbnail->src = $Image->id;
			$Thumbnail->name = "thumbnail_".$Image->name;
			$Thumbnail->data = file_get_contents($_FILES['Filedata']['tmp_name']);
			$ThumbnailSizing = new ImageProcessor($Thumbnail->data,$width,$height);
			
			switch ($ThumbnailSettings['sizing']) {
				// case "0": $ThumbnailSizing->scaleToWidth($ThumbnailSettings['width']); break;
				// case "1": $ThumbnailSizing->scaleToHeight($ThumbnailSettings['height']); break;
				case "0": $ThumbnailSizing->scaleToFit($ThumbnailSettings['width'],$ThumbnailSettings['height']); break;
				case "1": $ThumbnailSizing->scaleCrop($ThumbnailSettings['width'],$ThumbnailSettings['height']); break;
			}
			$ThumbnailSizing->UnsharpMask();
			$Thumbnail->data = addslashes($ThumbnailSizing->imagefile($QualityValue[$ThumbnailSettings['quality']]));
			$Thumbnail->properties = array();
			$Thumbnail->properties['width'] = $ThumbnailSizing->Processed->width;
			$Thumbnail->properties['height'] = $ThumbnailSizing->Processed->height;
			$Thumbnail->properties['mimetype'] = "image/jpeg";
			unset($ThumbnailSizing);
			$Thumbnail->save();
			unset($Thumbnail->data);
						
			echo json_encode(array("id"=>$Thumbnail->id,"src"=>$Thumbnail->src));
	}
		
	/**
	 * Category flow handlers
	 **/	
	function categories_list ($workflow=false) {
		global $Shopp;
		$db = DB::get();

		if ( !current_user_can(SHOPP_USERLEVEL) )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$defaults = array(
			'pagenum' => 1,
			'per_page' => 20,
			's' => ''
			);
		$args = array_merge($defaults,$_GET);
		extract($args,EXTR_SKIP);

		$pagenum = absint( $pagenum );
		if ( empty($pagenum) )
			$pagenum = 1;
		if( !$per_page || $per_page < 0 )
			$per_page = 20;
		$start = ($per_page * ($pagenum-1)); 
		
		$filters = array();
		// $filters['limit'] = "$start,$per_page";
		if (!empty($s)) 
			$filters['where'] = "cat.name LIKE '%$s%'";
		else $filters['where'] = "true";
		
		$table = DatabaseObject::tablename(Category::$table);
		$Catalog = new Catalog();
		$Catalog->outofstock = true;
		if ($workflow) {
			$filters['columns'] = "cat.id,cat.parent";
			$results = $Catalog->load_categories($filters,false,true);
			return array_slice($results,$start,$per_page);
		} else {
			$filters['columns'] = "cat.id,cat.parent,cat.name,cat.description,cat.uri,cat.slug,cat.spectemplate,cat.facetedmenus,count(DISTINCT pd.id) AS total";
			
			$Catalog->load_categories($filters);
			$Categories = array_slice($Catalog->categories,$start,$per_page);
		}

		$count = $db->query("SELECT count(*) AS total FROM $table");
		$num_pages = ceil($count->total / $per_page);
		$page_links = paginate_links( array(
			'base' => add_query_arg( array('edit'=>null,'pagenum' => '%#%' )),
			'format' => '',
			'total' => $num_pages,
			'current' => $pagenum
		));
		
		include("{$this->basepath}/core/ui/categories/categories.php");
	}

	function categories_list_columns () {
		shopp_register_column_headers('shopp_page_shopp-categories', array(
			'cb'=>'<input type="checkbox" />',
			'name'=>__('Name','Shopp'),
			'description'=>__('Description','Shopp'),
			'links'=>__('Products','Shopp'),
			'templates'=>__('Templates','Shopp'),
			'menus'=>__('Menus','Shopp'))
		);
	}

	function category_editor_ui () {
		global $Shopp;
		include("{$this->basepath}/core/ui/categories/ui.php");
	}
	
	function category_editor () {
		global $Shopp;
		$db = DB::get();
		
		if ( !current_user_can(SHOPP_USERLEVEL) )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		if (empty($Shopp->Category)) $Category = new Category();
		else $Category = $Shopp->Category;

		$Price = new Price();
		$priceTypes = array(
			array('value'=>'Shipped','label'=>__('Shipped','Shopp')),
			array('value'=>'Virtual','label'=>__('Virtual','Shopp')),
			array('value'=>'Download','label'=>__('Download','Shopp')),
			array('value'=>'Donation','label'=>__('Donation','Shopp')),
			array('value'=>'N/A','label'=>__('N/A','Shopp'))
		);

		
		// Build permalink for slug editor
		$permalink = trailingslashit($Shopp->link('catalog'))."category/";
		$Category->slug = apply_filters('editable_slug',$Category->slug);
		if (!empty($Category->slug))
			$permalink .= substr($Category->uri,0,strpos($Category->uri,$Category->slug));

		$pricerange_menu = array(
			"disabled" => __('Price ranges disabled','Shopp'),
			"auto" => __('Build price ranges automatically','Shopp'),
			"custom" => __('Use custom price ranges','Shopp'),
		);
		
		$Images = array();
		if (!empty($Category->id)) {
			$asset_table = DatabaseObject::tablename(Asset::$table);
			$Images = $db->query("SELECT id,src,properties FROM $asset_table WHERE context='category' AND parent=$Category->id AND datatype='thumbnail' ORDER BY sortorder",AS_ARRAY);
		}
		
		$categories_menu = $this->category_menu($Category->parent,$Category->id);
		$categories_menu = '<option value="0" rel="-1,-1">'.__('Parent Category','Shopp').'&hellip;</option>'.$categories_menu;

		$uploader = $Shopp->Settings->get('uploader_pref');
		if (!$uploader) $uploader = 'flash';
		
		$workflows = array(
			"continue" => __('Continue Editing','Shopp'),
			"close" => __('Categories Manager','Shopp'),
			"new" => __('New Category','Shopp'),
			"next" => __('Edit Next','Shopp'),
			"previous" => __('Edit Previous','Shopp')
			);
		
		include("{$this->basepath}/core/ui/categories/category.php");
	}
	
	function save_category ($Category) {
		global $Shopp;
		$db = DB::get();
		check_admin_referer('shopp-save-category');
		
		if ( !current_user_can(SHOPP_USERLEVEL) )
			wp_die(__('You do not have sufficient permissions to access this page.'));
		
		$this->settings_save(); // Save workflow setting
		
		$Shopp->Catalog = new Catalog();
		$Shopp->Catalog->load_categories(array('where'=>'true'));
		
		if (!isset($_POST['slug']) && empty($Category->slug))
			$Category->slug = sanitize_title_with_dashes($_POST['name']);
		if (isset($_POST['slug'])) unset($_POST['slug']);

		// Work out pathing
		$paths = array();
		if (!empty($Category->slug)) $paths = array($Category->slug);  // Include self
		
		$parentkey = -1;
		// If we're saving a new category, lookup the parent
		if ($_POST['parent'] > 0) {
			array_unshift($paths,$Shopp->Catalog->categories[$_POST['parent']]->slug);
			$parentkey = $Shopp->Catalog->categories[$_POST['parent']]->parent;
		}

		while ($category_tree = $Shopp->Catalog->categories[$parentkey]) {
			array_unshift($paths,$category_tree->slug);
			$parentkey = $category_tree->parent;
		}

		if (count($paths) > 1) $_POST['uri'] = join("/",$paths);
		else $_POST['uri'] = $paths[0];
					
		if (!empty($_POST['deleteImages'])) {			
			$deletes = array();
			if (strpos($_POST['deleteImages'],","))	$deletes = explode(',',$_POST['deleteImages']);
			else $deletes = array($_POST['deleteImages']);
			$Category->delete_images($deletes);
		}

		if (!empty($_POST['images']) && is_array($_POST['images'])) {
			$Category->link_images($_POST['images']);
			$Category->save_imageorder($_POST['images']);
			if (!empty($_POST['imagedetails']) && is_array($_POST['imagedetails'])) {
				foreach($_POST['imagedetails'] as $i => $data) {
					$Image = new Asset();
					unset($Image->_datatypes['data'],$Image->data);
					$Image->load($data['id']);
					$Image->properties['title'] = $data['title'];
					$Image->properties['alt'] = $data['alt'];
					$Image->save();
				}
			}
		}

		// Variation price templates
		if (!empty($_POST['price']) && is_array($_POST['price'])) {
			foreach ($_POST['price'] as &$pricing) {
				$pricing['price'] = floatvalue($pricing['price']);
				$pricing['saleprice'] = floatvalue($pricing['saleprice']);
				$pricing['shipfee'] = floatvalue($pricing['shipfee']);
			}
			$Category->prices = stripslashes_deep($_POST['price']);
		} else $Category->prices = array();

		if (empty($_POST['specs'])) $Category->specs = array();
		else $_POST['specs'] = stripslashes_deep($_POST['specs']);
		if (empty($_POST['options']) 
			|| (count($_POST['options'])) == 1 && !isset($_POST['options'][1]['options'])) {
				$_POST['options'] = $Category->options = array();
				$_POST['prices'] = $Category->prices = array();
		} else $_POST['options'] = stripslashes_deep($_POST['options']);
		if (isset($_POST['content'])) $_POST['description'] = $_POST['content'];

		$Category->updates($_POST);
		$Category->save();

		do_action_ref_array('shopp_category_saved',array(&$Category));
		
		$updated = '<strong>'.$Category->name.'</strong> '.__('category saved.','Shopp');
		
	}
		
	function category_menu ($selection=false,$current=false) {
		$db = DB::get();
		$table = DatabaseObject::tablename(Category::$table);			
		$categories = $db->query("SELECT id,name,parent FROM $table ORDER BY parent,name",AS_ARRAY);
		$categories = sort_tree($categories);

		$options = '';
		foreach ($categories as $category) {
			$padding = str_repeat("&nbsp;",$category->depth*3);
			$selected = ($category->id == $selection)?' selected="selected"':'';
			$disabled = ($current && $category->id == $current)?' disabled="disabled"':'';
			$options .= '<option value="'.$category->id.'" rel="'.$category->parent.','.$category->depth.'"'.$selected.$disabled.'>'.$padding.$category->name.'</option>';
		}
		return $options;
	}
	
	function category_products () {
		$db = DB::get();
		$catalog = DatabaseObject::tablename(Catalog::$table);
		$category = DatabaseObject::tablename(Category::$table);
		$products = DatabaseObject::tablename(Product::$table);
		$results = $db->query("SELECT p.id,p.name FROM $catalog AS catalog LEFT JOIN $category AS cat ON cat.id = catalog.category LEFT JOIN $products AS p ON p.id=catalog.product WHERE cat.id={$_GET['category']} ORDER BY p.name ASC",AS_ARRAY);
		$products = array();
		
		$products[0] = __("Select a product&hellip;","Shopp");
		foreach ($results as $result) $products[$result->id] = $result->name;
		return menuoptions($products,0,true);
		
	}
	
	function promotions_list () {
		global $Shopp;
		$db = DB::get();

		if ( !current_user_can(SHOPP_USERLEVEL) )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		require_once("{$this->basepath}/core/model/Promotion.php");
		
		$defaults = array(
			'page' => false,
			'deleting' => false,
			'delete' => false,
			'pagenum' => 1,
			'per_page' => 20,
			's' => ''
			);
			
		$args = array_merge($defaults,$_GET);
		extract($args,EXTR_SKIP);
		
		if ($page == "shopp-promotions"
				&& !empty($deleting) 
				&& !empty($delete) 
				&& is_array($delete)) {
			foreach($delete as $deletion) {
				$Promotion = new Promotion($deletion);
				$Promotion->delete();
			}
		}
		
		if (!empty($_POST['save'])) {
			check_admin_referer('shopp-save-promotion');

			if ($_POST['id'] != "new") {
				$Promotion = new Promotion($_POST['id']);
			} else $Promotion = new Promotion();

			if (!empty($_POST['starts']['month']) && !empty($_POST['starts']['date']) && !empty($_POST['starts']['year']))
				$_POST['starts'] = mktime(0,0,0,$_POST['starts']['month'],$_POST['starts']['date'],$_POST['starts']['year']);
			else $_POST['starts'] = 1;

			if (!empty($_POST['ends']['month']) && !empty($_POST['ends']['date']) && !empty($_POST['ends']['year']))
				$_POST['ends'] = mktime(23,59,59,$_POST['ends']['month'],$_POST['ends']['date'],$_POST['ends']['year']);
			else $_POST['ends'] = 1;
			if (isset($_POST['rules'])) $_POST['rules'] = stripslashes_deep($_POST['rules']);

			$Promotion->updates($_POST);
			$Promotion->save();

			do_action_ref_array('shopp_promo_saved',array(&$Promotion));

			if ($Promotion->scope == "Catalog")
				$Promotion->build_discounts();
			
			// Reset cart promotions cache
			// to force reload for these updates
			$Shopp->Cart->data->Promotions = false;
		}
		
		$pagenum = absint( $pagenum );
		if ( empty($pagenum) )
			$pagenum = 1;
		if( !$per_page || $per_page < 0 )
			$per_page = 20;
		$start = ($per_page * ($pagenum-1)); 
		
		
		$where = "";
		if (!empty($s)) $where = "WHERE name LIKE '%$s%'";
		
		$table = DatabaseObject::tablename(Promotion::$table);
		$promocount = $db->query("SELECT count(*) as total FROM $table $where");
		$Promotions = $db->query("SELECT * FROM $table $where",AS_ARRAY);
		
		$status = array(
			'enabled' => __('Enabled','Shopp'),
			'disabled' => __('Disabled','Shopp')
		);
		
		$num_pages = ceil($promocount->total / $per_page);
		$page_links = paginate_links( array(
			'base' => add_query_arg( 'pagenum', '%#%' ),
			'format' => '',
			'total' => $num_pages,
			'current' => $pagenum
		));
		
		include("{$this->basepath}/core/ui/promotions/promotions.php");
	}

	function promotions_list_columns () {
		shopp_register_column_headers('shopp_page_shopp-promotions', array(
			'cb'=>'<input type="checkbox" />',
			'name'=>__('Name','Shopp'),
			'discount'=>__('Discount','Shopp'),
			'applied'=>__('Applied To','Shopp'),
			'eff'=>__('Status','Shopp'))
		);
	}

	function promotion_editor_ui () {
		global $Shopp;
		include("{$this->basepath}/core/ui/promotions/ui.php");
	}
	
	function promotion_editor () {
		global $Shopp;

		if ( !current_user_can(SHOPP_USERLEVEL) )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		require_once("{$this->basepath}/core/model/Promotion.php");

		if ($_GET['id'] != "new") {
			$Promotion = new Promotion($_GET['id']);
		} else $Promotion = new Promotion();
		
		$scopes = array(
			'Catalog' => __('Catalog','Shopp'),
			'Order' => __('Order','Shopp')
		);
		
		$types = array(
			'Percentage Off' => __('Percentage Off','Shopp'),
			'Amount Off' => __('Amount Off','Shopp'),
			'Free Shipping' => __('Free Shipping','Shopp'),
			'Buy X Get Y Free' => __('Buy X Get Y Free','Shopp')			
		);
				
		include("{$this->basepath}/core/ui/promotions/editor.php");
	}
	
	/**
	 * Dashboard Widgets
	 */
	function dashboard_stats ($args=null) {
		global $Shopp;
		$db = DB::get();
		$defaults = array(
			'before_widget' => '',
			'before_title' => '',
			'widget_name' => '',
			'after_title' => '',
			'after_widget' => ''
		);
		if (!$args) $args = array();
		$args = array_merge($defaults,$args);
		if (!empty($args)) extract( $args, EXTR_SKIP );

		echo $before_widget;

		echo $before_title;
		echo $widget_name;
		echo $after_title;
		
		$purchasetable = DatabaseObject::tablename(Purchase::$table);

		$results = $db->query("SELECT count(id) AS orders, SUM(total) AS sales, AVG(total) AS average,
		 						SUM(IF(UNIX_TIMESTAMP(created) > UNIX_TIMESTAMP()-(86400*30),1,0)) AS wkorders,
								SUM(IF(UNIX_TIMESTAMP(created) > UNIX_TIMESTAMP()-(86400*30),total,0)) AS wksales,
								AVG(IF(UNIX_TIMESTAMP(created) > UNIX_TIMESTAMP()-(86400*30),total,null)) AS wkavg
		 						FROM $purchasetable");

		$orderscreen = add_query_arg('page',$this->Admin->orders,$Shopp->wpadminurl."admin.php");
		echo '<div class="table"><table><tbody>';
		echo '<tr><th colspan="2">'.__('Last 30 Days','Shopp').'</th><th colspan="2">'.__('Lifetime','Shopp').'</th></tr>';

		echo '<tr><td class="amount"><a href="'.$orderscreen.'">'.$results->wkorders.'</a></td><td>'.__('Orders','Shopp').'</td>';
		echo '<td class="amount"><a href="'.$orderscreen.'">'.$results->orders.'</a></td><td>'.__('Orders','Shopp').'</td></tr>';

		echo '<tr><td class="amount"><a href="'.$orderscreen.'">'.money($results->wksales).'</a></td><td>'.__('Sales','Shopp').'</td>';
		echo '<td class="amount"><a href="'.$orderscreen.'">'.money($results->sales).'</a></td><td>'.__('Sales','Shopp').'</td></tr>';

		echo '<tr><td class="amount"><a href="'.$orderscreen.'">'.money($results->wkavg).'</a></td><td>'.__('Average Order','Shopp').'</td>';
		echo '<td class="amount"><a href="'.$orderscreen.'">'.money($results->average).'</a></td><td>'.__('Average Order','Shopp').'</td></tr>';

		echo '</tbody></table></div>';
		
		echo $after_widget;
		
	}
	
	function dashboard_orders ($args=null) {
		global $Shopp;
		$db = DB::get();
		$defaults = array(
			'before_widget' => '',
			'before_title' => '',
			'widget_name' => '',
			'after_title' => '',
			'after_widget' => ''
		);
		if (!$args) $args = array();
		$args = array_merge($defaults,$args);
		if (!empty($args)) extract( $args, EXTR_SKIP );
		$statusLabels = $this->Settings->get('order_status');
		
		echo $before_widget;

		echo $before_title;
		echo $widget_name;
		echo $after_title;
		
		$purchasetable = DatabaseObject::tablename(Purchase::$table);
		$purchasedtable = DatabaseObject::tablename(Purchased::$table);
		
		$Orders = $db->query("SELECT p.*,count(i.id) as items FROM $purchasetable AS p LEFT JOIN $purchasedtable AS i ON i.purchase=p.id GROUP BY i.purchase ORDER BY created DESC LIMIT 6",AS_ARRAY);

		if (!empty($Orders)) {
		echo '<table class="widefat">';
		echo '<tr><th scope="col">'.__('Name','Shopp').'</th><th scope="col">'.__('Date','Shopp').'</th><th scope="col" class="num">'.__('Items','Shopp').'</th><th scope="col" class="num">'.__('Total','Shopp').'</th><th scope="col" class="num">'.__('Status','Shopp').'</th></tr>';
		echo '<tbody id="orders" class="list orders">';
		$even = false; 
		foreach ($Orders as $Order) {
			echo '<tr'.((!$even)?' class="alternate"':'').'>';
			$even = !$even;
			echo '<td><a class="row-title" href="'.add_query_arg(array('page'=>$this->Admin->orders,'id'=>$Order->id),$Shopp->wpadminurl."admin.php").'" title="View &quot;Order '.$Order->id.'&quot;">'.((empty($Order->firstname) && empty($Order->lastname))?'(no contact name)':$Order->firstname.' '.$Order->lastname).'</a></td>';
			echo '<td>'.date("Y/m/d",mktimestamp($Order->created)).'</td>';
			echo '<td class="num">'.$Order->items.'</td>';
			echo '<td class="num">'.money($Order->total).'</td>';
			echo '<td class="num">'.$statusLabels[$Order->status].'</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		} else {
			echo '<p>'.__('No orders, yet.','Shopp').'</p>';
		}

		echo $after_widget;
		
	}
	
	function dashboard_products ($args=null) {
		global $Shopp;
		$db = DB::get();
		$defaults = array(
			'before_widget' => '',
			'before_title' => '',
			'widget_name' => '',
			'after_title' => '',
			'after_widget' => ''
		);
		
		if (!$args) $args = array();
		$args = array_merge($defaults,$args);
		if (!empty($args)) extract( $args, EXTR_SKIP );

		echo $before_widget;

		echo $before_title;
		echo $widget_name;
		echo $after_title;

		$RecentBestsellers = new BestsellerProducts(array('where'=>'UNIX_TIMESTAMP(pur.created) > UNIX_TIMESTAMP()-(86400*30)','show'=>3));
		$RecentBestsellers->load_products();

		echo '<table><tbody><tr>';
		echo '<td><h4>'.__('Recent Bestsellers','Shopp').'</h4>';
		echo '<ul>';
		foreach ($RecentBestsellers->products as $product) 
			echo '<li><a href="'.add_query_arg(array('page'=>$this->Admin->editproduct,'id'=>$product->id),$Shopp->wpadminurl."admin.php").'">'.$product->name.'</a> ('.$product->sold.')</li>';
		echo '</ul></td>';
		
		
		$LifetimeBestsellers = new BestsellerProducts(array('show'=>3));
		$LifetimeBestsellers->load_products();
		echo '<td><h4>'.__('Lifetime Bestsellers','Shopp').'</h4>';
		echo '<ul>';
		foreach ($LifetimeBestsellers->products as $product) 
			echo '<li><a href="'.add_query_arg(array('page'=>$this->Admin->editproduct,'id'=>$product->id),$Shopp->wpadminurl."admin.php").'">'.$product->name.'</a> ('.$product->sold.')</li>';
		echo '</ul></td>';
		echo '</tr></tbody></table>';
		echo $after_widget;
		
	}
		
	/**
	 * Settings flow handlers
	 **/
	
	function settings_general () {
		global $Shopp;
		if ( !current_user_can('manage_options') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$country = (isset($_POST['settings']))?$_POST['settings']['base_operations']['country']:'';
		$countries = array();
		$countrydata = $Shopp->Settings->get('countries');
		foreach ($countrydata as $iso => $c) {
			if (isset($_POST['settings']) && $_POST['settings']['base_operations']['country'] == $iso) 
				$base_region = $c['region'];
			$countries[$iso] = $c['name'];
		}
		
		if (!empty($_POST['setup'])) {
			$_POST['settings']['display_welcome'] = "off";
			$this->settings_save();
		}
		
		if (!empty($_POST['save'])) {
			check_admin_referer('shopp-settings-general');
			$vat_countries = $Shopp->Settings->get('vat_countries');
			$zone = $_POST['settings']['base_operations']['zone'];
			$_POST['settings']['base_operations'] = $countrydata[$_POST['settings']['base_operations']['country']];
			$_POST['settings']['base_operations']['country'] = $country;
			$_POST['settings']['base_operations']['zone'] = $zone;
			$_POST['settings']['base_operations']['currency']['format'] = 
				scan_money_format($_POST['settings']['base_operations']['currency']['format']);
			if (in_array($_POST['settings']['base_operations']['country'],$vat_countries)) 
				$_POST['settings']['base_operations']['vat'] = true;
			else $_POST['settings']['base_operations']['vat'] = false;
			
			$this->settings_save();
			$updated = __('Shopp settings saved.');
		}

		$operations = $Shopp->Settings->get('base_operations');
		if (!empty($operations['zone'])) {
			$zones = $Shopp->Settings->get('zones');
			$zones = $zones[$operations['country']];
		}
		
		$targets = $Shopp->Settings->get('target_markets');
		if (!$targets) $targets = array();
		
		$statusLabels = $Shopp->Settings->get('order_status');
		include(SHOPP_ADMINPATH."/settings/settings.php");
	}
	
	function settings_presentation () {
		if ( !current_user_can('manage_options') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		if (isset($_POST['settings']['theme_templates']) && $_POST['settings']['theme_templates'] == "on") 
			$_POST['settings']['theme_templates'] = addslashes(template_path(STYLESHEETPATH.DIRECTORY_SEPARATOR."shopp"));
		if (!empty($_POST['save'])) {
			check_admin_referer('shopp-settings-presentation');
			if (empty($_POST['settings']['catalog_pagination']))
				$_POST['settings']['catalog_pagination'] = 0;
			$this->settings_save();
			$updated = __('Shopp presentation settings saved.');
		}
		
		$builtin_path = $this->basepath.DIRECTORY_SEPARATOR."templates";
		$theme_path = template_path(STYLESHEETPATH.DIRECTORY_SEPARATOR."shopp");

		// Copy templates to the current WordPress theme
		if (!empty($_POST['install'])) {
			check_admin_referer('shopp-settings-presentation');
			copy_shopp_templates($builtin_path,$theme_path);
		}
		
		$status = "available";
		if (!is_dir($theme_path)) $status = "directory";
		else {
			if (!is_writable($theme_path)) $status = "permissions";
			else {
				$builtin = array_filter(scandir($builtin_path),"filter_dotfiles");
				$theme = array_filter(scandir($theme_path),"filter_dotfiles");
				if (empty($theme)) $status = "ready";
				else if (array_diff($builtin,$theme)) $status = "incomplete";
			}
		}		

		$category_views = array("grid" => __('Grid','Shopp'),"list" => __('List','Shopp'));
		$row_products = array(2,3,4,5,6,7);
		$productOrderOptions = Category::sortoptions();
		
		$orderOptions = array("ASC" => __('Order','Shopp'),
							  "DESC" => __('Reverse Order','Shopp'),
							  "RAND" => __('Shuffle','Shopp'));

		$orderBy = array("sortorder" => __('Custom arrangement','Shopp'),
						 "name" => __('File name','Shopp'),
						 "created" => __('Upload date','Shopp'));
		
		$sizingOptions = array(	__('Scale to fit','Shopp'),
								__('Scale &amp; crop','Shopp'));
								
		$qualityOptions = array(__('Highest quality, largest file size','Shopp'),
								__('Higher quality, larger file size','Shopp'),
								__('Balanced quality &amp; file size','Shopp'),
								__('Lower quality, smaller file size','Shopp'),
								__('Lowest quality, smallest file size','Shopp'));
		
		include(SHOPP_ADMINPATH."/settings/presentation.php");
	}

	function settings_catalog () {
		// check_admin_referer('shopp-settings-catalog');
		if ( !current_user_can('manage_options') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		if (!empty($_POST['save'])) $this->settings_save();
		include(SHOPP_ADMINPATH."/settings/catalog.php");
	}

	function settings_cart () {
		if ( !current_user_can('manage_options') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		if (!empty($_POST['save'])) $this->settings_save();
		include(SHOPP_ADMINPATH."/settings/cart.php");
	}

	function settings_checkout () {
		global $Shopp;
		$db =& DB::get();
		if ( !current_user_can('manage_options') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$purchasetable = DatabaseObject::tablename(Purchase::$table);
		$next = $db->query("SELECT IF ((MAX(id)) > 0,(MAX(id)+1),1) AS id FROM $purchasetable LIMIT 1");
		$next_setting = $Shopp->Settings->get('next_order_id');
		
		if ($next->id > $next_setting) $next_setting = $next->id;
		
		if (!empty($_POST['save'])) {
			check_admin_referer('shopp-settings-checkout');
			if ($_POST['settings']['next_order_id'] != $next->id) {
				if ($db->query("ALTER TABLE $purchasetable AUTO_INCREMENT={$_POST['settings']['next_order_id']}"))
					$next->id = $_POST['settings']['next_order_id'];
			} 
				
			$this->settings_save();
			$updated = __('Shopp checkout settings saved.','Shopp');
		}
		
		
		$downloads = array("1","2","3","5","10","15","25","100");
		$promolimit = array("1","2","3","4","5","6","7","8","9","10","15","20","25");
		$time = array(
			'1800' => __('30 minutes','Shopp'),
			'3600' => __('1 hour','Shopp'),
			'7200' => __('2 hours','Shopp'),
			'10800' => __('3 hours','Shopp'),
			'21600' => __('6 hours','Shopp'),
			'43200' => __('12 hours','Shopp'),
			'86400' => __('1 day','Shopp'),
			'172800' => __('2 days','Shopp'),
			'259200' => __('3 days','Shopp'),
			'604800' => __('1 week','Shopp'),
			'2678400' => __('1 month','Shopp'),
			'7952400' => __('3 months','Shopp'),
			'15901200' => __('6 months','Shopp'),
			'31536000' => __('1 year','Shopp'),
			);
								
		include(SHOPP_ADMINPATH."/settings/checkout.php");
	}

	function settings_shipping () {
		global $Shopp;

		if ( !current_user_can('manage_options') )
			wp_die(__('You do not have sufficient permissions to access this page.'));
		
		if (!empty($_POST['save'])) {
			check_admin_referer('shopp-settings-shipping');
			
			// Sterilize $values
			foreach ($_POST['settings']['shipping_rates'] as $i => &$method) {
				$method['name'] = stripslashes($method['name']);
				foreach ($method as $key => &$mr) {
					if (!is_array($mr)) continue;
					foreach ($mr as $id => &$v) {
						if ($v == ">" || $v == "+" || $key == "services") continue;
						$v = floatnum($v);								
					}
				}
			}
			
			$_POST['settings']['order_shipfee'] = floatnum($_POST['settings']['order_shipfee']);
			
	 		$this->settings_save();
			$updated = __('Shipping settings saved.','Shopp');
			$Shopp->ShipCalcs = new ShipCalcs($Shopp->path);
			$rates = $Shopp->Settings->get('shipping_rates');

			$Errors = &ShoppErrors();
			foreach ($rates as $rate) {
				$process = '';
				$ShipCalcClass = $rate['method'];
				if (strpos($rate['method'],'::') != false)
					list($ShipCalcClass,$process) = explode("::",$rate['method']);
					
				if (isset($Shopp->ShipCalcs->modules[$ShipCalcClass]->requiresauth)
					&& $Shopp->ShipCalcs->modules[$ShipCalcClass]->requiresauth) {
						$Shopp->ShipCalcs->modules[$ShipCalcClass]->verifyauth();
						if ($Errors->exist()) $autherrors = $Errors->get();
					}
			}
			
			if (!empty($autherrors)) {
				$updated = __('Shipping settings saved but there were errors: ','Shopp');
				foreach ((array)$autherrors as $error) $updated .= '<p>'.$error->message().'</p>';
				$Errors->reset();
			}
			
		}

		$methods = $Shopp->ShipCalcs->methods;
		$base = $Shopp->Settings->get('base_operations');
		$regions = $Shopp->Settings->get('regions');
		$region = $regions[$base['region']];
		$useRegions = $Shopp->Settings->get('shipping_regions');

		$areas = $Shopp->Settings->get('areas');
		if (is_array($areas[$base['country']]) && $useRegions == "on") 
			$areas = array_keys($areas[$base['country']]);
		else $areas = array($base['country'] => $base['name']);
		unset($countries,$regions);

		$rates = $Shopp->Settings->get('shipping_rates');
		if (!empty($rates)) ksort($rates);
		
		$lowstock = $Shopp->Settings->get('lowstock_level');
		if (empty($lowstock)) $lowstock = 0;
				
		include(SHOPP_ADMINPATH."/settings/shipping.php");
	}

	function settings_taxes () {
		if ( !current_user_can('manage_options') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		if (!empty($_POST['save'])) {
			check_admin_referer('shopp-settings-taxes');
			$this->settings_save();
			$updated = __('Shopp taxes settings saved.','Shopp');
		}
		
		$rates = $this->Settings->get('taxrates');
		$base = $this->Settings->get('base_operations');
		
		$countries = array_merge(array('*' => __('All Markets','Shopp')),
			$this->Settings->get('target_markets'));

		
		$zones = $this->Settings->get('zones');
		
		include(SHOPP_ADMINPATH."/settings/taxes.php");
	}	

	function settings_payments () {
		global $Shopp;
		if ( !current_user_can('manage_options') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		// $gateway_dir = SHOPP_PATH.DIRECTORY_SEPARATOR."gateways".DIRECTORY_SEPARATOR;
		$payment_gateway = gateway_path($this->Settings->get('payment_gateway'));

		if (!empty($_POST['save'])) {
			check_admin_referer('shopp-settings-payments');

			// Update the accepted credit card payment methods
			if (!empty($_POST['settings']['payment_gateway']) 
					&& file_exists(SHOPP_GATEWAYS.$_POST['settings']['payment_gateway'])) {
				$gateway = $this->scan_gateway_meta(SHOPP_GATEWAYS.$_POST['settings']['payment_gateway']);
				$ProcessorClass = $gateway->tags['class'];
				// Load the gateway in case there are any save-time processes to be run
				$Processor = $Shopp->gateway($_POST['settings']['payment_gateway'],true);
				$_POST['settings']['gateway_cardtypes'] = $_POST['settings'][$ProcessorClass]['cards'];
			}
			if (is_array($_POST['settings']['xco_gateways'])) {
				foreach($_POST['settings']['xco_gateways'] as &$gateway) {
					$gateway = str_replace("\\","/",stripslashes($gateway));
					if (!file_exists(SHOPP_GATEWAYS.$gateway)) continue;
					$meta = $this->scan_gateway_meta(SHOPP_GATEWAYS.$gateway);
					$_POST['settings'][$ProcessorClass]['path'] = str_replace("\\","/",stripslashes($_POST['settings'][$ProcessorClass]['path']));
					$ProcessorClass = $meta->tags['class'];
					// Load the gateway in case there are any save-time processes to be run
					$Processor = $Shopp->gateway($gateway);
				}
			}
			
			do_action('shopp_save_payment_settings');

			$this->settings_save();
			$payment_gateway = stripslashes($this->Settings->get('payment_gateway'));
			
			$updated = __('Shopp payments settings saved.','Shopp');
		}

		
		// Get all of the installed gateways
		$data = $this->settings_get_gateways();

		$gateways = array();
		$LocalProcessors = array();
		$XcoProcessors = array();
		foreach ($data as $gateway) {
			$ProcessorClass = $gateway->tags['class'];
			include_once($gateway->file);
			$processor = new $ProcessorClass();
			if (isset($processor->type) && strtolower($processor->type) == "xco") {
				$XcoProcessors[] = $processor;
			} else {
				$gateways[gateway_path($gateway->file)] = $gateway->name;
				$LocalProcessors[] = $processor;
			}
		}

		include(SHOPP_ADMINPATH."/settings/payments.php");
	}
	
	function settings_update () {
		global $Shopp;
		
		if ( !current_user_can('manage_options') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$ftpsupport = (function_exists('ftp_connect'))?true:false;
		
		$credentials = $this->Settings->get('ftp_credentials');	
		if (!isset($credentials['password'])) $credentials['password'] = false;
		$updatekey = $this->Settings->get('updatekey');
		if (empty($updatekey)) 
			$updatekey = array('key' => '','type' => 'single','status' => 'deactivated');

		if (!empty($_POST['save'])) {
			$updatekey['key'] = $_POST['updatekey'];
			$_POST['settings']['updatekey'] = $updatekey;
			$this->settings_save();
		}

		if (!empty($_POST['activation'])) {
			check_admin_referer('shopp-settings-update');
			$updatekey['key'] = trim($_POST['updatekey']);
			$_POST['settings']['updatekey'] = $updatekey;

			$this->settings_save();	
			
			$request = array(
				"ShoppServerRequest" => $_POST['process'],
				"ver" => '1.0',
				"key" => $updatekey['key'],
				"type" => $updatekey['type'],
				"site" => get_bloginfo('siteurl')
			);
			
			$response = $this->callhome($request);
			$response = explode("::",$response);

			if (count($response) == 1)
				$activation = '<span class="shopp error">'.$response[0].'</span>';
			
			if ($_POST['process'] == "activate-key" && $response[0] == "1") {
				$updatekey['type'] = $response[1];
				$type = $updatekey['type'];
				$updatekey['key'] = $response[2];
				$updatekey['status'] = 'activated';
				$this->Settings->save('updatekey',$updatekey);
				$activation = __('This key has been successfully activated.','Shopp');
			}
			
			if ($_POST['process'] == "deactivate-key" && $response[0] == "1") {
				$updatekey['status'] = 'deactivated';
				if ($updatekey['type'] == "dev") $updatekey['key'] = '';
				$this->Settings->save('updatekey',$updatekey);
				$activation = __('This key has been successfully de-activated.','Shopp');
			}
		} else {
			if ($updatekey['status'] == "activated") 
				$activation = __('This key has been successfully activated.','Shopp');
			else $activation = __('Enter your Shopp upgrade key and activate it to enable easy, automatic upgrades.','Shopp');
		}
		
		$type = "text";
		if ($updatekey['status'] == "activated" && $updatekey['type'] == "dev") $type = "password";
		
		include(SHOPP_ADMINPATH."/settings/update.php");
	}
	
	function settings_system () {
		global $Shopp;
		if ( !current_user_can('manage_options') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$error = false;
		
		// Image path processing
		if (isset($_POST['settings']) && isset($_POST['settings']['image_storage_pref'])) 
			$_POST['settings']['image_storage'] = $_POST['settings']['image_storage_pref'];
		$imagepath = $this->Settings->get('image_path');
		if (isset($_POST['settings']['products_path'])) $imagepath = $_POST['settings']['image_path'];
		$imagepath_status = __("File system image hosting is enabled and working.","Shopp");
		if (!file_exists($imagepath)) $error = __("The current path does not exist. Using database instead.","Shopp");
		if (!is_dir($imagepath)) $error = __("The file path supplied is not a directory. Using database instead.","Shopp");
		if (!is_writable($imagepath) || !is_readable($imagepath)) 
			$error = __("Permissions error. This path must be writable by the web server. Using database instead.","Shopp");
		if (empty($imagepath)) $error = __("Enter the absolute path starting from the root of the server file system to your image storage directory.","Shopp");
		if ($error) {
			$_POST['settings']['image_storage'] = 'db';
			$imagepath_status = '<span class="error">'.$error.'</span>';
		}

		// Product path processing
		if (isset($_POST['settings']) && isset($_POST['settings']['product_storage_pref']))
		 	$_POST['settings']['product_storage'] = $_POST['settings']['product_storage_pref'];
		$productspath = $this->Settings->get('products_path');
		if (isset($_POST['settings']['products_path'])) $productspath = $_POST['settings']['products_path'];
		$error = ""; // Reset the error tracker
		$productspath_status = __("File system product file hosting is enabled and working.","Shopp");
		if (!file_exists($productspath)) $error = __("The current path does not exist. Using database instead.","Shopp");
		if (!is_dir($productspath)) $error = __("The file path supplied is not a directory. Using database instead.","Shopp");
		if (!is_writable($productspath) || !is_readable($productspath)) 
			$error = __("Permissions error. This path must be writable by the web server. Using database instead.","Shopp");
		if (empty($productspath)) $error = __("Enter the absolute path starting from the root of the server file system to your product file storage directory.","Shopp");
		if ($error) {
			$_POST['settings']['product_storage'] = 'db';
			$productspath_status = '<span class="error">'.$error.'</span>';
		}

		if (!empty($_POST['save'])) {
			check_admin_referer('shopp-settings-system');
			
			if (!isset($_POST['settings']['error_notifications'])) 
				$_POST['settings']['error_notifications'] = array();
			
			$this->settings_save();

			// Reinitialize Error System
			$Shopp->Cart->data->Errors = new ShoppErrors();
			$Shopp->ErrorLog = new ShoppErrorLogging($this->Settings->get('error_logging'));
			$Shopp->ErrorNotify = new ShoppErrorNotification($this->Settings->get('merchant_email'),
										$this->Settings->get('error_notifications'));

			$updated = __('Shopp system settings saved.','Shopp');
		}
		
		if (isset($_POST['resetlog'])) $Shopp->ErrorLog->reset();
		
		$notifications = $this->Settings->get('error_notifications');
		if (empty($notifications)) $notifications = array();
		
		$notification_errors = array(
			SHOPP_TRXN_ERR => __("Transaction Errors","Shopp"),
			SHOPP_AUTH_ERR => __("Login Errors","Shopp"),
			SHOPP_ADDON_ERR => __("Add-on Errors","Shopp"),
			SHOPP_COMM_ERR => __("Communication Errors","Shopp"),
			SHOPP_STOCK_ERR => __("Inventory Warnings","Shopp")
			);
		
		$errorlog_levels = array(
			0 => __("Disabled","Shopp"),
			SHOPP_ERR => __("General Shopp Errors","Shopp"),
			SHOPP_TRXN_ERR => __("Transaction Errors","Shopp"),
			SHOPP_AUTH_ERR => __("Login Errors","Shopp"),
			SHOPP_ADDON_ERR => __("Add-on Errors","Shopp"),
			SHOPP_COMM_ERR => __("Communication Errors","Shopp"),
			SHOPP_STOCK_ERR => __("Inventory Warnings","Shopp"),
			SHOPP_ADMIN_ERR => __("Admin Errors","Shopp"),
			SHOPP_DB_ERR => __("Database Errors","Shopp"),
			SHOPP_PHP_ERR => __("PHP Errors","Shopp"),
			SHOPP_ALL_ERR => __("All Errors","Shopp"),
			SHOPP_DEBUG_ERR => __("Debugging Messages","Shopp")
			);
								
		$filesystems = array("db" => __("Database","Shopp"),"fs" => __("File System","Shopp"));
		
		$loading = array("shopp" => __('Load on Shopp-pages only','Shopp'),"all" => __('Load on entire site','Shopp'));
		
		if ($this->Settings->get('error_logging') > 0)
			$recentlog = $Shopp->ErrorLog->tail(500);
			
		include(SHOPP_ADMINPATH."/settings/system.php");
	}	
	
	function settings_get_gateways () {
		$gateway_path = $this->basepath.DIRECTORY_SEPARATOR."gateways";
		
		$gateways = array();
		$gwfiles = array();
		find_files(".php",$gateway_path,$gateway_path,$gwfiles);
		if (empty($gwfiles)) return $gwfiles;
		
		foreach ($gwfiles as $file) {
			if (! is_readable($gateway_path.$file)) continue;
			if (! $gateway = $this->scan_gateway_meta($gateway_path.$file)) continue;
			$gateways[$file] = $gateway;
		}

		return $gateways;
	}
	
	function validate_addons () {
		$addons = array();

		$gateway_path = $this->basepath.DIRECTORY_SEPARATOR."gateways";		
		find_files(".php",$gateway_path,$gateway_path,$gateways);
		foreach ($gateways as $file) {
			if (in_array(basename($file),$this->coremods)) continue;
			$addons[] = md5_file($gateway_path.$file);
		}

		$shipping_path = $this->basepath.DIRECTORY_SEPARATOR."shipping";
		find_files(".php",$shipping_path,$shipping_path,$shipmods);
		foreach ($shipmods as $file) {
			if (in_array(basename($file),$this->coremods)) continue;
			$addons[] = md5_file($shipping_path.$file);
		}
		return $addons;
	}

	function scan_gateway_meta ($file) {
		$metadata = array();
		
		$meta = get_filemeta($file);

		if ($meta) {
			$lines = explode("\n",substr($meta,1));
			foreach($lines as $line) {
				preg_match("/^(?:[\s\*]*?\b([^@\*\/]*))/",$line,$match);
				if (!empty($match[1])) $data[] = $match[1];
				preg_match("/^(?:[\s\*]*?@([^\*\/]+?)\s(.+))/",$line,$match);
				if (!empty($match[1]) && !empty($match[2])) $tags[$match[1]] = $match[2];
			
			}
			$gateway = new stdClass();
			$gateway->file = $file;
			$gateway->name = $data[0];
			$gateway->description = (!empty($data[1]))?$data[1]:"";
			$gateway->tags = $tags;
			$gateway->activated = false;
			if ($this->Settings->get('payment_gateway') == $file) $module->activated = true;
			return $gateway;
		}
		return false;
	}
	
	function settings_save () {
		if (empty($_POST['settings']) || !is_array($_POST['settings'])) return false;
		foreach ($_POST['settings'] as $setting => $value)
			$this->Settings->save($setting,$value);
	}
	
	/**
	 * Installation, upgrade and initialization functions
	 */
	
	function update () {
		global $Shopp,$wp_version;
		$db = DB::get();
		$log = array();

		if (!isset($_POST['update'])) die("Update Failed: Update request is invalid.  No update specified.");
		if (!isset($_POST['type'])) die("Update Failed: Update request is invalid. Update type not specified");
		if (!isset($_POST['password'])) die("Update Failed: Update request is invalid. No FTP password provided.");

		$updatekey = $this->Settings->get('updatekey');
		
		$credentials = $this->Settings->get('ftp_credentials');
		if (empty($credentials)) {
			// Try to load from WordPress settings
			$credentials = get_option('ftp_credentials');
			if (!$credentials) $credentials = array();
		}
		
		// Make sure we can connect to FTP
		$ftp = new FTPClient($credentials['hostname'],$credentials['username'],$_POST['password']);
		if (!$ftp->connected) die("ftp-failed");
		else $log[] = "Connected with FTP successfully.";
		
		// Get zip functions from WP Admin
		if (class_exists('PclZip')) $log[] = "ZIP library available.";
		else {
			@require_once(ABSPATH.'wp-admin/includes/class-pclzip.php');
			$log[] = "ZIP library loaded.";
		}
		
		// Put site in maintenance mode
		if ($this->Settings->get('maintenance') != "on") {
			$this->Settings->save("maintenance","on");
			$log[] = "Enabled maintenance mode.";
		}
		
		// Find our temporary filesystem workspace
		$tmpdir = defined('SHOPP_TEMP_PATH') ? SHOPP_TEMP_PATH : sys_get_temp_dir();
		$tmpdir = str_replace('\\', '/', $tmpdir); //Windows path sanitiation
		
		$log[] = "Found temp directory: $tmpdir";
		
		// Download the new version of Shopp
		$updatefile = tempnam($tmpdir,"shopp_update_");
		if (($download = fopen($updatefile, 'wb')) === false) {
			$log[] = "A temporary file could not be created under $tmpdir, trying WordPress upload directory instead.";
			$tmpdir = trailingslashit(WP_CONTENT_DIR."/uploads");
			$updatefile = tempnam($tmpdir,"shopp_update_");
			$log[] = "Found temp directory: $tmpdir";
			if (($download = fopen($updatefile, 'wb')) === false)
				die(join("\n\n",$log)."\n\nUpdate Failed: Cannot save the Shopp update to the temporary workspace because of a write permission error.");
		}
		
		$query = build_query_request(array(
			"ShoppServerRequest" => "download-update",
			"ver" => "1.0",
		));
		
		$data = build_query_request(array(
			"key" => $updatekey['key'],
			"core" => SHOPP_VERSION,
			"wp" => $wp_version,
			"site" => get_bloginfo('siteurl'),
			"update" => $_POST['update']
		));
		
		$connection = curl_init();
		curl_setopt($connection, CURLOPT_URL, SHOPP_HOME."?".$query); 
		curl_setopt($connection, CURLOPT_USERAGENT, SHOPP_GATEWAY_USERAGENT); 
		curl_setopt($connection, CURLOPT_HEADER, 0);
		curl_setopt($connection, CURLOPT_POST, 1); 
		curl_setopt($connection, CURLOPT_POSTFIELDS, $data);
		curl_setopt($connection, CURLOPT_TIMEOUT, 20); 
	    curl_setopt($connection, CURLOPT_FILE, $download); 
		curl_exec($connection); 
		curl_close($connection);
		fclose($download);

		$downloadsize = filesize($updatefile);
		// Report error message returned by the server request
		if (filesize($updatefile) < 256) die(join("\n\n",$log)."\nUpdate Failed: ".file_get_contents($updatefile));
		
		// Nothing downloaded... couldn't reach the server?
		if (filesize($updatefile) == 0) die(join("\n\n",$log)."\n\Update Failed: The download did not complete succesfully.");
		
		// Download successful, log the size
		$log[] = "Downloaded update of ".number_format($downloadsize)." bytes";
		
		// Extract data
		$log[] = "Unpacking updates...";
		$archive = new PclZip($updatefile);
		$files = $archive->extract(PCLZIP_OPT_EXTRACT_AS_STRING);
		if (!is_array($files)) die(join("\n\n",$log)."\n\nUpdate Failed: The downloaded update did not complete or is corrupted and cannot be used.");
		else unlink($updatefile);
		$target = trailingslashit($tmpdir);
		
		// Move old updates that still exist in $tmpdir to a new location
		if (file_exists($target.$files[0]['filename']) 
			&& is_dir($target.$files[0]['filename']))
			rename($target.$files[0]['filename'],$updatefile.'_old_update');
		
		// Create file structure in working path target
		foreach ($files as $file) {
			if (!$file['folder'] ) {
				if (file_put_contents($target.$file['filename'], $file['content']))
					@chmod($target.$file['filename'], 0644);
			} else {
				if (!is_dir($target.$file['filename'])) {
					if (!@mkdir($target.$file['filename'],0755,true)) 
						die(join("\n\n",$log)."\n\nUpdate Failed: Couldn't create directory $target{$file['filename']}");
				}				
			}
		}
		$log[] = "Successfully unpacked the update.";
		
		// FTP files to make it "easier" than dealing with permissions
		$log[] = "Updating files via FTP connection";
		switch($_POST['type']) {
			case "core":
				$results = $ftp->update($target.$files[0]['filename'],$Shopp->path);
				if (!empty($results)) die(join("\n\n",$log).join("\n\n",$results)."\n\nFTP transfer failed.");
				break;
			case "Payment Gateway":
				$results = $ftp->update($target.$files[0]['filename'],
							$Shopp->path.DIRECTORY_SEPARATOR."gateways".DIRECTORY_SEPARATOR.$files[0]['filename']);
				if (!empty($results)) die(join("\n\n",$log).join("\n\n",$results)."\n\nFTP transfer failed.");
				break;
			case "Shipping Module":
				$results = $ftp->update($target.$files[0]['filename'],
							$Shopp->path.DIRECTORY_SEPARATOR."shipping".DIRECTORY_SEPARATOR.$files[0]['filename']);
				if (!empty($results)) die(join("\n\n",$log).join("\n\n",$results)."\n\nFTP transfer failed.");
				break;
		}
		
		echo "updated"; // Report success!
		exit();
	}
		
	function upgrade () {
		global $Shopp,$table_prefix;
		$db = DB::get();
		require_once(ABSPATH.'wp-admin/includes/upgrade.php');

		// Check for the schema definition file
		if (!file_exists(SHOPP_DBSCHEMA))
		 	die("Could not upgrade the Shopp database tables because the table definitions file is missing: ".SHOPP_DBSCHEMA);
		
		ob_start();
		include(SHOPP_DBSCHEMA);
		$schema = ob_get_contents();
		ob_end_clean();
		
		// Update the table schema
		$tables = preg_replace('/;\s+/',';',$schema);
		dbDelta($tables);
		
		$this->setup_regions();
		$this->setup_countries();
		$this->setup_zones();
		$this->setup_areas();
		$this->setup_vat();
		
		// Update the version number
		$settings = DatabaseObject::tablename(Settings::$table);
		$db->query("UPDATE $settings SET value='".SHOPP_VERSION." WHERE name='version'");
		$db->query("DELETE FROM $settings WHERE name='data_model' OR name='shipcalc_lastscan");
		
		return true;
	}

	function callhome ($request=array(),$data=array()) {
		$query = build_query_request($request);
		$data = build_query_request($data);
		
		$connection = curl_init(); 
		curl_setopt($connection, CURLOPT_URL, SHOPP_HOME."?".$query); 
		curl_setopt($connection, CURLOPT_USERAGENT, SHOPP_GATEWAY_USERAGENT); 
		curl_setopt($connection, CURLOPT_HEADER, 0);
		curl_setopt($connection, CURLOPT_POST, 1); 
		curl_setopt($connection, CURLOPT_POSTFIELDS, $data);
		curl_setopt($connection, CURLOPT_TIMEOUT, 20); 
		curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1); 
		$result = curl_exec($connection); 
		curl_close ($connection);
		
		return $result;
	}


	/**
	 * setup()
	 * Initialize default install settings and lists */
	function setup () {
		
		$this->setup_regions();
		$this->setup_countries();
		$this->setup_zones();
		$this->setup_areas();
		$this->setup_vat();

		$this->Settings->save('show_welcome','on');	
		$this->Settings->save('display_welcome','on');	
		
		// General Settings
		$this->Settings->save('version',SHOPP_VERSION);
		$this->Settings->save('shipping','on');	
		$this->Settings->save('order_status',array('Pending','Completed'));	
		$this->Settings->save('shopp_setup','completed');
		$this->Settings->save('maintenance','off');
		$this->Settings->save('dashboard','on');

		// Checkout Settings
		$this->Settings->save('order_confirmation','ontax');	
		$this->Settings->save('receipt_copy','1');	
		$this->Settings->save('account_system','none');	

		// Presentation Settings
		$this->Settings->save('theme_templates','off');
		$this->Settings->save('row_products','3');
		$this->Settings->save('catalog_pagination','25');
		$this->Settings->save('product_image_order','ASC');
		$this->Settings->save('product_image_orderby','sortorder');
		$this->Settings->save('gallery_small_width','240');
		$this->Settings->save('gallery_small_height','240');
		$this->Settings->save('gallery_small_sizing','1');
		$this->Settings->save('gallery_small_quality','2');
		$this->Settings->save('gallery_thumbnail_width','96');
		$this->Settings->save('gallery_thumbnail_height','96');
		$this->Settings->save('gallery_thumbnail_sizing','1');
		$this->Settings->save('gallery_thumbnail_quality','3');
		
		// System Settinggs
		$this->Settings->save('image_storage_pref','db');
		$this->Settings->save('product_storage_pref','db');
		$this->Settings->save('uploader_pref','flash');
		$this->Settings->save('script_loading','global');

		// Payment Gateway Settings
		$this->Settings->save('PayPalExpress',array('enabled'=>'off'));
		$this->Settings->save('GoogleCheckout',array('enabled'=>'off'));
	}

	function setup_regions () {
		global $Shopp;
		include_once("init.php");
		$this->Settings->save('regions',get_global_regions());
	}
	
	function setup_countries () {
		global $Shopp;
		include_once("init.php");
		$this->Settings->save('countries',addslashes(serialize(get_countries())),false);
	}
	
	function setup_zones () {
		global $Shopp;
		include_once("init.php");
		$this->Settings->save('zones',get_country_zones(),false);
	}

	function setup_areas () {
		global $Shopp;
		include_once("init.php");
		$this->Settings->save('areas',get_country_areas(),false);
	}

	function setup_vat () {
		global $Shopp;
		include_once("init.php");
		$this->Settings->save('vat_countries',get_vat_countries(),false);
	}
	
}
?>