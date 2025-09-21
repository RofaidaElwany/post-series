<?php
/**
 * Plugin Name: My Post Series
 * Description: Manage and display series for posts using object-oriented programming.
 * Version: 1.0
 * Author: Rofaida
 */

 // Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Post_Series {

    public function __construct() {
        add_action('init', [$this, 'register_series_taxonomy']);
    }

    // Create a new taxonomy for series using register_taxonomy
    public function register_series_taxonomy() {
        register_taxonomy('series', 'post', [
            'label' => 'Series',
            'rewrite' => ['slug' => 'series'],
            'hierarchical' => false,
        ]);
    }
}

new Post_Series();