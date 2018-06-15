<?php
/**
 * Template Name: Directory Template
 *
 * @package WordPress
 */
?>
<!-- See ./wp-content/themes/x/framework/views/integrity/template-blank-1.php for reference to below HTML -->
<?php get_header(); ?>

<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.7/angular.min.js"></script>

<?php wp_enqueue_script( 'directory', '/wp-content/plugins/directory-frontend/js/directory.js' ); ?>

<?php echo do_shortcode('[bps_display form=258]'); ?>

<div class="x-container max width offset" ng-app="directory" ng-controller="DirectoryController as vm">
    <div class="x-main full" role="main">

    <?php while ( have_posts() ) : the_post(); ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <div class="entry-wrap directory">
              <?php x_get_view( 'global', '_content', 'the-content' ); ?>
            </div>
        </article>

    <?php endwhile; ?>

    </div>
</div>

<?php get_footer(); ?>
