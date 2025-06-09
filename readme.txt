/***********************************************************************************************************/
/****************************************=== Smart Blocks ===***********************************************/
/***********************************************************************************************************/

=== Smart Blocks ===
Contributors: L&Fcode  
Tags: custom-fields, acf, repeater, image-upload, meta-box  
Requires at least: 5.0  
Tested up to: 6.5  
Stable tag: 1.0  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

A lightweight ACF-style custom field manager plugin with repeater support, image upload, editor fields, and post-based location rules.

== Description ==

Smart Blocks is a developer-friendly plugin to create and manage custom meta fields similar to ACF (Advanced Custom Fields), including:

- Text, Textarea, Image, Editor fields  
- Repeater fields with nested subfields  
- Location rules like Post Type, Specific Page, Page Template  
- Dynamic backend meta box rendering  
- Frontend field value access via `sdb_get_field()` helper function  

== Features ==

- 🧠 Field Types: text, textarea, image, editor, repeater  
- 📦 Group-wise field management  
- 📍 Location Rules:
  - Post Type
  - Specific Post/Page
  - Page Template
- 🖼️ Image upload via WordPress media uploader  
- 🔁 Repeater fields with dynamic subfields  
- 🧩 Dynamic metabox rendering on post/page edit  
- 🔌 Extendable via custom JS & PHP  

== Usage ==

### 1. Creating Field Groups:
- Go to **Smart Blocks → Add Group**
- Add fields inside each group (supports nested repeaters)

### 2. Location Rules:
Assign field groups to:
- All posts of a specific type  
- A specific post/page  
- Pages using a specific template  

### 3. Access Field Values in Theme (Frontend):

```php
// Get a single field
$value = sdb_get_field('group_slug', 'field_name');

// Get all fields in a group
$data = sdb_get_field('group_slug');

// Get field for a specific post (WIP)
$data = sdb_get_field('group_slug', 'field_name', $post_id);






✅ Smart Blocks Plugin — Full Review
🔧 FILE STRUCTURE OVERVIEW
File	Purpose
smart-blocks.php	Main plugin loader – defines constants, loads files
db-schema.php	Creates sdb_groups and sdb_fields tables
ajax.php	Handles dynamic value loading for location dropdowns
class-admin.php	Admin menu + page loading
settings-page.php	Field group (location rules) add/delete interface
settings-fields.php	Manage fields for selected group
class-metaboxes.php	Adds metabox on post edit screen based on location rules
admin.js	Dynamically updates "Location Rules" UI
metaboxes.js	Handles media upload and dynamic repeater UI


smart-blocks/
│
├── smart-blocks.php             → Main plugin loader – defines constants, loads files
├── readme.txt                   → You're reading it!
├── docs/
│   └── dev-reference.md         → File explanation
│
├── admin/
│   ├── class-admin.php          → Admin menu, UI loader (page loading)
│   ├── class-metaboxes.php      → Metabox render + save logic
│   ├── settings-page.php        → Group creation page
│   ├── settings-fields.php      → Field management UI
│
├── assets/
│   ├── js/
│   │   ├── admin.js             → Admin-side JS (for field builder UI)
│   │   ├── metaboxes.js         → Metabox field handling (repeater, image upload)
│   ├── css/
│   │   └── admin.css            → Admin panel styling (if needed)
│
├── includes/
│   ├── db-schema.php            → Creates database tables (groups, fields)
│   ├── ajax.php                 → AJAX handlers (delete field, load dropdowns)
│   ├── field-rendering.php      → Contains `sdb_get_field()` and output helpers




/***********************************************************************************************************/
/****************************************=== Issues ===***********************************************/
/***********************************************************************************************************/

This plugin includes a fix for an issue where long text or a large number of repeater fields (especially when nested) do not save properly in WordPress. The issue occurs due to server-side limits such as max_input_vars, post_max_size, and database field types.

📌 Problem
When submitting forms with many repeater rows or large text areas, data was not saving to the WordPress database. The issue was due to:

PHP's max_input_vars limit (default is 1000)

Large serialized data exceeding allowed POST size

meta_value column in the wp_postmeta table not supporting large content

Over-sanitization breaking the structure (wp_kses_post() on big content)

✅ Solution Implemented
Increased max_input_vars to 10000

Ensured post_max_size, memory_limit, and upload_max_filesize were sufficient

Changed database column type to LONGTEXT (if needed)

Used safer sanitization (sanitize_textarea_field() or wp_kses() as required)

Removed wp_die() or debugging print_r() from production code

