// // Add Fields
// function addField(container, index) {
//     const html = `
//             <div class="sdb-field" data-index="${index}">
//                 <div class="field-group">
//                     <p>
//                         <input type="text" name="fields[${index}][label]" placeholder="Field Label" />
//                         <input type="text" name="fields[${index}][name]" placeholder="field_name" />
//                         <select name="fields[${index}][type]" class="field-type-select">
//                             <option value="text">Text</option>
//                             <option value="textarea">Textarea</option>
//                             <option value="image">Image</option>
//                             <option value="editor">Editor</option>
//                             <option value="repeater">Repeater</option>
//                         </select>
//                         <button 
//                             type="button"  
//                             class="remove-field button"
//                             onclick="removeField(this, 0)">
//                             Remove
//                         </button>
//                     </p>
//                     <div class="sub-fields-container" style="display:none;">
//                         <h4>Sub Fields (Repeater)</h4>
//                         <div class="sub-fields-list"></div>
//                         <button 
//                             type="button" 
//                             class="add-sub-field button ">
//                             + Add Sub Field
//                         </button>
//                     </div>
//             </div>

//             </div>`;
//     container.insertAdjacentHTML('beforeend', html);
//     index++;
// }


// // Remove Fields
// function removeField(button, fieldId) {
//     const fieldWrapper = button.closest('.sdb-field');
//     if (fieldId) {
//         if (confirm("Are you sure you want to delete this field from the database?")) {
//             jQuery.ajax({
//                 url: sdb_admin.ajax_url,
//                 type: 'POST',
//                 data: {
//                     action: 'sdb_delete_field',
//                     field_id: fieldId,
//                     nonce: sdb_admin.nonce
//                 },
//                 success: function (res) {
//                     if (res.success) {
//                         fieldWrapper.remove();
//                     } else {
//                         alert('Failed to delete field.');
//                     }
//                 }
//             });
//         }
//     } else {
//         // new unsaved field, just remove from UI
//         fieldWrapper.remove();
//     }
// }


// document.addEventListener('DOMContentLoaded', () => {
//     let index = <?= count($fields) ?>;
//     const container = document.getElementById('sdb-fields-container');

//     // Add new main field
//     document.getElementById('add-field').addEventListener('click', addField.bind(null, container, index));

//     // Show/hide sub-fields container on type change
//     container.addEventListener('change', (e) => {
//         if (e.target.classList.contains('field-type-select')) {
//             const sdbField = e.target.closest('.sdb-field');
//             const subFieldsContainer = sdbField.querySelector('.sub-fields-container');
//             if (e.target.value === 'repeater') {
//                 subFieldsContainer.style.display = 'block';
//                 sdbField.classList.add('repeater-field-group');
//             } else {
//                 subFieldsContainer.style.display = 'none';
//             }
//         }
//     });

//     // Add sub field inside repeater
//     container.addEventListener('click', (e) => {
//         if (e.target.classList.contains('add-sub-field')) {
//             e.preventDefault();

//             const sdbField = e.target.closest('.sdb-field');
//             const subFieldsList = sdbField.querySelector('.sub-fields-list');
//             const mainIndex = sdbField.getAttribute('data-index');
//             const subIndex = subFieldsList.children.length;

//             const html = `
//                 <div class="sub-field">
//                     <input type="text" name="fields[${mainIndex}][sub_fields][${subIndex}][label]" placeholder="Sub Field Label" />
//                     <input type="text" name="fields[${mainIndex}][sub_fields][${subIndex}][name]" placeholder="sub_field_name" />
//                     <select name="fields[${mainIndex}][sub_fields][${subIndex}][type]" class="field-type-select">
//                         <option value="text">Text</option>
//                         <option value="textarea">Textarea</option>
//                         <option value="image">Image</option>
//                         <option value="editor">Editor</option>
//                     </select>
//                     <button type="button" class="remove-sub-field button">Remove</button>
//                 </div>`;

//             subFieldsList.insertAdjacentHTML('beforeend', html);
//         }

//         // Remove sub field
//         if (e.target.classList.contains('remove-sub-field')) {
//             e.target.closest('.sub-field').remove();
//         }





//         if (e.target.classList.contains('meta_key')) {
//             const meta_key_wrapper = e.target.closest('.meta_key_wrapper');
//             const meta_key = meta_key_wrapper.getAttribute('data-meta-key');

//             // Check if meta key is already shown
//             const existingCode = meta_key_wrapper.querySelector('code');

//             if (existingCode) {
//                 // Hide meta key
//                 existingCode.remove();
//                 e.target.setAttribute('title', 'Show');
//                 e.target.classList.remove('dashicons-hidden');
//                 e.target.classList.add('dashicons-visibility');
//             } else {
//                 // Show meta key
//                 const code = document.createElement('code');
//                 code.textContent = meta_key;
//                 meta_key_wrapper.insertBefore(code, e.target);

//                 e.target.setAttribute('title', 'Hide');
//                 e.target.classList.remove('dashicons-visibility');
//                 e.target.classList.add('dashicons-hidden');
//             }
//         }

//     });

// });