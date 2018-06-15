<?php

add_filter ('bps_add_fields', 'bps_xprofile_setup');
function bps_xprofile_setup ($fields)
{
	global $group, $field;

	if (!function_exists ('bp_has_profile'))
	{
		printf ('<p class="bps_error">'. __('%s: The BuddyPress Extended Profiles component is not active.', 'bp-profile-search'). '</p>',
			'<strong>BP Profile Search '. BPS_VERSION. '</strong>');
		return $fields;
	}

	$args = array ('hide_empty_fields' => false, 'member_type' => bp_get_member_types ());
	if (bp_has_profile ($args))
	{
		while (bp_profile_groups ())
		{
			bp_the_profile_group ();
			$group_name = str_replace ('&amp;', '&', stripslashes ($group->name));

			while (bp_profile_fields ())
			{
				bp_the_profile_field ();
				$f = new stdClass;

				$f->group = $group_name;
				$f->id = $field->id;
				$f->code = 'field_'. $field->id;
				$f->name = str_replace ('&amp;', '&', stripslashes ($field->name));
				$f->name = bps_wpml (0, $f->id, 'name', $f->name);
				$f->description = str_replace ('&amp;', '&', stripslashes ($field->description));
				$f->description = bps_wpml (0, $f->id, 'description', $f->description);
				$f->type = $field->type;
				$f->format = bps_xprofile_format ($field->type, $field->id);
				$f->search = 'bps_xprofile_search';
				if ($f->format != 'set')
				{
					$f->sort_directory = 'bps_xprofile_sort_directory';
					$f->get_value = 'bps_xprofile_get_value';
				}
				$f->options = bps_xprofile_options ($field->id);
				foreach ($f->options as $key => $label)
					$f->options[$key] = bps_wpml (0, $f->id, 'option', $label);

				if ($f->format == 'custom')
					do_action ('bps_custom_field', $f);

				list ($f->filters, $f->display) = bps_xprofile_filters ($f->format, count ($f->options), $f);
				$fields[] = $f;
			}
		}
	}

	return $fields;
}

function bps_xprofile_search ($f)
{
	global $bp, $wpdb;

	$value = $f->value;
	$filter = bps_filterXquery ($f);

	$sql = array ('select' => '', 'where' => array ());

	$sql['select'] = "SELECT user_id FROM {$bp->profile->table_name_data}";
	$sql['where']['field_id'] = $wpdb->prepare ("field_id = %d", $f->id);

	switch ($filter)
	{
	case 'range':
		$min = isset ($value['min'])? $value['min']: '';
		$max = isset ($value['max'])? $value['max']: '';

		if ($min !== '')  $sql['where']['min'] = $wpdb->prepare ("value >= %f", $min);
		if ($max !== '')  $sql['where']['max'] = $wpdb->prepare ("value <= %f", $max);
		break;

	case 'age_range':
		$min = isset ($value['min'])? $value['min']: '';
		$max = isset ($value['max'])? $value['max']: '';
		$time = time ();
		$day = date ("j", $time);
		$month = date ("n", $time);
		$year = date ("Y", $time);
		$ymin = $year - $max - 1;
		$ymax = $year - $min;

		if ($max !== '')  $sql['where']['age_min'] = $wpdb->prepare ("DATE(value) > %s", "$ymin-$month-$day");
		if ($min !== '')  $sql['where']['age_max'] = $wpdb->prepare ("DATE(value) <= %s", "$ymax-$month-$day");
		break;

	case 'contains':
		$value = str_replace ('&', '&amp;', $value);
		$escaped = '%'. bps_esc_like ($value). '%';
		$sql['where'][$filter] = $wpdb->prepare ("value LIKE %s", $escaped);
		break;

	case 'like':
		$value = str_replace ('&', '&amp;', $value);
		$value = str_replace ('\\\\%', '\\%', $value);
		$value = str_replace ('\\\\_', '\\_', $value);
		$sql['where'][$filter] = $wpdb->prepare ("value LIKE %s", $value);
		break;

	case '':
		$value = str_replace ('&', '&amp;', $value);
		$sql['where'][$filter] = $wpdb->prepare ("value = %s", $value);
		break;

	case 'num':
		$sql['where'][$filter] = $wpdb->prepare ("value = %f", $value);
		break;

	case 'is_in':
		$values = (array)$value;
		$parts = array ();
		foreach ($values as $value)
		{
			$value = str_replace ('&', '&amp;', $value);
			$parts[] = $wpdb->prepare ("value = %s", $value);
		}
		$sql['where'][$filter] = '('. implode (' OR ', $parts). ')';
		break;

	case 'match_any':
	case 'match_all':
		$values = (array)$value;
		$parts = array ();
		foreach ($values as $value)
		{
			$value = str_replace ('&', '&amp;', $value);
			$escaped = '%:"'. bps_esc_like ($value). '";%';
			$parts[] = $wpdb->prepare ("value LIKE %s", $escaped);
		}
		$match = ($filter == 'match_any')? ' OR ': ' AND ';
		$sql['where'][$filter] = '('. implode ($match, $parts). ')';
		break;

	default:
		return array ();
	}

	$sql = apply_filters ('bps_field_sql', $sql, $f);
	$query = $sql['select']. ' WHERE '. implode (' AND ', $sql['where']);

	$results = $wpdb->get_col ($query);
	return $results;
}

