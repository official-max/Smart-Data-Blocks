// \admin\assets\js\admin.js
jQuery(document).ready(function ($) {
    console.log('admin.js loaded');
    let index = 1;

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


    // Function to load value based on param
    function handleParamChange($param) {
        const $rule = $param.closest('.sdb-location-rule');
        const $value = $rule.find('.sdb-value-select');
        loadValues($param, $value);
    }

    // Update value dropdown when param changes
    $('#sdb-location-rules').on('change', '.sdb-param-select', function () {
        handleParamChange($(this));
    });


    // Load values for existing rules on page load
    $('.sdb-location-rule').each(function () {
        const $param = $(this).find('.sdb-param-select');
        handleParamChange($param);
    });

    // Add new location rule and load values
    $('#add-location-rule').click(function () {
        // Count current number of rules using direct children
        const parent = document.querySelector('#sdb-location-rules');
        const currentCount = parent.querySelectorAll(':scope > .sdb-location-rule').length;
        console.log(currentCount);

        const html = `
        <div class="sdb-location-rule" style="margin-top:10px;">
            <select name="location[${currentCount}][param]" class="sdb-param-select">
                <option value="post_type">Post Type</option>
                <option value="post">Page / Post</option>
                <option value="page_template">Page Template</option>
            </select>
            <select name="location[${currentCount}][value]" class="sdb-value-select"></select>
        </div>`;

        $('#sdb-location-rules').append(html);

        const $newRule = $('#sdb-location-rules .sdb-location-rule').last();
        const $newParam = $newRule.find('.sdb-param-select');

        handleParamChange($newParam);

        index++; // If you plan to use it elsewhere
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
