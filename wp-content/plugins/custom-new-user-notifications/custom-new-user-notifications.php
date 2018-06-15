<?php
/**
 * Plugin Name: Custom New User Notifications
 * Description: Customize the "New User Registration" and "Your username and password" notifications
 * Version: 1.0
 * Author: Ben Pearson
 * Author URI: http://benpearson.com.au
 * http://benpearson.com.au/customizing-the-new-user-registration-and-your-username-and-password-email-notifications-sent-by-wordpress/
 */

if ( !function_exists('wp_new_user_notification') ) :
/**
 * Notify the blog admin of a new user, normally via email.
 *
 * @since 2.0
 *
 * @param int $user_id User ID
 * @param string $plaintext_pass Optional. The user's plaintext password
 */
function wp_new_user_notification($user_id, $plaintext_pass = '') {
    $user = get_userdata( $user_id );

    // The blogname option is escaped with esc_html on the way into the database in sanitize_option
    // we want to reverse this for the plain text arena of emails.
    $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

    $message  = sprintf(__('New user registration on your site %s:'), $blogname) . "\r\n\r\n";
    $message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
    $message .= sprintf(__('E-mail: %s'), $user->user_email) . "\r\n";

    @wp_mail(get_option('admin_email'), sprintf(__('[%s] New User Registration'), $blogname), $message);

    if ( empty($plaintext_pass) )
        return;

    $message  = sprintf(__('Username: %s'), $user->user_login) . "\r\n";
    $message .= sprintf(__('Password: %s'), $plaintext_pass) . "\r\n";
    $message .= wp_login_url() . "\r\n";

    wp_mail($user->user_email, sprintf(__('[%s] Your username and password'), $blogname), $message);

}
endif;
?>
