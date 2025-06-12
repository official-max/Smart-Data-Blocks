
// admin/assets/js/metaboxes.js
jQuery(document).ready(function ($) {
    console.log('metaboxes.js loaded');

    // Image uploader
    $('.sdb-metabox-fields').on('click', '.sdb-upload-image', function (e) {
        e.preventDefault();
        const button = $(this);
        const target = button.data('target');

        const custom_uploader = wp.media({
            title: 'Select Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        custom_uploader.on('select', function () {
            const attachment = custom_uploader.state().get('selection').first().toJSON();
            $('#' + target).val(attachment.url);
            $('#' + target + '_preview').attr('src', attachment.url);
        });

        custom_uploader.open();
    });

    // Image remover
    $('.sdb-metabox-fields').on('click', '.sdb-remove-image', function (e) {
        e.preventDefault();
        const target = $(this).data('target');
        $('#' + target).val('');
        $('#' + target + '_preview').attr('src', '');
    });

    // Repeater: add new item
    $('.sdb-metabox-fields').on('click', '.sdb-add-repeater-item', function () {
        const repeater = $(this).closest('.sdb-repeater');
        const itemsContainer = repeater.find('.sdb-repeater-items');
        const fieldId = repeater.data('field-id');
        const fieldName = repeater.data('field-name');
        const index = itemsContainer.children('.sdb-repeater-item').length;

        const fieldConfig = sdbMetaboxData.fields.find(f => f.id == fieldId);
        if (!fieldConfig) return;

        const config = JSON.parse(fieldConfig.config);
        const subFields = config.sub_fields || [];

        let html = `<div class="sdb-repeater-item">`;

        subFields.forEach(sub => {
            const subname = sub.name;
            const sublabel = sub.label;
            const subtype = sub.type;
            const inputName = `${fieldName}[${index}][${subname}]`;
            const inputId = `repeater_${fieldId}_${index}_${subname}`;

            html += `<p><label>${sublabel}</label><br>`;

            switch (subtype) {
                case 'textarea':
                    html += `<textarea name="${inputName}" rows="3"></textarea>`;
                    break;

                case 'image':
                    html += `
                        <img src="" id="${inputId}_preview" style="max-width:100px; display:block;" />
                        <input type="hidden" name="${inputName}" id="${inputId}" />
                        <button type="button" class="button sdb-upload-image" data-target="${inputId}">Select Image</button>
                        <button type="button" class="button sdb-remove-image" data-target="${inputId}">Remove Image</button>
                    `;
                    break;

                case 'editor':
                    html += `<textarea name="${inputName}" id="${inputId}" class="wp-editor-area" rows="8"></textarea>`;
                    break;

                default: // text
                    html += `<input type="text" name="${inputName}" />`;
                    break;
            }

            html += `</p>`;
        });

        html += `<button type="button" class="button dashicons dashicons-remove sdb-remove-repeater-item" title="Remove"></button>`;
        html += `</div>`;

        itemsContainer.append(html);

        // Initialize any editor field inside the newly added item
        const $newItem = itemsContainer.children('.sdb-repeater-item').last();
        initializeEditorsInRepeater($newItem);
    });

    // Repeater: remove item
    $('.sdb-metabox-fields').on('click', '.sdb-remove-repeater-item', function () {
        $(this).closest('.sdb-repeater-item').remove();
    });

    // 🔁 Initialize editor on page load
    $('.sdb-metabox-fields').find('textarea.wp-editor-area').each(function () {
        const editorId = $(this).attr('id');
        if (!tinymce.get(editorId)) {
            if (typeof QTags !== 'undefined' && QTags.instances[editorId]) {
                QTags.instances[editorId].remove();
            }
            tinymce.execCommand('mceAddEditor', true, editorId);
        }
    });

});
