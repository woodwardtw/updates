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