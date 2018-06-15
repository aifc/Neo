<?php
/*
Plugin Name: BP Profile Search
Plugin URI: http://www.dontdream.it/bp-profile-search/
Description: Search your BuddyPress Members Directory.
Version: 4.7.9
Author: Andrea Tarantini
Author URI: http://www.dontdream.it/
Text Domain: bp-profile-search
*/

define ('BPS_VERSION', '4.7.9');
define ('BPS_FORM', 'bp_profile_search');

include 'bps-admin.php';
include 'bps-directory.php';
include 'bps-fields.php';
include 'bps-form.php';
include 'bps-help.php';
include 'bps-search.php';
include 'bps-templates47.php';
include 'bps-templates48.php';
include 'bps-widget.php';
include 'bps-xprofile.php';

add_action ('bp_include', 'bps_buddypress');
function bps_buddypress ()
{
	if (bp_is_active ('xprofile'))
	{
//		include ...
	}
}

$addons = array ('bps-custom.php');
foreach ($addons as $addon)
{
	$file = WP_PLUGIN_DIR. '/bp-profile-search-addons/'. $addon;
	if (file_exists ($file))  include $file;
}

add_action ('plugins_loaded', 'bps_translate');
function bps_translate ()
{
	load_plugin_textdomain ('bp-profile-search');
}

add_filter ('bp_get_template_stack', 'bps_template_stack', 20);
function bps_template_stack ($stack)
{
	$stack[] = dirname (__FILE__). '/templates';
	return $stack;
}

function bps_templates ()
{
	$templates = array ('members/bps-form-legacy', 'members/bps-form-sample-1', 'members/bps-form-sample-2');
	return apply_filters ('bps_templates', $templates);
}

function bps_default_template ()
{
	$templates = bps_templates ();
	return $templates[0];
}

add_action ('init', 'bps_upgrade');
function bps_upgrade ()
{
	$db_version = 471;

	$settings = get_option ('bps_settings');
	if ($settings === false)
	{
		$settings = new stdClass;
		$settings->db_version = 0;
	}

	$installed = $settings->db_version;
	if ($installed >= $db_version)  return false;

	$settings->db_version = $db_version;
	update_option ('bps_settings', $settings);

	$posts = get_posts (array ('post_type' => 'bps_form', 'nopaging' => true));
	foreach ($posts as $post)
	{
		$form = $post->ID;
		$meta = bps_meta ($form);

		if ($installed < 471)
		{
			if (!isset ($meta['action']))
				$meta['action'] = 0;

			if (!isset ($meta['template']))
				$meta['template'] = bps_default_template ();

			if (!isset ($meta['field_code']))
				foreach ($meta['field_name'] as $k => $id)
					$meta['field_code'][$k] = 'field_'. $id;

			if (!isset ($meta['field_mode']))
				foreach ($meta['field_range'] as $k => $range)
					$meta['field_mode'][$k] = isset ($range)? 'range': '';
		}

//		if ($installed < xyz)
//		{
//			...
//		}

		update_post_meta ($form, 'bps_options', $meta);
	}
}

add_filter ('plugin_action_links', 'bps_row_meta', 10, 2);
function bps_row_meta ($links, $file)
{
	if ($file == plugin_basename (__FILE__))
	{
		$settings_link = '<a href="'. admin_url ('edit.php?post_type=bps_form'). '">'. __('Settings'). '</a>';
		array_unshift ($links, $settings_link);
	}
	return $links;
}

function bps_meta ($form)
{
	static $options;
	if (isset ($options[$form]))  return $options[$form];

	$default = array ();
	$default['field_code'] = array ();
	$default['field_label'] = array ();
	$default['field_desc'] = array ();
	$default['field_mode'] = array ();
	$default['directory'] = 'No';
	$default['template'] = bps_default_template ();
	$default['header'] = __('<h4>Advanced Search</h4>', 'bp-profile-search');
	$default['toggle'] = 'Enabled';
	$default['button'] = __('Hide/Show Form', 'bp-profile-search');
	$default['method'] = 'POST';
	$default['action'] = 0;

	$meta = get_post_meta ($form);
	$options[$form] = isset ($meta['bps_options'])? unserialize ($meta['bps_options'][0]): $default;

	return $options[$form];
}

