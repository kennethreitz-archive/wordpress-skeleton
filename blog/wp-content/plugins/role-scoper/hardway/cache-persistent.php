<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

// cache.php from WP 2.3, with added ability to flush a specific flag/subdirectory
//
// Also added safeguards against retaining an invalid cache after failed update / flush attempt
//
// Added better MU support
//
function wpp_suffix_flag( $flag ) {
	if ( IS_MU_RS ) {
		global $wpp_object_cache;
		
		if ( is_array( $wpp_object_cache->global_groups ) && ! in_array( $flag, $wpp_object_cache->global_groups ) ) {
			global $blog_id;
			$flag .= "_$blog_id";
		}
	}
	
	return $flag;
}

function wpp_cache_add($key, $data, $flag = '', $expire = 0, $append_blog_suffix = true) {
	if ( ! empty($_POST) )	// kevinB: reduce elusive anomolies and allow flushing optimization by disabling cache updates during POST operation
		return;
		
	global $wpp_object_cache;
	
	if ( $append_blog_suffix )
		$flag = wpp_suffix_flag( $flag );
	
	//$data = unserialize(serialize($data));
	if ( is_serialized( $data ) )
		$data = unserialize($data);
	
	if ( empty($wpp_object_cache) )
		return;
	
	return $wpp_object_cache->add($key, $data, $flag, $expire);
}

function wpp_cache_close() {
	global $wpp_object_cache;

	if ( empty($wpp_object_cache) )
		return;
		
	return $wpp_object_cache->save();
}

function wpp_cache_delete($id, $flag = '', $append_blog_suffix = true) {
	global $wpp_object_cache;

	if ( $append_blog_suffix )
		$flag = wpp_suffix_flag( $flag );
	
	//rs_errlog("wpp_cache_delete: $id, $flag");
	
	if ( empty($wpp_object_cache) )
		return;
		
	return $wpp_object_cache->delete($id, $flag);
}

function wpp_cache_flush() {
	global $wpp_object_cache;

	if ( empty($wpp_object_cache) || ! is_object($wpp_object_cache) ) {
		
		if ( empty($wpp_object_cache->auto_flushed) ) {
			//rs_errlog('need flush - failed wpp_cache_flush');
			update_option( 'scoper_need_cache_flush', true );
		}
		return;
	}
		
	return $wpp_object_cache->flush();
}

// added by kevinB for use with Role Scoper
function wpp_cache_flush_group($flag, $append_blog_suffix = true) {
	global $wpp_object_cache;
	
	if ( $append_blog_suffix )
		$flag = wpp_suffix_flag( $flag );
	
	if ( ! empty($_POST) ) {	// kevinB: since cache updating during POST operation is disabled, improve perf by flushing each group only once
		static $already_flushed;
		
		if ( ! isset($already_flushed) )
			$already_flushed = array();
		
		if ( isset($already_flushed[$flag]) )
			return;
	}
	
	
	if ( empty($wpp_object_cache) || ! is_object($wpp_object_cache) )
		return;
		
	$flush_okay = $wpp_object_cache->flush($flag);
	
	if ( $flush_okay && ! empty($_POST) )
		$already_flushed[$flag] = true;

	return $flush_okay;
}

function wpp_cache_get($id, $flag = '', $append_blog_suffix = true) {
	global $wpp_object_cache;

	if ( $append_blog_suffix )
		$flag = wpp_suffix_flag( $flag );
	
	if ( empty($wpp_object_cache) )
		return;
		
	return $wpp_object_cache->get($id, $flag);
}

function wpp_cache_init( $sitewide_groups = true ) {
	global $wpp_object_cache;
	
	if ( isset($wpp_object_cache) )
		$wpp_object_cache->save();
		
	$GLOBALS['wpp_object_cache'] = new WP_Persistent_Object_Cache();
	
	if ( IS_MU_RS && $sitewide_groups )
		$GLOBALS['wpp_object_cache']->global_groups = array_merge( $GLOBALS['wpp_object_cache']->global_groups, array( 'all_usergroups', 'group_members' ) );
	
	// added by kevinB: if a flush fails, try try again (and meanwhile, DON'T use the old invalid cache)
	$need_flush = ( function_exists('scoper_get_option') ) ? scoper_get_option('need_cache_flush') : get_option('scoper_need_cache_flush');
	if ( $need_flush ) {
		//rs_errlog('cache init: performing pending flush');
		delete_option('scoper_need_cache_flush');
		
		$wpp_object_cache->auto_flushed = true;
		$GLOBALS['wpp_object_cache']->flush();
	}
		
}

