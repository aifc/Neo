<?php

/**
 * BuddyPress - Members Loop
 *
 * Querystring is set via AJAX in _inc/ajax.php - bp_legacy_theme_object_filter()
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

?>
<?php
	global $wpdb;
	$WOOCOMMERCE_APPOINTMENT_RELATIONSHIPS_TABLE = $wpdb->prefix . 'wc_appointment_relationships';
?>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" />

<?php do_action( 'bp_before_members_loop' ); ?>

<?php if ( bp_has_members( bp_ajax_querystring( 'members' ) ) ) : ?>

	<?php do_action( 'bp_before_directory_members_list' ); ?>

	<ul id="members-list" class="item-list" role="main">

	<?php $counter = 0; ?>
	<?php while ( bp_members() ) : bp_the_member(); ?>

		<?php 
		$user_id = bp_get_member_user_id(); 
		$user = new WP_User( $user_id );   
		if ( $user->roles[0] != 'shop_staff' )
		continue;
		?>

		<?php 
		if($counter % 2 == 0) {
			echo '<div class="x-container wpb_row">';
		}
		?>

			<?php do_action( 'bp_directory_members_item' ); ?>
			<?php
			$product_id = $wpdb->get_row( "SELECT * FROM $WOOCOMMERCE_APPOINTMENT_RELATIONSHIPS_TABLE WHERE staff_id='$user_id'" );
			$price = get_post_meta($product_id->product_id,'_price', true);
			?>
			<a href="<?php bp_member_permalink(); ?>" class="bioButton bookButton">
				<div class="x-column x-sm vc blurbCard x-1-2">
	        		<h2 class="h-custom-headline blurbHeading center-text h6"><span><?php bp_member_name(); ?></span><span class="right-float"><?php echo '$'.$price.'/hr'; ?></span></h2>
	                <div class="member_loop_content">
	                	<?php //construct popout url
						    $link = 'http://mychristiancounsellor.org.au/popout/?link=' . get_userdata($user_id)->user_nicename;
						    //echo ('<pre>'.var_dump($link).'	</pre>');
	                	?>
						<?php echo bp_core_fetch_avatar( array( 'item_id' => $user_id, 'type' => 'full' ) ) ?>
						<div class="bio-container">
				            <p class ="biotext"><b>Qualifications: </b>Graduate Diploma of Counselling and integrated Psychotherapy - 2012 From Australian Insitute of Family Counselling</p>
				            <p class ="biotext">&nbsp</p>
						</div>
						<div id="specialization">
							<ul class ="center-content" style="margin:0">
			                    <?php 
			                    foreach (xprofile_get_field_data( 'Counselling Specialities', $user_id ) as $specialty) {
			                        echo '<li style="margin-bottom:10px" class ="specialty">'.$specialty.'</li>';
			                    }
			                    ?>
	                		</ul>
                		</div>
		        	</div>
			 	</div>
	        </a> 
        <?php 
        if($counter % 2 != 0) {
        	echo '</div>';
        }
    	++$counter;
        ?>

	<?php endwhile; ?>

	</ul>

	<?php do_action( 'bp_after_directory_members_list' ); ?>

	<?php bp_member_hidden_fields(); ?>

	<div id="pag-bottom" class="x-pagination pagination">

		<div class="pagination-links" id="member-dir-pag-bottom">

			<?php bp_members_pagination_links(); ?>

		</div>

	</div>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( "Sorry, no members were found.", '__x__' ); ?></p>
	</div>

<?php endif; ?>

<?php do_action( 'bp_after_members_loop' ); ?>
