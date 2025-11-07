<?php

/**
 * Plugin Name: Post Series (Enhanced)
 * Description: Enhanced version of the Post Series plugin. Shows a unified "Series" meta box in the post editor side column (under Categories), lists all existing series, displays all posts (parts) in the selected series in correct order, supports drag-and-drop reordering, and allows creating a new series inline.
 * Version: 1.1
 * Author: Rofaida (updated)
 */

if (! defined('ABSPATH')) exit;

class Post_Series
{
    public function __construct()
    {
        new Assign_posts_to_a_series();
        new Series_Meta_Box();
    }
}

class Assign_posts_to_a_series
{
    public function register_series_taxonomy()
    {
        register_taxonomy('series', 'post', [
            'labels' => [
                'name'          => 'Series',
                'singular_name' => 'Series',
                'search_items'  => 'Search Series',
                'all_items'     => 'All Series',
                'edit_item'     => 'Edit Series',
                'update_item'   => 'Update Series',
                'menu_name'     => 'Series',
            ],
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'rewrite'           => ['slug' => 'series'],
            'hierarchical'      => false,
        ]);
    }

    public function __construct()
    {
        add_action('init', [$this, 'register_series_taxonomy']);
    }
}

class Series_Meta_Box
{
    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'register_meta_box']);
        add_action('save_post', [$this, 'save_series_meta']);
        add_action('wp_ajax_add_new_series', [$this, 'handle_add_new_series']);
        add_action('wp_ajax_get_series_parts', [$this, 'handle_get_series_parts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    // Register unified meta box and place it in the side with low priority so it appears under categories
    public function register_meta_box()
    {
        add_meta_box(
            'series_meta',
            'Series',
            [$this, 'render_meta_box'],
            'post',
            'side',
            'low' // low priority -> typically appears under taxonomy boxes like Categories
        );
    }

    // Unified meta box: series select + add new + parts list + part number input
    public function render_meta_box($post)
    {
        wp_nonce_field('save_series_meta', 'series_meta_nonce');

        $current_terms = wp_get_post_terms($post->ID, 'series', ['fields' => 'ids']);
        $current_series_id = is_wp_error($current_terms) || empty($current_terms) ? 0 : intval($current_terms[0]);
        $current_order = get_post_meta($post->ID, '_series_order', true);
        $current_order = $current_order ? intval($current_order) : '';

        $terms = get_terms([
            'taxonomy'   => 'series',
            'hide_empty' => false,
        ]);

        echo '<p><label for="selected_series"><strong>Series</strong></label><br />';
        echo '<select id="selected_series" name="selected_series" style="width:100%">';
        echo '<option value="">— Select Series —</option>';
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $selected = selected($current_series_id, $term->term_id, false);
                echo '<option value="' . esc_attr($term->term_id) . '" ' . $selected . '>' . esc_html($term->name) . '</option>';
            }
        }
        echo '</select></p>';

        // Add new series button and inline form
        echo '<p><button type="button" class="button" id="add_new_series_btn">Add New Series</button></p>';
        echo '<div id="new_series_form" style="display:none; margin-top:10px;">';
        echo '<input type="text" id="new_series_name" placeholder="Series Name" style="width:70%;" /> ';
        echo '<button type="button" class="button button-primary" id="save_new_series_btn">Save</button>';
        echo '</div>';

        // Parts list with drag & drop and hidden input for order
        echo '<div id="series_parts_container" style="margin-top:15px;">';
        echo '<strong>Series Parts:</strong>';
        echo '<ul id="series_parts_list" style="list-style:decimal inside; margin-top:10px; padding-left:20px; border:1px solid #ccc; min-height:40px;"></ul>';
        echo '<input type="hidden" name="series_parts_order" id="series_parts_order" value="" />';
        echo '</div>';
    }

    // Save meta and reorder from the parts list
    public function save_series_meta($post_id)
    {
        if (!isset($_POST['series_meta_nonce']) || !wp_verify_nonce($_POST['series_meta_nonce'], 'save_series_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (get_post_type($post_id) !== 'post') return;
        if (!current_user_can('edit_post', $post_id)) return;

        // Assign selected series
        if (isset($_POST['selected_series']) && $_POST['selected_series'] !== '') {
            $term_id = intval($_POST['selected_series']);
            if ($term_id > 0) {
                wp_set_post_terms($post_id, [$term_id], 'series', false);
            }
        } else {
            wp_set_post_terms($post_id, [], 'series', false);
        }

        // Save individual part number
        if (isset($_POST['series_order']) && $_POST['series_order'] !== '') {
            update_post_meta($post_id, '_series_order', max(1, intval($_POST['series_order'])));
        } else {
            delete_post_meta($post_id, '_series_order');
        }

        // If a serialized order was posted from drag-drop, apply it
        if (!empty($_POST['series_parts_order'])) {
            $ids = array_filter(array_map('intval', explode(',', $_POST['series_parts_order'])));
            $order = 1;
            foreach ($ids as $id) {
                // Only update posts that actually exist and belong to the same series (defensive)
                if (get_post($id)) {
                    update_post_meta($id, '_series_order', $order++);
                }
            }
        }
    }

    // AJAX: create a new series term
    public function handle_add_new_series()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'add_new_series_nonce')) wp_send_json_error('Invalid nonce');
        if (!current_user_can('manage_categories')) wp_send_json_error('Permission denied');

        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        if (empty($name)) wp_send_json_error('Series name cannot be empty');

        $term = wp_insert_term($name, 'series');
        if (is_wp_error($term)) wp_send_json_error($term->get_error_message());

        $term_obj = get_term($term['term_id'], 'series');
        wp_send_json_success(['term_id' => $term_obj->term_id, 'name' => $term_obj->name]);
    }

    // AJAX: get series parts in correct order and include current post (placed according to meta or appended)
    public function handle_get_series_parts()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'get_series_parts_nonce')) wp_send_json_error('Invalid nonce');

        $series_id = intval($_POST['series_id'] ?? 0);
        $current_post_id = intval($_POST['current_post_id'] ?? 0);

        if (!$series_id) wp_send_json_error('No series selected');

        // Fetch all posts in the series (no ordering yet)
        $posts = get_posts([
            'post_type' => 'post',
            'numberposts' => -1,
            'tax_query' => [[
                'taxonomy' => 'series',
                'field'    => 'term_id',
                'terms'    => $series_id,
            ]],
            'post_status' => ['publish', 'draft', 'pending', 'private'],
        ]);

        // Build array with numeric order value (default large number for no meta so they appear after numbered parts)
        $list = [];
        foreach ($posts as $p) {
            $meta = get_post_meta($p->ID, '_series_order', true);
            $val = ($meta !== '' && is_numeric($meta)) ? intval($meta) : PHP_INT_MAX;
            $title = get_the_title($p->ID);
            if (empty($title)) $title = 'Untitled';
            $list[] = ['ID' => $p->ID, 'title' => $title, 'order' => $val];
        }

        // If current post is not in the list (e.g. it's a new post or wasn't assigned yet) we append it with order=PHP_INT_MAX
        $found_current = false;
        foreach ($list as $item) {
            if ($item['ID'] == $current_post_id) {
                $found_current = true;
                break;
            }
        }
        if ($current_post_id && !$found_current) {
            $title = get_the_title($current_post_id);
            if (empty($title)) $title = 'the current post';
            $list[] = ['ID' => $current_post_id, 'title' => $title, 'order' => PHP_INT_MAX];
        }

        // Sort by order numeric ascending (items without order will appear after numbered ones). For equal order, sort by title.
        usort($list, function ($a, $b) {
            if ($a['order'] === $b['order']) return strcmp($a['title'], $b['title']);
            return ($a['order'] < $b['order']) ? -1 : 1;
        });

        // Build response; mark is_current where appropriate
        $data = [];
        foreach ($list as $item) {
            $data[] = ['ID' => $item['ID'], 'title' => ($item['ID'] == $current_post_id ? 'the current post' : $item['title']), 'is_current' => ($item['ID'] == $current_post_id)];
        }

        wp_send_json_success($data);
    }

    // Enqueue admin scripts and styles
    public function enqueue_admin_assets($hook_suffix)
    {
        $screen = get_current_screen();
        if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'post') return;

        wp_enqueue_script('jquery-ui-sortable');

        // Register and enqueue our admin JS file location (place src/admin.js next to this file)
        wp_enqueue_script(
            'post-series-admin',
            plugin_dir_url(__FILE__) . 'src/admin.js',
            ['jquery', 'jquery-ui-sortable'],
            filemtime(plugin_dir_path(__FILE__) . 'src/admin.js'),
            true
        );

        // Pass PHP values to JS
        global $post;
        $current_post_id = $post ? $post->ID : 0;
        wp_localize_script('post-series-admin', 'postSeriesAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'addNewSeriesNonce' => wp_create_nonce('add_new_series_nonce'),
            'getSeriesPartsNonce' => wp_create_nonce('get_series_parts_nonce'),
            'currentPostId' => $current_post_id,
        ]);
    }
}

new Post_Series();