function wpp_cache_replace($key, $data, $flag = '', $expire = 0, $append_blog_suffix = true) {
	if ( ! empty($_POST) )	// kevinB: reduce elusive anomolies and allow flushing optimization by disabling cache updates during POST operation
		return;

	global $wpp_object_cache;
	
	if ( $append_blog_suffix )
		$flag = wpp_suffix_flag( $flag );
	
	//$data = unserialize(serialize($data));
	if ( is_serialized( $data ) )
		$data = unserialize($data);

	if ( empty($wpp_object_cache) )
		return;
	
	return $wpp_object_cache->replace($key, $data, $flag, $expire);
}

function wpp_cache_set($key, $data, $flag = '', $expire = 0, $append_blog_suffix = true) {
	if ( ! empty($_POST) )	// kevinB: reduce elusive anomolies and allow flushing optimization by disabling cache updates during POST operation
		return;

	global $wpp_object_cache;
	
	if ( $append_blog_suffix )
		$flag = wpp_suffix_flag( $flag );
	
	//$data = unserialize(serialize($data));
	if ( is_serialized( $data ) )
		$data = unserialize($data);

	if ( empty($wpp_object_cache) )
		return;
	
	return $wpp_object_cache->set($key, $data, $flag, $expire);
}

// returns true on success
function wpp_cache_test( &$err_msg, $text_domain = '' ) {
	// intentionally not using WP_CACHE_DIR because we need a known location so rs_cache_flush.php can delete files without loading WP
	$cache_dir = ( defined( 'CACHE_PATH' ) ) ? CACHE_PATH : WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;
	$err = false;
	
	if ( ! defined( 'ENABLE_PERSISTENT_CACHE' ) ) {
		$err_msg = __('The file cache will not operate because ENABLE_PERSISTENT_CACHE is not defined in wp-config.php or role-scoper.php.', 'scoper');
		$err = true;
	} elseif ( defined( 'DISABLE_PERSISTENT_CACHE' ) ) {
		$err_msg = __('The file cache will not operate because DISABLE_PERSISTENT_CACHE is defined, possibly in wp-config.php or role-scoper.php.', 'scoper');
		$err = true;
	} elseif ( ! is_writable($cache_dir) || ! is_dir($cache_dir)) {
		$err_msg = sprintf( __('The file cache cannot operate because the cache directory (%s) is not writeable to WordPress.', $text_domain), $cache_dir );
		$err = true;
	} elseif ( ! defined('SCOPER_SAFE_MODE_CACHE') && ini_get('safe_mode') ) {
		$err_msg = __('The file cache cannot operate because PHP is running in safe mode.', $text_domain);
		$err = true;
	} else {
		global $wpp_object_cache;
		$temp_file = tempnam($cache_dir, 'tmp');
		
		$fd = @fopen($temp_file, 'w');
		if ( false === $fd ) {
			$err_msg = sprintf( __('The file cache cannot operate because file creation attempts fail in %s', $text_domain), $cache_dir );
			$err = true;
		} else {
			$serial = '';
			fputs($fd, $serial);
			fclose($fd);
			
			if ( file_exists($temp_file) )
				unlink($temp_file);
			else {
				$err_msg = sprintf( __('The file cache cannot operate because file storage attempts fail in %s', $text_domain), $cache_dir );
				$err = true;
			}
		}
	} // endif cache directory is writeable
	
	return ! $err;
}

if ( ! defined('CACHE_SERIAL_HEADER') )
	define('CACHE_SERIAL_HEADER', "<?php\n/*");