add_action ('init', 'bps_post_type');
function bps_post_type ()
{
	$args = array
	(
		'labels' => array
		(
			'name' => __('Profile Search Forms', 'bp-profile-search'),
			'singular_name' => __('Profile Search Form', 'bp-profile-search'),
			'all_items' => __('Profile Search', 'bp-profile-search'),
			'add_new' => __('Add New', 'bp-profile-search'),
			'add_new_item' => __('Add New Form', 'bp-profile-search'),
			'edit_item' => __('Edit Form', 'bp-profile-search'),
			'not_found' => __('No forms found.', 'bp-profile-search'),
			'not_found_in_trash' => __('No forms found in Trash.', 'bp-profile-search'),
		),
		'show_ui' => true,
		'show_in_menu' => 'users.php',
		'supports' => array ('title'),
		'rewrite' => false,
		'map_meta_cap' => true,
		'capability_type' => 'bps_form',
		'query_var' => false,
	);

	register_post_type ('bps_form', $args);

	$form_caps = array (
		'administrator' => array (
			'delete_bps_forms',
			'delete_others_bps_forms',
			'delete_published_bps_forms',
			'edit_bps_forms',
			'edit_others_bps_forms',
			'edit_published_bps_forms',
			'publish_bps_forms',
		)
	);

	$form_caps = apply_filters ('bps_form_caps', $form_caps);
	foreach ($form_caps as $key => $caps)
	{
		$role = get_role ($key);
		foreach ($caps as $cap)
			if (! $role->has_cap ($cap))  $role->add_cap ($cap);
	}
}

/******* edit.php */

add_filter ('manage_bps_form_posts_columns', 'bps_add_columns');
// file class-wp-posts-list-table.php
function bps_add_columns ($columns)
{
	return array
	(
		'cb' => '<input type="checkbox" />',
		'title' => __('Form', 'bp-profile-search'),
		'fields' => __('Fields', 'bp-profile-search'),
		'action' => __('Directory', 'bp-profile-search'),
		'directory' => __('Add to Directory', 'bp-profile-search'),
		'widget' => __('Widget', 'bp-profile-search'),
		'shortcode' => __('Shortcode', 'bp-profile-search'),
	);
}

add_action ('manage_posts_custom_column', 'bps_columns', 10, 2);
// file class-wp-posts-list-table.php line 675
function bps_columns ($column, $post_id)
{
	if (!bps_screen ())  return;

	$options = bps_meta ($post_id);
	if ($column == 'fields')  echo count ($options['field_code']);
	else if ($column == 'action')
	{
		$dirs = bps_directories ();
		echo isset ($dirs[$options['action']])? $dirs[$options['action']]->label:
			'<strong style="color:red;">'. __('undefined', 'bp-profile-search'). '</strong>';
	}
	else if ($column == 'directory')  _e($options['directory'], 'bp-profile-search');
	else if ($column == 'widget')  echo _bps_get_widget ($post_id);
	else if ($column == 'shortcode')  echo "[bps_display form=$post_id]";
}

function _bps_get_widget ($form)
{
	$widgets = get_option ('widget_bps_widget');
	if ($widgets == false)  return __('unused', 'bp-profile-search');

	$titles = array ();
	foreach ($widgets as $key => $widget)
		if (isset ($widget['form']) && $widget['form'] == $form)  $titles[] = !empty ($widget['title'])? $widget['title']: __('(no title)');
		
	return count ($titles)? implode ('<br/>', $titles): __('unused', 'bp-profile-search');
}

add_filter ('bulk_actions-edit-bps_form', 'bps_bulk_actions');
// file class-wp-list-table.php
function bps_bulk_actions ($actions)
{
	$actions = array ();
	$actions['trash'] = __('Move to Trash');
	$actions['untrash'] = __('Restore');
	$actions['delete'] = __('Delete Permanently');

	return $actions;
}

