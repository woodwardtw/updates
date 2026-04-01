<?php
/**
 * Template for the facet WP browser list
 *
 * @package Understrap
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<?php if ( have_posts() ) : ?>
  <?php while ( have_posts() ): the_post(); ?>
  <?php 
        $theme = get_the_terms( $post_id, 'theme' );
        $spotlight = is_array( $theme ) && in_array( 'faculty-spotlight', array_column( $theme, 'slug' ), true );
        $spotlight_class = '';
        if ( $spotlight ) {
            $spotlight_class = 'spotlight';
        }
  
  ?>
  <div class="entry-content <?php echo $spotlight_class; ?>">
    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
    <?php  $excerpt = has_excerpt( $post_id )
                    ? get_the_excerpt( $post_id )
                    : get_the_content( null, false, $post_id );
            echo $excerpt;
     ?>
  </div>
  <?php endwhile; ?>
<?php else : ?>
  <p><?php _e( 'Sorry, no updates matched your criteria.' ); ?></p>
<?php endif; ?>