if ( ! defined('CACHE_SERIAL_FOOTER') )
	define('CACHE_SERIAL_FOOTER', "*/\n?".">");

class WP_Persistent_Object_Cache {
	var $cache_dir;
	var $cache_enabled = false;
	var $expiration_time = 900;
	var $flock_filename = 'wpp_object_cache.lock';
	var $mutex;
	var $cache = array ();
	var $dirty_objects = array ();
	var $non_existant_objects = array ();
	var $global_groups = array ('users', 'userlogins', 'usermeta');
	var $non_persistent_groups = array('comment');
	var $blog_id;									// Note: wpp_cache_get() / wpp_cache_set() / wpp_cache_delete() functions also attach blog_id suffix to flag.  This blog_id property is just to establish a separate physical cache subfolder for each blog
	var $cold_cache_hits = 0;
	var $warm_cache_hits = 0;
	var $cache_misses = 0;
	var $secret = '';
	var $is_404;
	
	function WP_Persistent_Object_Cache() {
		global $blog_id;
		
		// Destructor method is not reliable.  Call non-object function manually via WP shutdown hook instead.
		// Also leave this method in place as a backup in case WP shutdown hook is not called.
		register_shutdown_function(array(&$this, "__destruct"));
		
		if ( defined('DISABLE_PERSISTENT_CACHE') || ! defined('ENABLE_PERSISTENT_CACHE') )
			return;

		// Disable the persistent cache if safe_mode is on.
		if ( ! defined('SCOPER_SAFE_MODE_CACHE') && ini_get('safe_mode') )
			return;
		
		if (defined('CACHE_PATH'))
			$this->cache_dir = CACHE_PATH;
		else {
			// Intentionally not using WP_CACHE_DIR because we need a known location so rs_cache_flush.php can delete files without loading WP.
			// Using the correct separator eliminates some cache flush errors on Windows
			$this->cache_dir = WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;
		}
		
		if (is_writable($this->cache_dir) && is_dir($this->cache_dir)) {
				$this->cache_enabled = true;
		} else {
			if (is_writable(WP_CONTENT_DIR)) {
				$this->cache_enabled = true;
			}
		}

		if (defined('CACHE_EXPIRATION_TIME'))
			$this->expiration_time = CACHE_EXPIRATION_TIME;

		if ( defined('WP_SECRET') )
			$this->secret = WP_SECRET;
		else
			$this->secret = DB_PASSWORD . DB_USER . DB_NAME . DB_HOST . ABSPATH;

		$this->blog_id = $this->hash($blog_id);
	}
	
	function __destruct() {
		//if ( empty($this->saved) )	// kevinB: no harm checking for new dirty objects even if save was already invoked manually
			$this->save();
			
		return true;
	}
	
	function acquire_lock() {
		// Acquire a write lock.
		$this->mutex = @fopen($this->cache_dir.$this->flock_filename, 'w');
		if ( false == $this->mutex)
			return false;
		flock($this->mutex, LOCK_EX);
		return true;
	}

	function add($id, $data, $group = 'default', $expire = '') {
		if (empty ($group))
			$group = 'default';

		if (false !== $this->get($id, $group, false))
			return false;

		return $this->set($id, $data, $group, $expire);
	}

	function delete($id, $group = 'default', $force = true) {
		if (empty ($group))
			$group = 'default';

		if (!$force && false === $this->get($id, $group, false))
			return false;

		//rs_errlog ("<br />deleting $id/$group:<br />");

		$this->non_existant_objects[$group][$id] = true;
		$this->dirty_objects[$group][] = $id;
		
		if ( isset($this->cache[$group][$id]) ) {
			//rs_errlog ("<br />deleting {$this->cache[$group][$id]}<br />");
			unset ($this->cache[$group][$id]);
		} else {
			// workaround for problematic servers - delete entire file just in case it's getting cached by the server
			//rs_errlog ("removing entire cache file.");
			$this->rm_cache_dir($group);
		}
	
		return true;
	}
	
