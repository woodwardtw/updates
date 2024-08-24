<?php
/**
 * Application functions
 *
 * @package Understrap
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


// function dlinq_update_purpose(){
//     if(get_field('what_does_it_do')){
//         $purpose = get_field('what_does_it_do');
//         echo "{$purpose}";
//     } else {
//         echo "I lack purpose. Please give me some.";
//     }
// }

function dlinq_update_generic_text($field_name,$alt_message){
    if(get_field($field_name)){
        $field_obj = get_field_object($field_name);
        $field_title =  $field_obj['label'];
        $field_text = $field_obj['value']; 
        //var_dump($field_obj);        
        echo "<h2>{$field_title}</h2>";
        echo "{$field_text}";
    } else {
        echo $alt_message;
    }
}

function dlinq_update_data(){
    echo "<h2>Data</h2>";
    if(get_field('data')){
        $url = get_field('data');
        if(str_contains($url, 'https://drive.google.com/drive/folders/')){
            $url = str_replace('https://drive.google.com/drive/folders/', 'https://drive.google.com/embeddedfolderview?id=', $url);//replace with https://drive.google.com/embeddedfolderview?id=
        }
        echo "<iframe class='app-data' src='{$url}' width='100%'></iframe>";
    }else{
        echo "No data present.";
    }
}

function dlinq_update_software_cat(){
    if(get_field('software_category')){
        $cats = get_field('software_category');
        foreach ($cats as $key => $value) {
            // code...
            $term_id = $value->term_id;
            $title = $value->name;
            $link = get_term_link($term_id);
            echo "<div class='software-cat'><a href='{$link}'>{$title}</a></div>";
        }
    } else {
        echo 'I lack definition.';
    }
}

function dlinq_update_big_audience(){
    if(get_field('license_audiences')){
        $cats = get_field('license_audiences');
        echo "<div class='audiences'><strong>Enterprise Audiences</strong>";
        foreach ($cats as $key => $value) {
            // code...
            $term_id = $value->term_id;
            $title = $value->name;
            $link = get_term_link($term_id);
            echo "<div class='software-license'><a href='{$link}'>{$title}</a></div>";
        }
        echo '</div>';
    } else {
        echo 'No enterprise audiences.';
    }
}

function dlinq_update_uses(){
    if(get_field('allowed_use_types')){
        $cats = get_field('allowed_use_types');
        foreach ($cats as $key => $value) {
            // code...
            $term_id = $value->term_id;
            $title = $value->name;
            $link = get_term_link($term_id);
            echo "<div class='software-license'><a href='{$link}'>{$title}</a></div>";
        }
    } else {
        echo 'No usage restrictions.';
    }
}


function dlinq_update_vendor_details(){
    $html = '';
    if(get_field('vendor_url')){
        $url = get_field('vendor_url');
        $vendor_url = "<a href='{$url}'>{$url}</a>";
        $html .= $vendor_url;
    } 
    if (get_field('vendor_contact')){
        $contact = get_field('vendor_contact');
        $html .= $contact;
    }
    if(!get_field('vendor_url') && get_field('vendor_contact')){
        $html = 'I lack vendor contact';
    }
    echo $html;
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
            $date = get_the_date();
            $content = get_the_content();
            $term_obj_list = get_the_terms( $post->ID, 'update-types' );
            $cats = join(', ', wp_list_pluck($term_obj_list, 'name'));
            echo "<div class='update'>
                    <a href='{$link}'>{$title}</a> - {$date}
                    <p>{$content}</p>
                    <div class='update-cats'>{$cats}</div>
                </div>";
        endwhile;
    endif;       
    // Reset Post Data
    wp_reset_postdata();    
}


function dlinq_update_history_repeater(){
    $html = '';
    if( have_rows('history') ):

        // Loop through rows.
        while( have_rows('history') ) : the_row();
            // Load sub field value.
            $date = substr(get_sub_field('date'),6,10);
            
            //$license_year = $date->format("Y");
            $cost = '$'. number_format(get_sub_field('cost'), 2, ".", ",");
            $details = get_sub_field('details');
            $html .= "<div class='pay-history'>{$date} - {$cost}
                        <div class='pay-details'>{$details}</div>
                    </div>";
            // Do something...
        // End loop.
        endwhile;
        return $html;
        // No value.
        else :
            // Do something...
        endif;
    }


//SORT payment history so years go recent to oldest
function updates_reorder_cost_history( $value, $post_id, $field ) {
    
    // vars
    $order = array();
    
    
    // bail early if no value
    if( empty($value) ) {
        
        return $value;
        
    }
    
    
    // populate order
    foreach( $value as $i => $row ) {
        
        $order[ $i ] = $row['field_65fdc12aa13ce'];
        
    }
    
    
    // multisort
    array_multisort( $order, SORT_DESC, $value );
    
    
    // return   
    return $value;
    
}

add_filter('acf/load_value/name=history', 'updates_reorder_cost_history', 10, 3);