function bps_xprofile_sort_directory ($sql, $object, $f, $order)
{
	global $bp, $wpdb;

	$object->uid_name = 'user_id';
	$object->uid_table = $bp->profile->table_name_data;

	$sql['select'] = "SELECT u.user_id AS id FROM {$object->uid_table} u";
	$sql['where'] = str_replace ('u.ID', 'u.user_id', $sql['where']);
	$sql['where'][] = "u.user_id IN (SELECT ID FROM {$wpdb->users} WHERE user_status = 0)";
	$sql['where'][] = $wpdb->prepare ("u.field_id = %d", $f->id);
	$sql['orderby'] = "ORDER BY u.value";
	$sql['order'] = $order;

	return $sql;
}

function bps_xprofile_get_value ($f)
{
	global $members_template;

	if ($members_template->current_member == 0)
	{
		$users = wp_list_pluck ($members_template->members, 'ID');
		BP_XProfile_ProfileData::get_value_byid ($f->id, $users);
	}

	return BP_XProfile_ProfileData::get_value_byid ($f->id, $members_template->member->ID);
}

function bps_xprofile_format ($type, $field_id)
{
	$formats = array
	(
		'textbox'			=> array ('text', 'decimal'),
		'number'			=> array ('integer'),
		'url'				=> array ('text'),
		'textarea'			=> array ('text'),
		'selectbox'			=> array ('text', 'integer', 'decimal', 'date'),
		'radio'				=> array ('text', 'integer', 'decimal', 'date'),
		'multiselectbox'	=> array ('set'),
		'checkbox'			=> array ('set'),
		'datebox'			=> array ('date'),
	);

	if (!isset ($formats[$type]))  return 'custom';

	$formats = $formats[$type];
	$default = $formats[0];
	$format = apply_filters ('bps_xprofile_format', $default, $field_id);

	return in_array ($format, $formats)? $format: $default;
}

function bps_xprofile_options ($field_id)
{
	$field = new BP_XProfile_Field ($field_id);
	if (empty ($field->id))  return array ();

	$options = array ();
	$rows = $field->get_children ();
	if (is_array ($rows))
		foreach ($rows as $row)
			$options[stripslashes (trim ($row->name))] = stripslashes (trim ($row->name));

	return $options;
}

function bps_xprofile_filters ($format, $enum, $f)
{
	$filters = array ();

	$selector = $format. ($enum? '/e': '');
	switch ($selector)
	{
	case 'text':
		$filters['contains']	= __('contains', 'bp-profile-search');
		$filters['']			= __('is', 'bp-profile-search');
		$filters['like']		= __('is like', 'bp-profile-search');
		break;

	case 'integer':
	case 'decimal':
		$filters['']			= __('is', 'bp-profile-search');
		$filters['range']		= __('range', 'bp-profile-search');
		break;

	case 'date':
//		$filters['']			= __('is', 'bp-profile-search');
//		$filters['range']		= __('range', 'bp-profile-search');
		$filters['age_range']	= __('age range', 'bp-profile-search');
		break;

	case 'location':
		$filters['distance']	= __('distance', 'bp-profile-search');
		$filters['contains']	= __('contains', 'bp-profile-search');
		$filters['']			= __('is', 'bp-profile-search');
		$filters['like']		= __('is like', 'bp-profile-search');
		break;

	case 'set/e':
		$filters['match_any']	= __('match any', 'bp-profile-search');
		$filters['match_all']	= __('match all', 'bp-profile-search');
		break;

	case 'text/e':
	case 'integer/e':
	case 'decimal/e':
	case 'date/e':
		$filters['']			= __('is', 'bp-profile-search');
		$filters['one_of']		= __('is one of', 'bp-profile-search');
		break;

	case 'custom':
	case 'custom/e':
		$filters = bps_cft_filters ($f->type, $f);
		return array ($filters, array ());

	default:
		return array (array (), array ());
	}

	$display = bps_xprofile_display ($format, $enum);
	return array ($filters, $display);
}