	// * flush method modified by kevinB for use with Role Scoper:
	// With scoped roles, get_terms and get_pages results are potentially unique for each user or set of groups
	// Avoid passing potentially huge cache arrays to and from wp_object_cache by giving 
	// each user/group set a separate cache group.  However, as of WP 2.5, these can
	// only be deleted by flushing the entire cache, or by explicit deletion of each
	// user/group id.  Neither option is reasonable; we need the ability to flush a wp_cache group. 
	//
	function flush($group = '') {
		if ( empty( $this->cache_enabled ) )
			return true;

		if ( ! $this->acquire_lock() ) {
			$this->cache_enabled = false; // if a pending flush failed, make sure we don't use an invalid cache
			
			if ( empty($this->auto_flushed) ) {
				//rs_errlog('need flush - failed flush');
				update_option( 'scoper_need_cache_flush', true );
			}
			
			return false;
		}
		
		$this->rm_cache_dir($group);
		
		//rs_errlog ("<br />removing cached group $group:<br />");
		
		if ( $group ) {
			if ( isset($this->cache[$group]) )
				unset($this->cache[$group]);
				
			if ( isset($this->dirty_objects[$group]) )
				unset($this->dirty_objects[$group]);
				
			if ( isset($this->non_existant_objects[$group]) )
				unset($this->non_existant_objects[$group]);
		} else {
			$this->cache = array ();
			$this->dirty_objects = array ();
			$this->non_existant_objects = array ();
		}
		
		$this->release_lock();

		return true;
	}

	function get($id, $group = 'default', $count_hits = true) {
		if (empty ($group))
			$group = 'default';

		if (isset ($this->cache[$group][$id])) {
			if ($count_hits)
				$this->warm_cache_hits += 1;
			return $this->cache[$group][$id];
		}

		if (isset ($this->non_existant_objects[$group][$id]))
			return false;

		//rs_errlog ("<br />getting $id/$group:<br />");
		
		//  If caching is not enabled, still return any memcached results from this http session
		if (!$this->cache_enabled) {
			if (isset ($this->cache[$group][$id])) {
				$this->cold_cache_hits += 1;
				return $this->cache[$group][$id];
			}

			$this->non_existant_objects[$group][$id] = true;
			$this->cache_misses += 1;
			return false;
		}

		$cache_file = $this->cache_dir.$this->get_group_dir($group)."/".$this->hash($id).'.php';
		if (!file_exists($cache_file)) {
			$this->non_existant_objects[$group][$id] = true;
			$this->cache_misses += 1;
			return false;
		}

		// If the object has expired, remove it from the cache and return false to force
		// a refresh.
		$now = time();
		if ((filemtime($cache_file) + $this->expiration_time) <= $now) {
			$this->cache_misses += 1;
			$this->delete($id, $group, true);
			return false;
		}

		$this->cache[$group][$id] = unserialize(base64_decode(substr(@ file_get_contents($cache_file), strlen(CACHE_SERIAL_HEADER), -strlen(CACHE_SERIAL_FOOTER))));
		if (false === $this->cache[$group][$id])
			$this->cache[$group][$id] = '';

		$this->cold_cache_hits += 1;
		return $this->cache[$group][$id];
	}

	function get_group_dir($group) {
		if (false !== array_search($group, $this->global_groups))
			return $group;

		return "{$this->blog_id}/$group";
	}

	function hash($data) {
		if ( function_exists('hash_hmac') ) {
			return hash_hmac('md5', $data, $this->secret);
		} else {
			return md5($data . $this->secret);
		}
	}

	function load_group_from_db($group) {
		return;
	}

	function make_group_dir($group, $perms) {
		$group_dir = $this->get_group_dir($group);
		$make_dir = '';
		foreach (split('/', $group_dir) as $subdir) {
			$make_dir .= "$subdir/";
			if (!file_exists($this->cache_dir.$make_dir)) {
				// kevinB: don't make an empty cache entry following unnecessary delete call
				if ( empty($this->cache[$group]) )
					return false;
			
				if (! @ mkdir($this->cache_dir.$make_dir))
					break;
				@ chmod($this->cache_dir.$make_dir, $perms);
			}

			if (!file_exists($this->cache_dir.$make_dir."index.php")) {
				$file_perms = $perms & 0000666;
				@ touch($this->cache_dir.$make_dir."index.php");
				@ chmod($this->cache_dir.$make_dir."index.php", $file_perms);
			}
		}

		return $this->cache_dir."$group_dir/";
	}

