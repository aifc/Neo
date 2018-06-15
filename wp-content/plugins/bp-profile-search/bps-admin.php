<?php

add_action ('add_meta_boxes', 'bps_add_meta_boxes');
function bps_add_meta_boxes ()
{
	add_meta_box ('bps_fields_box', __('Form Fields', 'bp-profile-search'), 'bps_fields_box', 'bps_form', 'normal');
	add_meta_box ('bps_attributes', __('Form Attributes', 'bp-profile-search'), 'bps_attributes', 'bps_form', 'side');
	add_meta_box ('bps_directory', __('Add to Directory', 'bp-profile-search'), 'bps_directory', 'bps_form', 'side');
}

function bps_fields_box ($post)
{
	$bps_options = bps_meta ($post->ID);

	list ($groups, $fields) = bps_get_fields ();
	echo '<script>var bps_groups = ['. json_encode ($groups). '];</script>';
?>

	<div id="field_box" class="field_box">
		<p>
			<span class="bps_col1"></span>
			<span class="bps_col2"><strong>&nbsp;<?php _e('Field', 'bp-profile-search'); ?></strong></span>&nbsp;
			<span class="bps_col3"><strong>&nbsp;<?php _e('Label', 'bp-profile-search'); ?></strong></span>&nbsp;
			<span class="bps_col4"><strong>&nbsp;<?php _e('Description', 'bp-profile-search'); ?></strong></span>&nbsp;
			<span class="bps_col5"><strong>&nbsp;<?php _e('Search Mode', 'bp-profile-search'); ?></strong></span>
		</p>
<?php

	foreach ($bps_options['field_code'] as $k => $id)
	{
		if (empty ($fields[$id]))  continue;

		$field = $fields[$id];
		$label = esc_attr ($bps_options['field_label'][$k]);
		$default = esc_attr ($field->name);
		$showlabel = empty ($label)? "placeholder=\"$default\"": "value=\"$label\"";
		$desc = esc_attr ($bps_options['field_desc'][$k]);
		$default = esc_attr ($field->description);
		$showdesc = empty ($desc)? "placeholder=\"$default\"": "value=\"$desc\"";
?>

		<div id="field_div<?php echo $k; ?>" class="sortable">
			<span class="bps_col1" title="<?php _e('drag to reorder fields', 'bp-profile-search'); ?>">&nbsp;&#x21C5;</span>
<?php
			_bps_field_select ($groups, "bps_options[field_name][$k]", "field_name$k", $id);
?>
			<input class="bps_col3" type="text" name="bps_options[field_label][<?php echo $k; ?>]" id="field_label<?php echo $k; ?>" <?php echo $showlabel; ?> />
			<input class="bps_col4" type="text" name="bps_options[field_desc][<?php echo $k; ?>]" id="field_desc<?php echo $k; ?>" <?php echo $showdesc; ?> />
<?php
			_bps_filter_select ($field->filters, "bps_options[field_mode][$k]", "field_mode$k", $bps_options['field_mode'][$k]);
?>
			<a href="javascript:remove('field_div<?php echo $k; ?>')" class="delete"><?php _e('Remove', 'bp-profile-search'); ?></a>
		</div>
<?php
	}
?>
	</div>
	<input type="hidden" id="field_next" value="<?php echo count ($bps_options['field_code']); ?>" />
	<p><a href="javascript:add_field()"><?php _e('Add Field', 'bp-profile-search'); ?></a></p>
<?php
}

function _bps_field_select ($groups, $name, $id, $value)
{
	echo "<select class='bps_col2' name='$name' id='$id'>\n";
	foreach ($groups as $group => $fields)
	{
		$group = esc_attr ($group);
		echo "<optgroup label='$group'>\n";
		foreach ($fields as $field)
		{
			$selected = $field['id'] == $value? " selected='selected'": '';
			echo "<option value='$field[id]'$selected>$field[name]</option>\n";
		}
		echo "</optgroup>\n";
	}
	echo "</select>\n";

	return true;
}

function _bps_filter_select ($filters, $name, $id, $value)
{
	if (count ($filters) == 0)
	{
		echo '<strong class="bps_col5" style="color:red;">&nbsp;&nbsp;'. __('undefined', 'bp-profile-search'). '</strong>';
		return false;
	}

	echo "<select class='bps_col5' name='$name' id='$id'>\n";
	foreach ($filters as $key => $label)
	{
		$selected = $value == $key? " selected='selected'": '';
		echo "<option value='$key'$selected>$label</option>\n";
	}
	echo "</select>\n";

	return true;
}

