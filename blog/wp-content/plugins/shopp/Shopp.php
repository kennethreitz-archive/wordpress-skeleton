<?php
/*
Plugin Name: Shopp
Version: 1.0.17
Description: Bolt-on ecommerce solution for WordPress
Plugin URI: http://shopplugin.net
Author: Ingenesis Limited
Author URI: http://ingenesis.net

	Portions created by Ingenesis Limited are Copyright © 2008-2009 by Ingenesis Limited

	This file is part of Shopp.

	Shopp is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	Shopp is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with Shopp.  If not, see <http://www.gnu.org/licenses/>.

*/

define('SHOPP_VERSION','1.0.17');
define('SHOPP_REVISION','$Rev: 661 $');
define('SHOPP_GATEWAY_USERAGENT','WordPress Shopp Plugin/'.SHOPP_VERSION);
define('SHOPP_HOME','http://shopplugin.net/');
define('SHOPP_DOCS','http://docs.shopplugin.net/');

require("core/functions.php");
require_once("core/DB.php");
require("core/model/Settings.php");

if (isset($_GET['shopp_image']) || 
		preg_match('/images\/\d+/',$_SERVER['REQUEST_URI'])) 
		shopp_image();
if (isset($_GET['shopp_lookup']) && $_GET['shopp_lookup'] == 'catalog.css') shopp_catalog_css();
if (isset($_GET['shopp_lookup']) && $_GET['shopp_lookup'] == 'settings.js') 
	shopp_settings_js(basename(dirname(__FILE__)));

require("core/Flow.php");
require("core/model/Cart.php");
require("core/model/ShipCalcs.php");
require("core/model/Catalog.php");
require("core/model/Purchase.php");

$Shopp = new Shopp();

class Shopp {
	var $Cart;
	var $Flow;
	var $Settings;
	var $ShipCalcs;
	var $Product;
	var $Category;
	var $Gateway;
	var $Catalog;
	var $_debug;
	
	function Shopp () {
		if (WP_DEBUG) {
			$this->_debug = new StdClass();
			if (function_exists('memory_get_peak_usage'))
				$this->_debug->memory = "Initial: ".number_format(memory_get_peak_usage(true)/1024/1024, 2, '.', ',') . " MB<br />";
			if (function_exists('memory_get_usage'))
				$this->_debug->memory = "Initial: ".number_format(memory_get_usage(true)/1024/1024, 2, '.', ',') . " MB<br />";
		}
		
		$this->path = dirname(__FILE__);
		$this->file = basename(__FILE__);
		$this->directory = basename($this->path);

		$this->uri = WP_PLUGIN_URL."/".$this->directory;
		$this->siteurl = get_bloginfo('url');
		$this->wpadminurl = admin_url();
		
		$this->secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on");
		if ($this->secure) {
			$this->uri = str_replace('http://','https://',$this->uri);
			$this->siteurl = str_replace('http://','https://',$this->siteurl);
			$this->wpadminurl = str_replace('http://','https://',$this->wpadminurl);
		}

		$this->Settings = new Settings();
		$this->Flow = new Flow($this);
		
		register_deactivation_hook($this->directory."/Shopp.php", array(&$this, 'deactivate'));
		register_activation_hook($this->directory."/Shopp.php", array(&$this, 'install'));

		// Keep any DB operations from occuring while in maintenance mode
		if (!empty($_GET['updated']) && 
				($this->Settings->get('maintenance') == "on" || $this->Settings->unavailable)) {
			$this->Flow->upgrade();
			$this->Settings->save("maintenance","off");
		} elseif ($this->Settings->get('maintenance') == "on") {
			add_action('init', array(&$this, 'ajax'));
			add_action('wp', array(&$this, 'shortcodes'));
			return true;
		}
		
		// Initialize defaults if they have not been entered
		if (!$this->Settings->get('shopp_setup')) {
			if ($this->Settings->unavailable) return true;
			$this->Flow->setup();
		}
		
		add_action('init', array(&$this,'init'));
		add_action('init', array(&$this, 'xorder'));
		add_action('init', array(&$this, 'ajax'));
		add_action('parse_request', array(&$this, 'lookups') );
		add_action('parse_request', array(&$this, 'cart'));
		add_action('parse_request', array(&$this, 'checkout'));
		add_action('parse_request', array(&$this, 'catalog') );
		add_action('wp', array(&$this, 'shortcodes'));
		add_action('wp', array(&$this, 'behaviors'));

		// Admin calls
		add_action('admin_menu', array(&$this, 'lookups'));
		add_action('admin_init', array(&$this, 'tinymce'));
		add_action('admin_init', array(&$this->Flow, 'admin'));
		add_action('admin_menu', array(&$this, 'add_menus'));
		add_filter('favorite_actions', array(&$this, 'favorites'));
		add_action('admin_footer', array(&$this, 'footer'));
		add_action('wp_dashboard_setup', array(&$this, 'dashboard_init'));
		add_action('wp_dashboard_widgets', array(&$this, 'dashboard'));
		add_action('admin_print_styles-index.php', array(&$this, 'dashboard_css'));
		add_action('save_post', array(&$this, 'pages_index'),10,2);

		// Theme widgets
		add_action('widgets_init', array(&$this, 'widgets'));
		add_filter('wp_list_pages',array(&$this->Flow,'secure_page_links'));

		add_action('admin_head-options-reading.php',array(&$this,'pages_index'));
		add_action('generate_rewrite_rules',array(&$this,'pages_index'));
		add_filter('rewrite_rules_array',array(&$this,'rewrites'));
		add_filter('query_vars', array(&$this,'queryvars'));
		
		// Extras & Integrations
		add_filter('aioseop_canonical_url', array(&$this,'canonurls'));

		// Start up the cart
		$this->Cart = new Cart();
		
	}
	
	function init() {
		$pages = $this->Settings->get('pages');
		if (SHOPP_PERMALINKS) {
			$this->shopuri = trailingslashit($this->link('catalog'));
			if ($this->shopuri == trailingslashit(get_bloginfo('url'))) $this->shopuri .= "{$pages['catalog']['name']}/";
			$this->imguri = trailingslashit($this->shopuri)."images/";
		} else {
			$this->shopuri = add_query_arg('page_id',$pages['catalog']['id'],get_bloginfo('url'));
			$this->imguri = add_query_arg('shopp_image','=',get_bloginfo('url'));
		}
		if ($this->secure) {
			$this->shopuri = str_replace('http://','https://',$this->shopuri);	
			$this->imguri = str_replace('http://','https://',$this->imguri);	
		}
		
		if (SHOPP_LOOKUP) return true;
		
		// Initialize the session if not already done
		// by another plugin
		if(session_id() == "") @session_start();
		
		// Setup Error handling
		$Errors = &ShoppErrors();
		
		$this->ErrorLog = new ShoppErrorLogging($this->Settings->get('error_logging'));
		$this->ErrorNotify = new ShoppErrorNotification($this->Settings->get('merchant_email'),
									$this->Settings->get('error_notifications'));
									
		if (!$this->Cart->handlers) new ShoppError(__('The Cart session handlers could not be initialized because the session was started by the active theme or an active plugin before Shopp could establish its session handlers. The cart will not function.','Shopp'),'shopp_cart_handlers',SHOPP_ADMIN_ERR);
		if (SHOPP_DEBUG && $this->Cart->handlers) new ShoppError('Session handlers initialized successfully.','shopp_cart_handlers',SHOPP_DEBUG_ERR);
		if (SHOPP_DEBUG) new ShoppError('Session started.','shopp_session_debug',SHOPP_DEBUG_ERR);
		
		// Initialize the catalog and shipping calculators
		$this->Catalog = new Catalog();
		$this->ShipCalcs = new ShipCalcs($this->path);

		// Handle WordPress-processed logins
		$this->Cart->logins();
	}

	/**
	 * install()
	 * Installs the tables and initializes settings */
	function install () {
		global $wpdb,$wp_rewrite;

		// If no settings are available,
		// no tables exist, so this is a
		// new install
		if ($this->Settings->unavailable) 
			include("core/install.php");
		
		$ver = $this->Settings->get('version');		
		if (!empty($ver) && $ver != SHOPP_VERSION)
			$this->Flow->upgrade();
				
		if ($this->Settings->get('shopp_setup')) {
			$this->Settings->save('maintenance','off');
			$this->Settings->save('shipcalc_lastscan','');
			
			// Publish/re-enable Shopp pages
			$filter = "";
			$pages = $this->Settings->get('pages');
			foreach ($pages as $page) $filter .= ($filter == "")?"ID={$page['id']}":" OR ID={$page['id']}";	
			if ($filter != "") $wpdb->query("UPDATE $wpdb->posts SET post_status='publish' WHERE $filter");
			$this->pages_index(true);
			
			// Update rewrite rules
			$wp_rewrite->flush_rules();
			$wp_rewrite->wp_rewrite_rules();
			
		}
		
		if ($this->Settings->get('show_welcome') == "on")
			$this->Settings->save('display_welcome','on');
	}
	
