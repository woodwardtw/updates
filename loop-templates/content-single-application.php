<?php
/**
 * Single application partial template
 *
 * @package Understrap
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<article <?php post_class(); ?> id="post-<?php the_ID(); ?>">

	<header class="entry-header">

		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>

		<div class="entry-meta">
			<div class="created">Created: <?php echo get_the_date(); ?></div>
			<div class="modified">Modified: <?php echo get_the_modified_date(); ?></div>

		</div><!-- .entry-meta -->

	</header><!-- .entry-header -->

	<?php echo get_the_post_thumbnail( $post->ID, 'large' ); ?>

	<div class="entry-content row">
		<div class="purpose col-md-8">			
			<?php dlinq_update_generic_text('what_does_it_do','Please give me purpose.');?>
		</div>
		<div class="col-md-4">
			<div class="cost">
				<?php dlinq_update_generic_text('cost','I need a cost.');?>
			</div>
			<div class="renewal">
				<?php dlinq_update_generic_text('renewal_date','I need a date.');?>
			</div>
		</div>
		<div class="col-md-12">
			<div class="data-block">
				<?php
					dlinq_update_data();
				?>
			</div>
		</div>		
		<div class="col-md-4">
			<div class="license">
				<?php dlinq_update_generic_text('license_details','I need to be bounded.');?>
				<?php dlinq_update_big_audience();?>
			</div>
		</div>	
		<div class="col-md-4">
			<div class="vendor">
				<h2>Vendor</h2>
				<?php dlinq_update_vendor_details();?>
			</div>
		</div>
		<div class="col-md-4">
			<div class="category">
				<h2>Category</h2>
				<?php dlinq_update_software_cat();?>
			</div>
		</div>
		<div class="col-md-4">
			<div class="users">
				<?php dlinq_update_generic_text('primary_users','I need to be associated with someone.');?>
			</div>
		</div>						
		<div class="col-md-12">
			<div class="update-box">
				<h2>Updates</h2>
				<div class="updates">
					<?php dlinq_update_app_updates();?>
				</div>
			</div>
		</div>
		<?php
		// the_content();
		// understrap_link_pages();
		?>

	</div><!-- .entry-content -->

	<footer class="entry-footer">

		<?php understrap_entry_footer(); ?>

	</footer><!-- .entry-footer -->

</article><!-- #post-## -->
