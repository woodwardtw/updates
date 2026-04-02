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
        $site_url   = get_site_url();
        $post_id = get_the_ID();
        $theme_list = [];
        $theme = get_the_terms( $post_id, 'theme' );
        $theme_count = is_array($theme) ? count($theme) : 0;
        $label = match ($theme_count) {
                    0 => "",
                    1     => 'Theme: ',
                    default => 'Themes: ',
                };
         if ( $theme_count > 0 && $theme_count !== FALSE) {
                    foreach ( $theme as $term ) {
                        array_push( $theme_list, "<a href='" . $site_url . "/?_theme=" . $term->slug . "'>" . $term->name . "</a> " );
                    }     
                }   
        $spotlight = is_array( $theme ) && in_array( 'faculty-spotlight', array_column( $theme, 'slug' ), true );
        $spotlight_class = '';
        if ( $spotlight ) {
            $spotlight_class = 'spotlight';
        }
        $theme_list = implode( ', ', $theme_list );
  ?>
  <div class="entry-content <?php echo $spotlight_class; ?>">
    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
    <?php  $excerpt = has_excerpt( $post_id )
                    ? get_the_excerpt( $post_id )
                    : get_the_content( null, false, $post_id );
            echo $excerpt;
            echo "<div class='update-tax'>{$label} {$theme_list}</div>";
     
     ?>
  </div>
  <?php endwhile; ?>
<?php else : ?>
  <p><?php _e( 'Sorry, no updates matched your criteria.' ); ?></p>
<?php endif; ?>