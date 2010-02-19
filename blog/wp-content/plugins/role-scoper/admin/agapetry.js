function agp_setcss(class_name,set_property,set_display){
	var rule_exists = false;
	
	num_sheets = document.styleSheets.length;
	for (var i = 0; i < num_sheets; i++) {
		sheet = document.styleSheets[i];
		if (sheet.rules) {
			sheet_length = sheet.rules.length;
			for (var r = 0; r < sheet_length; r++) {
				if (sheet.rules[r].selectorText == class_name) {
					if ( sheet.rules[r].style[set_property] ) {
						if ( sheet.rules[r].style[set_property] != set_display )
							sheet.rules[r].style[set_property] = set_display;
						rule_exists = true;
					}
				}
			}
		}
		else if (sheet.cssRules) {
			sheet_length = sheet.cssRules.length;
			for (var r = 0; r < sheet_length; r++) {
				if (sheet.cssRules[r].selectorText == class_name) {
					if ( sheet.cssRules[r].style[set_property] ) {
						if ( sheet.cssRules[r].style[set_property] != set_display )
							sheet.cssRules[r].style[set_property] = set_display;
						rule_exists = true;
					}
				}
			}
		}
		
		/* Rule insertion code by Shawn Olson
		http://www.shawnolson.net/a/503/altering-css-class-attributes-with-javascript.html */
		if(! rule_exists){if(sheet.insertRule){sheet.insertRule(class_name+" { "+set_property+": "+set_display+"; }",sheet_length)}else{if(sheet.addRule){sheet.addRule(class_name,set_property+": "+set_display+";")}}}
	}
	
	return null;
}



/* addLoadEvent function by Simon Willison
http://www.webreference.com/programming/javascript/onloads/ */
function agp_addLoadEvent(func) {
  var oldonload = window.onload;
  if (typeof window.onload != 'function') {
    window.onload = func;
  } else {
    window.onload = function() {
      if (oldonload) {
        oldonload();
      }
      func();
    }
  }
}

addLoadEvent(function() {
	//on page load, hide any elements which were marked with a special class name  
	agp_display_marked_elements('div', 'agp_js_hide', 'none');
	agp_display_marked_elements('div', 'agp_js_show', 'block');
	agp_display_marked_elements('ul', 'agp_js_hide', 'none');
	agp_display_marked_elements('ul', 'agp_js_show', 'block');
	agp_display_marked_elements('li', 'agp_js_hide', 'none');
	agp_display_marked_elements('li', 'agp_js_show', 'block');
});

function agp_display_marked_elements(tag_name, class_name, display_mode) {
	var elems = document.getElementsByTagName(tag_name);

	if ( ! elems)
		return;
	
	for (var i=0; i<elems.length; i++) {
		if ( elems[i].className.indexOf(class_name) !== -1 )
			elems[i].style.display = display_mode;
	}
}

function agp_set_marked_elem_property(tag_name, class_name, set_prop, set_val) {
	var elems = document.getElementsByTagName(tag_name);

	if ( ! elems)
		return;
	
	for (var i=0; i<elems.length; i++) {
		if ( elems[i].className.indexOf(class_name) !== -1 )
			elems[i][set_prop] = set_val;
	}
}

function agp_display_if(display_id, selection_id, display) {
	var foo = document.getElementById(selection_id);
	if (foo) {
		var fie = document.getElementById(display_id);
		if (fie) {
			if ( ! display )
				display = 'block';
			
			if ( foo.checked ) {
				fie.style.display = display;
			} else {
				fie.style.display = "none";
			}
		}
	}
}

function agp_set_display(item_id, set_display, hide_id) {
	var foo = document.getElementById(item_id);
	if (foo)
		foo.style.display = set_display;
		
	if (hide_id) {
		var foo = document.getElementById(hide_id);
		if (foo)
			foo.style.display = 'none';
	}
}

function agp_swap_display(show_id, hide_id, clicked_link_id, other_link_id, class_selected, class_unselected) {
	var show_elem = document.getElementById(show_id);
	if (show_elem) {
		show_elem.style.display = "block";
	}
	
	var hide_elem = document.getElementById(hide_id);
	if (hide_elem) {
		hide_elem.style.display = "none";
	}
	
	var clicked_link = document.getElementById(clicked_link_id);
	if ( clicked_link && class_selected ) {
		clicked_link.parentNode.setAttribute("class", class_selected);
		clicked_link.parentNode.setAttribute("className", class_selected);
	}
	
	var other_link = document.getElementById(other_link_id);
	if ( other_link && class_unselected ) {
		other_link.parentNode.setAttribute("class", class_unselected);
		other_link.parentNode.setAttribute("className", class_unselected);
	}
}

