<?php
/**
 * Template Name: Group Session Response Template
 *
 * @package WordPress
 */
?>
<!-- See ./wp-content/themes/x/framework/views/integrity/template-blank-1.php for reference to below HTML -->
<?php get_header(); ?>

<?php
    if(empty($_GET["key"]) || empty($_GET["status"]))
    {
      wp_redirect( home_url(), 301 ); 
      exit;
    }

    global $wpdb;

    $CLIENT_GROUP_SESSIONS_TABLE = $wpdb->prefix . 'client_group_sessions';

    $key = $_GET["key"];
    $status = $_GET["status"];

    $member = $wpdb->get_results($wpdb->prepare("SELECT * FROM $CLIENT_GROUP_SESSIONS_TABLE WHERE key_code = %s", $key));

    if(empty($member))
    {
      wp_redirect( home_url(), 301 ); 
      exit;
    }

    $appointment = get_wc_appointment($member[0]->appointment_id);
    $client = $appointment->get_customer();
    $user_data = get_userdata($client->user_id);

    
?>
  <div class="x-container max width offset">
    <div class="x-main full" role="main">
      <?php while ( have_posts() ) : the_post(); ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
          <div class="entry-wrap" style="width: 70%;margin: auto;">
            <?php if($status == 'decline') :?>
              <?php echo do_shortcode('[gravityform id=10 title=false]');?>
            <?php else: ?>
              <?php if(!email_exists($member[0]->email)) :?>
                <?php echo do_shortcode('[gravityform id=2 title=false]');?>
              <?php else: ?>
                <h3 class="my-headings" style="margin-top: 0px;">Thanks for Accepting</h3>
                <?php if(!is_user_logged_in()) :?>
                  <h5 class="my-headings">Please login to your account to view your current appointments</h5>
                <?php else: ?>
                  <h5 class="my-headings">Go to home to view upcoming appointments</h5>
                <?php endif?>
                <?php
                  $data = array( 'status' => 'accepted');
                  $where = array('key_code'=>$key);
                  $updated = $wpdb->update( $CLIENT_GROUP_SESSIONS_TABLE, $data, $where ); //update the status to accepted

                  $subject = "[Neo] Invitation Accepted";
                  $heading = "Invitation has been declined";
                  $message = '<p>Your Inviation has been Accepted by '.$member[0]->email;
                  send_email_woocommerce_style($user_data->user_email, $subject, $heading, $message); //this function is defined inside functions/woocommerce.php
                ?>
              <?php endif?>
            <?php endif?>
          </div>
        </article>

      <?php endwhile; ?>

    </div>
  </div>

<?php get_footer(); ?>