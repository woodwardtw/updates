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
//Gravity Forms, populate a filter with all members
//Adds a filter to form id 3. Replace 3 with your actual form id

add_filter('gform_pre_render_1', 'dlinq_update_software_dropdown');
function dlinq_update_software_dropdown($form){

    $terms = get_terms( array(
        'taxonomy' => 'Software',
        'hide_empty' => false,
        'orderby'   =>'title',
        'order'   =>'ASC',
    ) );

    //Creating drop down item array.
    $items = array();

    //Adding initial blank value.
    $items[] = array("text" => "", "value" => "");

    //Adding post titles to the items array
    foreach($terms as $term)
        $items[] = array(
           "value" => $term->term_id, 
           "text" =>  $term->name
      );

    //Adding items to field id 38
    foreach($form["fields"] as &$field)
        if($field["id"] == 5){
            $field["type"] = "select";
            $field["choices"] = $items;
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