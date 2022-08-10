<?php
/**
 * UnderStrap functions and definitions
 *
 * @package Understrap
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// UnderStrap's includes directory.
$understrap_inc_dir = 'inc';

// Array of files to include.
$understrap_includes = array(
	'/theme-settings.php',                  // Initialize theme default settings.
	'/setup.php',                           // Theme setup and custom theme supports.
	'/widgets.php',                         // Register widget area.
	'/enqueue.php',                         // Enqueue scripts and styles.
	'/template-tags.php',                   // Custom template tags for this theme.
	'/pagination.php',                      // Custom pagination for this theme.
	'/hooks.php',                           // Custom hooks.
	'/extras.php',                          // Custom functions that act independently of the theme templates.
	'/customizer.php',                      // Customizer additions.
	'/custom-comments.php',                 // Custom Comments file.
	'/class-wp-bootstrap-navwalker.php',    // Load custom WordPress nav walker. Trying to get deeper navigation? Check out: https://github.com/understrap/understrap/issues/567.
	'/editor.php',                          // Load Editor functions.
	'/block-editor.php',                    // Load Block Editor functions.
	'/custom-data.php',						//Load custom taxonomies and/or post types
	'/deprecated.php',                      // Load deprecated functions.
);

// Load WooCommerce functions if WooCommerce is activated.
if ( class_exists( 'WooCommerce' ) ) {
	$understrap_includes[] = '/woocommerce.php';
}

// Load Jetpack compatibility file if Jetpack is activiated.
if ( class_exists( 'Jetpack' ) ) {
	$understrap_includes[] = '/jetpack.php';
}

// Include files.
foreach ( $understrap_includes as $file ) {
	require_once get_theme_file_path( $understrap_inc_dir . $file );
}


//gravity forms
//Adds a filter to form id 3. Replace 3 with your actual form id
// add_filter( 'gform_pre_render_1', 'dlinq_update_software_dropdown' );
// add_filter( 'gform_pre_validation_1', 'dlinq_update_software_dropdown' );
// add_filter( 'gform_pre_submission_filter_1', 'dlinq_update_software_dropdown' );
// add_filter( 'gform_admin_pre_render_1', 'dlinq_update_software_dropdown' );
function dlinq_update_software_dropdown($form){

	    foreach( $form['fields'] as &$field )  {
			$field_id = 5;
		        if ( $field->id != $field_id ) {
		            continue;
		        }


		    $terms = get_terms( array(
		        'taxonomy' => 'software',
		        'hide_empty' => false,
		        'orderby'   =>'title',
		        'order'   =>'ASC',
		    ) );
		$input_id = 1;
	    //Adding post titles to the items array
	    foreach($terms as $term){
	    	 if ( $input_id % 10 == 0 ) {
	                $input_id++;
	            }
	 
	            $choices[] = array( 'text' => $term->name, 'value' => $term->term_id);
	            $inputs[] = array( 'label' => $term->name, 'id' => "software-{$term->term_id}" );
	 
	            $input_id++;
	    	}
	    }
	      //   $items[] = array(
	      //      "value" => $term->term_id, 
	      //      "text" =>  $term->name
	      // );
	    // $field->choices = $choices;
	    // $field->inputs = $inputs;

	     return $form;
}

// NOTE: update the '1' to the ID of your form
add_filter( 'gform_pre_render_1', 'dlinq_update_populate_software' );
add_filter( 'gform_pre_validation_1', 'dlinq_update_populate_software' );
add_filter( 'gform_pre_submission_filter_1', 'dlinq_update_populate_software' );
add_filter( 'gform_admin_pre_render_1', 'dlinq_update_populate_software' );
function dlinq_update_populate_software( $form ) {
 
    foreach( $form['fields'] as &$field )  {
 
        //NOTE: replace 5 with your checkbox field id
        $field_id = 5;
        if ( $field->id != $field_id ) {
            continue;
        }
 
        $terms = get_terms( array(
		        'taxonomy' => 'software',
		        'hide_empty' => false,
		        'orderby'   =>'title',
		        'order'   =>'ASC',
		    ) ); 
        $input_id = 1;
        foreach( $terms as $term ) {
 
            //skipping index that are multiples of 10 (multiples of 10 create problems as the input IDs)
            if ( $input_id % 10 == 0 ) {
                $input_id++;
            }
 
            $choices[] = array( 'text' => $term->name, 'value' => $term->name );
            $inputs[] = array( 'label' => $term->name, 'id' => "{$field_id}.{$input_id}");
 
            $input_id++;
        }
 
        $field->choices = $choices;
        $field->inputs = $inputs;
 
    }
 
    return $form;
}

// NOTE: update the '1' to the ID of your form
add_filter( 'gform_pre_render_1', 'dlinq_update_populate_type' );
add_filter( 'gform_pre_validation_1', 'dlinq_update_populate_type' );
add_filter( 'gform_pre_submission_filter_1', 'dlinq_update_populate_type' );
add_filter( 'gform_admin_pre_render_1', 'dlinq_update_populate_type' );
function dlinq_update_populate_type( $form ) {
 
    foreach( $form['fields'] as &$field )  {
 
        //NOTE: replace 5 with your checkbox field id
        $field_id = 4;
        if ( $field->id != $field_id ) {
            continue;
        }
 
        $terms = get_terms( array(
		        'taxonomy' => 'update-types',
		        'hide_empty' => false,
		        'orderby'   =>'title',
		        'order'   =>'ASC',
		    ) ); 
        $input_id = 1;
        foreach( $terms as $term ) {
 
            //skipping index that are multiples of 10 (multiples of 10 create problems as the input IDs)
            if ( $input_id % 10 == 0 ) {
                $input_id++;
            }
 
            $choices[] = array( 'text' => $term->name, 'value' => $term->name);
            $inputs[] = array( 'label' => $term->name, 'id' => "{$field_id}.{$input_id}");
 
            $input_id++;
        }
 
        $field->choices = $choices;
        $field->inputs = $inputs;
 
    }
 
    return $form;
}



//save acf json
add_filter('acf/settings/save_json', 'dlinq_updates_json_save_point');
 
function dlinq_updates_json_save_point( $path ) {
    
    // update path
    $path = get_stylesheet_directory() . '/acf-json'; //replace w get_stylesheet_directory() for theme
    
    
    // return
    return $path;
    
}


// load acf json
add_filter('acf/settings/load_json', 'dlinq_updates_json_load_point');

function dlinq_updates_json_load_point( $paths ) {
    
    // remove original path (optional)
    unset($paths[0]);
    
    
    // append path
    $paths[] = get_stylesheet_directory() . '/acf-json';//replace w get_stylesheet_directory() for theme
    
    
    // return
    return $paths;
    
}