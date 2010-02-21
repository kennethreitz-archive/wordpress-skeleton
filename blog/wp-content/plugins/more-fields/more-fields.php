<?php
/*
Plugin Name: More Fields
Version: 1.4Beta3
Author URI: http://henrikmelin.se/
Plugin URI: http://labs.dagensskiva.com/plugins/more-fields/
Description:  Adds any number of extra fields, in any number of additional boxes in the admin.;
Author: Henrik Melin, Kal Ström

	USAGE:

	See http://labs.dagensskiva.com/plugins/more-fields/

	LICENCE:

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
    
    
*/


// Functions to be used in templates
include('more-fields-template-functions.php');
include('more-fields-object.php');

$mf0 = new more_fields_object;
$mf0->init();
$mf0->init_field_types();

// Load admin components
if (is_admin()) {
	include('more-fields-manage-object.php');
	$mfo = new more_fields_manage;
	$mfo->init($mf0);
}


function mf_add_meta_box($title, $fields, $context = array(), $position = '') {
	global $more_fields_boxes;
	if (!$position) $position = 'left';
	if (!is_array($context)) $context = array($context);
	$on_post = (in_array('post', $context) || !$context) ? true : false;
	$on_page = (in_array('page', $context) || !$context) ? true : false;	
	$box = array('name' => $title, 'on_post' => $on_post, 'on_page' => $on_page, 'position' => $position);
	$box['field'] = $fields;
	$more_fields_boxes[$title]	= $box;
}

function mf_add_post_type($title, $options) {
	global $more_fields_page_types;
//	if (!$position) $position = 'left';
//	if (!is_array($context)) $context = array($context);
//	$on_post = (in_array('post', $context) || !$context) ? true : false;
//	$on_page = (in_array('page', $context) || !$context) ? true : false;	
//	$type = array('name' => $title, 'on_post' => $on_post, 'on_page' => $on_page, 'position' => $position);
//	$box['field'] = $fields;
	$more_fields_page_type[$title] = $options;
}



?>
