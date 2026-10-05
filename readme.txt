=== Kerishna Shop Migrator ===
Contributors: shcd
Tags: orders, customers, migration, backup, ecommerce
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: woocommerce
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Move store orders and customer accounts with source-safe backups, independent exports, smart imports, and duplicate-aware customer matching.

== Description ==

Kerishna Shop Migrator gives store owners a controlled way to move important commerce data between WordPress sites without turning migration into an all-or-nothing process.

Need only your orders? Export only orders. Need customer accounts for a new store? Export customers separately. Have a backup file and do not remember what it contains? Drop it into Kerishna and the importer identifies whether it contains orders, customers, or a compatible combined backup.

The source site stays read-only during export and direct site-to-site transfer. Kerishna does not intentionally delete, trash, renumber, or move source orders, customer accounts, products, or source IDs.

= Why Kerishna? =

Store migrations often become difficult for three reasons: source and destination IDs do not match, customer accounts can be duplicated, and users are forced to move more data than they actually need.

Kerishna is designed around those problems:

* **Choose what moves:** orders and customers can be exported independently.
* **Keep the source untouched:** export and direct-copy operations read source data without modifying it.
* **Avoid duplicate customer accounts:** existing destination users are checked by previous source mapping, email address, and normalized phone number before a new account is created.
* **Do not trust numeric IDs across sites:** source IDs are kept as reference data while the destination uses its own IDs.
* **Preserve orders when catalog data differs:** if a product cannot be safely matched, the order line can remain as a standalone historical line item instead of being attached to the wrong product.
* **Reduce import decisions:** one smart Drag & Drop area accepts JSON and CSV and detects the data type automatically.

= Independent Order Export =

Order backups are intentionally separate from customer-account backups. This is useful when you need sales history, accounting data, or order archives on another site but do not want to create customer accounts there.

Order exports include the order information required for restoration, including:

* Source order reference and order number.
* Status, currency, timestamps, totals, discounts, shipping, and taxes.
* Billing and shipping details stored on the order.
* Payment method information and transaction reference when available.
* Product line items, quantities, totals, taxes, public item metadata, and stable product identity information.
* Shipping lines, fees, and coupons.

During an order-only import, Kerishna does **not** create new customer accounts as a side effect. If a matching account already exists on the destination, the order can reuse that account; otherwise the order remains safely importable without forcing customer creation.

= Independent Customer Export =

Customer exports contain registered WooCommerce customer accounts separately from orders. This lets you migrate customer profiles only when the destination actually needs them.

Customer backups can include:

* Source user reference.
* Login name, email address, display name, first name, and last name.
* Registration date.
* Billing phone number.
* Billing and shipping profile fields.

Passwords and password hashes are never included in export files. A newly created destination customer receives a generated password and can use the normal WordPress password-reset flow.

= Duplicate-Aware Customer Import =

Before Kerishna creates a destination customer account, it checks for an existing match in this order:

1. A source-to-destination mapping created by a previous Kerishna import.
2. Exact email-address match.
3. Normalized phone-number match.
4. A new customer is created only when no safe match is found.

Phone matching normalizes common number formats, including Persian and Arabic digits and common Iranian mobile prefixes such as `+98`, `0098`, `98`, and `09`.

Existing matched accounts are reused instead of being duplicated. Kerishna does not overwrite an existing customer's profile merely because the source contains different values.

= Smart Drag & Drop Import =

The admin interface uses a single import area instead of separate JSON/CSV and order/customer import buttons.

Drop a supported file into the import area or click to select it. Kerishna determines:

* Whether the file is JSON or CSV.
* Whether it contains orders.
* Whether it contains customers.
* Whether it is a compatible combined backup from an earlier Kerishna/SHCD release.

This keeps the interface simpler while preserving explicit control over what is exported.

= JSON and CSV =

Kerishna supports both JSON and CSV workflows:

* **JSON** is recommended when you want the richest structured backup and the most reliable restoration of nested order/customer information.
* **CSV** is useful for spreadsheet-friendly archives and inspection. CSV exports include a UTF-8 BOM for better compatibility with spreadsheet applications.

New exports identify their data type so the importer can distinguish order-only and customer-only backups. Compatible older combined JSON/CSV backups remain supported.

= Product and Variation Matching =

WordPress and WooCommerce numeric IDs are site-specific. Kerishna does not assume that a product ID or variation ID from the source points to the same object on the destination.

Product and variation matching uses stable information such as available SKU/global identifiers, product identity data, parent-product context, and variation attributes. If a safe destination match cannot be found, the order line is preserved without connecting it to an unrelated product.

= Source-Safe Migration =

The source site is treated as read-only during export and direct site-to-site copy.

Kerishna includes safeguards intended to prevent accidental writes back to the source:

* Source orders are not deleted, trashed, moved, or renumbered by export/direct-copy operations.
* Source order IDs remain source references rather than being forced onto the destination database.
* A backup fingerprinted as originating from the current site is blocked from being imported back into that same site.
* Direct copy is blocked when the configured destination resolves to the source site itself.

The destination creates and uses its own WordPress/WooCommerce IDs.

= Direct Site-to-Site Transfer =

