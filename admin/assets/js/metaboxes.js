jQuery(document).ready(function ($) {
    console.log('metaboxes.js loaded');

    // Helper function to check if value is numeric
    function isNumeric(n) {
        return !isNaN(parseFloat(n)) && isFinite(n);
    }

    const all_fields = window.sdbMetaboxData.fields;

    // Encode quotes before submit
    // $('form#post').on('submit', function () {
    //     $('.sdb-metabox-fields input[type="text"], .sdb-metabox-fields textarea').each(function () {
    //         let val = $(this).val();
    //         if (val) {
    //             val = val.replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    //             $(this).val(val);
    //         }
    //     });
    // });



    // Image uploader for all image fields
    $('.sdb-metabox-fields').on('click', '.sdb-upload-image', function (e) {
        e.preventDefault();

        const button = $(this);
        const target = button.data('target');
        const input = $('#' + target);
        const preview = $('#' + target + '_preview');

        const frame = wp.media({
            title: 'Select or Upload Image',
            button: { text: 'Use this image' },
            multiple: false
        });

        // Pre-select the current image if it's an attachment ID
        if (input.val() && isNumeric(input.val())) {
            const selection = wp.media.attachment(input.val());
            frame.open();
            frame.content.mode('edit');
            frame.setState('gallery-edit').get('selection').add(selection);
        } else {
            frame.open();
        }

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();

            // Store the attachment ID (not URL)
            input.val(attachment.id);

            // Update preview with the full size URL
            preview.attr('src', attachment.url).show();
            button.text('Change Image');

            // Show remove button
            $('.sdb-remove-image[data-target="' + target + '"]').show();
        });

        frame.on('close', function () {
            // If no image was selected, restore the previous value
            if (!frame.state().get('selection').length) {
                const currentVal = input.val();
                if (currentVal && isNumeric(currentVal)) {
                    // If we have an ID, get the URL for preview
                    wp.media.attachment(currentVal).fetch().then(function (attachment) {
                        preview.attr('src', attachment.url).show();
                    });
                } else if (currentVal) {
                    // If we have a URL, use it directly
                    preview.attr('src', currentVal).show();
                }
            }
        });
    });

    // Remove image logic
    $('.sdb-metabox-fields').on('click', '.sdb-remove-image', function (e) {
        e.preventDefault();

        const button = $(this);
        const target = button.data('target');
        const input = $('#' + target);
        const preview = $('#' + target + '_preview');

        input.val('');
        preview.attr('src', '').hide();
        button.hide();

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
                        <img src="" id="${inputId}_preview" />
                        <input type="hidden" name="${subFieldName}" id="${inputId}" />
                        <button type="button" class="button sdb-upload-image" data-target="${inputId}">Select Image</button>
                        <button type="button" class="button sdb-remove-image" data-target="${inputId}">Remove</button>
                    `;
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
                        this.name = this.name.replace(/^(\w+\[\d+\])\[\d+\]/, `$1[${index}]`);
                    }
                    if (this.id) {
                        this.id = this.id.replace(/_(\d+)_/, `_${index}_`);
                    }

                    if ($(this).is('img')) {
                        const inputId = this.id.replace(/_preview$/, '');
                        $(this).attr('id', inputId + '_preview');
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



    // Gallery field handling
    $('.sdb-metabox-fields').on('click', '.sdb-add-gallery-images', function (e) {
        e.preventDefault();

        const container = $(this).closest('.sdb-gallery');
        const input = container.find('input[type="hidden"]');
        const thumbnails = container.find('.sdb-gallery-thumbnails');
        const currentIds = input.val() ? JSON.parse(input.val()) : [];

        const frame = wp.media({
            title: 'Select Gallery Images',
            button: { text: 'Use selected images' },
            multiple: true,
            library: { type: 'image' }
        });

        // Pre-select current images
        frame.on('open', function () {
            const selection = frame.state().get('selection');
            currentIds.forEach(function (id) {
                const attachment = wp.media.attachment(id);
                attachment.fetch();
                selection.add(attachment);
            });
        });

        frame.on('select', function () {
            const newIds = [];
            const attachments = frame.state().get('selection').toJSON();

            thumbnails.empty();

            attachments.forEach(function (attachment) {
                newIds.push(attachment.id);
                thumbnails.append(`
                <div class="sdb-gallery-thumb" data-id="${attachment.id}">
                    <img src="${attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url}">
                    <button type="button" class="sdb-remove-gallery-image">&times;</button>
                </div>
            `);
            });

            input.val(JSON.stringify(newIds));
            container.find('.sdb-add-gallery-images').text('Edit Gallery');
        });

        frame.open();
    });

    // Remove gallery image
    $('.sdb-metabox-fields').on('click', '.sdb-remove-gallery-image', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const thumb = $(this).closest('.sdb-gallery-thumb');
        const container = thumb.closest('.sdb-gallery');
        const input = container.find('input[type="hidden"]');
        const currentIds = input.val() ? JSON.parse(input.val()) : [];

        // Remove this image from array
        const imageId = thumb.data('id');
        const newIds = currentIds.filter(id => id != imageId);

        // Update UI and hidden field
        thumb.remove();
        input.val(JSON.stringify(newIds));

        // Update button text if empty
        if (newIds.length === 0) {
            container.find('.sdb-add-gallery-images').text('Add Gallery Images');
        }
    });

});