	/**
	 * deactivate()
	 * Resets the data_model to prepare for potential upgrades/changes to the table schema */
	function deactivate() {
		global $wpdb,$wp_rewrite;

		// Unpublish/disable Shopp pages
		$filter = "";
		$pages = $this->Settings->get('pages');
		if (!is_array($pages)) return true;
		foreach ($pages as $page) $filter .= ($filter == "")?"ID={$page['id']}":" OR ID={$page['id']}";	
		if ($filter != "") $wpdb->query("UPDATE $wpdb->posts SET post_status='draft' WHERE $filter");

		// Update rewrite rules
		$wp_rewrite->flush_rules();
		$wp_rewrite->wp_rewrite_rules();

		$this->Settings->save('data_model','');

		return true;
	}
	
	/**
	 * add_menus()
	 * Adds the WordPress admin menus */
	function add_menus () {
		$menus = array();
		if (function_exists('add_object_page')) $menus['main'] = add_object_page('Shopp', 'Shopp', SHOPP_USERLEVEL, $this->Flow->Admin->default, array(&$this,'orders'),$this->uri."/core/ui/icons/shopp.png");
		else $menus['main'] = add_menu_page('Shopp', 'Shopp', SHOPP_USERLEVEL, $this->Flow->Admin->default, array(&$this,'orders'),$this->uri."/core/ui/icons/shopp.png");

		$menus['orders'] = add_submenu_page($this->Flow->Admin->default,__('Orders','Shopp'), __('Orders','Shopp'), SHOPP_USERLEVEL, $this->Flow->Admin->orders, array(&$this,'orders'));

		$menus['customers'] = add_submenu_page($this->Flow->Admin->default,__('Customers','Shopp'), __('Customers','Shopp'), SHOPP_USERLEVEL, $this->Flow->Admin->customers, array(&$this,'customers'));
		if (SHOPP_WP27) $customers_parent = $menus['customers'];
		else $customers_parent = $this->Flow->Admin->default;
		$menus['editcustomer'] = add_submenu_page($customers_parent,__('Edit Customer','Shopp'), false, SHOPP_USERLEVEL, $this->Flow->Admin->editcustomer, array(&$this,'customers'));

		$menus['promotions'] = add_submenu_page($this->Flow->Admin->default,__('Promotions','Shopp'), __('Promotions','Shopp'), SHOPP_USERLEVEL, $this->Flow->Admin->promotions, array(&$this,'promotions'));
		if (SHOPP_WP27) $promos_parent = $menus['promotions'];
		else $promos_parent = $this->Flow->Admin->default;
		$menus['editpromos'] = add_submenu_page($promos_parent,__('Edit Promotion','Shopp'), false, SHOPP_USERLEVEL, $this->Flow->Admin->editpromo, array(&$this,'promotions'));

		$menus['products'] = add_submenu_page($this->Flow->Admin->default,__('Products','Shopp'), __('Products','Shopp'), SHOPP_USERLEVEL, $this->Flow->Admin->products, array(&$this,'products'));
		if (SHOPP_WP27) $products_parent = $menus['products'];
		else $products_parent = $this->Flow->Admin->default;
		$menus['editproducts'] = add_submenu_page($products_parent,__('Product Editor','Shopp'), false, SHOPP_USERLEVEL, $this->Flow->Admin->editproduct, array(&$this,'products'));
		
		$menus['categories'] = add_submenu_page($this->Flow->Admin->default,__('Categories','Shopp'), __('Categories','Shopp'), SHOPP_USERLEVEL, $this->Flow->Admin->categories, array(&$this,'categories'));
		if (SHOPP_WP27) $category_parent = $menus['categories'];
		else $category_parent = $this->Flow->Admin->default;
		$menus['editcategory'] = add_submenu_page($category_parent,__('Edit Category','Shopp'), false, SHOPP_USERLEVEL, $this->Flow->Admin->editcategory, array(&$this,'categories'));
		
		$menus['settings'] = add_submenu_page($this->Flow->Admin->default,__('Settings','Shopp'), __('Settings','Shopp'), 8, $this->Flow->Admin->settings['settings'][0], array(&$this,'settings'));

		$settings_screens = array();
		foreach ($this->Flow->Admin->settings as $key => $screen) {
			if (SHOPP_WP27) $settings_parent = $menus['settings'];
			else $settings_parent = $this->Flow->Admin->default;
			$settings_screens[$key] = add_submenu_page($settings_parent,$screen[1],false, 8, $screen[0], array(&$this,'settings'));
			// echo $settings_screens[$key].BR;
		}

		if (function_exists('add_contextual_help')) {
			foreach ($menus as $menu => $page) $this->Flow->helpdoc($menu,$page);
			foreach ($settings_screens as $menu => $page) $this->Flow->helpdoc($menu,$page);
		} else $menus['help'] = add_submenu_page($this->Flow->Admin->default,__('Help','Shopp'), __('Help','Shopp'), SHOPP_USERLEVEL, $this->Flow->Admin->help, array(&$this,'help'));
		
		// $welcome = add_submenu_page($this->Flow->Admin->default,__('Welcome','Shopp'), __('Welcome','Shopp'), SHOPP_USERLEVEL, $this->Flow->Admin->welcome, array(&$this,'welcome'));

		// add_action("admin_head-$editproduct", array(&$this, 'admin_behaviors'));
		
		foreach($menus as $name => $menu) 
			add_action("admin_print_scripts-$menu", array(&$this, 'admin_behaviors'));

		foreach ($settings_screens as $settings_screen)
			add_action("admin_print_scripts-$settings_screen", array(&$this, 'admin_behaviors'));
		
		add_action("admin_print_scripts-{$menus['orders']}", array(&$this->Flow, 'orders_list_columns'));
		add_action("admin_print_scripts-{$menus['customers']}", array(&$this->Flow, 'customers_list_columns'));
		add_action("admin_print_scripts-{$menus['promotions']}", array(&$this->Flow, 'promotions_list_columns'));
		add_action("admin_print_scripts-{$menus['products']}", array(&$this->Flow, 'products_list_columns'));
		add_action("admin_print_scripts-{$menus['categories']}", array(&$this->Flow, 'categories_list_columns'));
		
		if (SHOPP_WP27)	 {
			add_action("admin_head-{$menus['editproducts']}", array(&$this->Flow, 'product_editor_ui'));
			add_action("admin_head-{$menus['editcustomer']}", array(&$this->Flow, 'customer_editor_ui'));
			add_action("admin_head-{$menus['editcategory']}", array(&$this->Flow, 'category_editor_ui'));
			add_action("admin_head-{$menus['editpromos']}", array(&$this->Flow, 'promotion_editor_ui'));
		}

	}

	function favorites ($actions) {
		$key = add_query_arg(array('page'=>$this->Flow->Admin->editproduct,'id'=>'new'),$this->wpadminurl);
	    $actions[$key] = array(__('New Shopp Product','Shopp'),8);
		return $actions;
	}
		
	/**
	 * admin_behaviors()
	 * Dynamically includes necessary JavaScript and stylesheets for the admin */
	function admin_behaviors () {
		global $wp_version;
		wp_enqueue_script('jquery');
		wp_enqueue_script('shopp',"{$this->uri}/core/ui/behaviors/shopp.js",array('jquery'),SHOPP_VERSION,true);
		wp_enqueue_script('shopp-settings',add_query_arg('shopp_lookup','settings.js',get_bloginfo('url')),array(),SHOPP_VERSION);
		
		// Load only for the product editor to keep other admin screens snappy
		if (($_GET['page'] == $this->Flow->Admin->editproduct || 
			 $_GET['page'] == $this->Flow->Admin->editcustomer ||
			 $_GET['page'] == $this->Flow->Admin->editcategory ||
			 $_GET['page'] == $this->Flow->Admin->editpromo)) {
			if (SHOPP_WP27) {
				add_action( 'admin_head', 'wp_tiny_mce' );
				wp_enqueue_script('postbox');
				if ( user_can_richedit() )
					wp_enqueue_script('editor');
			}
				
			wp_enqueue_script("shopp-thickbox","{$this->uri}/core/ui/behaviors/thickbox.js",array('jquery'),SHOPP_VERSION);
			wp_enqueue_script('shopp.editor.lib',"{$this->uri}/core/ui/behaviors/editors.js",array('jquery'),SHOPP_VERSION,true);

			if ($_GET['page'] == $this->Flow->Admin->editproduct)
				wp_enqueue_script('shopp.product.editor',"{$this->uri}/core/ui/products/editor.js",array('jquery'),SHOPP_VERSION,true);

			if (SHOPP_WP27) wp_enqueue_script('shopp.editor.priceline',"{$this->uri}/core/ui/behaviors/priceline.js",array('jquery'),SHOPP_VERSION,true);
			else wp_enqueue_script('shopp.editor.priceline',"{$this->uri}/core/ui/behaviors/priceline-wp26.js",array('jquery'),SHOPP_VERSION,true);
			
			wp_enqueue_script('shopp.ocupload',"{$this->uri}/core/ui/behaviors/ocupload.js",array('jquery'),SHOPP_VERSION,true);
			wp_enqueue_script('jquery-ui-sortable', '/wp-includes/js/jquery/ui.sortable.js', array('jquery','jquery-ui-core'),SHOPP_VERSION,true);
			
			wp_enqueue_script('shopp.swfupload',"{$this->uri}/core/ui/behaviors/swfupload/swfupload.js",array(),SHOPP_VERSION);
			wp_enqueue_script('shopp.swfupload.swfobject',"{$this->uri}/core/ui/behaviors/swfupload/plugins/swfupload.swfobject.js",array('shopp.swfupload'),SHOPP_VERSION);
		}
		
		?>
		<link rel='stylesheet' href='<?php echo $this->uri; ?>/core/ui/styles/thickbox.css?ver=<?php echo SHOPP_VERSION; ?>' type='text/css' />
		<link rel='stylesheet' href='<?php echo $this->uri; ?>/core/ui/styles/admin.css?ver=<?php echo SHOPP_VERSION; ?>' type='text/css' />
		<?php
	}
	
