<?php
/**
 * Application functions
 *
 * @package Understrap
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


function dlinq_update_purpose(){
    if(get_field('what_does_it_do')){
        $purpose = get_field('what_does_it_do');
        echo "{$purpose}";
    } else {
        echo "I lack purpose. Please give me some.";
    }
}

function dlinq_update_generic_text($field_name,$alt_message){
    if(get_field($field_name)){
        $field_text = get_field($field_name);
        echo "{$field_text}";
    } else {
        echo $alt_message;
    }
}

function dlinq_update_app_updates(){
    global $post;
    $post_slug = $post->post_name;
    $args = array( 
        'post_type' => 'update',
        'posts_per_page' => 25,
        'post_status'=>'published',
        'tax_query' => array( // (array) - use taxonomy parameters (available with Version 3.1).
            array(
              'taxonomy' => 'software', // (string) - Taxonomy.
              'field' => 'slug', // (string) - Select taxonomy term by Possible values are 'term_id', 'name', 'slug' or 'term_taxonomy_id'. Default value is 'term_id'.
              'terms' => array( $post_slug  ), // (int/string/array) - Taxonomy term(s).              
              'operator' => 'IN' // (string) - Operator to test. Possible values are 'IN', 'NOT IN', 'AND', 'EXISTS' and 'NOT EXISTS'. Default value is 'IN'.
            ),
        )

    );

    $the_query = new WP_Query( $args );
    //var_dump($the_query);
    // The Loop
    if ( $the_query->have_posts() ) :
    while ( $the_query->have_posts() ) : $the_query->the_post();
      // Do Stuff
        $title = get_the_title();
        $link = get_the_permalink();
        $content = get_the_content();
        echo "<div class='update'><h3><a href='{$link}'>{$title}</a></h3><p>{$content}</p></div>";
    endwhile;
    endif;

    // Reset Post Data
    wp_reset_postdata();    
}