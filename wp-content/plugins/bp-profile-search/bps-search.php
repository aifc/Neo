<?php

add_action ('wp', 'bps_set_cookie');
function bps_set_cookie ()
{
	if (!isset ($_REQUEST[BPS_FORM]))  return false;

	$cookie = apply_filters ('bps_cookie_name', 'bps_request');
	if ($_REQUEST[BPS_FORM] != 'clear')
	{
		$current = parse_url ($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		$_REQUEST['bps_directory'] = $current;
		setcookie ($cookie, http_build_query ($_REQUEST), 0, COOKIEPATH);
	}
	else
	{
		setcookie ($cookie, '', 0, COOKIEPATH);
	}
}

function bps_get_request ()
{
	$cookie = apply_filters ('bps_cookie_name', 'bps_request');
	if (isset ($_REQUEST[BPS_FORM]))
		$request = $_REQUEST;
	else if (isset ($_COOKIE[$cookie]))
		parse_str (stripslashes ($_COOKIE[$cookie]), $request);
	else
		$request = array ();

	return apply_filters ('bps_request', $request);
}

function bps_active_form ($form)
{
	$request = bps_get_request ();
	if (!isset ($request[BPS_FORM]))  return false;

	return ($request[BPS_FORM] == $form);
}

function bps_active_directory ()
{
	$request = bps_get_request ();
	if (!isset ($request['bps_directory']))  return false;

	$current = defined ('DOING_AJAX')?
		parse_url ($_SERVER['HTTP_REFERER'], PHP_URL_PATH):
		parse_url ($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	
	return ($request['bps_directory'] == $current);
}

add_action ('bp_ajax_querystring', 'bps_filter_members', 99, 2);
function bps_filter_members ($qs, $object)
{
	if (bps_active_directory () == false)  return $qs;
	if (!in_array ($object, array ('members', 'group_members')))  return $qs;

	$results = bps_search ();
	if ($results['validated'])
	{
		$args = wp_parse_args ($qs);
		$users = $results['users'];

		if (isset ($args['include']))
		{
			$included = explode (',', $args['include']);
			$users = array_intersect ($users, $included);
			if (count ($users) == 0)  $users = array (0);
		}

		$users = apply_filters ('bps_filter_members', $users);
		$args['include'] = implode (',', $users);
		$qs = build_query ($args);
	}

	return $qs;
}

function bps_search ()
{
	$results = array ('users' => array (0), 'validated' => true);

	list (, $fields) = bps_get_fields ();
	foreach ($fields as $f)
	{
		if (!isset ($f->filter))  continue;

		do_action ('bps_field_before_query', $f);
		if (is_callable ($f->search))
			$found = call_user_func ($f->search, $f);
		else
			$found = apply_filters ('bps_field_query', array (), $f, $f->code, $f->value);  // to be removed

		do_action ('bps_field_after_query', $f, $found);

		$match_all = apply_filters ('bps_match_all', true);
		if ($match_all)
		{
			$users = isset ($users)? array_intersect ($users, $found): $found;
			if (count ($users) == 0)  return $results;
		}
		else
		{
			$users = isset ($users)? array_merge ($users, $found): $found;
		}
	}

	if (isset ($users))
		$results['users'] = $users;
	else
		$results['validated'] = false;

	return $results;
}

function bps_esc_like ($text)
{
    return addcslashes ($text, '_%\\');
}