	// modified by kevinB for use with Role Scoper ( see explanation above flush method )
	function rm_cache_dir($group = '') {
		// BEGIN Rolescoper Modification: optionally reference group subdir
		$group_dir = ( $group ) ? $this->get_group_dir($group) : '';
		
		$dir = $this->cache_dir . $group_dir;
		$dir = rtrim($dir, DIRECTORY_SEPARATOR);
		// END RoleScoper Modification --//
		
		$top_dir = $dir;
		$stack = array($dir);
		$index = 0;
		$errors = 0;
		
		while ($index < count($stack)) {
			# Get indexed directory from stack
			$dir = $stack[$index];

			if ( ! is_dir($dir) ) {
				$index++;
				continue;
			}
			
			$dh = @ opendir($dir);
			if (!$dh) {
				$this->cache_enabled = false; // if a pending flush failed, make sure we don't use an invalid cache
				
				if ( empty($this->auto_flushed) ) {
					//rs_errlog('need flush - failed rm_cache_dir opendir');
					update_option( 'scoper_need_cache_flush', true );
				}
				
				return false;
			}
			
			while (($file = @ readdir($dh)) !== false) {
				if ($file == '.' or $file == '..')
					continue;

				if (@ is_dir($dir . DIRECTORY_SEPARATOR . $file))
					$stack[] = $dir . DIRECTORY_SEPARATOR . $file;
				else if (@ is_file($dir . DIRECTORY_SEPARATOR . $file)) {
					if ( file_exists($dir . DIRECTORY_SEPARATOR . $file) ) {
						if ( !@ unlink($dir . DIRECTORY_SEPARATOR . $file)) {
							$errors++;
						}
					}
				}
			}

			$index++;
		}

		$stack = array_reverse($stack);  // Last added dirs are deepest
		foreach($stack as $dir) {
			if ( $dir != $top_dir) {
				if ( ! @ rmdir($dir) ) {
					$errors++;
				}
			}
		}
		
		if ( $errors ) {
			// false positives cause perpetual flush
			$this->cache_enabled = false; // if a pending flush failed, make sure we don't use an invalid cache
			
			if ( empty($this->auto_flushed) ) {
				//rs_errlog('need flush - failed rm_cache_dir');
				update_option( 'scoper_need_cache_flush', true );
			}
		}
	}

	function release_lock() {
		// Release write lock.
		flock($this->mutex, LOCK_UN);
		fclose($this->mutex);
	}

	function replace($id, $data, $group = 'default', $expire = '') {
		if (empty ($group))
			$group = 'default';

		if (false === $this->get($id, $group, false))
			return false;

		return $this->set($id, $data, $group, $expire);
	}

	function set($id, $data, $group = 'default', $expire = '') {
		if (empty ($group))
			$group = 'default';

		if ( ! is_array($data) && ( NULL == $data ) )
			$data = '';

		//rs_errlog ("<br />setting $id/$group:<br />");
		
		// is_404() function is no longer available at the execution of this wpp_cache_close, so check it here
		if ( function_exists( 'is_404' ) && is_404() && empty( $this->is_404 ) )
			$this->is_404 = true;
		
		if ( ! empty( $this->is_404 ) )
			return true;
			
		$this->cache[$group][$id] = $data;
		unset ($this->non_existant_objects[$group][$id]);
		$this->dirty_objects[$group][] = $id;

		return true;
	}