	/**
	 * dashbaord_css()
	 * Loads only the Shopp Admin CSS on the WordPress dashboard for widget styles */
	function dashboard_css () {
		?><link rel='stylesheet' href='<?php echo $this->uri; ?>/core/ui/styles/admin.css?ver=<?php echo SHOPP_VERSION; ?>' type='text/css' />
<?php
	}
	
	/**
	 * dashboard_init()
	 * Initializes the Shopp dashboard widgets */
	function dashboard_init () {
		
		wp_register_sidebar_widget('dashboard_shopp_stats', __('Shopp Stats','Shopp'), array(&$this->Flow,'dashboard_stats'),
			array('all_link' => '','feed_link' => '','width' => 'half','height' => 'single')
		);

		wp_register_sidebar_widget('dashboard_shopp_orders', __('Shopp Orders','Shopp'), array(&$this->Flow,'dashboard_orders'),
			array('all_link' => 'admin.php?page='.$this->Flow->Admin->orders,'feed_link' => '','width' => 'half','height' => 'single')
		);

		wp_register_sidebar_widget('dashboard_shopp_products', __('Shopp Products','Shopp'), array(&$this->Flow,'dashboard_products'),
			array('all_link' => 'admin.php?page='.$this->Flow->Admin->products,'feed_link' => '','width' => 'half','height' => 'single')
		);
		
	}

	/**
	 * dashboard ()
	 * Adds the Shopp dashboard widgets to the WordPress Dashboard */
	function dashboard ($widgets) {
		$dashboard = $this->Settings->get('dashboard');
		if (current_user_can(SHOPP_USERLEVEL) && $dashboard == "on")
			array_unshift($widgets,'dashboard_shopp_stats','dashboard_shopp_orders','dashboard_shopp_products');
		return $widgets;
	}
	
	/**
	 * behaviors()
	 * Dynamically includes necessary JavaScript and stylesheets as needed in 
	 * public shopping pages handled by Shopp */
	function behaviors () {
		global $wp_query;
		$object = $wp_query->get_queried_object();

		if(isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == "on") {
			add_filter('option_siteurl', 'force_ssl');
			add_filter('option_home', 'force_ssl');
			add_filter('option_url', 'force_ssl');
			add_filter('option_wpurl', 'force_ssl');
			add_filter('option_stylesheet_url', 'force_ssl');
			add_filter('option_template_url', 'force_ssl');
			add_filter('script_loader_src', 'force_ssl');
		}
		
		// Determine which tag is getting used in the current post/page
		$tag = false;
		$tagregexp = join( '|', array_keys($this->shortcodes) );
		foreach ($wp_query->posts as $post) {
			if (preg_match('/\[('.$tagregexp.')\b(.*?)(?:(\/))?\](?:(.+?)\[\/\1\])?/',$post->post_content,$matches))
				$tag = $matches[1];
		}

		// Include stylesheets and javascript based on whether shopp shortcodes are used
		add_action('wp_head', array(&$this, 'header'));
		add_action('wp_footer', array(&$this, 'footer'));
		
		$loading = $this->Settings->get('script_loading');
		if (!$loading || $loading == "global" || $tag !== false) {
			wp_enqueue_script('jquery');
			wp_enqueue_script('shopp-settings',add_query_arg('shopp_lookup','settings.js',get_bloginfo('url')));
			wp_enqueue_script("shopp-thickbox","{$this->uri}/core/ui/behaviors/thickbox.js",array('jquery'),SHOPP_VERSION,true);
			wp_enqueue_script("shopp","{$this->uri}/core/ui/behaviors/shopp.js",array('jquery'),SHOPP_VERSION,true);
		}

		if ($tag == "checkout")
			wp_enqueue_script('shopp_checkout',"{$this->uri}/core/ui/behaviors/checkout.js",array('jquery'),SHOPP_VERSION,true);		
		
			
	}

	/**
	 * widgets()
	 * Initializes theme widgets */
	function widgets () {
		global $wp_version;

		include('core/ui/widgets/cart.php');
		include('core/ui/widgets/categories.php');
		include('core/ui/widgets/section.php');
		include('core/ui/widgets/tagcloud.php');
		include('core/ui/widgets/facetedmenu.php');
		include('core/ui/widgets/product.php');
		
		if (version_compare($wp_version,'2.8-dev','<')) {
			$ShoppCategories = new LegacyShoppCategoriesWidget();
			$ShoppSection = new LegacyShoppCategorySectionWidget();
			$ShoppTagCloud = new LegacyShoppTagCloudWidget();
			$ShoppFacetedMenu = new LegacyShoppFacetedMenuWidget();
			$ShoppCart = new LegacyShoppCartWidget();
			$ShoppProduct = new LegacyShoppProductWidget();
		}
		
	}
		
	/**
	 * shortcodes()
	 * Handles shortcodes used on Shopp-installed pages and used by
	 * site owner for including categories/products in posts and pages */
	function shortcodes () {

		$this->shortcodes = array();
		$this->shortcodes['catalog'] = array(&$this->Flow,'catalog');
		$this->shortcodes['cart'] = array(&$this->Flow,'cart');
		$this->shortcodes['checkout'] = array(&$this->Flow,'checkout');
		$this->shortcodes['account'] = array(&$this->Flow,'account');
		$this->shortcodes['product'] = array(&$this->Flow,'product_shortcode');
		$this->shortcodes['category'] = array(&$this->Flow,'category_shortcode');
		
		foreach ($this->shortcodes as $name => &$callback)
			if ($this->Settings->get("maintenance") == "on")
				add_shortcode($name,array(&$this->Flow,'maintenance_shortcode'));
			else add_shortcode($name,$callback);
	}
	
	function tinymce () {
		if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) return;

