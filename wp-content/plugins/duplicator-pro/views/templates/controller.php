<?php
defined("ABSPATH") or die("");

DUP_PRO_U::hasCapability('export');

global $wpdb;

//COMMON HEADER DISPLAY
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/assets/js/javascript.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/views/inc.header.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/ui/class.ui.dialog.php');

$nonce = wp_create_nonce('duplicator_pro_download_package_file');
?>

<script>
    jQuery(document).ready(function($)
	{

    });
</script>

<div class="wrap">
    <?php 
		duplicator_pro_header(DUP_PRO_U::__("Templates"));
		include('template.controller.php');
    ?>
</div>