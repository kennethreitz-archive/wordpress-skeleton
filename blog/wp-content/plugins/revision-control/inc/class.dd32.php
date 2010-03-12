<?php
if ( 3 >= $GLOBALS['dd32_version'] && !class_exists('DD32') ) {
class DD32 {
	var $version = 3;
	function DD32() {
		
	}

	//$folder = Full path to folder
	function find_files( $folder, $args = array() ) {
	
		$folder = untrailingslashit($folder);
	
		$defaults = array( 'pattern' => '', 'levels' => 100, 'relative' => '' );
		$r = wp_parse_args($args, $defaults);
	
		extract($r, EXTR_SKIP);
		
		//Now for recursive calls, clear relative, we'll handle it, and decrease the levels.
		unset($r['relative']);
		--$r['levels'];
	
		if ( ! $levels )
			return array();
		
		if ( ! is_readable($folder) )
			return false;
	
		$files = array();
		if ( $dir = @opendir( $folder ) ) {
			while ( ( $file = readdir($dir) ) !== false ) {
				if ( in_array($file, array('.', '..') ) )
					continue;
				if ( is_dir( $folder . '/' . $file ) ) {
					$files2 = DD32::find_files( $folder . '/' . $file, $r );
					if( $files2 )
						$files = array_merge($files, $files2 );
					else if ( empty($pattern) || preg_match('|^' . str_replace('\*', '\w+', preg_quote($pattern)) . '$|i', $file) )
						$files[] = $folder . '/' . $file . '/';
				} else {
					if ( empty($pattern) || preg_match('|^' . str_replace('\*', '\w+', preg_quote($pattern)) . '$|i', $file) )
						$files[] = $folder . '/' . $file;
				}
			}
		}
		@closedir( $dir );
	
		if ( ! empty($relative) ) {
			$relative = trailingslashit($relative);
			foreach ( $files as $key => $file )
				$files[$key] = preg_replace('!^' . preg_quote($relative) . '!', '', $file);
		}
	
		return $files;
	}
	
	function add_configure($plugin, $title, $url, $args = array() ) {
		$defaults = array( 'class' => '', 'title' => $title );
		$r = wp_parse_args($args, $defaults);
		$link = "<a href='$url' class='{$r['class']}' title='{$r['title']}'>$title</a>";
		add_action("plugin_action_links_$plugin", create_function('$links', 'return array_merge( array("' . $link . '"), $links);'));
	}

	//Function adds a list of changes after the plugin in the plugins table.
	function add_changelog($plugin, $url) {
		add_action("after_plugin_row_$plugin", create_function('$data', 'DD32::add_changelog_rows("' . $plugin .'", "' . $url . '", $data);'), 10, 2);
	}
	function add_changelog_rows($plugin, $url, $plugin_data) {

		if ( false === get_option('dd32_changelogs', false) )
			add_option('dd32_changelogs', array(), '', 'no'); //Add a no-auto-load option.

		$update = get_option('update_plugins');
		if ( ! isset($update->response[$plugin]) )
			return;

		$changelogs = get_option('dd32_changelogs', array());
		if ( ! isset($changelogs[$url]) || !isset($changelogs[$url]['time']) || $changelogs[$url]['time'] < time()-24*60*60 ) {
			$log = wp_remote_get($url);
			if ( $log['response']['code'] != 200 )
				return;
			if ( ! preg_match('!== Changelog ==\s+(.*?)\s+(==|$)!is', $log['body'], $mat) )
				return;
			$mat = preg_split('!^=!im', $mat[1]);
			$changes = array();
			foreach ( (array)$mat as $version ) {
				if ( preg_match('!^\s+([\w.]+)\s*=!i', $version, $mat_version) )
					$change_version = $mat_version[1];
				else
					$change_version = 'unknown';

				if ( preg_match_all('!^\s*[*](.*)$!im', $version, $mat_changes) )
					foreach ( (array)$mat_changes[1] as $change )
						$changes[ $change_version ][] = trim($change);
			}
			
			$changelogs[ $url ] = array('time' => time(), 'changes' => $changes);
			update_option('dd32_changelogs', $changelogs);
		} else {
			$changes = $changelogs[ $url ]['changes'];
		}

		foreach ( (array) $changes as $version => $changelog_item ) {
			if ( version_compare($version, $plugin_data['Version'], '<=') && 'unknown' != $version )
				continue;
			echo '
			<tr>
				<td colspan="2" class="plugin-update">&nbsp;</td>
				<td class="plugin-update">' . $version . '</td>
				<td colspan="2" class="plugin-update" style="text-align: left;"><ol style="list-style:circle"><li>' . implode('</li><li>', $changelog_item) .'</li></ol></td>
			</tr>
			';
		}
	}

}}
?>