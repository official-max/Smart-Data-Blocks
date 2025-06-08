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
        });

        frame.open();
    });

    $('.sdb-metabox-fields').on('click', '.sdb-remove-image', function (e) {
        e.preventDefault();
        const target = $(this).data('target');
        $('#' + target).val('');
        $('#' + target + '_preview').attr('src', '').hide();
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
                    itemHtml += `<textarea name="${subFieldName}" rows="3" style="width:90%;"></textarea>`;
                    break;

                case 'image':
                    itemHtml += `
                        <img src="" id="${inputId}_preview" style="max-width:100px; display:none; margin-bottom:5px;" />
                        <input type="hidden" name="${subFieldName}" id="${inputId}" />
                        <button type="button" class="button sdb-upload-image" data-target="${inputId}">Select Image</button>
                        <button type="button" class="button sdb-remove-image" data-target="${inputId}">Remove</button>
                    `;
                    break;

                default:
                    itemHtml += `<input type="text" name="${subFieldName}" style="width:90%;" />`;
                    break;
            }

            itemHtml += `</p>`;
        });

        itemHtml += `<button type="button" class="button sdb-remove-repeater-item">Remove</button>`;
        itemHtml += `</div>`;

        container.append(itemHtml);
    });

    // Repeater: Remove Item
    $('.sdb-metabox-fields').on('click', '.sdb-remove-repeater-item', function (e) {
        e.preventDefault();
        const repeater = $(this).closest('.sdb-repeater');
        const container = repeater.find('.sdb-repeater-items');
        const fieldName = repeater.data('field-name');

        $(this).closest('.sdb-repeater-item').remove();

        // Reindex all remaining items
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
