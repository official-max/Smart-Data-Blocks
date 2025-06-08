| Part                         | Meaning                                                                    |
| ---------------------------- | ---------------------------------------------------------------------------|
| `register_activation_hook()` | WordPress ko bolna: plugin activate hote hi kuch kaam karna hai            |
| `__FILE__`                   | Plugin file path jahan ye likha gaya hai                                   |
| `'sdb_create_table'`         | Aapka function jo tab chalega jab plugin activate hoga (create Table)      |


| Line                            | Purpose                                                |
| ------------------------------- | ------------------------------------------------------ |
| `global $wpdb;`                 | WordPress DB object use karne ke liye                  |
| `$wpdb->get_charset_collate();` | DB encoding/collation set karne ke liye                |
| `$wpdb->prefix . 'tablename';`  | WordPress-safe table names banane ke liye              |
| `require_once ABSPATH...`       | `dbDelta()` use karne ke liye required file load karna |

// prefix
$table_groups = $wpdb->prefix . 'sdb_groups';
Ye variable aapke custom table ka naam set karta hai — with WordPress table prefix.


// dbDelta()
Aap jab custom table banana chahte ho dbDelta() se (safe way), toh us function ko use karne se pehle uska file include karna padta hai.
Important Restrictions:
dbDelta() sirf CREATE TABLE, ALTER TABLE, DROP INDEX, etc. jaisi SQL commands ke liye use hota hai.
INSERT, SELECT, UPDATE, DELETE ke liye dbDelta() use nahi hota.
Unke liye aap $wpdb->insert(), $wpdb->get_results(), etc. use karte ho.


// database object $wpdb 
$wpdb WordPress ka built-in database access object hai — jisse aap queries likh sakte ho, jaise:
$wpdb->insert()
$wpdb->get_results()
$wpdb->prefix



Group Table: wp_sdb_groups
id              -- PK
name, location  -- required fields
key_slug        -- used for referencing
created_at      -- timestamp

Fields Table: wp_sdb_fields

id              -- PK
group_id        -- FK (linked to sdb_groups)
label, name     -- field info
type            -- 'text', 'image', etc.
options         -- (for dropdowns/repeaters in JSON)
sort_order      -- for ordering fields
created_at      -- timestamp

















/***********************************************************************************************************/
/****************************************=== Smart Blocks ===***********************************************/
/***********************************************************************************************************/

=== Smart Blocks ===
Contributors: jatincode
Tags: custom-fields, acf, repeater, image-upload, meta-box
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight ACF-style custom field manager plugin with repeater support, image upload, editor fields, and post-based location rules.

== Description ==

Smart Blocks is a developer-friendly plugin to create and manage custom meta fields like ACF (Advanced Custom Fields), including:
- Text, Textarea, Image, Editor fields
- Repeater fields with nested subfields
- Location rules like Post Type, Specific Page, Page Template
- Dynamic backend meta box rendering
- Frontend field value access using `sdb_get_field()` function




== Features ==

- 🧠 Field Types: text, textarea, image, editor, repeater
- 📦 Group-wise field management
- 📍 Location Rules:
  - Post Type
  - Specific Post/Page
  - Page Template
- 🖼️ Image upload via WordPress media uploader
- 🔁 Repeater fields with dynamic subfields
- 🧩 Dynamic metabox render on post/page edit
- 🔌 Extendable with your own custom JS & PHP

== Usage ==

**1. Creating Field Groups:**
- Go to **Smart Blocks → Add Group**
- Add fields inside each group (supports nested repeater)

**2. Location Rules:**
- You can assign field groups to:
  - All posts of a specific type
  - A specific post/page
  - Pages using a specific template

**3. Access Field Values in Theme (Frontend):**

Use the helper function:

```php
// Get a single field
$value = sdb_get_field('group_slug', 'field_name');

// Get all fields of a group
$data = sdb_get_field('group_slug');

// Get Field specific post using post ID
$data = sdb_get_field('group_slug', 'field_name', PostID);









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
│
├── admin/
│   ├── class-admin.php          → Admin menu, UI loader (page loading)
│   ├── class-metaboxes.php      → Metabox render + save logic
│   ├── settings-page.php        → Group creation page
│   ├── settings-fields.php      → Field management UI
│   ├── assets/
│   │   ├── admin.js             → Admin-side JS (for field builder UI)
│   │   ├── metaboxes.js         → Metabox field handling (repeater, image upload)
│   │   └── admin.css            → Admin panel styling (if needed)
│
├── includes/
│   ├── db-schema.php            → Creates database tables (groups, fields)
│   ├── ajax.php                 → AJAX handlers (delete field, etc.)
│   ├── field-rendering.php      → Contains `sdb_get_field()` and output helpers
