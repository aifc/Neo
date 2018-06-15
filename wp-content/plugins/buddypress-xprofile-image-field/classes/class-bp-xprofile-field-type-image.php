<?php
/**
 * BuddyPress XProfile Image Field Classes
 *
 * @package BuddyPress
 * @subpackage XProfileClasses
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Image xprofile field type.
 *
 * @since BuddyPress XProfile Image Field (2.0.0)
 */
class BP_XProfile_Field_Type_Image extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the image field type
	 *
	 * @since BuddyPress XProfile Image Field (2.0.0)
 	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Custom Fields', 'bp-xprofile-image-field' );
		$this->name     = __( 'Image', 'bp-xprofile-image-field' );

		$this->set_format( '/^.*$/', 'replace' );

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_Image class.
		 *
		 * @since BuddyPress XProfile Image Field (2.0.0)
		 *
		 * @param BP_XProfile_Field_Type_Image $this Current instance of
		 *                                             the field type image.
		 */
		do_action( 'bp_xprofile_field_type_image', $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of {@link http://dev.w3.org/html5/markup/input.text.html permitted attributes} that you want to add.
	 * @since BuddyPress XProfile Image Field (2.0.0)
	 */
	public function edit_field_html( array $raw_properties = array() ) {

                do_action('bpxp_image_field_before_edit_render');
                
		// user_id is a special optional parameter that certain other fields
		// types pass to {@link bp_the_profile_field_options()}.
		if ( isset( $raw_properties['user_id'] ) ) {
			unset( $raw_properties['user_id'] );
		}
                
                $field_id = bp_get_the_profile_field_id();
                $image_field_input_name = bp_get_the_profile_field_input_name();
                $field_name_hidden = 'field_' . $field_id . '_hidden';
                $field_name_delete = 'field_' . $field_id . '_delete';
                $image_id = 'bpxp_image_' . $field_id;
                $image = bp_get_the_profile_field_edit_value();
                $image_link = WP_CONTENT_URL . $image;
                
                $image_field_input = bp_parse_args( $raw_properties, array(
                                                                            'type'  => 'file',
                                                                            'name' => $image_field_input_name,
                                                                            'id' => $image_field_input_name,
                                                                            'value' => $image,
                                                                            ) 
                                                  );
                
                $image_field_hidden_input = bp_parse_args( $raw_properties, array(
                                                                            'type'  => 'hidden',
                                                                            'name' => $field_name_hidden,
                                                                            'id' => $field_name_hidden,
                                                                            'value' => $image,
                                                                            ) 
                                                  );
                
                $image_field_delete_input = bp_parse_args( $raw_properties, array(
                                                                            'type'  => 'hidden',
                                                                            'name' => $field_name_delete,
                                                                            'id' => $field_name_delete,
                                                                            'value' => '',
                                                                            ) 
                                                  );
                
                ob_start();
                ?>
                        <label for="<?php bp_the_profile_field_input_name(); ?>"><?php bp_the_profile_field_name(); ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'bp-xprofile-image-field' ); ?><?php endif; ?></label>
                        
                        <?php
                            /** This action is documented in bp-xprofile/bp-xprofile-classes */
                            do_action( bp_get_the_profile_field_errors_action() ); 
                        ?>
                        
                        <input <?php echo $this->get_edit_field_html_elements( $image_field_input ); ?>>
                        <input <?php echo $this->get_edit_field_html_elements( $image_field_hidden_input ); ?>>
                <?php
                    if(!empty($image)) {
                ?>    
                        <input <?php echo $this->get_edit_field_html_elements( $image_field_delete_input ); ?>>
                        <img src="<?php echo $image_link; ?>" alt="<?php bp_the_profile_field_name(); ?>" id="<?php echo $image_id; ?>" />
                        <a href="#" data-delete_id="<?php echo $field_name_delete; ?>" data-image_id="<?php echo $image_id; ?>" class="rtd-button delete-icon"><?php _e('Delete', 'bp-xprofile-image-field'); ?></a>

                <?php
                    }
                    $output = ob_get_contents();
                ob_end_clean();

                echo $output;
                
		do_action('bpxp_image_field_after_edit_render', $output);
	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 * @since BuddyPress XProfile Image Field (2.0.0)
	 */
	public function admin_field_html( array $raw_properties = array() ) {

                do_action('bpxp_image_field_before_admin_render');
                
		$r = bp_parse_args( $raw_properties, array( 'type' => 'file',
                                                            'disabled' => 'disabled'
                                                           ) 
                                   ); 
                
                ob_start();
                    ?>

                        <input <?php echo $this->get_edit_field_html_elements( $r ); ?>>

                    <?php

                    $output = ob_get_contents();
                ob_end_clean();
                
                do_action('bpxp_image_field_after_admin_render', $output);
                
                echo $output;
	}
        
        

	/**
	 * Format Image for display.
	 *
	 * @since BuddyPress XProfile Image Field (2.0.0)
	 * @since BuddyPress XProfile Image Field (2.0.2) Added `$field_id` parameter.
	 *
	 * @param string $field_value The URL value, as saved in the database.
	 * @return string URL converted to a link.
	 */
	public static function display_filter( $field_value, $field_id = '' ) {
            
            $field_type = BP_XPROFILE_IMAGE_FIELD;
            $raw_field_value = bp_unserialize_profile_field( $field_value );
            $bpxp_field_value = WP_CONTENT_URL . $raw_field_value;
            $bpxp_field_value = "<img src=\"{$bpxp_field_value}\" alt=\"" . __('image', 'bp-xprofile-image-field') . "\" />";

            $filtered_field_value = apply_filters('bpxp_image_field_frontend_field_value', $bpxp_field_value, $field_value, $raw_field_value, $field_type, $field_id);
        

            return $filtered_field_value;
	}
        

	/**
	 * This method usually outputs HTML for this field type's children options on the wp-admin Profile Fields
	 * "Add Field" and "Edit Field" screens, but for this field type, we don't want it, so it's stubbed out.
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string $control_type Optional. HTML input type used to render the current field's child options.
	 * @since BuddyPress XProfile Image Field (2.0.0)
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {}
}