		// Add TinyMCE buttons when using rich editor
		if (get_user_option('rich_editing') == 'true') {
			add_filter('tiny_mce_version', array(&$this,'mceupdate')); // Move to plugin activation
			add_filter('mce_external_plugins', array(&$this,'mceplugin'),5);
			add_filter('mce_buttons', array(&$this,'mcebutton'),5);
		}
	}

	function mceplugin ($plugins) {
		$plugins['Shopp'] = $this->uri.'/core/ui/behaviors/tinymce/editor_plugin.js';
		return $plugins;
	}

	function mcebutton ($buttons) {
		array_push($buttons, "separator", "Shopp");
		return $buttons;
	}

	function my_change_mce_settings( $init_array ) {
	    $init_array['disk_cache'] = false; // disable caching
	    $init_array['compress'] = false; // disable gzip compression
	    $init_array['old_cache_max'] = 3; // keep 3 different TinyMCE configurations cached (when switching between several configurations regularly)
	}

	function mceupdate($ver) {
	  return ++$ver;
	}
	
	
	/**
	 * pages_index()
	 * Handles changes to Shopp-installed pages that may affect 'pretty' urls */
	function pages_index ($update=false,$updates=false) {
		global $wpdb;
		$pages = $this->Settings->get('pages');
		
		// No pages setting, use defaults
		$pages = $this->Flow->Pages;
		
		// Find pages with Shopp-related main shortcodes
		$codes = array();
		$search = "";
		foreach ($pages as $page) $codes[] = $page['content'];
		foreach ($codes as $code) $search .= ((!empty($search))?" OR ":"")."post_content LIKE '%$code%'";
		$query = "SELECT ID,post_title,post_name,post_content FROM $wpdb->posts WHERE post_status='publish' AND ($search)";
		$results = $wpdb->get_results($query);

		// Match updates from the found results to our pages index
		foreach ($pages as $key => &$page) {
			foreach ($results as $index => $post) {
				if (strpos($post->post_content,$page['content']) !== false) {
					$page['id'] = $post->ID;
					$page['title'] = $post->post_title;
					$page['name'] = $post->post_name;
					$page['permalink'] = str_replace(trailingslashit(get_bloginfo('url')),'',get_permalink($page['id']));
					if ($page['permalink'] == get_bloginfo('url')) $page['permalink'] = "";
					break;
				}
			}
		}
		
		$this->Settings->save('pages',$pages);

		if ($update) return $update;
	}
			
	/**
	 * rewrites()
	 * Adds Shopp-specific pretty-url rewrite rules to the WordPress rewrite rules */
	function rewrites ($wp_rewrite_rules) {
		$this->pages_index(true);
		$pages = $this->Settings->get('pages');
		if (!$pages) $pages = $this->Flow->Pages;
		$shop = $pages['catalog']['permalink'];
		if (!empty($shop)) $shop = trailingslashit($shop);
		$catalog = $pages['catalog']['name'];
		$cart = $pages['cart']['permalink'];
		$checkout = $pages['checkout']['permalink'];
		$account = $pages['account']['permalink'];

		$rules = array(
			$cart.'?$' => 'index.php?pagename='.shopp_pagename($cart),
			$account.'?$' => 'index.php?pagename='.shopp_pagename($account),
			$checkout.'?$' => 'index.php?pagename='.shopp_pagename($checkout).'&shopp_proc=checkout',
			(empty($shop)?"$catalog/":$shop).'feed/?$' => 'index.php?shopp_lookup=newproducts-rss',
			(empty($shop)?"$catalog/":$shop).'receipt/?$' => 'index.php?pagename='.shopp_pagename($checkout).'&shopp_proc=receipt',
			(empty($shop)?"$catalog/":$shop).'confirm-order/?$' => 'index.php?pagename='.shopp_pagename($checkout).'&shopp_proc=confirm-order',
			(empty($shop)?"$catalog/":$shop).'download/([a-z0-9]{40})/?$' => 'index.php?pagename='.shopp_pagename($account).'&shopp_download=$matches[1]',
			(empty($shop)?"$catalog/":$shop).'images/(\d+)/?.*?$' => 'index.php?shopp_image=$matches[1]'
		);

		// catalog/category/category-slug
		if (empty($shop)) {
			$rules[$catalog.'/category/(.+?)/feed/?$'] = 'index.php?shopp_lookup=category-rss&shopp_category=$matches[1]';
			$rules[$catalog.'/category/(.+?)/page/?([A-Z0-9]{1,})/?$'] = 'index.php?pagename='.shopp_pagename($catalog).'&shopp_category=$matches[1]&paged=$matches[2]';
			$rules[$catalog.'/category/(.+)/?$'] = 'index.php?pagename='.shopp_pagename($catalog).'&shopp_category=$matches[1]';
		} else {
			$rules[$shop.'category/(.+?)/feed/?$'] = 'index.php?shopp_lookup=category-rss&shopp_category=$matches[1]';
			$rules[$shop.'category/(.+?)/page/?([A-Z0-9]{1,})/?$'] = 'index.php?pagename='.shopp_pagename($shop).'&shopp_category=$matches[1]&paged=$matches[2]';
			$rules[$shop.'category/(.+)/?$'] = 'index.php?pagename='.shopp_pagename($shop).'&shopp_category=$matches[1]';
		}

		// tags
		if (empty($shop)) {
			$rules[$catalog.'/tag/(.+?)/feed/?$'] = 'index.php?shopp_lookup=category-rss&shopp_tag=$matches[1]';
			$rules[$catalog.'/tag/(.+?)/page/?([0-9]{1,})/?$'] = 'index.php?pagename='.shopp_pagename($catalog).'&shopp_tag=$matches[1]&paged=$matches[2]';
			$rules[$catalog.'/tag/(.+)/?$'] = 'index.php?pagename='.shopp_pagename($catalog).'&shopp_tag=$matches[1]';
		} else {
			$rules[$shop.'tag/(.+?)/feed/?$'] = 'index.php?shopp_lookup=category-rss&shopp_tag=$matches[1]';
			$rules[$shop.'tag/(.+?)/page/?([0-9]{1,})/?$'] = 'index.php?pagename='.shopp_pagename($shop).'&shopp_tag=$matches[1]&paged=$matches[2]';
			$rules[$shop.'tag/(.+)/?$'] = 'index.php?pagename='.shopp_pagename($shop).'&shopp_tag=$matches[1]';
		}

		// catalog/productid
		if (empty($shop)) $rules[$catalog.'/(\d+(,\d+)?)/?$'] = 'index.php?pagename='.shopp_pagename($catalog).'&shopp_pid=$matches[1]';
		else $rules[$shop.'(\d+(,\d+)?)/?$'] = 'index.php?pagename='.shopp_pagename($shop).'&shopp_pid=$matches[1]';

		// catalog/product-slug
		if (empty($shop)) $rules[$catalog.'/(.+)/?$'] = 'index.php?pagename='.shopp_pagename($catalog).'&shopp_product=$matches[1]'; // category/product-slug
		else $rules[$shop.'(.+)/?$'] = 'index.php?pagename='.shopp_pagename($shop).'&shopp_product=$matches[1]'; // category/product-slug			

		// catalog/categories/path/product-slug
		if (empty($shop)) $rules[$catalog.'/([\w%_\\+-\/]+?)/([\w_\-]+?)/?$'] = 'index.php?pagename='.shopp_pagename($catalog).'&shopp_category=$matches[1]&shopp_product=$matches[2]'; // category/product-slug
		else $rules[$shop.'([\w%_\+\-\/]+?)/([\w_\-]+?)/?$'] = 'index.php?pagename='.shopp_pagename($shop).'&shopp_category=$matches[1]&shopp_product=$matches[2]'; // category/product-slug			

		return $rules + $wp_rewrite_rules;
	}
	
	/**
	 * queryvars()
	 * Registers the query variables used by Shopp */
	function queryvars ($vars) {
		$vars[] = 'shopp_proc';
		$vars[] = 'shopp_category';
		$vars[] = 'shopp_tag';
		$vars[] = 'shopp_pid';
		$vars[] = 'shopp_product';
		$vars[] = 'shopp_lookup';
		$vars[] = 'shopp_image';
		$vars[] = 'shopp_download';
		$vars[] = 'shopp_xco';
		$vars[] = 'st';
	
		return $vars;
	}
		
	/**
	 * orders()
	 * Handles order administration screens */
	function orders () {
		if ($this->Settings->get('display_welcome') == "on") {
			$this->welcome(); return;
		}
		if (!empty($_GET['id'])) $this->Flow->order_manager();
		else $this->Flow->orders_list();
	}

	/**
	 * customers()
	 * Handles order administration screens */
	function customers () {
		if ($this->Settings->get('display_welcome') == "on") {
			$this->welcome(); return;
		}
		
		if ($_GET['page'] == $this->Flow->Admin->editcustomer)
			$this->Flow->customer_editor();
		else $this->Flow->customers_list();
	}

	/**
	 * categories()
	 * Handles category administration screens */
	function categories () {
		if ($this->Settings->get('display_welcome') == "on") {
			$this->welcome(); return;
		}
		if ($_GET['page'] == $this->Flow->Admin->editcategory)
			$this->Flow->category_editor();
		else $this->Flow->categories_list();
	}

	/**
	 * products()
	 * Handles product administration screens */
	function products () {
		if ($this->Settings->get('display_welcome') == "on") {
			$this->welcome(); return;
		}

		if ($_GET['page'] == $this->Flow->Admin->editproduct) 
			$this->Flow->product_editor();
		else $this->Flow->products_list();
		
	}

	/**
	 * promotions()
	 * Handles product administration screens */
	function promotions () {
		if ($this->Settings->get('display_welcome') == "on") {
			$this->welcome(); return;
		}
		if ($_GET['page'] == $this->Flow->Admin->editpromo)
			$this->Flow->promotion_editor();
		else $this->Flow->promotions_list();
	}

	/**
	 * settings()
	 * Handles settings administration screens */
	function settings () {
		if ($this->Settings->get('display_welcome') == "on" && empty($_POST['setup'])) {
			$this->welcome(); return;
		}
		
		$pages = explode("-",$_GET['page']);
		$screen = end($pages);
		switch($screen) {
			case "catalog": 		$this->Flow->settings_catalog(); break;
			case "cart": 			$this->Flow->settings_cart(); break;
			case "checkout": 		$this->Flow->settings_checkout(); break;
			case "payments": 		$this->Flow->settings_payments(); break;
			case "shipping": 		$this->Flow->settings_shipping(); break;
			case "taxes": 			$this->Flow->settings_taxes(); break;
			case "presentation":	$this->Flow->settings_presentation(); break;
			case "system":			$this->Flow->settings_system(); break;
			case "update":			$this->Flow->settings_update(); break;
			default: 				$this->Flow->settings_general();
		}
		
	}

	/**
	 * titles ()
	 * Changes the Shopp catalog page titles to include the product
	 * name and category (when available) */
	function titles ($title,$sep=" &mdash; ",$placement="left") {
		if (empty($this->Product->name) && empty($this->Category->name)) return $title;
		if ($placement == "right") {
			if (!empty($this->Product->name)) $title = $this->Product->name." $sep ".$title;
			if (!empty($this->Category->name)) $title = $this->Category->name." $sep ".$title;
		} else {
			if (!empty($this->Category->name)) $title .= " $sep ".$this->Category->name;
			if (!empty($this->Product->name)) $title .=  " $sep ".$this->Product->name;
		}
		return $title;
	}

	function feeds () {
		if (empty($this->Category)):?>

<link rel='alternate' type="application/rss+xml" title="<?php htmlentities(bloginfo('name')); ?> New Products RSS Feed" href="<?php echo $this->shopuri.((SHOPP_PERMALINKS)?'feed/':'&shopp_lookup=newproducts-rss'); ?>" />
<?php
			else:
			$uri = 'category/'.$this->Category->uri;
			if ($this->Category->slug == "tag") $uri = $this->Category->slug.'/'.$this->Category->tag;

			if (SHOPP_PERMALINKS) $link = $this->shopuri.urldecode($uri).'/feed/';
			else $link = add_query_arg(array('shopp_category'=>urldecode($this->Category->uri),'shopp_lookup'=>'category-rss'),$this->shopuri);
			?>

<link rel='alternate' type="application/rss+xml" title="<?php htmlentities(bloginfo('name')); ?> <?php echo urldecode($this->Category->name); ?> RSS Feed" href="<?php echo $link; ?>" />
<?php
		endif;
	}

	function updatesearch () {
		global $wp_query;
		$wp_query->query_vars['s'] = $this->Cart->data->Search;
	}

	function metadata () {
		$keywords = false;
		$description = false;
		if (!empty($this->Product)) {
			if (empty($this->Product->tags)) $this->Product->load_data(array('tags'));
			foreach($this->Product->tags as $tag)
				$keywords .= (!empty($keywords))?", {$tag->name}":$tag->name;
			$description = $this->Product->summary;
		} elseif (!empty($this->Category)) {
			$description = $this->Category->description;
		}
		$keywords = attribute_escape(apply_filters('shopp_meta_keywords',$keywords));
		$description = attribute_escape(apply_filters('shopp_meta_description',$description));
		?>
		<?php if ($tags): ?><meta name="keywords" content="<?php echo $keywords; ?>" /><?php endif; ?>
		<?php if ($description): ?><meta name="description" content="<?php echo $description; ?>" /><?php endif;
	}

	function canonurls ($url) {
		global $Shopp;
		if (!empty($Shopp->Product->slug)) return $Shopp->Product->tag('url','echo=0');
		if (!empty($Shopp->Category->slug)) return $Shopp->Category->tag('url','echo=0');
		return $url;
	}

	/**
	 * header()
	 * Adds stylesheets necessary for Shopp public shopping pages */
	function header () { 
?>
<link rel='stylesheet' href='<?php echo htmlentities( add_query_arg(array('shopp_lookup'=>'catalog.css','ver'=>urlencode(SHOPP_VERSION)),get_bloginfo('url'))); ?>' type='text/css' />
<link rel='stylesheet' href='<?php echo SHOPP_TEMPLATES_URI; ?>/shopp.css?ver=<?php echo urlencode(SHOPP_VERSION); ?>' type='text/css' />
<link rel='stylesheet' href='<?php echo $this->uri; ?>/core/ui/styles/thickbox.css?ver=<?php echo urlencode(SHOPP_VERSION); ?>' type='text/css' />
<?php 
	$canonurl = $this->canonurls(false);
	if (is_shopp_page('catalog') && !empty($canonurl)): ?><link rel='canonical' href='<?php echo $canonurl ?>' /><?php
	endif;
	}
	
	/**
	 * footer()
	 * Adds report information and custom debugging tools to the public and admin footers */
	function footer () {
		if (!WP_DEBUG) return true;
		if (!current_user_can('manage_options')) return true;
		$db = DB::get();
		global $wpdb;
		
		if (function_exists('memory_get_peak_usage'))
			$this->_debug->memory .= "End: ".number_format(memory_get_peak_usage(true)/1024/1024, 2, '.', ',') . " MB<br />";
		elseif (function_exists('memory_get_usage'))
			$this->_debug->memory .= "End: ".number_format(memory_get_usage(true)/1024/1024, 2, '.', ',') . " MB";

		echo '<script type="text/javascript">'."\n";
		echo '//<![CDATA['."\n";
		echo 'var memory_profile = "'.$this->_debug->memory.'";';
		echo 'var wpquerytotal = '.$wpdb->num_queries.';';
		echo 'var shoppquerytotal = '.count($db->queries).';';
		echo '//]]>'."\n";
		echo '</script>'."\n";

	}
	
	function catalog ($wp) {
		$pages = $this->Settings->get('pages');
		$options = array();
		
		$type = "catalog";
		if (isset($wp->query_vars['shopp_category']) &&
			$category = $wp->query_vars['shopp_category']) $type = "category";
		if (isset($wp->query_vars['shopp_pid']) && 
			$productid = $wp->query_vars['shopp_pid']) $type = "product";
		if (isset($wp->query_vars['shopp_product']) && 
			$productname = $wp->query_vars['shopp_product']) $type = "product";

		if (isset($wp->query_vars['shopp_tag']) && 
			$tag = $wp->query_vars['shopp_tag']) {
			$type = "category";
			$category = "tag";
		}

		$referer = wp_get_referer();
		$target = "blog";
		if (isset($wp->query_vars['st'])) $target = $wp->query_vars['st'];
		if (!empty($wp->query_vars['s']) && // Search query is present and...
			// The search target is set to shopp
			($target == "shopp" 
				// The referering page includes a Shopp catalog page path
				|| strpos($referer,$this->link('catalog')) !== false || 
				strpos($referer,'page_id='.$pages['catalog']['id']) !== false || 
				// Or the referer was a search that matches the last recorded Shopp search
				substr($referer,-1*(strlen($this->Cart->data->Search))) == $this->Cart->data->Search || 
				// Or the blog URL matches the Shopp catalog URL (Takes over search for store-only search)
				trailingslashit(get_bloginfo('url')) == $this->link('catalog') || 
				// Or the referer is one of the Shopp cart, checkout or account pages
				$referer == $this->link('cart') || $referer == $this->link('checkout') || 
				$referer == $this->link('account'))) {
			$this->Cart->data->Search = $wp->query_vars['s'];
			$wp->query_vars['s'] = "";
			$wp->query_vars['pagename'] = $pages['catalog']['name'];
			add_action('wp_head', array(&$this, 'updatesearch'));
			if ($type != "product") $type = "category"; 
			$category = "search-results";
		}
		
		// Load a category/tag
		if (!empty($category) || !empty($tag)) {
			if (isset($this->Cart->data->Search)) $options = array('search'=>$this->Cart->data->Search);
			if (isset($tag)) $options = array('tag'=>$tag);

			// Split for encoding multi-byte slugs
			$slugs = explode("/",$category);
			$category = join("/",array_map('urlencode',$slugs));

			// Load the category
			$this->Category = Catalog::load_category($category,$options);
			$this->Cart->data->breadcrumb = (isset($tag)?"tag/":"").$this->Category->uri;
		} 
		
		if (empty($category) && empty($tag) && 
			empty($productid) && empty($productname)) 
			$this->Cart->data->breadcrumb = "";
		
		// Category Filters
		if (!empty($this->Category->slug)) {
			if (empty($this->Cart->data->Category[$this->Category->slug]))
				$this->Cart->data->Category[$this->Category->slug] = array();
			$CategoryFilters =& $this->Cart->data->Category[$this->Category->slug];
			
			// Add new filters
			if (isset($_GET['shopp_catfilters'])) {
				if (is_array($_GET['shopp_catfilters'])) {
					$CategoryFilters = array_filter(array_merge($CategoryFilters,$_GET['shopp_catfilters']));
					$CategoryFilters = stripslashes_deep($CategoryFilters);
					if (isset($wp->query_vars['paged'])) $wp->query_vars['paged'] = 1; // Force back to page 1
				} else unset($this->Cart->data->Category[$this->Category->slug]);
			}
			
		}
		
		// Catalog sort order setting
		if (isset($_GET['shopp_orderby']))
			$this->Cart->data->Category['orderby'] = $_GET['shopp_orderby'];

		if (empty($this->Category)) $this->Category = Catalog::load_category($this->Cart->data->breadcrumb,$options);

		// Find product by given ID
		if (!empty($productid) && empty($this->Product->id))
			$this->Product = new Product($productid);
			
		// Find product by product slug
		if (!empty($productname) && empty($this->Product->id))
			$this->Product = new Product(urlencode($productname),"slug");
		
		// Product must be published
		if (!empty($this->Product->id) && $this->Product->published == "off" || empty($this->Product->id))
			$this->Product = false;
		
		// No product found, try to load a page instead
		if ($type == "product" && !$this->Product) 
			$wp->query_vars['pagename'] = $wp->request;

		$this->Catalog = new Catalog($type);
		add_filter('wp_title', array(&$this, 'titles'),10,3);
		add_action('wp_head', array(&$this, 'metadata'));
		add_action('wp_head', array(&$this, 'feeds'));

	}
		
	/**
	 * cart()
	 * Handles shopping cart requests */
	function cart () {
		if (isset($_REQUEST['shopping']) && $_REQUEST['shopping'] == "reset") {
			$this->Cart->reset();
			shopp_redirect($this->link());
		}

		if (empty($_REQUEST['cart'])) return true;

		$this->Cart->request();
		if ($this->Cart->updated) $this->Cart->totals();
		if (isset($_REQUEST['ajax'])) $this->Cart->ajax();
		$redirect = false;
		if (isset($_REQUEST['redirect'])) $redirect = $_REQUEST['redirect'];
		switch ($redirect) {
			case "checkout": shopp_redirect($this->link($redirect,true)); break;
			default: 
				if (!empty($_REQUEST['redirect']))
					shopp_redirect(esc_url($this->link($_REQUEST['redirect'])));
				else shopp_redirect($this->link('cart'));
		}
	}
	
	/**
	 * checkout()
	 * Handles checkout process */
	function checkout ($wp) {

		$pages = $this->Settings->get('pages');
		// If checkout page requested
		// Note: we have to use custom detection here as 
		// the wp->post vars are not available at this point
		// to make use of is_shopp_page()
		if (((SHOPP_PERMALINKS && isset($wp->query_vars['pagename']) 
			&& $wp->query_vars['pagename'] == $pages['checkout']['permalink'])
			|| (isset($wp->query_vars['page_id']) && $wp->query_vars['page_id'] == $pages['checkout']['id']))
		 	&& $wp->query_vars['shopp_proc'] == "checkout") {
			
			$this->Cart->updated();
			$this->Cart->totals();

			if ($this->Cart->data->ShippingPostcodeError) {
				header('Location: '.$this->link('cart'));
				exit();
			}

			// Force secure checkout page if its not already
			$secure = true;
			$gateway = $this->Settings->get('payment_gateway');
			if (strpos($gateway,"TestMode") !== false 
					|| isset($wp->query_vars['shopp_xco']) 
					|| $this->Cart->orderisfree()) 
				$secure = false;

			if ($secure && !$this->secure && !SHOPP_NOSSL) {
				header('Location: '.$this->link('checkout',$secure));
				exit();
			}
		}
		
		// Cancel this process if there is no order data
		if (!isset($this->Cart->data->Order)) return;
		$Order = $this->Cart->data->Order;

		// Intercept external checkout processing
		if (!empty($wp->query_vars['shopp_xco'])) {
			if ($this->gateway($wp->query_vars['shopp_xco'])) {
				if ($wp->query_vars['shopp_proc'] != "confirm-order" && 
						!isset($_POST['checkout'])) {
					$this->Gateway->checkout();
					$this->Gateway->error();
				}
			}
		}

		// Cancel if no checkout process detected
		if (empty($_POST['checkout'])) return true;
		// Handoff to order processing
		if ($_POST['checkout'] == "confirmed") return $this->Flow->order();
		// Cancel if checkout process is not ready for processing
		if ($_POST['checkout'] != "process") return true;
		// Cancel if processing a login from the checkout form
		if (isset($_POST['process-login']) 
			&& $_POST['process-login'] == "true") return true;
		
		// Start processing the checkout form
		$_POST = attribute_escape_deep($_POST);
		
		$_POST['billing']['cardexpires'] = sprintf("%02d%02d",$_POST['billing']['cardexpires-mm'],$_POST['billing']['cardexpires-yy']);

		// If the card number is provided over a secure connection
		// Change the cart to operate in secure mode
		if (isset($_POST['billing']['card']) && is_shopp_secure())
			$this->Cart->secured(true);

		// Sanitize the card number to ensure it only contains numbers
		$_POST['billing']['card'] = preg_replace('/[^\d]/','',$_POST['billing']['card']);

		if (isset($_POST['data'])) $Order->data = stripslashes_deep($_POST['data']);
		if (empty($Order->Customer))
			$Order->Customer = new Customer();
		$Order->Customer->updates($_POST);

		if (isset($_POST['confirm-password']))
			$Order->Customer->confirm_password = $_POST['confirm-password'];

		if (empty($Order->Billing))
			$Order->Billing = new Billing();
		$Order->Billing->updates($_POST['billing']);
		
		if (!empty($_POST['billing']['cardexpires-mm']) && !empty($_POST['billing']['cardexpires-yy'])) {
			$Order->Billing->cardexpires = mktime(0,0,0,
					$_POST['billing']['cardexpires-mm'],1,
					($_POST['billing']['cardexpires-yy'])+2000
				);
		} else $Order->Billing->cardexpires = 0;
		
		$Order->Billing->cvv = preg_replace('/[^\d]/','',$_POST['billing']['cvv']);

		if (empty($Order->Shipping))
			$Order->Shipping = new Shipping();
			
		if (isset($_POST['shipping'])) $Order->Shipping->updates($_POST['shipping']);
		if (!empty($_POST['shipmethod'])) $Order->Shipping->method = $_POST['shipmethod'];
		else $Order->Shipping->method = key($this->Cart->data->ShipCosts);

		// Override posted shipping updates with billing address
		if ($_POST['sameshipaddress'] == "on")
			$Order->Shipping->updates($Order->Billing,
				array("_datatypes","_table","_key","_lists","id","created","modified"));

		$estimatedTotal = $this->Cart->data->Totals->total;
		$this->Cart->updated();
		$this->Cart->totals();
		if ($this->Cart->validate() !== true) return;
		else $Order->Customer->updates($_POST); // Catch changes from validation

		// If the cart's total changes at all, confirm the order
		if ($estimatedTotal != $this->Cart->data->Totals->total || 
				$this->Settings->get('order_confirmation') == "always") {
			$gateway = $this->Settings->get('payment_gateway');
			$secure = true;
			if (strpos($gateway,"TestMode") !== false 
				|| isset($wp->query_vars['shopp_xco'])
				|| $this->Cart->orderisfree()) 
				$secure = false;
			shopp_redirect($this->link('confirm-order',$secure));
		} else $this->Flow->order();

	}

	/**
	 * xorder ()
	 * Handle external checkout system order notifications */
	function xorder () {
		$path = false;
		if (!empty($_GET['shopp_xorder'])) {
			$gateway = $this->Settings->get($_GET['shopp_xorder']);
			if (isset($gateway['path'])) $path = $gateway['path'];
			// Use the old path support for transition if the new path setting isn't available
			if (empty($path)) $path = "{$_GET['shopp_xorder']}/{$_GET['shopp_xorder']}.php";
			if ($this->gateway($path)) $this->Gateway->process();
			exit();
		}
	}
	
	/**
	 * gateway ()
	 * Loads a requested gateway */
	function gateway ($gateway,$load=false) {
		if (substr($gateway,-4) != ".php") $gateway .= ".php";
		$filepath = join(DIRECTORY_SEPARATOR,array($this->path,'gateways',$gateway));
		if (!file_exists($filepath)) {
			new ShoppError(__('There was a problem loading the requested payment processor.','Shopp').' ('.$gateway.')','shopp_load_gateway');
			return false;
		}
		$meta = $this->Flow->scan_gateway_meta($filepath);
		$ProcessorClass = $meta->tags['class'];
		include_once($filepath);

		if (isset($this->Cart->data->Order) && !$load) $this->Gateway = new $ProcessorClass($this->Cart->data->Order);
		else $this->Gateway = new $ProcessorClass();

		return true;
	}
	
	/**
	 * link ()
	 * Builds a full URL for a specific Shopp-related resource */
	function link ($target,$secure=false) {
		$internals = array("receipt","confirm-order");
		$pages = $this->Settings->get('pages');
		
		if (!is_array($pages)) $pages = $this->Flow->Pages;
		
		$uri = get_bloginfo('url');
		if ($secure && !SHOPP_NOSSL) $uri = str_replace('http://','https://',$uri);

		if (array_key_exists($target,$pages)) $page = $pages[$target];
		else {
			if (in_array($target,$internals)) {
				$page = $pages['checkout'];
				if (SHOPP_PERMALINKS) {
					$catalog = $pages['catalog']['permalink'];
					if (empty($catalog)) $catalog = $pages['catalog']['name'];
					$page['permalink'] = trailingslashit($catalog).$target;
				} else $page['id'] .= "&shopp_proc=$target";
			} else $page = $pages['catalog'];
 		}

		if (SHOPP_PERMALINKS) return $uri."/".$page['permalink'];
		else return add_query_arg('page_id',$page['id'],trailingslashit($uri));
	}
		
	/**
	 * help()
	 * This function provides graceful degradation when the 
	 * contextual javascript behavior isn't working, this
	 * provides the default behavior of showing a help gateway
	 * page with instructions on where to find help on Shopp. */
	function help () {
		include(SHOPP_ADMINPATH."/help/help.php");
	}

	function welcome () {
		include(SHOPP_ADMINPATH."/help/welcome.php");
	}
	
	/**
	 * AJAX Responses */
	
	/**
	 * lookups ()
	 * Provides fast db lookups with as little overhead as possible */
	function lookups($wp) {
		$db =& DB::get();

		// Grab query requests from permalink rewriting query vars
		$admin = false;
		$download = (isset($wp->query_vars['shopp_download']))?$wp->query_vars['shopp_download']:'';
		$lookup = (isset($wp->query_vars['shopp_lookup']))?$wp->query_vars['shopp_lookup']:'';
				
		// Admin Lookups
		if (isset($_GET['page']) && $_GET['page'] == "shopp-lookup") {
			$admin = true;
			$image = $_GET['id'];
			$download = $_GET['download'];
		}
		
		if (!empty($download)) $lookup = "download";
		if (empty($lookup)) $lookup = (isset($_GET['lookup']))?$_GET['lookup']:'';
		
		switch($lookup) {
			case "purchaselog":
				if (!defined('WP_ADMIN') || !is_user_logged_in() || !current_user_can('manage_options')) die('-1');
				$db =& DB::get();

				if (!isset($_POST['settings']['purchaselog_columns'])) {
					$_POST['settings']['purchaselog_columns'] =
					 	array_keys(array_merge($Purchase,$Purchased));
					$_POST['settings']['purchaselog_headers'] = "on";
				}
				
				$this->Flow->settings_save();
				
				$format = $this->Settings->get('purchaselog_format');
				if (empty($format)) $format = 'tab';
				
				switch ($format) {
					case "csv": new PurchasesCSVExport(); break;
					case "xls": new PurchasesXLSExport(); break;
					case "iif": new PurchasesIIFExport(); break;
					default: new PurchasesTabExport();
				}
				exit();
				break;
			case "customerexport":
				if (!defined('WP_ADMIN') || !is_user_logged_in() || !current_user_can('manage_options')) die('-1');
				$db =& DB::get();

				if (!isset($_POST['settings']['customerexport_columns'])) {
					$Customer = Customer::exportcolumns();
					$Billing = Billing::exportcolumns();
					$Shipping = Shipping::exportcolumns();
					$_POST['settings']['customerexport_columns'] =
					 	array_keys(array_merge($Customer,$Billing,$Shipping));
					$_POST['settings']['customerexport_headers'] = "on";
				}

				$this->Flow->settings_save();

				$format = $this->Settings->get('customerexport_format');
				if (empty($format)) $format = 'tab';

				switch ($format) {
					case "csv": new CustomersCSVExport(); break;
					case "xls": new CustomersXLSExport(); break;
					default: new CustomersTabExport();
				}
				exit();
				break;
			case "receipt":
				if (!defined('WP_ADMIN') || !is_user_logged_in() || !current_user_can('manage_options')) die('-1');
				if (preg_match("/\d+/",$_GET['id'])) {
					$this->Cart->data->Purchase = new Purchase($_GET['id']);
					$this->Cart->data->Purchase->load_purchased();
				} else die('-1');
				echo "<html><head>";
					echo '<style type="text/css">body { padding: 20px; font-family: Arial,Helvetica,sans-serif; }</style>';
					echo "<link rel='stylesheet' href='".SHOPP_TEMPLATES_URI."/shopp.css' type='text/css' />";
				echo "</head><body>";
				echo $this->Flow->order_receipt();
				if (isset($_GET['print']) && $_GET['print'] == 'auto')
					echo '<script type="text/javascript">window.onload = function () { window.print(); window.close(); }</script>';
				echo "</body></html>";
				exit();
				break;
			case "zones":
				$zones = $this->Settings->get('zones');
				if (isset($_GET['country']))
					echo json_encode($zones[$_GET['country']]);
				exit();
				break;
			case "shipcost":
				@session_start();
				$this->ShipCalcs = new ShipCalcs($this->path);
				if (isset($_GET['method'])) {
					$this->Cart->data->Order->Shipping->method = $_GET['method'];
					$this->Cart->retotal = true;
					$this->Cart->updated();
					$this->Cart->totals();
					echo json_encode($this->Cart->data->Totals);
				}
				exit();
				break;
			case "category-menu":
				echo $this->Flow->category_menu();
				exit();
				break;
			case "category-products-menu":
				echo $this->Flow->category_products();
				exit();
				break;
			case "spectemplate":
				$db = DB::get();
				$table = DatabaseObject::tablename(Category::$table);			
				$result = $db->query("SELECT specs FROM $table WHERE id='{$_GET['cat']}' AND spectemplate='on'");
				echo json_encode(unserialize($result->specs));
				exit();
				break;
			case "optionstemplate":
				$db = DB::get();
				$table = DatabaseObject::tablename(Category::$table);			
				$result = $db->query("SELECT options,prices FROM $table WHERE id='{$_GET['cat']}' AND variations='on'");
				if (empty($result)) exit();
				$result->options = unserialize($result->options);
				$result->prices = unserialize($result->prices);
				foreach ($result->options as &$menu) {
					foreach ($menu['options'] as &$option) $option['id'] += $_GET['cat'];
				}
				foreach ($result->prices as &$price) {
					$optionids = explode(",",$price['options']);
					foreach ($optionids as &$id) $id += $_GET['cat'];
					$price['options'] = join(",",$optionids);
					$price['optionkey'] = "";
				}
				
				echo json_encode($result);
				exit();
				break;
			case "newproducts-rss":
				$NewProducts = new NewProducts(array('show' => 5000));
				header("Content-type: application/rss+xml; charset=utf-8");
				echo shopp_rss($NewProducts->rss());
				exit();
				break;
			case "category-rss":
				$this->catalog($wp);
				header("Content-type: application/rss+xml; charset=utf-8");
				echo shopp_rss($this->Category->rss());
				exit();
				break;
			case "download":
				if (empty($download)) break;
		
				if ($admin) {
					$Asset = new Asset($download);
				} else {
					$db = DB::get();
					$pricetable = DatabaseObject::tablename(Purchase::$table);			
					$pricetable = DatabaseObject::tablename(Price::$table);			
					$assettable = DatabaseObject::tablename(Asset::$table);			
					
					require_once("core/model/Purchased.php");
					$Purchased = new Purchased($download,"dkey");
					$Purchase = new Purchase($Purchased->purchase);
					$target = $db->query("SELECT target.* FROM $assettable AS target LEFT JOIN $pricetable AS pricing ON pricing.id=target.parent AND target.context='price' WHERE pricing.id=$Purchased->price AND target.datatype='download'");
					$Asset = new Asset();
					$Asset->populate($target);

					$forbidden = false;

					// Purchase Completion check
					if ($Purchase->transtatus != "CHARGED" 
						&& !SHOPP_PREPAYMENT_DOWNLOADS) {
						new ShoppError(__('This file cannot be downloaded because payment has not been received yet.','Shopp'),'shopp_download_limit');
						$forbidden = true;
					}
					
					// Account restriction checks
					if ($this->Settings->get('account_system') != "none"
						&& (!$this->Cart->data->login
						|| $this->Cart->data->Order->Customer->id != $Purchase->customer)) {
							new ShoppError(__('You must login to access this download.','Shopp'),'shopp_download_limit',SHOPP_ERR);
							header('Location: '.$this->link('account'));
							exit();
					}
					
					// Download limit checking
					if ($this->Settings->get('download_limit') // Has download credits available
						&& $Purchased->downloads+1 > $this->Settings->get('download_limit')) {
							new ShoppError(__('This file can no longer be downloaded because the download limit has been reached.','Shopp'),'shopp_download_limit');
							$forbidden = true;
						}
							
					// Download expiration checking
					if ($this->Settings->get('download_timelimit') // Within the timelimit
						&& $Purchased->created+$this->Settings->get('download_timelimit') < mktime() ) {
							new ShoppError(__('This file can no longer be downloaded because it has expired.','Shopp'),'shopp_download_limit');
							$forbidden = true;
						}
					
					// IP restriction checks
					if ($this->Settings->get('download_restriction') == "ip"
						&& !empty($Purchase->ip) 
						&& $Purchase->ip != $_SERVER['REMOTE_ADDR']) {
							new ShoppError(__('The file cannot be downloaded because this computer could not be verified as the system the file was purchased from.','Shopp'),'shopp_download_limit');
							$forbidden = true;	
						}

					do_action_ref_array('shopp_download_request',array(&$Purchased));
				}
			
				if ($forbidden) {
					header("Status: 403 Forbidden");
					return;
				}
				
				if ($Asset->download($download)) {
					$Purchased->downloads++;
					$Purchased->save();
					do_action_ref_array('shopp_download_success',array(&$Purchased));
					exit();
				}
				break;
		}
	}

	/**
	 * ajax ()
	 * Handles AJAX request processing */
	function ajax() {
		if (!isset($_REQUEST['action']) || !defined('DOING_AJAX')) return;
		
		if (isset($_POST['action'])) {			
			switch($_POST['action']) {
				// Upload an image in the product editor
				case "shopp_add_image":
					$this->Flow->add_images();
					exit();
					break;
				
				// Upload a product download file in the product editor
				case "shopp_add_download":
					$this->Flow->product_downloads();
					exit();
					break;
			}
		}
		
		if ((!is_user_logged_in() || !current_user_can('manage_options'))
			&& strpos($_GET['action'],'wp_ajax_shopp_') !== false) die('-1');
		
		if (empty($_GET['action'])) return;
		switch($_GET['action']) {
			
			// Add a category in the product editor
			case "wp_ajax_shopp_add_category":
				check_admin_referer('shopp-ajax_add_category');
			
				if (!empty($_GET['name'])) {
					$Catalog = new Catalog();
					$Catalog->load_categories();
				
					$Category = new Category();
					$Category->name = $_GET['name'];
					$Category->slug = sanitize_title_with_dashes($Category->name);
					$Category->parent = $_GET['parent'];
										
					// Work out pathing
					$paths = array();
					if (!empty($Category->slug)) $paths = array($Category->slug);  // Include self
				
					$parentkey = -1;
					// If we're saving a new category, lookup the parent
					if ($Category->parent > 0) {
						array_unshift($paths,$Catalog->categories[$Category->parent]->slug);
						$parentkey = $Catalog->categories[$Category->parent]->parent;
					}
				
					while ($category_tree = $Catalog->categories[$parentkey]) {
						array_unshift($paths,$category_tree->slug);
						$parentkey = $category_tree->parent;
					}
				
					if (count($paths) > 1) $Category->uri = join("/",$paths);
					else $Category->uri = $paths[0];
					
					$Category->save();
					echo json_encode($Category);
				}
				exit();
				break;

			case "wp_ajax_shopp_edit_slug":
				check_admin_referer('shopp-ajax_edit_slug');
				if ( !current_user_can('manage_options') ) die("-1");
								
				switch ($_REQUEST['type']) {
					case "category":
						$Category = new Category($_REQUEST['id']);
						if (empty($_REQUEST['slug'])) $_REQUEST['slug'] = $Category->name;
						$Category->slug = sanitize_title_with_dashes($_REQUEST['slug']);
						if ($Category->save()) echo apply_filters('editable_slug',$Category->slug);
						else echo '-1';
						break;
					case "product":
						$Product = new Product($_REQUEST['id']);
						if (empty($_REQUEST['slug'])) $_REQUEST['slug'] = $Product->name;
						$Product->slug = sanitize_title_with_dashes($_REQUEST['slug']);
						if ($Product->save()) echo apply_filters('editable_slug',$Product->slug);
						else echo '-1';
						break;
				}
				exit();
				break;
				
			// Upload a product download file in the product editor
			case "wp_ajax_shopp_verify_file":
				check_admin_referer('shopp-ajax_verify_file');
				if ( !current_user_can('manage_options') ) exit();
				$target = trailingslashit($this->Settings->get('products_path')).$_POST['filepath'];
				if (!file_exists($target)) die("NULL");
				if (is_dir($target)) die("ISDIR");
				if (!is_readable($target)) die("READ");
				die("OK");
				break;
				
			// Perform a version check for any updates
			case "wp_ajax_shopp_version_check":	
				check_admin_referer('shopp-wp_ajax_shopp_update');
				$request = array(
					"ShoppServerRequest" => "version-check",
					"ver" => '1.0'
				);
				$data = array(
					'core' => SHOPP_VERSION,
					'addons' => join("-",$this->Flow->validate_addons())
				);
				echo $this->Flow->callhome($request,$data);
				exit();
			case "wp_ajax_shopp_verify":
				if ($this->Settings->get('maintenance') == "on") echo "1";
				exit();

			// Perform an update process
			case "wp_ajax_shopp_update":
				check_admin_referer('shopp-wp_ajax_shopp_update');
				$this->Flow->update();
				exit();
			case "wp_ajax_shopp_setftp":
				check_admin_referer('shopp-wp_ajax_shopp_update');
				$this->Flow->settings_save();
				$updates = $this->Settings->get('ftp_credentials');
				exit();
		}
				
	}

} // end Shopp

