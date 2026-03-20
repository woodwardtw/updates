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
        $current_date = current_time( 'M Y' );
        $done_ids = array(); // To track displayed post IDs and avoid duplicates
        if ( $discipline ) {
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
        } else {
            echo '<p>Showing all updates added during ' . esc_html( $current_date ) . '.</p>';
            $discipline_query = new WP_Query( array(
                'post_type' => 'update',
                'date_query' => array(
                    array(
                        'year'  => date( 'Y' ),
                        'month' => date( 'm' ),
                    ),
                ),
            ) );
        }

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
            $theme_list = '';
            if ( $theme_count > 0 && $theme_count !== FALSE) {
                foreach ( $theme as $term ) {
                    //https://wpmu.local/updates/?post_type=update&themes=access-and-equity
                    $theme_list .= "<a href='" . $site_url . "/?post_type=update&themes=" . $term->slug . "'>" . $term->name . "</a>, ";
                }
            }
            $theme_list = rtrim( $theme_list, ', ' );
           // $excerpt =  wp_trim_words( get_the_content( null, false, $post_id ), 125, '&hellip;' );
           $excerpt = get_the_content( null, false, $post_id );
          echo "<div class='update-item'>
                <h2 class='update-title'><a href='{$url}'>{$title}</a></h2>
                <div class='update-excerpt'>{$excerpt}</div>
                <div class='update-tax'>{$label} {$theme_list}</div>
            </div>";
        }
        wp_reset_postdata();
        //GENERAL QUERY - for stuff not already selected - nothing should show if no discipline is selected
        $general_query = new WP_Query( array(
            'post_type' => 'update',
            'post__not_in' => $done_ids, // Exclude already displayed posts
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

        if ( $general_query->have_posts() ) {
            echo '<div class="general-updates"><h1>General Updates</h1>';
         }
        foreach ( $general_query->posts as $post ) {
            $site_url = get_site_url();
            $post_id = $post->ID;
            $url = get_permalink($post_id);
            $title = get_the_title($post_id);
            $theme = get_the_terms( $post_id, 'theme' );
            $theme_count = is_array($theme) ? count($theme) : 0;
            $label = match ($theme_count) {
                0 => "",
                1     => 'Theme: ',
                default => 'Themes: ',
            };
            $theme_list = ''; 
            if ( $theme_count > 0 && $theme_count !== FALSE) {
                foreach ( $theme as $term ) {
                    //https://wpmu.local/updates/?post_type=update&themes=access-and-equity
                    $theme_list .= "<a href='" . $site_url . "/?post_type=update&themes=" . $term->slug . "'>" . $term->name . "</a>, ";
                }
            }
            $theme_list = rtrim( $theme_list, ', ' );
           // $excerpt =  wp_trim_words( get_the_content( null, false, $post_id ), 125, '&hellip;' );
           $excerpt = get_the_content( null, false, $post_id );
            echo "<div class='update-item'>
                    <h2 class='update-title'><a href='{$url}'>{$title}</a></h2>
                    <div class='update-excerpt'>{$excerpt}</div>
                    <div class='update-tax'>{$label} {$theme_list}</div>
                </div>";    
        }  
        wp_reset_postdata();
        echo '</div>';
		//the_content();
		understrap_link_pages();
		?>

	</div><!-- .entry-content -->

	<footer class="entry-footer">

		<?php understrap_edit_post_link(); ?>

	</footer><!-- .entry-footer -->

</article><!-- #post-## -->