function bps_cft_filters ($type, $f)
{
	$filters = array
	(
		'textbox'			=> array ('' => 'normal', 'range' => 'range'),
		'number'			=> array ('' => 'normal', 'range' => 'range'),
		'url'				=> array ('' => 'normal'),
		'textarea'			=> array ('' => 'normal'),
		'selectbox'			=> array ('' => 'normal', 'range' => 'range'),
		'radio'				=> array ('' => 'normal', 'range' => 'range'),
		'multiselectbox'	=> array ('' => 'normal'),
		'checkbox'			=> array ('' => 'normal'),
		'datebox'			=> array ('range' => 'range'),
	);

	$mapped = apply_filters ('bps_field_validation_type', $type, $f);			// to be removed
	$mapped = apply_filters ('bps_field_type_for_validation', $mapped, $f);		// to be removed

	if ($mapped != $type)
		return isset ($filters[$mapped])? $filters[$mapped]: array ();

	list (, , $range) = apply_filters ('bps_field_validation', array ('test', 'test', 'test'), $f);		// to be removed

	if ($range === true)
		return array ('range' => 'range');
	else if ($range === false)
		return array ('' => 'normal');
	else
		return array ();
}

function bps_xprofile_display ($format, $enum)
{
	$selector = $format. ($enum? '/e': '');

	$new_display = array
	(
		'integer'		=> array ('' => 'integer', 'range' => 'integer_range'),
		'decimal'		=> array ('' => 'decimal', 'range' => 'decimal_range'),
		'text'			=> array ('' => 'text', 'contains' => 'text', 'like' => 'text'),
		'date'			=> array ('age_range' => 'integer_range'),
		'location'		=> array ('' => 'text', 'contains' => 'text', 'like' => 'text', 'distance' => 'distance'),

		'integer/e'		=> array ('' => array ('selectbox', 'radio'), 'one_of'	=> array ('checkbox', 'multiselectbox')),
		'decimal/e'		=> array ('' => array ('selectbox', 'radio'), 'one_of'	=> array ('checkbox', 'multiselectbox')),
		'text/e'		=> array ('' => array ('selectbox', 'radio'), 'one_of'	=> array ('checkbox', 'multiselectbox')),
		'date/e'		=> array ('' => array ('selectbox', 'radio'), 'one_of'	=> array ('checkbox', 'multiselectbox')),
		'set/e'			=> array ('match_any' => array ('checkbox', 'multiselectbox'), 'match_all'	=> array ('checkbox', 'multiselectbox')),
	);

	$display = array
	(
		'integer'		=> array ('' => 'number', 'range' => 'range'),
		'decimal'		=> array ('' => 'textbox', 'range' => 'range'),
		'text'			=> array ('' => 'textbox', 'contains' => 'textbox', 'like' => 'textbox'),
		'date'			=> array ('age_range' => 'range'),
		'location'		=> array ('' => 'textbox', 'contains' => 'textbox', 'like' => 'textbox', 'distance' => 'distance'),

		'integer/e'		=> array ('' => array ('selectbox', 'radio'), 'one_of'	=> array ('checkbox', 'multiselectbox')),
		'decimal/e'		=> array ('' => array ('selectbox', 'radio'), 'one_of'	=> array ('checkbox', 'multiselectbox')),
		'text/e'		=> array ('' => array ('selectbox', 'radio'), 'one_of'	=> array ('checkbox', 'multiselectbox')),
		'date/e'		=> array ('' => array ('selectbox', 'radio'), 'one_of'	=> array ('checkbox', 'multiselectbox')),
		'set/e'			=> array ('match_any' => array ('checkbox', 'multiselectbox'), 'match_all'	=> array ('checkbox', 'multiselectbox')),
	);

	return $display[$selector];
}

function bps_field_display ($display, $mode, $f)
{
	$display = isset ($display[$mode])? $display[$mode]: bps_displayXsearch_form ($f);
	if (is_string ($display))  return $display;

	$choice = apply_filters ('bps_field_display', $f->type, $f);
	return in_array ($choice, $display)? $choice: $display[0];
}

