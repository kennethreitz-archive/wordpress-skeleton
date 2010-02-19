<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

require_once('db-config_rs.php');
	
global $wpdb;


function scoper_db_setup($last_db_ver) {
	scoper_update_schema($last_db_ver);
	
	global $scoper_db_setup_done;
	$scoper_db_setup_done = 1;
	
	if ( ! $last_db_ver )
		scoper_set_conditional_defaults();
}

function scoper_update_schema($last_db_ver) {
	global $wpdb;
	
	/*--- Groups table def 
		(complicated because (a) we support usage of pre-existing group table from other app
		 					 (b) existing group table may include a subset of our required columns
		 					 (c) existing group table may require authenticated/unauthenticated default group flag to be stored in two different columns 
	*/
	
	//first define column(s) to create for default groups
	$cols = array();
	$cols[$wpdb->groups_id_col] = "bigint(20) NOT NULL auto_increment";
	$cols[$wpdb->groups_name_col] = "text NOT NULL";
	$cols[$wpdb->groups_descript_col] = "text NOT NULL";
	$cols[$wpdb->groups_homepage_col] = "varchar(128) NOT NULL default ''";
	$cols[$wpdb->groups_meta_id_col] = "varchar(64) NOT NULL default ''";
	
	if($tables = $wpdb->get_col('SHOW TABLES;'))
		foreach($tables as $table)
			if ($table == $wpdb->groups_rs)
				break;	
				
	if ( $table != $wpdb->groups_rs ) { //group table doesn't already exist
		 $query = "CREATE TABLE IF NOT EXISTS " . $wpdb->groups_rs . ' (';
		 
		 foreach ($cols as $colname => $typedef)
		 	$query .= $colname . ' ' . $typedef . ',';

		$query .= " PRIMARY KEY ($wpdb->groups_id_col),"
				. " KEY meta_id ($wpdb->groups_meta_id_col, $wpdb->groups_id_col) );";
	
		$wpdb->query($query);
		
	} else {
		//specified group table exists already, so do not alter any existing columns
		// (we're not fussy about data types since the joins to these tables are infrequent and/or buffered, 
		// but other app(s) might be fussy).
		
		$tablefields = $wpdb->get_col("DESC $wpdb->groups_rs", 0);
		
		if ( $add_cols = array_diff_key( $cols, array_flip($tablefields) ) ) {
			foreach ( $add_cols as $requiredcol_name => $requiredcol_typedef ) {
				if ( $requiredcol_name == $wpdb->groups_id_col ) // don't try to add id column
					continue;

				$wpdb->query("ALTER TABLE $wpdb->groups_rs ADD COLUMN $requiredcol_name $requiredcol_typedef");	
				
				if ( $wpdb->groups_meta_id_col == $requiredcol_name )
					$wpdb->query( "CREATE INDEX meta_id ON $wpdb->groups_rs ($wpdb->groups_meta_id_col, $wpdb->groups_id_col)" );
			}
		}
		
		if ( ! version_compare( $last_db_ver, '1.0.2', '>=') ) {
			// DB version < 1.0.2 used varchar columns, which don't support unicode
			$wpdb->query("ALTER TABLE $wpdb->groups_rs MODIFY COLUMN $wpdb->groups_name_col text NOT NULL");
			$wpdb->query("ALTER TABLE $wpdb->groups_rs MODIFY COLUMN $wpdb->groups_descript_col text NOT NULL");	
		}
	
		if ( ! version_compare( $last_db_ver, '1.1.1', '>=') ) {
			$query = "ALTER TABLE $wpdb->groups_rs MODIFY $wpdb->groups_meta_id_col VARCHAR(64)";	
			$wpdb->query($query);
		}
	}
	
	
	// User2Group table def (use existing table from other app if so defined in grp-config.php)
	$cols = array();
	$cols[$wpdb->user2group_gid_col] = "bigint(20) unsigned NOT NULL default '0'";
	$cols[$wpdb->user2group_uid_col] = "bigint(20) unsigned NOT NULL default '0'";
	$cols[$wpdb->user2group_assigner_id_col] = "bigint(20) unsigned NOT NULL default '0'";
				 
	if($tables = $wpdb->get_col('SHOW TABLES;'))
		foreach($tables as $table)
			if ($table == $wpdb->user2group_rs)
				break;	
	
	if ( $table != $wpdb->user2group_rs ) { // table doesn't already exist

		$query = "CREATE TABLE IF NOT EXISTS " . $wpdb->user2group_rs . ' (';
		 
		foreach ($cols as $colname => $typedef)
			$query .= $colname . ' ' . $typedef . ',';
		 
		$query .= "PRIMARY KEY user2group ($wpdb->user2group_uid_col, $wpdb->user2group_gid_col));";
		
		$wpdb->query($query);
		
	} else {

		// if existing table was found but specified groupid and userid columns are invalid, bail
		$tablefields = $wpdb->get_col("DESC $wpdb->user2group_rs", 0);
		
		foreach ($tablefields as $column) if ($column == $wpdb->user2group_gid_col) break;	
		if ($column != $wpdb->user2group_gid_col)
			wp_die ( sprintf( 'Database config error: specified ID column (%1$s) not found in table %2$s', $wpdb->user2group_gid_col, $wpdb->user2group_rs ) );
		
		foreach ($tablefields as $column) if ($column == $wpdb->user2group_uid_col) break;	
		if ($column != $wpdb->user2group_uid_col)
			wp_die ( sprintf( 'Database config error: specified ID column (%1$s) not found in table %2$s', $wpdb->user2group_uid_col, $wpdb->user2group_rs ) );

		foreach ($cols as $requiredcol_name => $requiredcol_typedef) {
			foreach ($tablefields as $col_name)
				if ($requiredcol_name == $col_name) break;
				
			if ($requiredcol_name != $col_name)
				//column was not found, so create it
				$wpdb->query("ALTER TABLE $wpdb->user2group_rs ADD COLUMN $requiredcol_name $requiredcol_typedef");
		}	
	}
	
	$tabledefs='';

	/*  user2role2object_rs: 
		
		(scope == 'object' ) => for the specified object, all users in specified group have all caps in specified role
					- OR -
		(scope == 'term' ) => for all entities for which the specified object is a category, all users in specified group have all caps in specified role
	
		(assign_for = 'children' or 'both' ) => new children of the specified object inherit this object_role_name
	
	Abstract object type to support group control of new content-specific roles without revising db schema
	
	note: Term roles are retrieved and buffered into memory for the current user at login.
	*/
	
	// note: dbDelta_rs requires two spaces after PRIMARY KEY, no spaces between KEY columns
	$tabledefs .= "CREATE TABLE $wpdb->user2role2object_rs (
	 assignment_id bigint(20) unsigned NOT NULL auto_increment,
	 user_id bigint(20) unsigned NULL,
	 group_id bigint(20) unsigned NULL,
	 role_name varchar(32) NOT NULL default '',
	 role_type enum('rs', 'wp', 'wp_cap') NOT NULL default 'rs',
	 scope enum('blog', 'term', 'object') NOT NULL,
	 src_or_tx_name varchar(32) NOT NULL default '',
	 obj_or_term_id bigint(20) unsigned NOT NULL default '0',
	 assign_for enum('entity', 'children', 'both') NOT NULL default 'entity',
	 inherited_from bigint(20) unsigned NOT NULL default '0',
	 assigner_id bigint(20) unsigned NOT NULL default '0',
	 date_limited tinyint(2) NOT NULL default '0',
	 start_date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
	 end_date_gmt datetime NOT NULL default '2035-01-01 00:00:00',
	 content_date_limited tinyint(2) NOT NULL default '0',
	 content_min_date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
	 content_max_date_gmt datetime NOT NULL default '2035-01-01 00:00:00',
	 	PRIMARY KEY  (assignment_id),
	 	KEY role2obj (scope,assign_for,role_type,role_name,src_or_tx_name,obj_or_term_id),
	 	KEY role2agent (assign_for,scope,role_type,role_name,group_id,user_id),
	 	KEY role_rs (date_limited,role_type,role_name,scope,assign_for,src_or_tx_name,group_id,user_id,obj_or_term_id),
	 	KEY role_assignments (role_name,role_type,scope,assign_for,src_or_tx_name,group_id,user_id,obj_or_term_id,inherited_from,assignment_id)
	);
	";
	
	$tabledefs .= "CREATE TABLE $wpdb->role_scope_rs (
	 requirement_id bigint(20) NOT NULL auto_increment,
	 role_name varchar(32) NOT NULL default '',
	 role_type enum('rs', 'wp', 'wp_cap') NOT NULL default 'rs',
	 topic enum('blog', 'term', 'object') NOT NULL,
	 src_or_tx_name varchar(32) NOT NULL default '',
	 obj_or_term_id bigint(20) NOT NULL default '0',
	 max_scope enum('blog', 'term', 'object') NOT NULL,
	 require_for enum('entity', 'children', 'both') NOT NULL default 'entity',
	 inherited_from bigint(20) NOT NULL default '0',
	 	PRIMARY KEY  (requirement_id),
	 	KEY role_scope (max_scope,topic,require_for,role_type,role_name,src_or_tx_name,obj_or_term_id),
	 	KEY role_scope_assignments (max_scope,topic,require_for,role_type,role_name,src_or_tx_name,obj_or_term_id,inherited_from,requirement_id)
	);
	";

	// apply all table definitions
	dbDelta_rs($tabledefs);
	
} //end update_schema function


