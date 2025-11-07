
jQuery(document).ready(function ($) {
    // Toggle new-series form
    $('#add_new_series_btn').on('click', function () {
        $('#new_series_form').toggle();
        $('#new_series_name').focus();
    });

    // Save new series via AJAX
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
                alert(response.data || 'Error creating series');
            }
        });
    });

    // Load series parts via AJAX
    function loadSeriesParts(series_id, current_post_id) {
        if (!series_id) {
            // If no series selected, show only the current post (if one exists)
            $('#series_parts_list').html('');
            if (current_post_id) {
                $('#series_parts_list').append('<li data-id="' + current_post_id + '" style="color: blue;">the current post</li>');
                $('#series_parts_order').val(current_post_id);
            }
            makeSortable();
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
                    $('#series_parts_list').append('<li data-id="' + item.ID + '"' + extraStyle + '>' + item.title + '</li>');
                });
                // Build initial order hidden field
                var order = [];
                $('#series_parts_list li').each(function () { order.push($(this).data('id')); });
                $('#series_parts_order').val(order.join(','));
                makeSortable();
            } else {
                $('#series_parts_list').html('<li>No posts in this series</li>');
            }
        });
    }

    // Make the list sortable and update hidden input on change
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

    // When series selection changes
    $('#selected_series').on('change', function () {
        var series_id = $(this).val();
        loadSeriesParts(series_id, postSeriesAdmin.currentPostId);
    });

    // Load on init
    loadSeriesParts($('#selected_series').val(), postSeriesAdmin.currentPostId);
});

