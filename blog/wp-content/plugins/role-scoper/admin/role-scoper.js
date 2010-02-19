addLoadEvent(function() {
	//on page load, highlight the UI if scoped roles affect to the item being edited
	//scoper_in_the_house('rs-scoping_alert');
	maybe_hide_quickedit('rs_hide_quickedit');
});

function maybe_hide_quickedit(flag_id) {
	var got_flag = document.getElementById(flag_id);
	if ( got_flag )
		agp_setcss('.editinline', 'display', 'none');
}

/* note: supports some operations no longer used by role scoper admin.  TODO: reduce to required functionality */
function scoper_checkroles(togglebox, allterms_ser, rolenums_ser) {
	allterms_ser += '-Z';
	var term_ids = allterms_ser.split('-');
	var role_nums = rolenums_ser.split('-');
	var val = document.getElementById(togglebox).checked;
	var term_count = term_ids.length;
	var role_count = role_nums.length;
	
	var i, j, baseid, term_id, cbox;
	
	for ( j = 0; j < term_count; j++ ) {
		term_id = term_ids[j];
		for ( i = 0; i < role_count; i++ ) {
			cbox = document.getElementById('rs-' + role_nums[i] + '-' + term_id);
		  	if (cbox) {
	      		cbox.checked = val;
			}
	  	}
	}
}

function rs_display_version_more() {
	agp_set_display('rs_version_more', 'block');
}