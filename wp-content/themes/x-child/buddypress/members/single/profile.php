<?php

/**
 * BuddyPress - Users Profile
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

?>

<?php if ( bp_is_my_profile() ) : ?>

<div class="x-item-list-tabs-subnav item-list-tabs no-ajax" id="subnav" role="navigation">
	<ul>
		<?php 
			$user = wp_get_current_user();
		    if ( in_array( 'shop_staff', (array) $user->roles ) ) {
		        bp_get_options_nav();
		    }
		    else {
		    	
		    	$selected_item = bp_current_action(); 
		    	$bp = buddypress(); 
 
			    $component_index = 'profile'; 
			    
				if ( ! bp_is_single_item() ) { 
			        if ( empty( $parent_slug ) ) { 
			            $parent_slug = $component_index; 
			        } 
 					
        			$secondary_nav_items = $bp->members->nav->get_secondary( array( 'parent_slug' => $parent_slug ) ); 
 					
			        if ( ! $secondary_nav_items ) { 
			            return false; 
			        } 
 				}

		    	foreach ( $secondary_nav_items as $subnav_item ) { 
        			if($subnav_item->slug === 'public') continue;
        			if($subnav_item->slug === 'edit') {
        				$subnav_item->link = get_site_url().'/edit-user';
        			}
			        if ( $subnav_item->slug === $selected_item ) { 
			            $selected = ' class="current selected"'; 
			        } else { 
			            $selected = ''; 
			        } 
 					
			        echo '<li id="' . esc_attr( $subnav_item->css_id . '-' . $list_type . '-li' ) . '" ' . $selected . '><a id="' . esc_attr( $subnav_item->css_id ) . '" href="' . esc_url( $subnav_item->link ) . '">' . $subnav_item->name . '</a></li>'; 
			    } 
		    }
    	?>
	</ul>
</div><!-- .item-list-tabs -->

<?php endif; ?>

<?php do_action( 'bp_before_profile_content' ); ?>

<div class="profile" role="main">

<?php switch ( bp_current_action() ) :

	// Edit
	case 'edit'   :
		bp_get_template_part( 'members/single/profile/edit' );
		break;

	// Change Avatar
	case 'change-avatar' :
		bp_get_template_part( 'members/single/profile/change-avatar' );
		break;

	// Compose
	case 'public' :

		// Display XProfile
		if ( bp_is_active( 'xprofile' ) )
			get_template_part( 'templates/profile' );
			// bp_get_template_part( 'members/single/profile/profile-loop' );

		// Display WordPress profile (fallback)
		else
			bp_get_template_part( 'members/single/profile/profile-wp' );

		break;

	// Any other
	default :
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch; ?>
</div><!-- .profile -->

<?php do_action( 'bp_after_profile_content' ); ?>