add_filter ('post_row_actions', 'bps_row_actions', 10, 2);
// file class-wp-posts-list-table.php
function bps_row_actions ($actions, $post)
{
	if (!bps_screen ())  return $actions;

	unset ($actions['inline hide-if-no-js']);
	return $actions;
}

add_filter ('manage_edit-bps_form_sortable_columns', 'bps_sortable');
// file class-wp-list-table.php
function bps_sortable ($columns)
{
	return array ('title' => 'title');
}

add_filter ('request', 'bps_orderby');
function bps_orderby ($vars)
{
	if (!bps_screen ())  return $vars;
	if (isset ($vars['orderby']))  return $vars;
	
	$vars['orderby'] = 'ID';
	$vars['order'] = 'ASC';
	return $vars;
}

/******* post.php, post-new.php */

add_filter ('post_updated_messages', 'bps_updated_messages');
function bps_updated_messages ($messages)
{
	$messages['bps_form'] = array
	(
		 0 => 'message 0',
		 1 => __('Form updated.', 'bp-profile-search'),
		 2 => 'message 2',
		 3 => 'message 3',
		 4 => 'message 4',
		 5 => 'message 5',
		 6 => __('Form created.', 'bp-profile-search'),
		 7 => 'message 7',
		 8 => 'message 8',
		 9 => 'message 9',
		10 => 'message 10',
	);
	return $messages;
}

add_filter ('bulk_post_updated_messages', 'bps_bulk_updated_messages', 10, 2);
function bps_bulk_updated_messages ($bulk_messages, $bulk_counts)
{
	$bulk_messages['bps_form'] = array
	(
		'updated'   => 'updated',
		'locked'    => 'locked',
		'deleted'   => _n('%s form permanently deleted.', '%s forms permanently deleted.', $bulk_counts['deleted'], 'bp-profile-search'),
		'trashed'   => _n('%s form moved to the Trash.', '%s forms moved to the Trash.', $bulk_counts['trashed'], 'bp-profile-search'),
		'untrashed' => _n('%s form restored from the Trash.', '%s forms restored from the Trash.', $bulk_counts['untrashed'], 'bp-profile-search'),
	);
	return $bulk_messages;
}

/******* common */

function bps_screen ()
{
	global $current_screen;
	return isset ($current_screen->post_type) && $current_screen->post_type == 'bps_form';
}

add_action ('admin_head', 'bps_admin_head');
function bps_admin_head ()
{
	global $current_screen;
	if (!bps_screen ())  return;

	bps_help ();
	if ($current_screen->id == 'bps_form')  _bps_admin_js ();
?>
	<style type="text/css">
		.search-box, .actions, .view-switch {display: none;}
		.bulkactions {display: block;}
		#minor-publishing {display: none;}
		.fixed .column-fields {width: 8%;}
		.fixed .column-action {width: 14%;}
		.fixed .column-directory {width: 16%;}
		.fixed .column-widget {width: 14%;}
		.fixed .column-shortcode {width: 18%;}
		.bps_col1 {display: inline-block; width: 2%; cursor: move;}
		.bps_col2 {display: inline-block; width: 20%;}
		.bps_col3 {display: inline-block; width: 16%;}
		.bps_col4 {display: inline-block; width: 32%;}
		.bps_col5 {display: inline-block; width: 16%;}
		a.delete {color: #aa0000;}
		a.delete:hover {color: #ff0000;}
	</style>
<?php
}

function _bps_admin_js ()
{
	$translations = array (
		'drag' => __('drag to reorder fields', 'bp-profile-search'),
		'field' => __('select field', 'bp-profile-search'),
		'remove' => __('Remove', 'bp-profile-search'),
	);
	wp_enqueue_script ('bps-admin', plugins_url ('bps-admin.js', __FILE__), array ('jquery-ui-sortable'), BPS_VERSION);
	wp_localize_script ('bps-admin', 'bps_strings', $translations);
}
