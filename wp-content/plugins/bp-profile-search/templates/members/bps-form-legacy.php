<?php

/*
 * BP Profile Search - form template 'bps-form-legacy'
 *
 * See http://dontdream.it/bp-profile-search/form-templates/ if you wish to modify this template or develop a new one.
 *
 */

	$F = bps_escaped_form_data ();

	$toggle_id = 'bps_toggle'. $F->id;
	$form_id = 'bps_'. $F->location. $F->id;

	if ($F->location != 'directory')
	{
		echo "<div id='buddypress'>";
	}
	else
	{
?>
	<div class="item-list-tabs bps_header" style="clear: both;">
	  <ul>
		<li><?php echo $F->header; ?></li>
<?php
		if ($F->toggle)
		{
?>
		<li class="last">
		  <input id="<?php echo $toggle_id; ?>" type="submit" value="<?php echo $F->toggle_text; ?>">
		</li>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('#<?php echo $form_id; ?>').hide();
				$('#<?php echo $toggle_id; ?>').click(function(){
					$('#<?php echo $form_id; ?>').toggle('slow');
				});
			});
		</script>
<?php
		}
?>
	  </ul>
	</div>
<?php
	}

	echo "<form action='$F->action' method='$F->method' id='$form_id' class='standard-form bps_form'>\n";

	$j = 0;
	foreach ($F->fields as $f)
	{
		if ($f->display == 'hidden')
		{
			echo "<input type='hidden' name='$f->code' value='$f->value'>\n";
			continue;
		}

		$name = sanitize_title ($f->name);
		$alt = ($j++ % 2)? 'alt': '';
		$class = "editfield $f->code field_$name $alt";

		echo "<div class='$class'>\n";

		switch ($f->display)
		{
		case 'range':
			echo "<label for='$f->code'>$f->label</label>\n";
			echo "<input style='width: 10%; display: inline;' type='text' name='{$f->code}_min' id='$f->code' value='$f->min'>";
			echo '&nbsp;-&nbsp;';
			echo "<input style='width: 10%; display: inline;' type='text' name='{$f->code}_max' value='$f->max'>\n";
			break;

		case 'textbox':
			echo "<label for='$f->code'>$f->label</label>\n";
			echo "<input type='text' name='$f->code' id='$f->code' value='$f->value'>\n";
			break;

		case 'number':
			echo "<label for='$f->code'>$f->label</label>\n";
			echo "<input type='number' name='$f->code' id='$f->code' value='$f->value'>\n";
			break;

		case 'url':
			echo "<label for='$f->code'>$f->label</label>\n";
			echo "<input type='text' inputmode='url' name='$f->code' id='$f->code' value='$f->value'>\n";
			break;

		case 'textarea':
			echo "<label for='$f->code'>$f->label</label>\n";
			echo "<textarea rows='5' cols='40' name='$f->code' id='$f->code'>$f->value</textarea>\n";
			break;

		case 'distance':
			$within = __('Within', 'bp-profile-search');
			$of = __('of', 'bp-profile-search');
			$km = __('km', 'bp-profile-search');
			$miles = __('miles', 'bp-profile-search');
?>
			<label for="<?php echo $f->unique_id; ?>"><?php echo $f->label; ?></label>
			<span><?php echo $within; ?></span>
			<input style="width: 4em;" type="number" min="1"
				name="<?php echo $f->code. '[distance]'; ?>"
				value="<?php echo $f->value['distance']; ?>">
			<select name="<?php echo $f->code. '[units]'; ?>">
				<option value="km" <?php selected ($f->value['units'], "km"); ?>><?php echo $km; ?></option>
				<option value="miles" <?php selected ($f->value['units'], "miles"); ?>><?php echo $miles; ?></option>
			</select>
			<span><?php echo $of; ?></span>
			<input type="text" id="<?php echo $f->unique_id; ?>"
				name="<?php echo $f->code. '[location]'; ?>"
				value="<?php echo $f->value['location']; ?>"
				placeholder="<?php _e('Start typing, then select a location', 'bp-profile-search'); ?>">
<?php
			bps_autocomplete_script ($f);
			break;

		case 'selectbox':
			echo "<label for='$f->code'>$f->label</label>\n";
			echo "<select name='$f->code' id='$f->code'>\n";

			$no_selection = apply_filters ('bps_field_selectbox_no_selection', '', $f);
			if (is_string ($no_selection))
				echo "<option  value=''>$no_selection</option>\n";

			foreach ($f->options as $key => $label)
			{
				$selected = in_array ($key, $f->values)? "selected='selected'": "";
				echo "<option $selected value='$key'>$label</option>\n";
			}
			echo "</select>\n";
			break;

		case 'multiselectbox':
			echo "<label for='$f->code'>$f->label</label>\n";
			echo "<select name='{$f->code}[]' id='$f->code' multiple='multiple'>\n";

			foreach ($f->options as $key => $label)
			{
				$selected = in_array ($key, $f->values)? "selected='selected'": "";
				echo "<option $selected value='$key'>$label</option>\n";
			}
			echo "</select>\n";
			break;

		case 'radio':
			echo "<div class='radio'>\n";
			echo "<span class='label'>$f->label</span>\n";
			echo "<div id='$f->code'>\n";

			foreach ($f->options as $key => $label)
			{
				$checked = in_array ($key, $f->values)? "checked='checked'": "";
				echo "<label><input $checked type='radio' name='$f->code' value='$key'>$label</label>\n";
			}
			echo "</div>\n";
			echo "<a style='display: inline;' class='clear-value' href='javascript:clear(\"$f->code\");'>". __('Clear', 'buddypress'). "</a>\n";
			echo "</div>\n";
			break;

		case 'checkbox':
			echo "<div class='checkbox'>\n";
			echo "<span class='label'>$f->label</span>\n";

			foreach ($f->options as $key => $label)
			{
				$checked = in_array ($key, $f->values)? "checked='checked'": "";
				echo "<label><input $checked type='checkbox' name='{$f->code}[]' value='$key'>$label</label>\n";
			}
			echo "</div>\n";
			break;

		default:
			echo "<p>BP Profile Search: unknown display <em>$f->display</em> for field <em>$f->name</em>.</p>\n";
			break;
		}

		if (!empty ($f->description) && $f->description != '-')
			echo "<p class='description'>$f->description</p>\n";

		echo "</div>\n";
	}

	echo "<div class='submit'>\n";
	echo "<input type='submit' value='". __('Search', 'buddypress'). "'>\n";
	echo "</div>\n";
	echo "</form>\n";

	if ($F->location != 'directory')  echo "</div>\n";

// BP Profile Search - end of template
