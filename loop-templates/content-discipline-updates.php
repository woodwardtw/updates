<?php
/**
 * Partial template for content in discipline-updates.php
 *
 * @package Understrap
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<article <?php post_class(); ?> id="post-<?php the_ID(); ?>">

	<header class="entry-header">

		<?php 
            $discipline = get_query_var( 'discipline' ) . ' ';
            the_title( '<h1 class="entry-title">' . $discipline, '</h1>' ); ?>

	</header><!-- .entry-header -->

	<?php echo get_the_post_thumbnail( $post->ID, 'large' ); ?>

	<div class="entry-content">

		<?php
        $current_date = current_time( 'M Y' );
        if ( $discipline ) {
            echo '<p>Showing updates in ' . esc_html( $discipline ) . ' added during ' . esc_html( $current_date ) . '.</p>';
            $discipline_query = new WP_Query( array(
                'post_type' => 'update',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'discipline',
                        'field'    => 'slug',
                        'terms'    => $discipline,
                    ),
                ),
                'date_query' => array(
                    array(
                        'year'  => date( 'Y' ),
                        'month' => date( 'm' ),
                    ),
                ),
            ) );
        }

        foreach ( $discipline_query->posts as $post ) {
            $post_id = $post->ID;
            $url = get_permalink($post_id);
            $title = get_the_title($post_id);
            $excerpt =  wp_trim_words( get_the_content( null, false, $post_id ), 55, '&hellip;' );
          echo "<div class='update-item'>
                <h2><a href='{$url}'>{$title}</a></h2>
                <div class='update-excerpt'>{$excerpt}</div>
            </div>";
        }
        wp_reset_postdata();

		//the_content();
		understrap_link_pages();
		?>

	</div><!-- .entry-content -->

	<footer class="entry-footer">

		<?php understrap_edit_post_link(); ?>

	</footer><!-- .entry-footer -->

</article><!-- #post-## -->
