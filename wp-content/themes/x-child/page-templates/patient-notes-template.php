<?php
/**
 * Template Name: Patient Note Template
 *
 * @package WordPress
 */
?>
<!-- See ./wp-content/themes/x/framework/views/integrity/template-blank-1.php for reference to below HTML -->
<?php get_header(); ?>

<?php

    global $wpdb;
    $user = wp_get_current_user(); 
    $user_id = $user->ID;

    $NOTES_COUNSELOR_TABLE = $wpdb->prefix . 'notes_counselor';
    $results = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $NOTES_COUNSELOR_TABLE WHERE counselor_id = %d", $user_id) );
    $results = array_group_by($results,'client_id');
        
 	if (in_array( 'shop_staff', (array)$user->roles)) {
 		$counselor = true;
 	}
 	if(!$counselor)
 	{
 		wp_redirect(home_url().'/home');
    	exit();
 	}
    date_default_timezone_set('Australia/Canberra');
?>
<div class="x-container max width offset">
	<h3 class="my-headings">Client Notes</h3>
	<p class = "paragraph-uncontained">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas at faucibus neque.</p>
    <div class="x-main full" role="main">

    <?php foreach($results as $result):?>
        
        <article class="notes">
            <div class="entry-wrap" style="margin-bottom: 20px;">
                <?php $client_name = xprofile_get_field_data( 'Name', $result[0]->client_id ); ?>
                <h3 class ="counselor_notes_heading my-headings"><?php echo $client_name?></h3>
                <?php foreach($result as $obj):?>
                    <p class="note_date_counselor"><?php echo date('h:ia, jS \of F',$obj->note_date)?></p>
                    <div class="note_body">
                        <p><?php echo $obj->note?></p>
                    </div>
                <?php endforeach; ?>
                
            </div>
        </article>

    <?php endforeach; ?>

    </div>
</div>

<?php get_footer(); ?>
<?php

function array_group_by(array $array, $key)
{
    if (!is_string($key) && !is_int($key) && !is_float($key) && !is_callable($key) ) {
        trigger_error('array_group_by(): The key should be a string, an integer, or a callback', E_USER_ERROR);
        return null;
    }
    $func = (!is_string($key) && is_callable($key) ? $key : null);
    $_key = $key;
    // Load the new array, splitting by the target key
    $grouped = [];
    foreach ($array as $value) {
        $key = null;
        if (is_callable($func)) {
            $key = call_user_func($func, $value);
        } elseif (is_object($value) && isset($value->{$_key})) {
            $key = $value->{$_key};
        } elseif (isset($value[$_key])) {
            $key = $value[$_key];
        }
        if ($key === null) {
            continue;
        }
        $grouped[$key][] = $value;
    }
    // Recursively build a nested grouping if more parameters are supplied
    // Each grouped array value is grouped according to the next sequential key
    if (func_num_args() > 2) {
        $args = func_get_args();
        foreach ($grouped as $key => $value) {
            $params = array_merge([ $value ], array_slice($args, 2, func_num_args()));
            $grouped[$key] = call_user_func_array('array_group_by', $params);
        }
    }
    return $grouped;
    }