function scoper_update_supplemental_schema($table_name) {
	global $wpdb;

	if ( 'data_rs' == $table_name ) {
		$tabledefs .= "CREATE TABLE {$wpdb->prefix}{$table_name} (
		 rs_id bigint(20) NOT NULL auto_increment,
		 topic enum('term', 'object') default 'object',
		 src_or_tx_name varchar(32) NOT NULL default '',
		 object_type varchar(32) NOT NULL default '',
		 actual_id bigint(20) NOT NULL default '0',
		 name text NOT NULL,
		 parent bigint(20) NOT NULL default '0',
		 owner bigint(20) NOT NULL default '0',
		 status varchar(20) NOT NULL default '',
		 	PRIMARY KEY  (rs_id),
		 	KEY actual_id (actual_id,src_or_tx_name,object_type,topic)
		);
		";
		
		// apply all table definitions
		dbDelta_rs($tabledefs);
		
		return true;
	}
}


/**
 * {@internal Missing Short Description}}
 *
 * {@internal Missing Long Description}}
 *
 * @since unknown
 *
 * @param unknown_type $queries
 * @param unknown_type $execute
 * @return unknown
 */
function dbDelta_rs($queries, $execute = true) {	// lifted from MU 2.8.4a because forced inclusion of schema.php by Role Scoper interferes with blog creation
	global $wpdb;

	// Separate individual queries into an array
	if( !is_array($queries) ) {
		$queries = explode( ';', $queries );
		if('' == $queries[count($queries) - 1]) array_pop($queries);
	}

	$cqueries = array(); // Creation Queries
	$iqueries = array(); // Insertion Queries
	$for_update = array();

	// Create a tablename index for an array ($cqueries) of queries
	foreach($queries as $qry) {
		if(preg_match("|CREATE TABLE (?:IF NOT EXISTS )?([^ ]*)|", $qry, $matches)) {
			$cqueries[trim( strtolower($matches[1]), '`' )] = $qry;
			$for_update[$matches[1]] = 'Created table '.$matches[1];
		}
		else if(preg_match("|CREATE DATABASE ([^ ]*)|", $qry, $matches)) {
			array_unshift($cqueries, $qry);
		}
		else if(preg_match("|INSERT INTO ([^ ]*)|", $qry, $matches)) {
			$iqueries[] = $qry;
		}
		else if(preg_match("|UPDATE ([^ ]*)|", $qry, $matches)) {
			$iqueries[] = $qry;
		}
		else {
			// Unrecognized query type
		}
	}

	// Check to see which tables and fields exist
	if($tables = $wpdb->get_col('SHOW TABLES;')) {
		// For every table in the database
		foreach($tables as $table) {
			// If a table query exists for the database table...
			if( array_key_exists(strtolower($table), $cqueries) ) {
				// Clear the field and index arrays
				unset($cfields);
				unset($indices);
				// Get all of the field names in the query from between the parens
				preg_match("|\((.*)\)|ms", $cqueries[strtolower($table)], $match2);
				$qryline = trim($match2[1]);

				// Separate field lines into an array
				$flds = explode("\n", $qryline);

				// For every field line specified in the query
				foreach($flds as $fld) {
					// Extract the field name
					preg_match("|^([^ ]*)|", trim($fld), $fvals);
					$fieldname = trim( $fvals[1], '`' );

					// Verify the found field name
					$validfield = true;
					switch(strtolower($fieldname))
					{
					case '':
					case 'primary':
					case 'index':
					case 'fulltext':
					case 'unique':
					case 'key':
						$validfield = false;
						$indices[] = trim(trim($fld), ", \n");
						break;
					}
					$fld = trim($fld);

					// If it's a valid field, add it to the field array
					if($validfield) {
						$cfields[strtolower($fieldname)] = trim($fld, ", \n");
					}
				}

				// Fetch the table column structure from the database
				$tablefields = $wpdb->get_results("DESCRIBE {$table};");

				// For every field in the table
				foreach($tablefields as $tablefield) {
					// If the table field exists in the field array...
					if(array_key_exists(strtolower($tablefield->Field), $cfields)) {
						// Get the field type from the query
						preg_match("|".$tablefield->Field." ([^ ]*( unsigned)?)|i", $cfields[strtolower($tablefield->Field)], $matches);
						$fieldtype = $matches[1];

						// Is actual field type different from the field type in query?
						if($tablefield->Type != $fieldtype) {
							// Add a query to change the column type
							$cqueries[] = "ALTER TABLE {$table} CHANGE COLUMN {$tablefield->Field} " . $cfields[strtolower($tablefield->Field)];
							$for_update[$table.'.'.$tablefield->Field] = "Changed type of {$table}.{$tablefield->Field} from {$tablefield->Type} to {$fieldtype}";
						}

						// Get the default value from the array
							//echo "{$cfields[strtolower($tablefield->Field)]}<br>";
						if(preg_match("| DEFAULT '(.*)'|i", $cfields[strtolower($tablefield->Field)], $matches)) {
							$default_value = $matches[1];
							if($tablefield->Default != $default_value)
							{
								// Add a query to change the column's default value
								$cqueries[] = "ALTER TABLE {$table} ALTER COLUMN {$tablefield->Field} SET DEFAULT '{$default_value}'";
								$for_update[$table.'.'.$tablefield->Field] = "Changed default value of {$table}.{$tablefield->Field} from {$tablefield->Default} to {$default_value}";
							}
						}

						// Remove the field from the array (so it's not added)
						unset($cfields[strtolower($tablefield->Field)]);
					}
					else {
						// This field exists in the table, but not in the creation queries?
					}
				}

				// For every remaining field specified for the table
				foreach($cfields as $fieldname => $fielddef) {
					// Push a query line into $cqueries that adds the field to that table
					$cqueries[] = "ALTER TABLE {$table} ADD COLUMN $fielddef";
					$for_update[$table.'.'.$fieldname] = 'Added column '.$table.'.'.$fieldname;
				}

				// Index stuff goes here
				// Fetch the table index structure from the database
				$tableindices = $wpdb->get_results("SHOW INDEX FROM {$table};");

				if($tableindices) {
					// Clear the index array
					unset($index_ary);

					// For every index in the table
					foreach($tableindices as $tableindex) {
						// Add the index to the index data array
						$keyname = $tableindex->Key_name;
						$index_ary[$keyname]['columns'][] = array('fieldname' => $tableindex->Column_name, 'subpart' => $tableindex->Sub_part);
						$index_ary[$keyname]['unique'] = ($tableindex->Non_unique == 0)?true:false;
					}

					// For each actual index in the index array
					foreach($index_ary as $index_name => $index_data) {
						// Build a create string to compare to the query
						$index_string = '';
						if($index_name == 'PRIMARY') {
							$index_string .= 'PRIMARY ';
						}
						else if($index_data['unique']) {
							$index_string .= 'UNIQUE ';
						}
						$index_string .= 'KEY ';
						if($index_name != 'PRIMARY') {
							$index_string .= $index_name;
						}
						$index_columns = '';
						// For each column in the index
						foreach($index_data['columns'] as $column_data) {
							if($index_columns != '') $index_columns .= ',';
							// Add the field to the column list string
							$index_columns .= $column_data['fieldname'];
							if($column_data['subpart'] != '') {
								$index_columns .= '('.$column_data['subpart'].')';
							}
						}
						// Add the column list to the index create string
						$index_string .= ' ('.$index_columns.')';
						if(!(($aindex = array_search($index_string, $indices)) === false)) {
							unset($indices[$aindex]);
						}
					}
				}

				// For every remaining index specified for the table
				foreach ( (array) $indices as $index ) {
					// Push a query line into $cqueries that adds the index to that table
					$cqueries[] = "ALTER TABLE {$table} ADD $index";
					$for_update[$table.'.'.$fieldname] = 'Added index '.$table.' '.$index;
				}

				// Remove the original table creation query from processing
				unset($cqueries[strtolower($table)]);
				unset($for_update[strtolower($table)]);
			} else {
				// This table exists in the database, but not in the creation queries?
			}
		}
	}

	$allqueries = array_merge($cqueries, $iqueries);
	if($execute) {
		foreach($allqueries as $query) {
			$wpdb->query($query);
		}
	}

	return $for_update;
}
?>