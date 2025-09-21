<?php
/**
 * Plugin Name: Post Series
 * Description: A comprehensive WordPress plugin for managing and displaying post series with part numbering functionality.
 * 
 * Features:
 * - Creates a custom 'series' taxonomy for organizing posts into series
 * - Adds a meta box to post editor for selecting series and setting part numbers
 * - Displays series information at the top of post content with part numbers
 * - Includes security features: nonce verification, permission checks, and autosave protection
 * - Validates part numbers to ensure only positive integers are saved
 * - Uses object-oriented programming approach for clean, maintainable code
 * 
 * Usage:
 * 1. Create series terms through the WordPress admin
 * 2. Edit any post and select a series from the meta box
 * 3. Optionally set a part number for the post
 * 4. The series information will automatically display at the top of the post content
 * 
 * Technical Details:
 * - Uses WordPress taxonomy system for series management
 * - Stores part numbers as post meta data
 * - Implements proper WordPress security practices
 * - Follows WordPress coding standards
 * 
 * Version: 1.0
 * Author: Rofaida
 */

 // Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Post_Series {

    public function __construct() {
        add_action('init', [$this, 'register_series_taxonomy']);
        add_action('add_meta_boxes', [$this, 'add_series_meta_box']);
        add_action('save_post', [$this, 'save_series_meta_box']);
        add_filter('the_content', [$this, 'display_series_in_content']);
    }

    // Create a new taxonomy for series using register_taxonomy
    public function register_series_taxonomy() {
        register_taxonomy('series', 'post', [
            'label' => 'Series',
            'rewrite' => ['slug' => 'series'],
            'hierarchical' => false,
        ]);
    }

    // add a meta box to the post editor
    public function add_series_meta_box() {
        add_meta_box(
            'series_meta_box',
            'Series',
            [$this, 'render_series_meta_box'],
            'post', 'side', 'low');
    }
    
    // render the meta box
    public function render_series_meta_box($post) {
        $series = get_terms(['taxonomy' => 'series', 'hide_empty' => false]);
        $current_series = wp_get_post_terms($post->ID, 'series', ['fields' => 'ids']);
        $part = get_post_meta($post->ID, '_series_part', true);

        // Add nonce for security
        wp_nonce_field('series_meta_box_nonce', 'series_meta_box_nonce');

        ?>
        <p>
            <label for="post_series">Choose Series:</label><br>
            <select name="post_series" id="post_series">
                <option value="">-- None --</option>
                <?php foreach ($series as $s) : ?>
                    <option value="<?php echo esc_attr($s->term_id); ?>" 
                        <?php selected(in_array($s->term_id, $current_series)); ?>>
                        <?php echo esc_html($s->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="series_part">Part Number:</label><br>
            <input type="number" id="series_part" name="series_part" value="<?php echo esc_attr($part); ?>" min="1" style="width:100%;" />
        </p>
        <?php
    }
   
    // Save the data when the user clicks update/publish 
    public function save_series_meta_box($post_id) {
        // Check nonce for security
        if (!isset($_POST['series_meta_box_nonce']) || !wp_verify_nonce($_POST['series_meta_box_nonce'], 'series_meta_box_nonce')) {
            return;
        }

        // Check if user has permissions to edit this post
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Save the part number
        if (isset($_POST['series_part']) && $_POST['series_part'] !== '') {
            $part_number = intval($_POST['series_part']);
            if ($part_number > 0) {
                update_post_meta($post_id, '_series_part', $part_number);
            }
        } else {
            delete_post_meta($post_id, '_series_part');
        }

        // Save the series
        if (isset($_POST['post_series']) && $_POST['post_series'] !== '') {
            wp_set_post_terms($post_id, [(int) $_POST['post_series']], 'series');
        } else {
            wp_set_post_terms($post_id, [], 'series');
        }
    }

    // display the series in the content
    public function display_series_in_content($content) {
        if (is_singular('post')) {
            $terms = get_the_terms(get_the_ID(), 'series');
            if ($terms && !is_wp_error($terms)) {
                $series_names = wp_list_pluck($terms, 'name');
                $part_number = get_post_meta(get_the_ID(), '_series_part', true);
                
                $series_text = '<p><strong>This article is part of the series: </strong>' . implode(', ', $series_names);
                
                if ($part_number && $part_number > 0) {
                    $series_text .= ' <strong>(Part ' . esc_html($part_number) . ')</strong>';
                }
                
                $series_text .= '</p>';
                $content = $series_text . $content;
            }
        }
        return $content;
    }
}


new Post_Series();


















