// Global Funtion

console.log('function.js Loaded');

// 🧠 Function to reinit editors inside any newly added repeater item
function initializeEditorsInRepeater($container) {
    $container.find('textarea.wp-editor-area').each(function () {
        const editorId = $(this).attr('id');
        if (!tinymce.get(editorId)) {
            if (typeof QTags !== 'undefined' && QTags.instances[editorId]) {
                QTags.instances[editorId].remove();
            }
            tinymce.execCommand('mceAddEditor', true, editorId);
        }
    });
}