	function save() {
		//$this->stats();
		
		//rs_errlog ("<br />cache save function<br />");
		
		if ( ! $this->cache_enabled )
			return true;

		if (empty ($this->dirty_objects))
			return true;
			
		if ( ! empty( $this->is_404 ) )  // is_404() function is no longer available at the execution of this wpp_cache_close
			return true;
			
		//rs_errlog ("<br />saving pers cache:<br />");
		
		// Give the new dirs the same perms as WP_CONTENT_DIR
		$stat = stat(WP_CONTENT_DIR);
		$dir_perms = $stat['mode'] & 0007777; // Get the permission bits.
		$file_perms = $dir_perms & 0000666; // Remove execute bits for files.

		// Make the base cache dir.
		if (!file_exists($this->cache_dir)) {
			if (! @ mkdir($this->cache_dir))
				return false;
			@ chmod($this->cache_dir, $dir_perms);
		}
		
		//rs_errlog ("<br />made cache dir:<br />");

		if (!file_exists($this->cache_dir."index.php")) {
			@ touch($this->cache_dir."index.php");
			@ chmod($this->cache_dir."index.php", $file_perms);
		}

		if ( ! $this->acquire_lock() ) {
			// This causes perpetual auto-flushing
			//$this->cache_enabled = false; // if a pending flush failed, make sure we don't use an invalid cache
			//update_option( 'scoper_need_cache_flush', true );
			
			return false;
		}
		
		//rs_errlog ("<br />acquired lock<br />");
			
		// Loop over dirty objects and save them.
		$errors = 0;
		foreach ($this->dirty_objects as $group => $ids) {
			if ( in_array($group, $this->non_persistent_groups) )
				continue;

			if ( ! $group_dir = $this->make_group_dir($group, $dir_perms) )
				continue;

			$ids = array_unique($ids);
			foreach ($ids as $id) {
				$cache_file = $group_dir.$this->hash($id).'.php';

				// Remove the cache file if the key is not set.
				if (!isset ($this->cache[$group][$id])) {
					if (file_exists($cache_file))
						if ( !@ unlink($cache_file) )
							$errors++;
							
					continue;
				}

				$temp_file = tempnam($group_dir, 'tmp');
				$serial = CACHE_SERIAL_HEADER.base64_encode(serialize($this->cache[$group][$id])).CACHE_SERIAL_FOOTER;
				
				//rs_errlog ("<br />$temp_file<br />");
				
				$fd = @fopen($temp_file, 'w');
				if ( false === $fd ) {
					$errors++;
					continue;
				}
				
				//rs_errlog ("<br />file $temp_file opened<br />");
				
				fputs($fd, $serial);
				fclose($fd);
				if (!@ rename($temp_file, $cache_file)) {
					if (!@ copy($temp_file, $cache_file))
						$errors++;
					
					if ( file_exists($temp_file) )
						if ( !@ unlink($temp_file))
							$errors++;
				}
				@ chmod($cache_file, $file_perms);
			}
		}

		if ( $errors && $this->dirty_objects ) {
			$this->cache_enabled = false; // if a pending flush failed, make sure we don't use an invalid cache
			
			// This causes perpetual flushing, at least with WP 2.6
			//if ( empty($this->auto_flushed) )
				//update_option( 'scoper_need_cache_flush', true );
		}
		
		$this->dirty_objects = array();

		$this->release_lock();

		if ( $errors ) {
			return false;
		}
		
		$this->saved = true;
			
		return true;
	}

	function stats() {
		echo "<p>";
		echo "<strong>Cold Cache Hits:</strong> {$this->cold_cache_hits}<br />";
		echo "<strong>Warm Cache Hits:</strong> {$this->warm_cache_hits}<br />";
		echo "<strong>Cache Misses:</strong> {$this->cache_misses}<br />";
		echo "</p>";

		foreach ($this->cache as $group => $cache) {
			echo "<p>";
			echo "<strong>Group:</strong> $group<br />";
			echo "<strong>Cache:</strong>";
			echo "<pre>";
			print_r($cache);
			echo "</pre>";
			if (isset ($this->dirty_objects[$group])) {
				echo "<strong>Dirty Objects:</strong>";
				echo "<pre>";
				print_r(array_unique($this->dirty_objects[$group]));
				echo "</pre>";
				echo "</p>";
			}
		}
	}
}

add_action('admin_footer', 'wpp_cache_close');
add_action('wp_footer', 'wpp_cache_close');
?>
