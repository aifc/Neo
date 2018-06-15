<?php
/**
 * Template Name: User Edit Template
 *
 * @package WordPress
 */
?>
<?php


get_header(); 
?>
<div class="x-container max width offset">
    <div class="x-main full" role="main">

      	<?php while ( have_posts() ) : the_post(); ?>

	        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	          	<div class="entry-wrap">
	          		<div id="buddypress">
	          			<div id="item-body" role="main">
			          		<div class="x-item-list-tabs-subnav item-list-tabs no-ajax" id="subnav" role="navigation">
			          			<ul>
				          			<?php 
				          				$selected_item = 'edit';
				          				$bp = buddypress();
				          				$secondary_nav_items = $bp->members->nav->get_secondary( array( 'parent_slug' => 'profile' ) );
				          				foreach ( $secondary_nav_items as $subnav_item ) { 
						        			if($subnav_item->slug === 'public') continue;
						        			if($subnav_item->slug === 'edit') {
						        				$subnav_item->link = get_site_url().'/edit-user';
						        			}
									        if ( $subnav_item->slug === $selected_item ) { 
									            $selected = ' class="current selected"'; 
									        } else { 
									            $selected = ''; 
									        } 
						 					
									        echo '<li id="' . esc_attr( $subnav_item->css_id . '-' . $list_type . '-li' ) . '" ' . $selected . '><a id="' . esc_attr( $subnav_item->css_id ) . '" href="' . esc_url( $subnav_item->link ) . '">' . $subnav_item->name . '</a></li>'; 
									    }
			          				?>
		          				</ul>
		          			</div><!-- .item-list-tabs -->
		          			<h4>Edit Profile</h4>
		            		<?php x_get_view( 'global', '_content', 'the-content' ); ?>
		            		<?php echo do_shortcode('[gravityform id=14 title=false description=false]');?>
		            	</div>
	            	</div>
	          	</div>
	        </article>

      	<?php endwhile; ?>

    </div>
</div>

<?php get_footer(); ?>

<style type="text/css">
.x-item-list-tabs-subnav {
    margin: 0 0 15px;
    text-align: center;
}
.item-list-tabs ul {
    list-style: none;
}
.item-list-tabs ul li {
    display: inline-block;
}
.x-item-list-tabs-subnav ul li a {
    margin: 0 10px;
    color: rgba(0,0,0,0.35);
}
.x-item-list-tabs-subnav > ul > li > a:hover,.x-item-list-tabs-subnav > ul > li.current > a {
	color: rgb(1,132,204);
}
ul .gfield_radio {
    border: none;
}
</style>