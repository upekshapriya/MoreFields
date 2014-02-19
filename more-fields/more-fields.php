<?php
/*
Plugin Name: More Fields
Version: 1000.5.4
Author URI: http://labs.dagensskiva.com/
Plugin URI: http://labs.dagensskiva.com/plugins/more-fields/
Description:  Adds any number of extra fields, in any number of additional boxes in the admin.
Author: Henrik Melin, Kal Ström mod by Upekshapriya according to Forum post http://labs.dagensskiva.com/forum/topic/more-fields-14b3-bug-report-lots-of-js-conflict-still#post-1047 plus make compatible with WP 3.5.1 and Featured Images

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

add_action('admin_init', 'more_fields_prepare_for_20');

function more_fields_prepare_for_20 () {
	global $more_fields;
	if (is_object($more_fields)) return false;

	$old = get_option('more_fields_boxes', array());
	$oldmt = get_option('more_fields_pages', array());
	
	$post_types = array();
	foreach ((array) $oldmt as $pts) $post_types[] = sanitize_title($pts['name']);
	
	$form_link = array(); //'navigation' => 'box', 'keys' => $keys[0], 'action_keys' => $keys, 'action' => 'save');
	$new = array();
	$nbr = 0;
	foreach ((array) $old as $key => $ob) {
		$convert = $_POST[$nbr];
		$checked = ($_POST[$nbr]) ? ' checked="checked"' : '';

		$mf2 = array();
		$key = sanitize_title($ob['name']);
		$mf2['label'] = $ob['name'];
		$mf2['position'] = $ob['position'];
		$mf2['name'] = $key;
		$mf2['post_types'] = $post_types;

		$mf2f = array();
		foreach ((array) $ob['field'] as $f) {
			$fk = sanitize_title($f['title']);
			$mf2f[$fk]['label'] = $f['title'];
			$mf2f[$fk]['key'] = $f['key'];
			$mf2f[$fk]['slug'] = $f['slug'];
			$mf2f[$fk]['values'] = $f['select_values'];
			$mf2f[$fk]['field_type'] = $f['type'];
			$mf2f[$fk]['name'] = $fk;
		}
		$mf2['fields'] = $mf2f;
		$new[$key] = $mf2;
		$nbr++;
	}
	if (!empty($new)) update_option('more_fields', $new);
        
	$supports_c = array();
	$supports_c['authordiv'] = 'author';
	$supports_c['postexcerpt'] = 'excerpt';
// 	$supports_c['trackbacksdiv'] = 'author';
	$supports_c['postcustom'] = 'custom-fields';
	$supports_c['slugdiv'] = 'slug';
	$supports_c['commentstatusdiv'] = 'comments';
	$supports_c['revisionsdiv'] = 'revisions';

	$supports_t['tagsdiv'] = 'tags';
	$supports_t['categorydiv'] = 'category';

	$caps = array('more_edit_type_cap', 'more_edit_cap', 'more_edit_others_cap', 'more_publish_others_cap', 'more_read_cap', 'more_delete_cap');

	$new2 = array();
	$old = get_option('more_fields_pages');
	foreach ((array) $old as $key => $ob) {
		$convert = $_POST[$nbr];
		$checked = ($_POST[$nbr]) ? ' checked="checked"' : '';

		$mf2 = array();
		$key = sanitize_title($ob['name']);
		$mf2['label'] = $ob['plural'];
		$mf2['singular_label'] = $ob['name'];
		
		$mf2['labels']['name'] = $ob['plural'];
		$mf2['labels']['singular_name'] = $ob['name'];
		
		$mf2['template'] = $ob['template'];
		$mf2['capability_type'] = $ob['based_on'];
		$mf2['menu_icon'] = $ob['icon'];
		// $mf2['label'] = $ob['name'];
		$mf2['name'] = $key;
		
		// Defaults
		$mf2['public'] = true;
		$mf2['publicly_queryable'] = true;
		$mf2['exclude_from_search'] = false;
		$mf2['public'] = true;
		$mf2['show_ui'] = true;
		
		$mf2['supports'][] = 'title';
		$mf2['supports'][] = 'editor';
		foreach((array) $ob['visible_boxes'] as $vis) {
			if (array_key_exists($vis, $supports_c))
				$mf2['supports'][] = $supports_c[$vis];
			else if (array_key_exists($vis, $supports_t))
				$mf2['taxonomies'][] = $supports_c[$vis];
			else $mf2['boxes'][] = sanitize_title($vis);
		}
		
		foreach ($caps as $cap) $mf2[$cap] = array();

		$new2[$key] = $mf2;
		$nbr++;
	}
	if (!empty($new2)) update_option('more_types', $new2);

}

?>
