# Developer Reference – Smart Blocks Plugin

This file contains developer-facing notes and internal logic explanation for the Smart Blocks plugin.

---

## 📦 Custom Tables

### 1. Group Table: `wp_sdb_groups`

| Field       | Description                  |
|-------------|------------------------------|
| `id`        | Primary Key (auto increment) |
| `name`      | Group name (required)        |
| `location`  | JSON string (location rules) |
| `key_slug`  | Unique slug for referencing  |
| `created_at`| Timestamp                    |

---

### 2. Fields Table: `wp_sdb_fields`

| Field        | Description                                      |
|--------------|--------------------------------------------------|
| `id`         | Primary Key                                      |
| `group_id`   | Foreign Key – links to `sdb_groups.id`           |
| `label`      | Field label (UI)                                 |
| `name`       | Field name (for saving/getting value)            |
| `type`       | Field type (`text`, `image`, `editor`, etc.)     |
| `options`    | Extra field data (used for dropdowns, repeaters) |
| `sort_order` | Field ordering inside a group                    |
| `created_at` | Timestamp                                        |

---

## 🧩 Database Handling

### `register_activation_hook()`

| Part                     | Meaning                                                              |
|--------------------------|----------------------------------------------------------------------|
| `register_activation_hook()` | Runs a function when plugin is activated                          |
| `__FILE__`               | Refers to current plugin file (used as the first parameter)          |
| `'sdb_create_table'`     | The function that creates necessary DB tables                        |

---

### Code Breakdown for DB Table Creation

| Line                              | Purpose                                           |
|-----------------------------------|---------------------------------------------------|
| `global $wpdb;`                   | Access WordPress DB object                       |
| `$wpdb->get_charset_collate();`   | Fetches charset/collation for SQL safety         |
| `$wpdb->prefix . 'tablename';`    | Creates WP-safe table names                      |
| `require_once ABSPATH...`         | Loads `upgrade.php` (needed for `dbDelta()`)     |

---

### 🔄 `dbDelta()` Usage Notes

- Only supports SQL for `CREATE TABLE`, `ALTER TABLE`, `DROP INDEX`, etc.
- Does **not** support `SELECT`, `INSERT`, `UPDATE`, or `DELETE`.
- For those, use `$wpdb->insert()`, `$wpdb->get_results()`, etc.

---

## 💾 WordPress DB Object – `$wpdb`

Common functions:
```php
$wpdb->insert();       // Insert data
$wpdb->get_results();  // Fetch multiple rows
$wpdb->get_row();      // Fetch a single row
$wpdb->get_var();      // Fetch single value
$wpdb->prefix;         // WP prefix (e.g., wp_)


## ⚙️ File Overview

| File Path                      | Purpose                                                |
| ------------------------------ | ------------------------------------------------------ |
| `smart-blocks.php`             | Main loader – defines constants, hooks, includes files |
| `includes/db-schema.php`       | Creates DB tables via `dbDelta()`                      |
| `includes/ajax.php`            | Handles AJAX for dynamic field data                    |
| `admin/class-admin.php`        | Admin menu + page loader                               |
| `admin/settings-page.php`      | UI for group & location rules                          |
| `admin/settings-fields.php`    | Field add/edit/delete UI                               |
| `admin/class-metaboxes.php`    | Adds metaboxes based on location rules                 |
| `admin/assets/js/admin.js`     | Field group UI logic (location rule changes)           |
| `admin/assets/js/metaboxes.js` | Handles media + repeater fields on post editor         |
| `admin/assets/css/admin.css`   | (Optional) admin panel styling                         |
