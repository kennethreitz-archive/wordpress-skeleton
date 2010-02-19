addLoadEvent(function() {
	//on page load...
	scoper_rig_role_checkboxes();
});

function scoper_rig_role_checkboxes() {
	var elems = document.getElementsByTagName('input');
	
	var role_for_object_regex = /^r[0-9]+[g,u][0-9]+/
	var role_for_child_regex = /^p_r[0-9]+[g,u][0-9]+/

	for (var i=0; i<elems.length; i++) {
		if ( elems[i].type != 'checkbox' )
			continue;
		
		if ( ! elems[i].id )
			continue;
		
		if ( role_for_object_title ) {
			if ( elems[i].id.match(role_for_object_regex) ) {
				elems[i].title = role_for_object_title;
				
				continue;
			}
		}
		
		if ( elems[i].id.match(role_for_child_regex) ) {
			if ( role_for_children_title )
				elems[i].title = role_for_children_title;
		}
	}
}