<?php

// =============================================================================
// TEMPLATE NAME: Members popout
// -----------------------------------------------------------------------------
// A blank page for creating unique layouts.
// =============================================================================

?>
<?php x_get_view( 'global', '_header' ); ?>

<?php x_get_view( 'global', '_slider-above' ); ?>
<?php x_get_view( 'global', '_slider-below' ); ?>


<?php 
	$user_slug = $_GET["link"];
	$user = get_user_by('slug',$user_slug);
	$user_id = $user->ID;
?>

<div class="x-container max width offset">
    <div class="x-main full" role="main">
		<div class="entry-wrap">
			<div class="leftCollapsableColumn">
	            <!-- <?php echo bp_core_fetch_avatar( array( 'item_id' => $user_id, 'type' => 'full' ) ) ?> -->
	            <h1 style="margin: 0;"><?php echo xprofile_get_field_data( 'Name', $user_id ); ?></h1>
	            <?php echo xprofile_get_field_data( 'Profile Picture', $user_id ); ?>
	        </div>
	        <div class="rightCollapsableColumn">
	            <h2>About Me</h2>
	            <p><?php echo xprofile_get_field_data( 'About Me', $user_id ); ?></p>
	        </div>
	        <div class="x-video embed clear">
	            <!-- <div class="x-video-inner">
	                <iframe src="<?php echo "https://www.youtube.com/embed/" . xprofile_get_field_data( 'Video Code', $user_id) . "?modestbranding=1&autohide=1&showinfo=0" ?>"
	                width="300" height="150" frameborder="0" wmode="Opaque"></iframe>
	            </div> -->
	        </div> 
	        <div class="expertise">
	            <h2>My fields of expertise</h2>
	            <ul>
	            <?php 
	            foreach (xprofile_get_field_data( 'Counselling Specialities', $user_id ) as $specialty) {
	                echo '<li>'.$specialty.'</li>'; // In the future can search on these terms through http://mychristiancounsellor.org.au/members/?members_search=Anger+management
	            }
	            ?>
	            </ul>
	        </div>		
		</div>
	</div>
</div>


<?php x_get_view( 'global', '_footer', 'scroll-top' ); ?>

<?php x_get_view( 'global', '_footer' ); ?>

