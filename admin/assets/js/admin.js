// // \admin\assets\admin.js
jQuery(document).ready(function ($) {

    console.log('admin.js loaded');

    function loadValues($paramSelect, $valueSelect) {
        var param = $paramSelect.val();

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'sdb_fetch_location_values',
                param: param,
                nonce: sdb_admin.nonce
            },
            success: function (response) {
                if (response.success) {
                    $valueSelect.html('');
                    $.each(response.data, function (key, val) {
                        $valueSelect.append('<option value="' + key + '">' + val + '</option>');
                    });
                }
            }
        });
    }

    // on param change
    $(document).on('change', '.sdb-param-select', function () {
        var $param = $(this);
        var $value = $param.closest('.sdb-location-rule').find('.sdb-value-select');
        loadValues($param, $value);
    });

    // Load initial for first rule
    $('.sdb-location-rule').each(function () {
        var $param = $(this).find('.sdb-param-select');
        var $value = $(this).find('.sdb-value-select');
        loadValues($param, $value);
    });

    // Add rule button (same as before, remember to initialize)
    var index = 1;
    $('#add-location-rule').click(function () {
        var html = `<div class="sdb-location-rule" style="margin-top:10px;">
            <select name="location[${index}][param]" class="sdb-param-select">
                <option value="post_type">Post Type</option>
                <option value="post">Page / Post</option>
                <option value="page_template">Page Template</option>
            </select>
            <select name="location[${index}][value]" class="sdb-value-select"></select>
        </div>`;
        $('#sdb-location-rules').append(html);
        // Load values for new rule
        var $newParam = $('#sdb-location-rules').find('.sdb-location-rule').last().find('.sdb-param-select');
        var $newValue = $('#sdb-location-rules').find('.sdb-location-rule').last().find('.sdb-value-select');
        loadValues($newParam, $newValue);
        index++;
    });
});


