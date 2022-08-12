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

			<?php understrap_posted_on(); ?>

		</div><!-- .entry-meta -->

	</header><!-- .entry-header -->

	<?php echo get_the_post_thumbnail( $post->ID, 'large' ); ?>

	<div class="entry-content row">
		<div class="purpose col-md-12">
			<h2>Purpose</h2>
			<?php dlinq_update_generic_text('what_does_it_do','Please give me purpose.');?>
		</div>

		<div class="col-md-3">
			<div class="users">
				<h2>Primary Users</h2>
				<?php dlinq_update_generic_text('primary_users','I need to be associated with someone.');?>
			</div>
		</div>
		<div class="col-md-3">
			<div class="license">
				<h2>License Details</h2>
				<?php dlinq_update_generic_text('license_details','I need to be boundaries.');?>
			</div>
		</div>
		<div class="col-md-8 offset-md-2">
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