For repeated migrations or backup workflows, Kerishna can connect two sites with generated API credentials and send order batches directly to the destination.

You can choose whether registered customer accounts should be included in direct transfers. Requests are signed and authenticated, and the source remains read-only during the operation.

= Built for Real Store Workflows =

Kerishna can be useful when you are:

* Moving order history to a rebuilt store.
* Keeping a secondary store or recovery site with order backups.
* Separating order migration from customer-account migration.
* Rebuilding a store where product IDs differ from the original database.
* Consolidating registered customers while avoiding obvious email/phone duplicates.
* Creating JSON or spreadsheet-friendly CSV archives before a major site change.

= Admin Experience =

The Kerishna admin interface includes:

* A neumorphic interface inspired by the Kerishna brand colors.
* A locally bundled Shabnam font with no external font CDN dependency.
* Real-time Persian, English, and Arabic interface switching.
* RTL layout for Persian and Arabic and LTR layout for English.
* Progress feedback and an activity log for import/export operations.
* A single Drag & Drop import workflow for JSON and CSV.

== Installation ==

1. Upload the `kerishna-shop-migrator` folder to `/wp-content/plugins/`, or install the ZIP from Plugins > Add New > Upload Plugin.
2. Make sure WooCommerce is installed and active.
3. Activate Kerishna Shop Migrator.
4. Open **Kerishna** from the WordPress admin menu.
5. Choose JSON or CSV as the export format.
6. Export orders or customers independently, depending on what you need.
7. On the destination, drop the exported file into the smart import area.
8. For direct site-to-site transfer, install Kerishna on both sites, generate credentials on the destination, and save them on the source.

== Frequently Asked Questions ==

= Can I migrate orders without importing customer accounts? =

Yes. Orders and customers have independent export actions. An order-only import does not create new customer accounts. If a matching customer already exists on the destination, the order may reuse that existing account.

= Can I import customers without importing orders? =

Yes. Export customers separately and drop the customer JSON or CSV file into the smart import area.

= How does the importer know what is inside my file? =

New backups include a data-type marker. Kerishna also inspects compatible older backups and CSV headers to identify orders, customers, or combined data.

= Does Kerishna delete or change orders on the source site? =

No. Export and direct-copy workflows are designed to read source order/customer data without deleting, moving, or changing source records or IDs.

= Are source WordPress IDs copied directly to the destination? =

No. Source IDs are stored as references. The destination creates or uses its own order, product, variation, and user IDs.

= What happens when source and destination product IDs are different? =

Kerishna does not rely on matching numeric IDs. It uses stable product/variation identity information. When a safe match cannot be found, the order line can be retained independently instead of being linked to the wrong product.

= What if a customer already exists on the destination? =

Kerishna checks previous source mapping, email, and normalized phone number before creating an account. A safe existing match is reused.

= Are customer passwords copied? =

No. Passwords and password hashes are intentionally excluded from customer backups.

= What happens to guest orders? =

Guest orders remain guest orders. Kerishna does not create a customer account for a checkout that was a guest on the source.

= Which format should I use? =

JSON is recommended for the richest migration backup. CSV is useful when you also want a spreadsheet-friendly archive or need to inspect the exported data manually.

= Does Kerishna support HPOS? =

Kerishna uses WooCommerce order APIs and detects High-Performance Order Storage or legacy order storage for the migration environment.

= Does the admin panel load fonts or UI assets from a CDN? =

No. The Shabnam font and Kerishna/SHABNAM.DEV visual assets are bundled inside the plugin. The plugin UI does not depend on an external font CDN.

== Screenshots ==

1. Kerishna dashboard with migration readiness and destination connection settings.
2. Independent order and customer export controls with JSON/CSV format selection.
3. Smart Drag & Drop importer that detects file format and data type.
4. Direct site-to-site migration settings and progress log.
5. Real-time Persian, English, and Arabic interface switching.

== Changelog ==

= 1.0.0 =
* Replaced direct admin `<style>` output for the menu icon with a properly enqueued admin stylesheet.
* Kept plugin-page CSS/JS scoped to the Kerishna admin page.
* First public release of Kerishna Shop Migrator.
* Added independent JSON/CSV exports for orders and registered customers.
* Added smart Drag & Drop import with automatic JSON/CSV and data-type detection.
* Added backward compatibility for compatible combined backups from previous SHCD/Kerishna builds.
* Added order-only imports that do not create new customer accounts as a side effect.
* Added customer duplicate protection using source mapping, email, and normalized phone matching.
* Added direct authenticated site-to-site transfer with optional registered-customer migration.
* Added source read-only protections and same-source import guard.
* Added destination product/variation matching without assuming source IDs match destination IDs.
* Added preservation of unmatched product lines so order history is not discarded.
* Added Persian, English, and Arabic real-time admin interface switching.
* Added locally bundled Shabnam typography and Kerishna/SHABNAM.DEV branding assets.
* Added HPOS and legacy order storage detection.

== Upgrade Notice ==

= 1.0.0 =
Initial public release of Kerishna Shop Migrator.


== Source Code ==

The JavaScript source code is included in the plugin package:

* assets/js/admin.js
* assets/js/admin.src.js

No obfuscated code is included. The source files are readable and maintained with the plugin.
