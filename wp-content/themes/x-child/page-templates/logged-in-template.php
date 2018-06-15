<?php 
    /*
    Template Name: Logged-In Users Page
    http://fotan.us/require-login-for-single-wordpress-page/
    */
?>

<?php get_header(); ?>

<div class="x-container max width offset">
    <div class="x-main full" role="main">

<?php if ( is_user_logged_in() ) : ?>

        <?php while ( have_posts() ) : the_post(); ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
              <div class="entry-wrap">
                <?php x_get_view( 'global', '_content', 'the-content' ); ?>
              </div>
            </article>

        <?php endwhile; ?>

<?php else : ?>
    <div class="entry-wrap center-text">
        <p>To view this page, you must first <a href='<?php echo wp_login_url(get_permalink()) ?>' title='Login'>log in</a></p>
    </div>
<?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>