(function ($) {
    'use strict';

    /**
     * All of the code for your public-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */


    $.fn.isExternal = function () {

        var host = new RegExp('/' + window.location.host + '/');
        var link = 'http://' + window.location.host + this.attr('href');
        return !host.test(link);

    };

    $(function () {

        // Detect windows width function
        var $window = $(window);

        /**
         * Close modal functionality
         */

        // when clicking on close button
        $(document).on('click', '.close-modal', function () {
            $('.modal-wrapper').removeClass('show').hide();
            $('.modal').removeClass('show');
            $('#modal-content').html('');
        });

        // when clicking outside of modal
        $(window).on('click', function () {
            $('.modal-wrapper').removeClass('show').hide();
            $('.modal').removeClass('show');
            $('#modal-content').html('');
        });

        $(document).on('click', '.modal', function (e) {
            e.stopPropagation();
        });

        function checkWidth() {
            var windowsize = $window.width();

            // if the window is greater than 767px wide then do below. we don't want the modal to show on mobile devices and instead the link will be followed.
            if (windowsize >= fromPHP.breakpoint) {
                $('body').on('click', '.modal-link', function (e) {

                    // Define variables
                    var modalContent = $('#modal-content');
                    var $this = ($(this).attr('href') != null) ? $(this) : $(this).children('a').first();
                    var postLink = $this.attr('href');
                    var postUrl = $this[0].pathname.substring(1);
                    var lastSegment = postLink.split('/').pop();
                    var combined = postUrl + lastSegment;
                    var dataDivID = ' #' + $this.attr('data-div');
                    var loader = '<img class="loading" src="' + fromPHP.pluginUrl + '/images/loading.gif" />';

                    // prevent link from being followed
                    e.preventDefault();

                    // display loading animation or in this case static content
                    if (fromPHP.styled) {
                        modalContent.html(loader);
                    }

                    // Load content from internal
                  
                        $.ajax({
                            url: '/wp-json/wp-post-modal/v1/any-post-type?slug=' + combined,
                            success: function (data) {
                                var page = data;
                                modalContent.html('<iframe style="border: 0px; " src="http://' + window.location.hostname +"/"+ combined + '" width="100%" onload="resizeIframe(this)" ></iframe>');
                            },
                            cache: false
                        });
                    

                    // show class to display the previously hidden modal
                    $('.modal-wrapper').slideDown('slow', function () {
                        $(this).addClass('show');
                        $('.modal').addClass('show');
                    });

                    return false;
                });
            }
        }

        checkWidth();
        $(window).resize(checkWidth);
    });

    // Suppress modal link redirect in WP Customizer
    function modalCustomizer() {
        if (wp.customize) {
            var body = $('body');
            body.off('click.preview');

            body.on('click.preview', 'a[href]:not(.modal-link)', function (e) {
                var link = $(this);
                e.preventDefault();
                wp.customize.preview.send('scroll', 0);
                wp.customize.preview.send('url', link.prop('href'));
            });
        }
    }

    $(window).on('load', function () {
        modalCustomizer();
    });

})(jQuery);