function agp_toggle_display(topic_id, display_mode, switch_id, hide_caption, show_caption) {
	var topic = document.getElementById(topic_id);
	
	if (!topic) return;
	
	if (switch_id)
		var switch_elem = document.getElementById(switch_id);

	if (topic.style.display == "none") {
		topic.style.display = display_mode;
		if (hide_caption && switch_elem)
			switch_elem.innerHTML = hide_caption;
	} else {
		topic.style.display = "none";
		if (show_caption && switch_elem)
			switch_elem.innerHTML = show_caption;
	}
}

function agp_filter_ul(list_id, filter_entry, checkbox_id, links_id) {
    var listobj = document.getElementById(list_id);
    
    if (!listobj) return;
    if (!listobj.childNodes) return;

    if ( filter_entry ) {
    	filter_entry = ' ' + filter_entry.toLowerCase();
	}
    
	for ( var i in listobj.childNodes ) {
		 if ( listobj.childNodes[i].title ) {
		      if ( listobj.childNodes[i].title.indexOf(filter_entry) >= 0 ) {
		      	listobj.childNodes[i].style.display = "block";
			  } else {
				listobj.childNodes[i].style.display = "none";
		      }
	 	}
    }
 
    if ( listobj.parentNode.parentNode.style.display == "none" )
	    listobj.parentNode.parentNode.style.display = "block";
    
    var checkboxobj = document.getElementById(checkbox_id);
    if ( checkboxobj )
    	checkboxobj.checked = 'checked';
	
    if ( links_id ) {
	    var links_obj = document.getElementById(links_id);
	    if ( links_obj )
	    	links_obj.style.display = "block";
	}
}

function agp_display_child_nodes( parent_id, child_tag_name, display ) {
	var parent_obj = document.getElementById(parent_id);
    
    for ( var i in parent_obj.childNodes ) {
	    if ( parent_obj.childNodes[i].tagName == child_tag_name )
			parent_obj.childNodes[i].style.display = display;
    }
}

/* note: default config if no num_parent_nodes arg: checkbox contained in label, li, and ul 
*/
function agp_check_by_name(elem_name, check_it, visibility_check, click_event, required_container_id, num_parent_nodes) {
	var elems = document.getElementsByTagName('input');
	
	if ( ! elems )
		return;
	
	if ( ! num_parent_nodes )
		var num_parent_nodes = 3;
		
	var checkbox_val = ( check_it ) ? 'checked' : '';

	var parent_node;
	var first_pass = true;
	
	for (var i=0; i< elems.length; i++) {
		if ( (elems[i].type == "checkbox") && (elems[i].name == elem_name) ) {
			parent_node = elems[i].parentNode;
			
			if ( num_parent_nodes > 1 ) {
				for ( var j = 1; i < num_parent_nodes; j++ ) {
					if ( ! parent_node.parentNode )
						break;
					
					parent_node = parent_node.parentNode;
				}
			}
		
			if ( required_container_id ) {
				if ( parent_node.parentNode.id != required_container_id )
					continue;
			}
			
			if ( visibility_check ) {
				 if ( parent_node.style.display == "none" )
					continue;
			}
			
			if ( first_pass && visibility_check ) {
				first_pass = false;
				
				if ( parent_node.parentNode.style.display == "none" || parent_node.parentNode.parentNode.style.display == "none" || parent_node.parentNode.parentNode.parentNode.style.display == "none" )
					break;
			}
			
			if ( checkbox_val && click_event ) {
				elems[i].checked = '';
				elems[i].click();
			} else {
				elems[i].checked = checkbox_val;
			}
		}
	}
}

function agp_check_it(foo, check_it) {
	if ( '' == check_it )
		check_it = true;
	
	var checkbox_val = ( check_it ) ? 'checked' : '';
	
	var fie = document.getElementById(foo);
	if (fie)
		fie.checked = 'checked';
}

function agp_append(foo, val) {
	var fie = document.getElementById(foo);
	if (fie)
		fie.value = fie.value + val;
}

function agp_uncheck(all_ids_str, keep_checked_id, skip_if_id, skip_if_val) {
	if ( ! skip_if_id )
		skip_if_id = 'assign_for';
		
	if ( ! skip_if_val )
		skip_if_val = '';
		
	var skipper_obj = document.getElementById(skip_if_id);
	
	if ( skipper_obj && (skipper_obj.value == skip_if_val) )
		return;
	
	var all_ids = all_ids_str.split(',');
	var obj;
	
	for ( var i = 0; i < all_ids.length; i++ ) {
		if ( all_ids[i] != keep_checked_id ) {
			obj = document.getElementById(all_ids[i]);
			if ( obj )
				obj.checked = '';
		}
	}
}

function rs_uncheck_others(all_ids_str, keep_checked_id, skip_if_id, skip_if_val) {
	agp_uncheck(all_ids_str, keep_checked_id, skip_if_id, skip_if_val);
}