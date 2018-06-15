<?php 
/* 
Plugin Name: BuddyPress XProfile Image Field
Plugin URI: http://nerdonia.co.ke/
Description: BuddyPress XProfile addon that adds an Image field type
Version: 2.0.3
Author: Alex Githatu
Author URI: http://nerdonia.co.ke/buddypress-xprofile-image-field
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Copyright: 2014  Alex Githatu  ( email : alex@nerdonia.co.ke )
*/

//define(BP_XPROFILE_IMAGE_FIELD, 'image');

if( ! class_exists( 'BP_XProfile_Image_Field' ) ) {
    class BP_XProfile_Image_Field {
        
        private static $instance;
	
	public static $version;
	
	public $file;
	
	public $plugin_path;
	
	public $plugin_url;
	
	public $plugin_name;
        
        
        public static function instance() {
            if ( ! isset( self::$instance ) && ! ( self::$instance instanceof BP_XProfile_Image_Field ) ) {
                self::$instance = new BP_XProfile_Image_Field(__FILE__);
                self::$version = '2.0.3';
                
                if(version_compare( BP_VERSION, '2.0.2', '>' )) {
                    require dirname( __FILE__ ) . '/classes/class-bp-xprofile-field-type-image.php';
                }
                
            }
            return self::$instance;
	}
        
        private function __construct( $file ) {
		
		$this->file = $file;
		$this->plugin_url = trailingslashit( plugins_url( '', $plugin = $file ) );
		$this->plugin_path = trailingslashit( dirname( $file ) );
                $this->plugin_name = basename( dirname( __FILE__ ) );
		
		                
		
                if(version_compare( BP_VERSION, '2.0.2', '<=' )) {
                    // Add new image field type
                    add_filter( 'xprofile_field_types', array( $this, 'bpxp_image_field_add_field_type') );
                    
                    // Render the image field on the admin panel
                    add_filter( 'xprofile_admin_field', array( $this, 'bpxp_image_field_admin_render_field_type') );
                    
                    // Render the image field on the edit front-end
                    add_action( 'bp_custom_profile_edit_fields', array( $this, 'bpxp_image_field_edit_render_field') );
                }
                else {
                    // Add new image field type
                    add_filter( 'bp_xprofile_get_field_types', array( $this, 'bpxp_image_field_add_field_type') );
                }
                
                
                if(version_compare( BP_VERSION, '2.1.0', '<' )) {
                    // Display the image on the front-end
                    add_filter( 'bp_get_the_profile_field_value', array( $this, 'bpxp_image_field_frontend_render'), 10, 3 );
                }
                      
                                
                // take over the rendering of the frontend profile edit screen in order to handle the image field
                add_action( 'bp_actions', array( $this, 'bpxp_image_field_override_xprofile_screen_edit_profile'), 10 );
                
                // save image fields during user signup process
                add_action( 'bp_core_signup_user', array( $this, 'bpxp_image_field_save_on_signup'), 10, 5 );
                
                $script_hook = 'wp_enqueue_scripts';
                if(is_admin()) {
                    
                    if(version_compare( BP_VERSION, '2.0.2', '>' )) {
                        // take over the rendering of the admin profile edit screen in order to handle the image field
                        add_action( 'bp_members_admin_update_user', array( $this, 'bpxp_image_field_save_on_admin_edit_profile'), 8, 4 );
                    }
                    
                    $script_hook = 'admin_enqueue_scripts';
                }
                
                // load javascript
                add_action( $script_hook, array( $this, 'bpxp_image_field_load_js') );
                
                // load css
                add_action( $script_hook, array( $this, 'bpxp_image_field_load_css') );
		
                // Language support
                add_action( 'init', array( $this, 'bpxp_image_field_l10n') );
		
	}
        
        
        function bpxp_image_field_load_css() {
            wp_enqueue_style( 'bpxp_image_field-css', $this->plugin_url . 'css/bp-xp-img-fld.css', array( ), '1.0.0' );
        }
        
        
        function bpxp_image_field_load_js() {
            wp_enqueue_script( 'version_compare-js', $this->plugin_url . 'js/version_compare.js', array( ), '0.0.3' );
            wp_enqueue_script( 'bpxp_image_field-js', $this->plugin_url . 'js/bp-xp-img-fld.js', array( 'jquery', 'version_compare-js' ), '1.4.0' );
            $bp_version = BP_VERSION;
            $bpxp_l10n = array('imageOptionLabel' => __( 'Image', 'bp-xprofile-image-field' ),
                                'customFieldsLabel' => __( 'Custom Fields', 'bp-xprofile-image-field' ),
                                'bpVersion' => "{$bp_version}");
            wp_localize_script( 'bpxp_image_field-js', 'bpxpL10n', $bpxp_l10n );
        }
        
        function bpxp_image_field_l10n(){
            load_plugin_textdomain('bp-xprofile-image-field', false, $this->plugin_name . '/lang');
        }
        
        function bpxp_image_field_add_field_type($field_types){
            if(version_compare( BP_VERSION, '2.0.2', '<=' )) {
                $image_field_type = array(BP_XPROFILE_IMAGE_FIELD);
            }
            else {
                $image_field_type = array(BP_XPROFILE_IMAGE_FIELD => 'BP_XProfile_Field_Type_Image');
            }
            
            $field_types = array_merge($field_types, $image_field_type);
            return $field_types;
        }

        
        function bpxp_image_field_admin_render_field_type($field, $echo = true){

            do_action('bpxp_image_field_before_admin_render');
            
            ob_start();
                switch ( $field->type ) {
                    case BP_XPROFILE_IMAGE_FIELD:
                        ?>
                            <input type="file" name="<?php bp_the_profile_field_input_name() ?>" id="<?php bp_the_profile_field_input_name() ?>" value="" />
                        <?php
                        break;    
                    default :
                        ?>
                            <p><?php _e('Field type unrecognized', 'bp-xprofile-image-field'); ?></p>
                        <?php
                }

                $output = ob_get_contents();
            ob_end_clean();
            
            do_action('bpxp_image_field_after_admin_render', $output);

            if($echo){
                echo $output;
                return;
            }
            else{
                return $output;
            }

        }

        
        function bpxp_image_field_frontend_render($field_value, $field_type, $field_id){

            if ( $field_type == BP_XPROFILE_IMAGE_FIELD ){
                global $field;
                
                $raw_field_value = bp_unserialize_profile_field( $field->data->value );
                $bpxp_field_value = WP_CONTENT_URL . $raw_field_value;
                $bpxp_field_value = "<img src=\"{$bpxp_field_value}\" alt=\"" . __('image', 'bp-xprofile-image-field') . "\" />";
                
                $field_value = apply_filters('bpxp_image_field_frontend_field_value', $bpxp_field_value, $field_value, $raw_field_value, $field_type, $field_id);
            }
            
            return $field_value;

        }
        
        

        function bpxp_image_field_edit_render_field($echo = true){

            if ( bp_get_the_profile_field_type() == BP_XPROFILE_IMAGE_FIELD ){
                
                if(empty ($echo)){
                    $echo = true;
                }
                
                do_action('bpxp_image_field_before_edit_render');

                ob_start();
                    $field_id = bp_get_the_profile_field_id();
                    $image_field_input_name = bp_get_the_profile_field_input_name();
                    $field_name_hidden = 'field_' . $field_id . '_hidden';
                    $field_name_delete = 'field_' . $field_id . '_delete';
                    $image_id = 'bpxp_image_' . $field_id;
                    $image = bp_get_the_profile_field_edit_value();
                    $image_link = WP_CONTENT_URL . $image;

                ?>
                        <label for="<?php bp_the_profile_field_input_name(); ?>"><?php bp_the_profile_field_name(); ?> <?php if ( bp_get_the_profile_field_is_required() ) : ?><?php _e( '(required)', 'bp-xprofile-image-field' ); ?><?php endif; ?></label>
                        <input type="file" name="<?php echo $image_field_input_name; ?>" id="<?php echo $image_field_input_name; ?>" value="" <?php if ( bp_get_the_profile_field_is_required() ) : ?>aria-required="true"<?php endif; ?>/>
                        <input type="hidden" name="<?php echo $field_name_hidden; ?>" id="<?php echo $field_name_hidden; ?>" value="<?php echo bp_get_the_profile_field_edit_value(); ?>" />
                <?php
                    if(!empty($image)) {
                ?>    
                        <input type="hidden" name="<?php echo $field_name_delete; ?>" id="<?php echo $field_name_delete; ?>" value="" />
                        <img src="<?php echo $image_link; ?>" alt="<?php bp_the_profile_field_name(); ?>" id="<?php echo $image_id; ?>" />
                        <a href="#" data-delete_id="<?php echo $field_name_delete; ?>" data-image_id="<?php echo $image_id; ?>" class="rtd-button delete-icon"><?php _e('Delete', 'bp-xprofile-image-field'); ?></a>

                <?php
                    }
                    $output = ob_get_contents();
                ob_end_clean();

                do_action('bpxp_image_field_after_edit_render', $output);
                
                
                if($echo){
                    echo $output;
                    return;
                }
                else{
                    return $output;
                }
                
            }

        }

        //save profile images during edit in admin
        function bpxp_image_field_save_on_admin_edit_profile($doaction = '', $user_id = 0, $request = array(), $redirect_to = ''){

            if ( isset( $_POST['field_ids'] ) ) {
                $action = 'edit-bp-profile_' . $user_id;
                if(wp_verify_nonce( $_POST['_wpnonce'], $action )){
                    global $bp;
                    
                    $bp->bpxp_image_field = new stdClass(); 
                    $bp->bpxp_image_field->user_id = $user_id;
                    
                    $posted_field_ids = explode(',', join( ',', $_POST['field_ids'] ));
                    $this->bpxp_image_field_save($posted_field_ids);

                    do_action( 'bpxp_image_field_save_on_admin_edit_profile', $doaction, $user_id, $request, $redirect_to );
                }
            }

        }
        

        // Override default action hook in order to support images
        function bpxp_image_field_override_xprofile_screen_edit_profile(){
            $screen_edit_profile_priority = has_filter('bp_screens', 'xprofile_screen_edit_profile');

            if($screen_edit_profile_priority !== false){
                
                if ( isset( $_POST['field_ids'] ) ) { //only override during post
                    //Remove the default profile_edit handler
                    remove_action( 'bp_screens', 'xprofile_screen_edit_profile', $screen_edit_profile_priority );

                    //Install replalcement hook
                    add_action( 'bp_screens', array( $this, 'bpxp_image_field_save_on_edit'), $screen_edit_profile_priority );
                }
            }
            
        }

        //save profile images during edit
        function bpxp_image_field_save_on_edit($trigger_xprofile_edit = true){

            if ( isset( $_POST['field_ids'] ) ) {
                if($trigger_xprofile_edit === "") {
                    $trigger_xprofile_edit = true;
                }
                
                if(wp_verify_nonce( $_POST['_wpnonce'], 'bp_xprofile_edit' )){
                    global $bp;
                    
                    $bp->bpxp_image_field = new stdClass(); 
                    $bp->bpxp_image_field->user_id = $bp->displayed_user->id;
                    
                    $posted_field_ids = explode( ',', $_POST['field_ids'] );
                    $this->bpxp_image_field_save($posted_field_ids);

                }
            }
            
            do_action( 'bpxp_image_field_save_on_edit', $bp->displayed_user->id );

            if($trigger_xprofile_edit){
                if(function_exists('xprofile_screen_edit_profile')){
                    xprofile_screen_edit_profile();
                }
            }

        }

        // save image fields during user sign-up
        // NOTE: 1. bp_core_signup_blog() registration branch is not supported since it does not return a user_id.
        //       2. The image upload functionality assumes that BP allows the creation of unactivated user accounts
        function bpxp_image_field_save_on_signup($user_id, $user_login, $user_password, $user_email, $usermeta){
            global $bp;
             
            $bp->bpxp_image_field = new stdClass(); 
            $bp->bpxp_image_field->user_id = $user_id;
            $posted_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );
            
            $this->bpxp_image_field_save($posted_field_ids);
            
            
            foreach ( (array)$posted_field_ids as $field_id ) {
                $field_name = 'field_' . $field_id;

                if ( isset( $_FILES[$field_name] ) ) {
                    if ( empty( $_POST[$field_name] ) ) {
                        // $file_name = $_FILES[$field_name]['name'];
                        // $error_message = sprintf( __( 'The image %1$s was not uploaded.', 'bp-xprofile-image-field' ), $file_name );
                        // bp_core_add_message( $error_message, 'error' );
                    }
                    else {
                        $image_path = $_POST[$field_name];
                        xprofile_set_field_data( $field_id, $user_id, $image_path );
                        $usermeta[$field_name] = $image_path;
                    }
                }
            }
            
            do_action( 'bpxp_image_field_save_on_signup', $user_id, $user_login, $user_password, $user_email, $usermeta );
        }
        
        
        // save profile images
        protected function bpxp_image_field_save($posted_field_ids) {
            
            $post_action_found = false;
            $post_action = '';
            if (isset($_POST['action'])){
                $post_action_found = true;

                $post_action = apply_filters('bpxp_image_field_preserve_post_action', $_POST['action']);

            }

            foreach ( (array)$posted_field_ids as $field_id ) {
                $field_name = 'field_' . $field_id;

                if ( isset( $_FILES[$field_name] ) ) {
                    if($_FILES[$field_name]['size'] > 0){
                        require_once( ABSPATH . '/wp-admin/includes/file.php' );

                        $uploaded_file = $_FILES[$field_name]['tmp_name'];

                        // Filter the upload location
                        add_filter( 'upload_dir', array( $this, 'bpxp_image_field_profile_upload_dir'), 10, 1 );

                        //ensure WP accepts the upload job
                        $_POST['action'] = 'wp_handle_upload';

                        $extension_pos = strrpos($_FILES[$field_name]['name'], '.'); // find position of the last dot, so where the extension starts
                        $_FILES[$field_name]['name'] = substr($_FILES[$field_name]['name'], 0, $extension_pos) . '-bpfull' . substr($_FILES[$field_name]['name'], $extension_pos);

                        $wp_uploaded_file = wp_handle_upload( $_FILES[$field_name] );

                        $db_uploaded_file = str_replace(WP_CONTENT_URL, '', $wp_uploaded_file['url']) ;

                        $uploaded_file = apply_filters('bpxp_image_field_image_uploaded', $db_uploaded_file, $wp_uploaded_file);

                        $_POST[$field_name] = $uploaded_file;
                    }
                    else{
                        $field_name_hidden = 'field_' . $field_id . '_hidden';
                        if ( isset( $_POST[$field_name_hidden] ) ) {
                            $field_name_delete = 'field_' . $field_id . '_delete';
                            if ( isset( $_POST[$field_name_delete] ) && $_POST[$field_name_delete] == 'deleted') {
                                $_POST[$field_name] = '';
                            }
                            else {
                                $_POST[$field_name] = $_POST[$field_name_hidden];
                            }
                        }
                    }

                }

            }

            if($post_action_found){
                $_POST['action'] = apply_filters('bpxp_image_field_restore_post_action', $post_action);
            }
            else{
                unset($_POST['action']);
            }
        }
        

        function bpxp_image_field_profile_upload_dir( $upload_dir ) {
            global $bp;

            $original_upload_dir = $upload_dir;
            $user_id = $bp->bpxp_image_field->user_id;
            $profile_subdir = '/avatars/' . $user_id;

            $upload_dir['path'] = $upload_dir['basedir'] . $profile_subdir;
            $upload_dir['url'] = $upload_dir['baseurl'] . $profile_subdir;
            $upload_dir['subdir'] = $profile_subdir;

            $upload_dir = apply_filters('bpxp_image_field_upload_dir', $upload_dir, $original_upload_dir, $user_id);
            
            return $upload_dir;
        }
        
    } // end BP_XProfile_Image_Field Class
}


