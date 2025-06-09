// \admin\assets\js\admin.js
jQuery(document).ready(function ($) {
    console.log('admin.js loaded');

    /*********************************************************************************************************
    ************************************************ Groups JS ************************************************
    *********************************************************************************************************/

    // Load values based on selected location type (param)
    function loadValues($paramSelect, $valueSelect) {
        let param = $paramSelect.val();

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

    // Update value dropdown when param changes
    $('#sdb-location-rules').on('change', '.sdb-param-select', function () {
        let $param = $(this);
        let $value = $param.closest('.sdb-location-rule').find('.sdb-value-select');
        loadValues($param, $value);
    });


    // Load values for existing rules on page load
    $('.sdb-location-rule').each(function () {
        let $param = $(this).find('.sdb-param-select');
        let $value = $(this).find('.sdb-value-select');
        loadValues($param, $value);
    });

    // Add new location rule and load values
    let index = 1;
    $('#add-location-rule').click(function () {
        let html = `<div class="sdb-location-rule" style="margin-top:10px;">
            <select name="location[${index}][param]" class="sdb-param-select">
                <option value="post_type">Post Type</option>
                <option value="post">Page / Post</option>
                <option value="page_template">Page Template</option>
            </select>
            <select name="location[${index}][value]" class="sdb-value-select"></select>
        </div>`;

        $('#sdb-location-rules').append(html);

        let $newParam = $('#sdb-location-rules').find('.sdb-location-rule').last().find('.sdb-param-select');
        let $newValue = $('#sdb-location-rules').find('.sdb-location-rule').last().find('.sdb-value-select');
        loadValues($newParam, $newValue);
        index++;
    });

    // Auto-generate slug from input
    document.querySelectorAll('[data-slug-target]').forEach(input => {
        input.addEventListener('input', () => {
            const targetSelector = input.getAttribute('data-slug-target');
            const target = document.querySelector(targetSelector);
            if (!target) return;

            const appendId = input.getAttribute('data-append-id') || '';
            const slug = input.value
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '');

            target.value = appendId ? `${slug}_${appendId}` : slug;
        });
    });

});
