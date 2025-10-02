<?php
/**
 * Plugin Name: Post Series
 * Description: A comprehensive WordPress plugin for managing and displaying post series with part numbering functionality.
 * 

 * 
 * Version: 1.0
 * Author: Rofaida
 */

 // Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Post_Series {
    public function __construct() {
          new Assign_posts_to_a_series();
          new Series_Meta_Box();
    }       
}

class Assign_posts_to_a_series {
    public function register_series_taxonomy() {
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
            'show_admin_column' => true,
            'rewrite'           => ['slug' => 'series'],
            'hierarchical' => false,
            
        ]);
    }
    public function __construct() {
        add_action('init', [$this, 'register_series_taxonomy']);
    }

}

class Series_Meta_Box {

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'register_meta_box']);
        add_action('save_post', [$this, 'save_series_meta']);
        add_action('admin_footer', [$this, 'series_admin_js']);
        add_action('wp_ajax_add_new_series', [$this, 'handle_add_new_series']);
        add_action('wp_ajax_get_series_parts', [$this, 'handle_get_series_parts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    // Register unified meta box and remove default taxonomy box
    public function register_meta_box() {
        // Remove default non-hierarchical taxonomy meta box
        remove_meta_box('tagsdiv-series', 'post', 'side');

        add_meta_box(
            'series_meta',
            'Series',
            [$this, 'render_meta_box'],
            'post',
            'side',
            'default'
        );
    }

    // Render unified meta box: series select + part number
    public function render_meta_box($post) {
        wp_nonce_field('save_series_meta', 'series_meta_nonce');

        $current_terms = wp_get_post_terms($post->ID, 'series', ['fields' => 'ids']);
        $current_series_id = is_wp_error($current_terms) || empty($current_terms) ? 0 : intval($current_terms[0]);
        $current_order = get_post_meta($post->ID, '_series_order', true);

        $terms = get_terms([
            'taxonomy'   => 'series',
            'hide_empty' => false,
        ]);

        //series select
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

        //add new series
        echo '<p><button type="button" class="button" id="add_new_series_btn">Add New Series</button></p>';
        echo '<div id="new_series_form" style="display:none; margin-top:10px;">
        <input type="text" id="new_series_name" placeholder="Series Name" style="width:70%;" />
        <button type="button" class="button button-primary" id="save_new_series_btn">Save</button>
      </div>';

        //part number
        // echo '<p><label for="series_order"><strong>Part Number</strong></label><br />';
        // echo '<input type="number" min="1" step="1" id="series_order" name="series_order" value="' . esc_attr($current_order) . '" style="width:100%" />';
        // echo '</p>';

         //series parts
        echo '<div id="series_parts_container" style="margin-top:15px;">
        <strong>Series Parts:</strong>
        <ul id="series_parts_list" style="list-style:decimal inside; margin-top:10px; padding-left:20px; border:1px solid #ccc; min-height:40px;"></ul>
        <input type="hidden" name="series_parts_order" id="series_parts_order" value="" />
        </div>';
        echo '<style>
        #series_parts_list li{cursor: grab;}
        #series_parts_list li:active{cursor: grabbing;}
        #series_parts_list .ui-sortable-helper{cursor: grabbing !important;}
        </style>';
    }

   

    // Save selected series term and part number
    public function save_series_meta($post_id) {
        if (!isset($_POST['series_meta_nonce']) || !wp_verify_nonce($_POST['series_meta_nonce'], 'save_series_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $post_type = get_post_type($post_id);
        if ($post_type !== 'post') {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Assign selected series
        if (isset($_POST['selected_series']) && $_POST['selected_series'] !== '') {
            $term_id = intval($_POST['selected_series']);
            if ($term_id > 0) {
                wp_set_post_terms($post_id, [$term_id], 'series', false);
            }
        } else {
            // Clear series if empty selection
            wp_set_post_terms($post_id, [], 'series', false);
        }

        // Save part number meta
        if (isset($_POST['series_order']) && $_POST['series_order'] !== '') {
            update_post_meta($post_id, '_series_order', max(1, intval($_POST['series_order'])));
        } else {
            delete_post_meta($post_id, '_series_order');
        }

        if (!empty($_POST['series_parts_order'])) {
            $ids = explode(',', $_POST['series_parts_order']);
            $order = 1;
            foreach ($ids as $id) {
                update_post_meta(intval($id), '_series_order', $order++);
            }
        }
        
    }

    // Add new series
    public function series_admin_js() {
        global $post;
        if ($post->post_type !== 'post') return;
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($){
            $('#add_new_series_btn').on('click', function(){
                $('#new_series_form').toggle();
            });
    
            $('#save_new_series_btn').on('click', function(){
                var name = $('#new_series_name').val();
                if(!name) return;
    
                $.post(ajaxurl, {
                    action: 'add_new_series',
                    name: name,
                    nonce: '<?php echo wp_create_nonce("add_new_series_nonce"); ?>'
                }, function(response){
                    if(response.success){
                        var term = response.data;
                        $('#selected_series').append('<option value="'+term.term_id+'">'+term.name+'</option>');
                        $('#selected_series').val(term.term_id).trigger('change');
                        $('#new_series_name').val('');
                        $('#new_series_form').hide();
                    } else {
                        alert(response.data);
                    }
                });
            });
        });
        </script>
        <script type="text/javascript">
        jQuery(document).ready(function($){
            function loadSeriesParts(series_id, current_post_id){
                if(!series_id) {
                        $('#series_parts_list').html('<li style="color: blue;">the current post</li>');
                    return;
                }
                $.post(ajaxurl, {
                    action: 'get_series_parts',
                    series_id: series_id,
                    current_post_id: current_post_id,
                    nonce: '<?php echo wp_create_nonce("get_series_parts_nonce"); ?>'
                }, function(response){
                    if(response.success){
                        $('#series_parts_list').html('');
                response.data.forEach(function(item){
                    var extraStyle = item.is_current ? ' style="color: blue;"' : '';
                    $('#series_parts_list').append(
                        '<li data-id="'+item.ID+'"'+extraStyle+'>'+item.title+'</li>'
                    );
                });
                        makeSortable();
                    } else {
                        $('#series_parts_list').html('<li>New Post</li>');
                    }
                });
            }
        
            function makeSortable(){
                $('#series_parts_list').sortable({
                    update: function(){
                        var order = [];
                        $('#series_parts_list li').each(function(){
                            order.push($(this).data('id'));
                        });
                        $('#series_parts_order').val(order.join(','));
                    }
                });
            }
        
            // عند تغيير السلسلة
            $('#selected_series').on('change', function(){
                var series_id = $(this).val();
                loadSeriesParts(series_id, '<?php echo get_the_ID(); ?>');
            });
        
            // تحميل أول مرة لو فيه سلسلة مختارة
            loadSeriesParts($('#selected_series').val(), '<?php echo get_the_ID(); ?>');
        });
        </script>
        <?php
    }


    public function handle_add_new_series() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'add_new_series_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        if (!current_user_can('manage_categories')) {
            wp_send_json_error('Permission denied');
        }

        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        if (empty($name)) {
            wp_send_json_error('Series name cannot be empty');
        }

        $term = wp_insert_term($name, 'series');
        if (is_wp_error($term)) {
            wp_send_json_error($term->get_error_message());
        }

        $term_obj = get_term($term['term_id'], 'series');
        wp_send_json_success(['term_id' => $term_obj->term_id, 'name' => $term_obj->name]);
    }

    // Enqueue admin assets for sortable functionality
    public function enqueue_admin_assets($hook_suffix) {
        $screen = get_current_screen();
        if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'post') {
            return;
        }
        wp_enqueue_script('jquery-ui-sortable');
    }

    // AJAX handler: Get series parts for selected series
    public function handle_get_series_parts() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'get_series_parts_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        $series_id = intval($_POST['series_id'] ?? 0);
        $current_post_id = intval($_POST['current_post_id'] ?? 0);

        if (!$series_id) {
            wp_send_json_error('No series selected');
        }

        $posts = get_posts([
            'post_type' => 'post',
            'numberposts' => -1,
            'tax_query' => [[
                'taxonomy' => 'series',
                'field'    => 'term_id',
                'terms'    => $series_id,
            ]],
            'meta_key' => '_series_order',
            'orderby'  => 'meta_value_num',
            'order'    => 'ASC',
        ]);

        $data = [];
        $found_current = false;
        foreach ($posts as $p) {
            if ($p->ID == $current_post_id) $found_current = true;
            $title = get_the_title($p->ID);
            if ($p->ID == $current_post_id) {
                $title = 'the current post';
            } elseif (empty($title)) {
                $title = 'Untitled';
            }
            $data[] = ['ID' => $p->ID, 'title' => $title, 'is_current' => ($p->ID == $current_post_id)];
        }

        if (!$found_current && $current_post_id) {
            $title = 'the current post';
            $data[] = ['ID' => $current_post_id, 'title' => $title, 'is_current' => true];
        }

        wp_send_json_success($data);
    }

}


new Post_Series();


















