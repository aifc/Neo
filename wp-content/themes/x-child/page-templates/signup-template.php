<?php
/**
 * Template Name: Signup Template
 *
 * @package WordPress
 */
?>
<?php


get_header(); 
?>
<div class="x-container max width offset">
  	<h3 class="my-headings">Signup</h3>
  	<p class = "paragraph-uncontained" style="margin: 2px 0 2.313em;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas at faucibus neque.</p>
	<div class="x-main full" role="main">
		<div id="signup-page">
			<div class="entry-wrap">
				<div id="signup-as-client">
					<div class="submit">
						<input type="submit" onclick="location.href = 'register';" value="Client">
					</div>
				</div>
			</div>
			<div class="entry-wrap">
				<div id="signup-as-counsellor">
					<div class="submit">
						<input type="submit" onclick="location.href= 'register/?role=counselor';" value="Counsellor">
					</div>
				</div>
			</div>
		</div><!--end post-appointment-page-->
	</div>
</div>
<?php get_footer(); ?>

<style type="text/css">

#signup-page .entry-wrap {
	text-align: center;
    margin: 20px;
}
@media (min-width: 979px) {
	#signup-page {
	    display: flex;
	}

	#signup-page .entry-wrap {
	    flex: 50%;
	}
}
</style>