function bps_attributes ($post)
{
	$options = bps_meta ($post->ID);
?>
	<p><strong><?php _e('Form Method', 'bp-profile-search'); ?></strong></p>
	<label class="screen-reader-text" for="method"><?php _e('Form Method', 'bp-profile-search'); ?></label>
	<select name="options[method]" id="method">
		<option value='POST' <?php selected ($options['method'], 'POST'); ?>><?php _e('POST', 'bp-profile-search'); ?></option>
		<option value='GET' <?php selected ($options['method'], 'GET'); ?>><?php _e('GET', 'bp-profile-search'); ?></option>
	</select>

	<p><strong><?php _e('Form Action (Results Directory)', 'bp-profile-search'); ?></strong></p>
	<label class="screen-reader-text" for="action"><?php _e('Form Action (Results Directory)', 'bp-profile-search'); ?></label>
	<select name="options[action]" id="action">
<?php
	$dirs = bps_directories ();
	foreach ($dirs as $id => $dir)
	{
?>
		<option value='<?php echo $id; ?>' <?php selected ($options['action'], $id); ?>><?php echo esc_attr ($dir->label); ?></option>
<?php
	}
?>
	</select>

	<p><?php _e('Need help? Use the Help tab in the upper right of your screen.'); ?></p>
<?php
}

function bps_directory ($post)
{
	$options = bps_meta ($post->ID);
?>
	<p><strong><?php _e('Add to Directory', 'bp-profile-search'); ?></strong></p>
	<label class="screen-reader-text" for="directory"><?php _e('Add to Directory', 'bp-profile-search'); ?></label>
	<select name="options[directory]" id="directory">
		<option value='Yes' <?php selected ($options['directory'], 'Yes'); ?>><?php _e('Yes', 'bp-profile-search'); ?></option>
		<option value='No' <?php selected ($options['directory'], 'No'); ?>><?php _e('No', 'bp-profile-search'); ?></option>
	</select>

	<p><strong><?php _e('Form Template', 'bp-profile-search'); ?></strong></p>
	<select name="options[template]" id="template">
<?php
	$templates =  bps_templates ();
	foreach ($templates as $template)
	{
?>
		<option value='<?php echo $template; ?>' <?php selected ($options['template'], $template); ?>><?php echo $template; ?></option>
<?php
	}
?>
	</select>

	<p><strong><?php _e('Form Header', 'bp-profile-search'); ?></strong></p>
	<label class="screen-reader-text" for="header"><?php _e('Form Header', 'bp-profile-search'); ?></label>
	<textarea name="options[header]" id="header" class="large-text code" rows="4"><?php echo $options['header']; ?></textarea>

	<p><strong><?php _e('Toggle Form', 'bp-profile-search'); ?></strong></p>
	<label class="screen-reader-text" for="toggle"><?php _e('Toggle Form', 'bp-profile-search'); ?></label>
	<select name="options[toggle]" id="toggle">
		<option value='Enabled' <?php selected ($options['toggle'], 'Enabled'); ?>><?php _e('Enabled', 'bp-profile-search'); ?></option>
		<option value='Disabled' <?php selected ($options['toggle'], 'Disabled'); ?>><?php _e('Disabled', 'bp-profile-search'); ?></option>
	</select>

	<p><strong><?php _e('Toggle Form Button', 'bp-profile-search'); ?></strong></p>
	<label class="screen-reader-text" for="button"><?php _e('Toggle Form Button', 'bp-profile-search'); ?></label>
	<input type="text" name="options[button]" id="button" value="<?php echo esc_attr ($options['button']); ?>" />
<?php
}

add_action ('save_post', 'bps_update_meta', 10, 2);
function bps_update_meta ($form, $post)
{
	if ($post->post_type != 'bps_form' || $post->post_status != 'publish')  return false;
	if (empty ($_POST['options']) && empty ($_POST['bps_options']))  return false;

	$meta = array ();
	$meta['field_code'] = array ();
	$meta['field_label'] = array ();
	$meta['field_desc'] = array ();
	$meta['field_mode'] = array ();

	list ($x, $fields) = bps_get_fields ();

	$j = 0;
	$posted = isset ($_POST['bps_options'])? $_POST['bps_options']: array ();
	if (isset ($posted['field_name']))  foreach ($posted['field_name'] as $k => $id)
	{
		if (empty ($fields[$id]))  continue;

		$f = $fields[$id];

		$meta['field_code'][$j] = $f->code;
		$meta['field_label'][$j] = isset ($posted['field_label'][$k])? stripslashes ($posted['field_label'][$k]): '';
		$meta['field_desc'][$j] = isset ($posted['field_desc'][$k])? stripslashes ($posted['field_desc'][$k]): '';
		$meta['field_mode'][$j] = isset ($posted['field_mode'][$k])? stripslashes ($posted['field_mode'][$k]): bps_default_filter ($f);

		$filter = $meta['field_mode'][$j];
		if (!bps_validate_filter ($filter, $f))
			$meta['field_mode'][$j] = bps_default_filter ($f);

		bps_set_wpml ($form, $f->code, 'label', $meta['field_label'][$j]);
		bps_set_wpml ($form, $f->code, 'comment', $meta['field_desc'][$j]);
		$j = $j + 1;
	}

	foreach (array ('directory', 'template', 'header', 'toggle', 'button', 'method', 'action') as $key)
		$meta[$key] = stripslashes ($_POST['options'][$key]);

	bps_set_wpml ($form, '-', 'header', $meta['header']);
	bps_set_wpml ($form, '-', 'toggle form', $meta['button']);

	update_post_meta ($form, 'bps_options', $meta);
	return true;
}

function bps_default_filter ($f)
{
	reset ($f->filters);
	return key ($f->filters);
}
