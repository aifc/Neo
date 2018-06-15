<?php
/**
 * Template Name: Home
 *
 * @package WordPress
 */
?>
<!-- See ./wp-content/themes/x/framework/views/integrity/template-blank-1.php for reference to below HTML -->
<?php get_header(); ?>

<div class="x-main full" role="main">
    <article id="post-27" class="post-27 page type-page status-publish hentry no-post-thumbnail">
        <div class="entry-content content">
            <div id="cs-content" class="cs-content cs-editor-active">
                <div data-element="section" class="x-section  bg-color" id="x-section-1">
                    <div data-element="row" class="x-container max width">
                    <?php $user=wp_get_current_user(); if ( in_array( 'shop_staff', (array) $user->roles ) || in_array( 'administrator', (array) $user->roles ) ) : ?>
                        <div data-element="column" class="x-column x-sm x-1-3">
                                <div data-element="gap" class="cs-preview-element-wrapper cs-invisible">
                                    <div class="cs-empty-element cs-gap cs-hide-xl cs-hide-lg cs-hide-md"></div>
                                </div>
                                <div data-element="promo" class="cs-preview-element-wrapper">
                                  <div class="x-promo man">
                                      <div class="x-promo-image-wrap">
                                          <img src="http://placehold.it/750x350/3498db/2980b9" alt="Placeholder">
                                      </div>
                                      <div class="x-promo-content">
                                        <a href="http://mychristiancounsellor.org.au/wp-admin/post.php?post=7&action=edit">
                                            <h3 class="h4 man">Manage Calendar</h3>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div data-element="column" class="x-column x-sm x-1-3">
                        <div data-element="promo" class="cs-preview-element-wrapper">
                            <div class="x-promo man">
                                <div class="x-promo-image-wrap">
                                    <img src="http://placehold.it/750x350/3498db/2980b9" alt="Placeholder">
                                </div>
                                <div class="x-promo-content">
                                    <a href="http://mychristiancounsellor.org.au/woocommerce-account-page/appointments/">
                                        <h3 class="h4 man">Your Appointments</h3>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div data-element="gap" class="cs-preview-element-wrapper cs-invisible">
                            <div class="cs-empty-element cs-gap cs-hide-xl cs-hide-lg cs-hide-md"></div>
                        </div>
                    </div>
                    <div data-element="column" class="x-column x-sm x-1-3">
                        <div data-element="promo" class="cs-preview-element-wrapper">
                            <div class="x-promo man">
                                <div class="x-promo-image-wrap">
                                    <img src="http://placehold.it/750x350/3498db/2980b9" alt="Placeholder">
                                </div>
                                <div class="x-promo-content">
                                    <a href="http://mychristiancounsellor.org.au/product/test-psychologist-appointable-availability/">
                                        <h3 class="h4 man">Book Appointment</h3>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="cs-indicator-container">
            </div>
            <div class="cs-observer" style="top: 0px; left: 0px; width: 1432px; height: 441px; display: none;">
                <div class="cs-observer-tooltip left">Section – 3 Features</div>
            </div>
        </div>
    </div>
</article>
</div>

<?php get_footer(); ?>