/**
 * shopp()
 * Provides the Shopp 'tag' support to allow for complete 
 * customization of customer interfaces
 *
 * @param $object The object to get the tag property from
 * @param $property The property of the object to get/output
 * @param $options Custom options for the property result in query form 
 *                   (option1=value&option2=value&...) or alternatively as an associative array
 */
function shopp () {
	global $Shopp;
	$args = func_get_args();

	$object = strtolower($args[0]);
	$property = strtolower($args[1]);
	$options = array();
	
	if (isset($args[2])) {
		if (is_array($args[2]) && !empty($args[2])) {
			// handle associative array for options
			foreach(array_keys($args[2]) as $key)
				$options[strtolower($key)] = $args[2][$key];
		} else {
			// regular url-compatible arguments
			$paramsets = explode("&",$args[2]);
			foreach ((array)$paramsets as $paramset) {
				if (empty($paramset)) continue;
				$key = $paramset;
				$value = "";
				if (strpos($paramset,"=") !== false) 
					list($key,$value) = explode("=",$paramset);
				$options[strtolower($key)] = $value;
			}
		}
	}
	
	$Object = false; $result = false;
	switch (strtolower($object)) {
		case "cart": if (isset($Shopp->Cart)) $Object =& $Shopp->Cart; break;
		case "cartitem": if (isset($Shopp->Cart)) $Object =& $Shopp->Cart; break;
		case "shipping": if (isset($Shopp->Cart)) $Object =& $Shopp->Cart; break;
		case "checkout": if (isset($Shopp->Cart)) $Object =& $Shopp->Cart; break;
		case "category": if (isset($Shopp->Category)) $Object =& $Shopp->Category; break;
		case "subcategory": if (isset($Shopp->Category->child)) $Object =& $Shopp->Category->child; break;
		case "catalog": if (isset($Shopp->Catalog)) $Object =& $Shopp->Catalog; break;
		case "product": if (isset($Shopp->Product)) $Object =& $Shopp->Product; break;
		case "purchase": if (isset($Shopp->Cart->data->Purchase)) $Object =& $Shopp->Cart->data->Purchase; break;
		case "customer": if (isset($Shopp->Cart->data->Order->Customer)) $Object =& $Shopp->Cart->data->Order->Customer; break;
		case "error": if (isset($Shopp->Cart->data->Errors)) $Object =& $Shopp->Cart->data->Errors; break;
		default: $Object = false;
	}
	
	if (!$Object) new ShoppError("The shopp('$object') tag cannot be used in this context because the object responsible for handling it doesn't exist.",'shopp_tag_error',SHOPP_ADMIN_ERR);
	else {
		switch (strtolower($object)) {
			case "cartitem": $result = $Object->itemtag($property,$options); break;
			case "shipping": $result = $Object->shippingtag($property,$options); break;
			case "checkout": $result = $Object->checkouttag($property,$options); break;
			default: $result = $Object->tag($property,$options); break;
		}
	}

	// Force boolean result
	if (isset($options['is'])) {
		if (value_is_true($options['is'])) {
			if ($result) return true;
		} else {
			if ($result == false) return true;
		}
		return false;
	}

	// Always return a boolean if the result is boolean
	if (is_bool($result)) return $result;

	// Return the result instead of outputting it
	if ((isset($options['return']) && value_is_true($options['return'])) ||
			isset($options['echo']) && !value_is_true($options['echo'])) 
		return $result;

	// Output the result
	echo $result;
	return true;
}

?>
