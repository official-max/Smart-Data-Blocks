jQuery(document).ready(function ($) {
    console.log('metaboxes.js loaded');

    const all_fields = window.sdbMetaboxData.fields;

    // Image uploader for all image fields
    $('.sdb-metabox-fields').on('click', '.sdb-upload-image', function (e) {
        e.preventDefault();

        const button = $(this);
        const target = button.data('target');

        const frame = wp.media({
            title: 'Select or Upload Image',
            button: { text: 'Use this image' },
            multiple: false
        });

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            $('#' + target).val(attachment.url);
            $('#' + target + '_preview').attr('src', attachment.url).show();
            button.text('Change Image');
        });

        frame.open();
    });

    // Remove image logic
    $('.sdb-metabox-fields').on('click', '.sdb-remove-image', function (e) {
        e.preventDefault();

        const button = $(this);
        const target = button.data('target');

        $('#' + target).val('');
        $('#' + target + '_preview').attr('src', '').hide();

        // Reset upload button text
        $('.sdb-upload-image[data-target="' + target + '"]').text('Select Image');
    });

    // Repeater: Add Item
    $('.sdb-metabox-fields').on('click', '.sdb-add-repeater-item', function (e) {
        e.preventDefault();

        const repeater = $(this).closest('.sdb-repeater');
        const container = repeater.find('.sdb-repeater-items');
        const fieldName = repeater.data('field-name');
        const index = container.children().length;

        const fieldIdMatch = fieldName.match(/sdb_fields\[(\d+)\]/);
        if (!fieldIdMatch) return;

        const fieldId = fieldIdMatch[1];
        const fieldConfig = all_fields.find(f => f.id == fieldId);
        if (!fieldConfig) return;

        const config = JSON.parse(fieldConfig.config);
        const subFields = config.sub_fields || [];

        let itemHtml = `<div class="sdb-repeater-item">`;

        subFields.forEach(function (subField) {
            const subFieldName = `${fieldName}[${index}][${subField.name}]`;
            const inputId = `repeater_${fieldId}_${index}_${subField.name}`;

            itemHtml += `<p><label>${subField.label}</label><br>`;

            switch (subField.type) {
                case 'textarea':
                    itemHtml += `<textarea name="${subFieldName}" rows="3"></textarea>`;
                    break;

                case 'image':
                    itemHtml += `
                        <img src="" id="${inputId}_preview" style="max-width: 150px; display:none;" />
                        <input type="hidden" name="${subFieldName}" id="${inputId}" />
                        <button type="button" class="button sdb-upload-image" data-target="${inputId}">Select Image</button>
                        <button type="button" class="button sdb-remove-image" data-target="${inputId}">Remove</button>
                    `;
                    break;

                case 'editor':
                    itemHtml += `<textarea id="${inputId}" name="${subFieldName}" class="sdb-editor-area"></textarea>`;
                    break;

                default:
                    itemHtml += `<input type="text" name="${subFieldName}" />`;
                    break;
            }

            itemHtml += `</p>`;
        });

        itemHtml += `<button type="button" class="button dashicons dashicons-remove sdb-remove-repeater-item"></button>`;
        itemHtml += `</div>`;

        container.append(itemHtml);

        // Init TinyMCE for any new editor field
        container.find('textarea.sdb-editor-area').each(function () {
            const editorId = $(this).attr('id');

            if (typeof tinymce !== 'undefined') {
                if (tinymce.get(editorId)) {
                    tinymce.get(editorId).remove();
                }

                tinymce.init({
                    selector: `#${editorId}`,
                    menubar: false,
                    toolbar: 'bold italic underline bullist numlist blockquote',
                    quickbars_selection_toolbar: 'bold italic | quicklink blockquote',
                    height: 200
                });
            }

            if (typeof quicktags !== 'undefined') {
                quicktags({ id: editorId });
            }
        });
    });

    // Repeater: Remove Item
    $('.sdb-metabox-fields').on('click', '.sdb-remove-repeater-item', function (e) {
        e.preventDefault();
        const repeater = $(this).closest('.sdb-repeater');
        const container = repeater.find('.sdb-repeater-items');
        const fieldName = repeater.data('field-name');

        // Remove TinyMCE instance if present
        $(this).closest('.sdb-repeater-item').find('textarea.sdb-editor-area').each(function () {
            const editorId = $(this).attr('id');
            if (tinymce.get(editorId)) {
                tinymce.get(editorId).remove();
            }
        });

        $(this).closest('.sdb-repeater-item').remove();

        // Reindex remaining items
        container.children('.sdb-repeater-item').each(function (index) {
            $(this)
                .find('input, textarea, select, img')
                .each(function () {
                    if (this.name) {
                        this.name = this.name.replace(/\[\d+\]/, `[${index}]`);
                    }
                    if (this.id) {
                        this.id = this.id.replace(/_(\d+)_/, `_${index}_`);
                    }
                    if ($(this).is('img')) {
                        $(this).attr('id', this.id + '_preview');
                    }
                });

            $(this)
                .find('.sdb-upload-image, .sdb-remove-image')
                .each(function () {
                    const target = $(this).data('target');
                    if (target) {
                        const newTarget = target.replace(/_(\d+)_/, `_${index}_`);
                        $(this).attr('data-target', newTarget);
                    }
                });
        });
    });
});