function bpxp_image_field_error_wordpress_version() {
    global $wp_version;
    
    echo '<div class="error"><p>' . sprintf( __( 'Please upgrade WordPress to version 3.2.1 or later. This plugin may not work properly on version %1$s.', 'bp-xprofile-image-field' ), $wp_version ) . '</p></div>';
}

function bpxp_image_field_error_missing_xprofile() {
    
    echo '<div class="error"><p>' . sprintf( __( 'Please ensure you are running BuddyPress 1.5 or later and %1$s BuddyPress Extended Profiles Component %2$s is activated in order for this plugin to work.', 'bp-xprofile-image-field' ), '<a href="' . admin_url( 'options-general.php?page=bp-components' ) . '">', '</a>' ) . '</p></div>';
}


   /**
    * Creates the single BP_XProfile_Image_Field instance.
    *
    * 
    * 
    * @return BP_XProfile_Image_Field 
    * 
    */
function bpxp_image_field() {
    
    return BP_XProfile_Image_Field::instance();
}

function bpxp_image_field_init() {
    global $wp_version;

    if ( !version_compare( $wp_version, '3.2.1', '>=' ) ) {
        add_action( 'all_admin_notices', 'bpxp_image_field_error_wordpress_version' );
    } 
    elseif ( class_exists( 'BP_XProfile_Component') && version_compare( BP_VERSION, '1.5', '>=' ) ) {
        
        do_action('bpxp_image_field_before_load');
            bpxp_image_field(); 
        do_action('bpxp_image_field_after_load', bpxp_image_field());
        
        define( 'BPXP_IMAGE_FIELD_IS_LOADED', 1 );
        
    } 
    else {
        add_action( 'all_admin_notices', 'bpxp_image_field_error_missing_xprofile' );
    }
}

add_action( 'bp_xprofile_includes', 'bpxp_image_field_init' ); // Ensures it's only loaded if XProfile is active