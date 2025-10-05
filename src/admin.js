/**
 * Post Series Admin JavaScript
 * Handles series management functionality in WordPress admin
 */

jQuery(document).ready(function ($) {
    // Add new series functionality
    $('#add_new_series_btn').on('click', function () {
        $('#new_series_form').toggle();
    });

    $('#save_new_series_btn').on('click', function () {
        var name = $('#new_series_name').val();
        if (!name) return;

        $.post(postSeriesAdmin.ajaxurl, {
            action: 'add_new_series',
            name: name,
            nonce: postSeriesAdmin.addNewSeriesNonce
        }, function (response) {
            if (response.success) {
                var term = response.data;
                $('#selected_series').append('<option value="' + term.term_id + '">' + term.name + '</option>');
                $('#selected_series').val(term.term_id).trigger('change');
                $('#new_series_name').val('');
                $('#new_series_form').hide();
            } else {
                alert(response.data);
            }
        });
    });

    // Load series parts functionality
    function loadSeriesParts(series_id, current_post_id) {
        if (!series_id) {
            $('#series_parts_list').html('<li style="color: blue;">the current post</li>');
            return;
        }

        $.post(postSeriesAdmin.ajaxurl, {
            action: 'get_series_parts',
            series_id: series_id,
            current_post_id: current_post_id,
            nonce: postSeriesAdmin.getSeriesPartsNonce
        }, function (response) {
            if (response.success) {
                $('#series_parts_list').html('');
                response.data.forEach(function (item) {
                    var extraStyle = item.is_current ? ' style="color: blue;"' : '';
                    $('#series_parts_list').append(
                        '<li data-id="' + item.ID + '"' + extraStyle + '>' + item.title + '</li>'
                    );
                });
                makeSortable();
            } else {
                $('#series_parts_list').html('<li>New Post</li>');
            }
        });
    }

    // Make series parts sortable
    function makeSortable() {
        $('#series_parts_list').sortable({
            update: function () {
                var order = [];
                $('#series_parts_list li').each(function () {
                    order.push($(this).data('id'));
                });
                $('#series_parts_order').val(order.join(','));
            }
        });
    }

    // Handle series change
    $('#selected_series').on('change', function () {
        var series_id = $(this).val();
        loadSeriesParts(series_id, postSeriesAdmin.currentPostId);
    });

    // Load series parts on page load if series is selected
    loadSeriesParts($('#selected_series').val(), postSeriesAdmin.currentPostId);
});
