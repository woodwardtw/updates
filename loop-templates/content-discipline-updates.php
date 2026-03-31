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
            $discipline = urldecode( get_query_var( 'discipline' ) );
            the_title( '<h1 class="entry-title">' . $discipline . ' ', '</h1>' ); ?>

	</header><!-- .entry-header -->

	<?php echo get_the_post_thumbnail( $post->ID, 'large' ); ?>

	<div class="entry-content discipline-updates">
        <?php
        //build navigation for discipline updates
            $disciplines = get_terms( array(
                            'taxonomy'   => 'discipline',
                            'hide_empty' => true,
                        ) );
            $current_url = home_url( $wp->request );
            echo "<div class='chooser'><a href='{$current_url}'>General updates</a>";
            foreach ( $disciplines as $term ) {
                $term_name = urlencode($term->name);
                echo "<a class='discipline-link' href='{$current_url}/?discipline={$term_name}'>{$term->name}</a>";
            }
            echo "</div>";
        ?>
		<?php
        //SHOW GENERAL UPDATES IF NO DISCIPLINE SELECTED
        $current_date = current_time( 'M Y' );
        if($discipline == '') {
            echo '<p>Showing general updates added during ' . esc_html( $current_date ) . '.</p>';
            $discipline_query = new WP_Query( array(
                'post_type' => 'update',
                'date_query' => array(
                    array(
                        'year'  => date( 'Y' ),
                        'month' => date( 'm' ),
                    ),
                ),
            ) );
        
            //GENERAL QUERY - for stuff not already selected 
            $general_query = new WP_Query( array(
                'post_type' => 'update',
                'tax_query'  => array(
                    array(
                        'taxonomy' => 'discipline',
                        'operator' => 'NOT EXISTS', // EXCLUDE ANY POSTS WITH A DISCIPLINE TERM
                    ),
                ),
                'date_query' => array(
                    array(
                        'year'  => date( 'Y' ),
                        'month' => date( 'm' ),
                    ),
                ),
            ) );
        // Group general posts by theme; a post with multiple themes appears under each.
        $site_url   = get_site_url();
    
        foreach ( $general_query->posts as $post ) {
            $post_id = $post->ID;
            $url     = get_permalink( $post_id );
            $title   = get_the_title( $post_id );
            $excerpt = get_the_content( null, false, $post_id );
            echo "<div class='update-item'>
                    <h2 class='update-title'><a href='{$url}'>{$title}</a></h2>
                    <div class='update-excerpt'>{$excerpt}</div>
                </div>";
        }
        wp_reset_postdata();
        echo '</div>';
        }


        $done_ids = array(); // To track displayed post IDs and avoid duplicates
        if ( $discipline != '') {
            echo '<p>Showing updates in ' . esc_html( $discipline ) . ' added during ' . esc_html( $current_date ) . '.</p>';
            $discipline_query = new WP_Query( array(
                'post_type' => 'update',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'discipline',
                        'field'    => 'slug',
                        'terms'    => urldecode($discipline),
                    ),
                ),
                'date_query' => array(
                    array(
                        'year'  => date( 'Y' ),
                        'month' => date( 'm' ),
                    ),
                ),
            ) );
        
            echo '<ol class="discipline-updates-list">';
            foreach ( $discipline_query->posts as $post ) {
                $site_url = get_site_url();
                $post_id = $post->ID;
                $done_ids[] = $post_id; // Add current post ID to the tracking array
                $url = get_permalink($post_id);
                $title = get_the_title($post_id);
                $theme = get_the_terms( $post_id, 'theme' );
                $theme_count = is_array($theme) ? count($theme) : 0;
                $label = match ($theme_count) {
                    0 => "",
                    1     => 'Theme: ',
                    default => 'Themes: ',
                };
                $spotlight = is_array( $theme ) && in_array( 'faculty-spotlight', array_column( $theme, 'slug' ), true );
                $spotlight_class = '';
                if ( $spotlight ) {
                    $spotlight_class = 'spotlight';
                }
                $theme_list = '';
                if ( $theme_count > 0 && $theme_count !== FALSE) {
                    foreach ( $theme as $term ) {
                        //https://wpmu.local/updates/?post_type=update&themes=access-and-equity
                        $theme_list .= "<a href='" . $site_url . "/?post_type=update&themes=" . $term->slug . "'>" . $term->name . "</a>, ";
                    }
                }
                $theme_list = rtrim( $theme_list, ', ' );
                $excerpt = has_excerpt( $post_id )
                    ? get_the_excerpt( $post_id )
                    : get_the_content( null, false, $post_id );
                // Replace top-level <span> wrappers with <p>.
                $excerpt = preg_replace( '#^<span([^>]*)>(.*)</span>$#is', '<p$1>$2</p>', trim( $excerpt ) );
                // Wrap in <p> if no block-level element is present.
                if ( ! preg_match( '#^\s*<(p|div|ul|ol|blockquote|h[1-6])[\s>]#i', $excerpt ) ) {
                    $excerpt = '<p>' . $excerpt . '</p>';
                }
            echo "<li><div class='update-item {$spotlight_class}'>
                    <h2 class='update-title'><a href='{$url}'>{$title}</a></h2>
                    <div class='update-excerpt'>{$excerpt}</div>
                    <div class='update-tax'>{$label} {$theme_list}</div>
                </div></li>";
            }
            echo '</ol>';
            wp_reset_postdata();
        }
		//the_content();
		understrap_link_pages();
		?>

	</div><!-- .entry-content -->

	<footer class="entry-footer">

		<?php understrap_edit_post_link(); ?>

	</footer><!-- .entry-footer -->

</article><!-- #post-## -->