function bps_filterXquery ($f)
{
	if ($f->format != 'custom')
	{
		if ($f->filter == 'range' || $f->filter == 'age_range')  return $f->filter;

		switch ($f->type)
		{
		case 'number':
			return 'num';

		case 'selectbox':
		case 'radio':
			return 'is_in';
		}

		return $f->filter;
	}

	$type = apply_filters ('bps_field_query_type', $f->type, $f);		// to be removed
	$type = apply_filters ('bps_field_type_for_query', $type, $f);		// to be removed
	
	if ($f->filter == 'range' || $f->filter == 'age_range')
		return ($type == 'datebox')? 'age_range': 'range';

	switch ($type)
	{
	case 'textbox':
	case 'textarea':
	case 'url':
		return $f->filter;

	case 'number':
		return 'num';

	case 'selectbox':
	case 'radio':
		return 'is_in';

	case 'multiselectbox':
	case 'checkbox':
		return $f->filter;
	}

	return false;
}

function bps_displayXsearch_form ($f)
{
	$type = apply_filters ('bps_field_type_for_filters', $f->type, $f);		// to be removed
	$type = apply_filters ('bps_field_type_for_search_form', $type, $f);	// to be removed

	return $type;
}

add_filter ('bps_add_fields', 'bps_anyfield_setup', 99);
function bps_anyfield_setup ($fields)
{
	$f = new stdClass;

	if (!function_exists ('bp_has_profile'))  return $fields;

	$f->group = __('Other', 'bp-profile-search');
	$f->code = 'field_any';
	$f->name = __('Any field', 'bp-profile-search');
	$f->description = __('Search every BP Profile Field', 'bp-profile-search');
	$f->type = '';
	$f->options = array ();
	$f->format = 'text';
	$f->filters = array ('contains' => __('contains', 'bp-profile-search'));
	$f->display = array ('contains' => 'textbox');
	$f->search = 'bps_anyfield_search';

	$fields[] = $f;
	return $fields;
}

function bps_anyfield_search ($f)
{
	global $bp, $wpdb;

	$value = str_replace ('&', '&amp;', $f->value);
	$escaped = '%'. bps_esc_like ($value). '%';

	$sql = array ('select' => '', 'where' => array ());
	$sql['select'] = "SELECT DISTINCT user_id FROM {$bp->profile->table_name_data}";
	$sql['where'][$f->filter] = $wpdb->prepare ("value LIKE %s", $escaped);

	$sql = apply_filters ('bps_field_sql', $sql, $f);
	$query = $sql['select']. ' WHERE '. implode (' AND ', $sql['where']);

	$results = $wpdb->get_col ($query);
	return $results;
}

add_filter ('bps_add_fields', 'bps_membertype_setup');
function bps_membertype_setup ($fields)
{
	if (!function_exists ('bp_get_member_types'))  return $fields;

	$member_types = bp_get_member_types (array (), 'objects');
	if (count ($member_types) == 0)  return $fields;

	$f = new stdClass;
	$f->group = __('Other', 'bp-profile-search');
	$f->code = 'membertype';
	$f->name = __('Member type', 'bp-profile-search');
	$f->description = __('Select the member type', 'bp-profile-search');
	$f->type = '';

	$f->options = array ();
	foreach ($member_types as $type)
	{
		$label = $type->labels['singular_name'];
		$f->options[$label] = $label;
	}

	$f->format = 'text';
	$f->filters = array ('' => __('is', 'bp-profile-search'));
	$f->display = array ('' => 'selectbox');
	$f->search = 'bps_membertype_search';

	$fields[] = $f;
	return $fields;
}

function bps_membertype_search ($f)
{
	global $wpdb;

	$types = array ();
	$values = stripslashes_deep ((array)$f->value);
	$member_types = bp_get_member_types (array (), 'objects');

	foreach ($values as $value) {
		foreach ($member_types as $type) {
			if ($value == $type->labels['singular_name'])  { $types[] = $type->name;  break; }
		}
	}

	$sql = array ('select' => '', 'where' => array ());
	$sql['select'] = "SELECT object_id FROM {$wpdb->base_prefix}term_relationships";
	$sql['where'][$f->filter] = "term_taxonomy_id IN (
		SELECT term_taxonomy_id
		FROM {$wpdb->base_prefix}term_taxonomy
		INNER JOIN {$wpdb->base_prefix}terms USING (term_id)
		WHERE taxonomy = 'bp_member_type'
		AND name IN ('". implode ("','", $types). "'))";

	$sql = apply_filters ('bps_field_sql', $sql, $f);
	$query = $sql['select']. ' WHERE '. implode (' AND ', $sql['where']);

	$results = $wpdb->get_col ($query);
	return $results;
}
