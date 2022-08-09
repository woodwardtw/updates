<?php
/**
 * Custom data 
 *
 * @package Understrap
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


//update custom post type

// Register Custom Post Type update
// Post Type Key: update

function create_update_cpt() {

  $labels = array(
    'name' => __( 'Updates', 'Post Type General Name', 'textdomain' ),
    'singular_name' => __( 'Update', 'Post Type Singular Name', 'textdomain' ),
    'menu_name' => __( 'Update', 'textdomain' ),
    'name_admin_bar' => __( 'Update', 'textdomain' ),
    'archives' => __( 'Update Archives', 'textdomain' ),
    'attributes' => __( 'Update Attributes', 'textdomain' ),
    'parent_item_colon' => __( 'Update:', 'textdomain' ),
    'all_items' => __( 'All Updates', 'textdomain' ),
    'add_new_item' => __( 'Add New Update', 'textdomain' ),
    'add_new' => __( 'Add New', 'textdomain' ),
    'new_item' => __( 'New Update', 'textdomain' ),
    'edit_item' => __( 'Edit Update', 'textdomain' ),
    'update_item' => __( 'Update Update', 'textdomain' ),
    'view_item' => __( 'View Update', 'textdomain' ),
    'view_items' => __( 'View Updates', 'textdomain' ),
    'search_items' => __( 'Search Updates', 'textdomain' ),
    'not_found' => __( 'Not found', 'textdomain' ),
    'not_found_in_trash' => __( 'Not found in Trash', 'textdomain' ),
    'featured_image' => __( 'Featured Image', 'textdomain' ),
    'set_featured_image' => __( 'Set featured image', 'textdomain' ),
    'remove_featured_image' => __( 'Remove featured image', 'textdomain' ),
    'use_featured_image' => __( 'Use as featured image', 'textdomain' ),
    'insert_into_item' => __( 'Insert into update', 'textdomain' ),
    'uploaded_to_this_item' => __( 'Uploaded to this update', 'textdomain' ),
    'items_list' => __( 'Update list', 'textdomain' ),
    'items_list_navigation' => __( 'Update list navigation', 'textdomain' ),
    'filter_items_list' => __( 'Filter Update list', 'textdomain' ),
  );
  $args = array(
    'label' => __( 'update', 'textdomain' ),
    'description' => __( '', 'textdomain' ),
    'labels' => $labels,
    'menu_icon' => '',
    'supports' => array('title', 'editor', 'revisions', 'author', 'trackbacks', 'custom-fields', 'thumbnail',),
    'taxonomies' => array('category', 'post_tag'),
    'public' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'menu_position' => 5,
    'show_in_admin_bar' => true,
    'show_in_nav_menus' => true,
    'can_export' => true,
    'has_archive' => true,
    'hierarchical' => false,
    'exclude_from_search' => false,
    'show_in_rest' => true,
    'publicly_queryable' => true,
    'capability_type' => 'post',
    'menu_icon' => 'dashicons-universal-access-alt',
  );
  register_post_type( 'update', $args );
  
  // flush rewrite rules because we changed the permalink structure
  global $wp_rewrite;
  $wp_rewrite->flush_rules();
}
add_action( 'init', 'create_update_cpt', 0 );

add_action( 'init', 'create_software_taxonomies', 0 );
function create_software_taxonomies()
{
  // Add new taxonomy, NOT hierarchical (like tags)
  $labels = array(
    'name' => _x( 'Software', 'taxonomy general name' ),
    'singular_name' => _x( 'Software', 'taxonomy singular name' ),
    'search_items' =>  __( 'Search Software' ),
    'popular_items' => __( 'Popular Software' ),
    'all_items' => __( 'All Softwares' ),
    'parent_item' => null,
    'parent_item_colon' => null,
    'edit_item' => __( 'Edit Software' ),
    'update_item' => __( 'Update software' ),
    'add_new_item' => __( 'Add New software' ),
    'new_item_name' => __( 'New software' ),
    'add_or_remove_items' => __( 'Add or remove Software' ),
    'choose_from_most_used' => __( 'Choose from the most used Software' ),
    'menu_name' => __( 'software' ),
  );

//registers taxonomy specific post types - default is just post
  register_taxonomy('Software',array('update'), array(
    'hierarchical' => true,
    'labels' => $labels,
    'show_ui' => true,
    'update_count_callback' => '_update_post_term_count',
    'query_var' => true,
    'rewrite' => array( 'slug' => 'software' ),
    'show_in_rest'          => true,
    'rest_base'             => 'software',
    'rest_controller_class' => 'WP_REST_Terms_Controller',
    'show_in_nav_menus' => true,    
  ));
}


add_action( 'init', 'create_update_type_taxonomies', 0 );
function create_update_type_taxonomies()
{
  // Add new taxonomy, NOT hierarchical (like tags)
  $labels = array(
    'name' => _x( 'Update types', 'taxonomy general name' ),
    'singular_name' => _x( 'update type', 'taxonomy singular name' ),
    'search_items' =>  __( 'Search Update types' ),
    'popular_items' => __( 'Popular Update types' ),
    'all_items' => __( 'All Update_types' ),
    'parent_item' => null,
    'parent_item_colon' => null,
    'edit_item' => __( 'Edit Update types' ),
    'update_item' => __( 'Update update type' ),
    'add_new_item' => __( 'Add New update type' ),
    'new_item_name' => __( 'New update type' ),
    'add_or_remove_items' => __( 'Add or remove Update_types' ),
    'choose_from_most_used' => __( 'Choose from the most used Update_types' ),
    'menu_name' => __( 'update_type' ),
  );

//registers taxonomy specific post types - default is just post
  register_taxonomy('Update types',array('update'), array(
    'hierarchical' => true,
    'labels' => $labels,
    'show_ui' => true,
    'update_count_callback' => '_update_post_term_count',
    'query_var' => true,
    'rewrite' => array( 'slug' => 'update_type' ),
    'show_in_rest'          => true,
    'rest_base'             => 'update_type',
    'rest_controller_class' => 'WP_REST_Terms_Controller',
    'show_in_nav_menus' => true,    
  ));
}


