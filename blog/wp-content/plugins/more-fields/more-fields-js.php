<?php
      $root = dirname(dirname(dirname(dirname(__FILE__))));
      if (file_exists($root.'/wp-load.php')) {
          // WP 2.6
          require_once($root.'/wp-load.php');
      } else {
          // Before 2.6
          require_once($root.'/wp-config.php');
      }

	header("Content-Type: text/javascript");
 	// These are options associate with each field
	$p2m_fid = array('key_', 'title_','type_', 'select_values_');
	// These are settings associated with each box
	$p2m_oth = array('page_', 'post_', 'name_', 'position_');
?>
	/**
	***  delete_widget (  )
	**/
	function mf_delete_box (wnbr) {
		box = wnbr;
		while (box < mf_nbr_boxes() - 1) {

			// Copy the number of fields & values from the next widget
			var nbr_fields = mf_nbr_fields(box + 1);
			for (var row = 0; row < mf_nbr_fields(box); row++) mf_remove(box, 1);
			while (mf_nbr_fields(box) < nbr_fields) mf_add_field(box);			

			// Copy the values from the widget down from this one.
<?php foreach ($p2m_fid as $field) echo "\t\t\tfor (var row = 0; row < nbr_fields; row++) \$('$field' + box + '_' + row).value = \$('$field' + (box + 1) + '_' + row).value;\n"; ?>
<?php foreach ($p2m_oth as $field) echo "\t\t\tfor (var row = 0; row < nbr_fields; row++) \$('$field' + box).value = \$('$field' + (box + 1)).value;\n"; ?>		
			box++;		
		}
		// Remove the div!
		$('box_div_' + (mf_nbr_boxes() - 1)).remove();
	}	
	/**
	***  add_box (  )
	**/
	function mf_add_box () {

		// Save state
		var values = mf_store_fields();

		// Create and insert a new widget
		form_html = '<?php echo addcslashes(p2m_meta_get_box(7777), "\r\n\"\'"); ?>';
		form_html = form_html.replace(/7777/g, mf_nbr_boxes());
		new Insertion.Bottom('boxes_div', form_html);

		// Restore state
		mf_restore_fields(values);
	}
	/**
	***  add_field (  )
	**/
	function mf_add_field (wnbr) {

		// Save our values
		var values = mf_store_fields();

		// Create and insert another field				
		var form_html = "<?php echo addcslashes(p2m_meta_get_field(8888, 9999), "\n\r\"\'"); ?>";
		form_html = form_html.replace(/8888/g, wnbr);
		form_html = form_html.replace(/9999/g, mf_nbr_fields(wnbr));
		new Insertion.Bottom('box_fields_div_' + wnbr, form_html);

		// Restore the fields
		mf_restore_fields(values);
	}
	/**
	***  nbr_fields (  )
	**/
	function mf_nbr_fields(wnbr) {
		var block = $(('box_fields_div_' + wnbr));
		var fieldsets = block.getElementsByTagName("input");
		return (parseFloat(fieldsets.length)) / <?php echo count($p2m_fid) - 1; ?> ;
	}
	/**
	***  nbr_boxes (  )
	**/
	function mf_nbr_boxes() {
		var block = $('boxes_div');
		var fieldsets = block.getElementsByTagName('hr');
		return (parseFloat(fieldsets.length));
	}
	/**
	***  remove (  )
	**/
	function mf_remove(wnbr, nbr) {
<?php foreach($p2m_fid as $field) echo "\t\t\$(('$field' + wnbr + '_' + nbr)).value = ''; \n"; ?>
		if (mf_nbr_fields(wnbr) > 1) {
			while (nbr < mf_nbr_fields(wnbr) - 1) {
<?php foreach($p2m_fid as $field) echo "\t\t\tmf_swap('$field' + wnbr + '_' + nbr, '$field' + wnbr + '_' + (nbr+1)); \n"; ?>
				mf_swap_select_fields(wnbr + '_' + nbr, wnbr + '_' + (nbr + 1));
				nbr++;
			}
			// Now remove the last div
			$('box_field_div_' + wnbr + '_' + (mf_nbr_fields(wnbr) - 1)).remove();
		}
		return mf_nbr_fields(wnbr);
	}
	/**
	***  store_fields (  )
	**/
	function mf_store_fields () {
		var value = Array();
		var counter = 0;
		for (var box = 0; box < mf_nbr_boxes(); box++) {
<?php foreach($p2m_fid as $field) echo "\t\t\tfor (var i = 0; i < mf_nbr_fields(box); i++) { value['$field' + box + '_' + i] = \$(('$field' + box + '_' + i)).value;  } \n"; ?>
<?php foreach($p2m_oth as $field) echo "\t\t\tvalue['$field' + box] = \$(('$field' + box)).value;  \n"; ?>
		}
		return value;
	}
	/**
	***  restore_fields (  )
	**/
	function mf_restore_fields (value) {
		var counter = 0;
		for (var box = 0; box < mf_nbr_boxes(); box++) {
<?php foreach($p2m_fid as $field) echo "\t\t\tfor (var i = 0; i < mf_nbr_fields(box); i++)  if (value['$field' + box + '_' + i] != undefined) \$(('$field' + box + '_' + i)).value = value['$field' + box + '_' + i]; \n"; ?>
<?php foreach($p2m_oth as $field) echo "\t\t\tif (value['$field' + box] != undefined) \$(('$field' + box)).value = value['$field' + box]; \n"; ?>
		}
		return value;
	}

	/**
	***  move_up (  )
	**/	
	function mf_move_up(wnbr, nbr) {
		if (nbr > 0) {
			var id1 = wnbr + '_' + nbr;
			var id2 = wnbr + '_' + (nbr - 1);
<?php foreach($p2m_fid as $field) echo "\t\t\tmf_swap('$field' + id1, '$field' + id2);\n"; ?>
			mf_swap_select_fields(id1, id2);
		}
	}
	
	/**
	***  swap_select_fields ( )
	**/
	function mf_swap_select_fields (id1, id2) {	
		var vis1 = $('select_values_div_' + id2).style.display;
		$('select_values_div_' + id2).style.display = $('select_values_div_' + id1).style.display;
		$('select_values_div_' + id1).style.display = vis1;	
	}

	/**
	***  move_down (  )
	**/
	function mf_move_down(wnbr, nbr) {
		if (nbr < mf_nbr_fields(wnbr) - 1) {
			var id1 = wnbr + '_' + nbr ;
			var id2 = wnbr + '_' + (nbr + 1);
<?php foreach($p2m_fid as $field) echo "\t\t\tmf_swap(('$field' + id1), ('$field' + id2));\n"; ?>
			mf_swap_select_fields(id1, id2);
	
		}	
	}
	/**
	***  swap (  )
	**/
	function mf_swap(id1, id2) {		
		var var1 = $(id1).value;
		var var2 = $(id2).value;
		$(id1).value = var2;
		$(id2).value = var1;		
	}
	
	function mf_check_for_duplicates(nbr) {
		for (var i=0; i < mf_nbr_boxes(); i++) {
			if ((i != nbr) && ($('name_' + nbr).value == $('name_' + i).value)) {
					alert('This key already exists - the box name must be unique!');
					$('name_' + nbr).focus();
					return 0;
			}
		}
	}	