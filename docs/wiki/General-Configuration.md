# General configuration

General Configuration defines the store context, output location, delimiter, price behavior, and stock behavior for one feed.

> Documentation baseline: release 1.2.1 (`v1.2.1`); earlier acceptance is identified by version. Last reviewed: 2026-10-07. The version-display correction below is unreleased.

The 1.2.0 editor labels this section **General**. Its [Admin form guide](Admin-UI-Component-Forms) covers save controls, store-view reloads, and current acceptance results and limits.

## Feed settings

| Setting | What it controls |
| --- | --- |
| Name | The label shown in the Admin. It is not the output filename. |
| Store View | The store context used for product attributes, URLs, prices, categories, and inventory. |
| Feed Currency | The currency used when formatting price directives. Allowed currencies populate the choices; the 1.2.0 editor also preserves an unavailable saved selection for review. |
| Feed Path | The output directory. Output is restricted to `pub/media/mageos-shopping-feed` and safe subdirectories. |
| File Name | The output filename; `%s` substitutes the feed ID. A literal name is retained when cloning, so review it before generating a clone. |
| Delimiter | The field separator for generated rows. Google templates default to tabs. Generic comma output quotes CSV fields by default. |
| Cell Enclosure (Generic) | Optional enclosure for each header and value. Blank uses double quotes for comma output and no enclosure for other delimiters. |
| Enclosure Escape (Generic) | Prefix for an enclosure inside a cell. Blank doubles the enclosure character. |
| Empty Cell Value (Generic) | Optional replacement for empty values. Numeric zero is retained. |

Changing the store view can change the category tree and attribute values. Save the feed, then review Categories Map, currency, URLs, and representative product output again.

In the 1.2.0 editor, an existing feed with a blank saved currency displays its effective generation currency. An unchanged save makes that currency explicit, preserving prices even when the store default differs. New feeds use the store default. Explicit saved selections remain unchanged.

## Public feed files

Generated files are publicly downloadable from the media URL. Default filenames contain the feed ID and can be guessed. FTP or SFTP upload leaves the local file in place; a custom filename is not access control. The module does not provide a private-output mode.

Review every mapped attribute before generation and include only data intended for public distribution. If a recipient requires confidential data, arrange access controls with the hosting operator before generating it. Verify the exact URL from a signed-out session and confirm the recipient can still fetch it after any hosting change.

When inspecting CSV or TSV feeds in a spreadsheet, import the columns as text and disable formula evaluation. Values beginning with `=`, `+`, `-`, or `@` can be interpreted as formulas. The module preserves those values for feed recipients without adding apostrophes or tabs. Check the raw file in a text editor if the spreadsheet changes a value.

## Price and inventory settings

### Apply Catalog Price Rules

When enabled, catalog price rules participate in sale-price calculation. Guest and all-group tier discounts available for one unit also participate; bulk-only and other customer-group discounts do not. Tier-only discounts have no invented sale date range. Confirm the resulting regular price, sale price, and sale dates against the selected store view and timezone.

### Use default Stock Statuses

When enabled, the module uses Magento stock information. Set it to **No** only when a product attribute intentionally carries the feed's availability state.

### Alternate Stock/Availability Attribute

Select the custom attribute used when default stock status is disabled. Supported output values are `in_stock`, `out_of_stock`, `backorder`, and `preorder`; spaces in `in stock` and `out of stock` are normalized to underscores. Unrecognized values fall back to `out_of_stock`.

Google Shopping backorders and preorders also require a valid `availability_date` in 1.1. Google Local Inventory uses local availability rules and does not accept online backorder or preorder states. See [Google Shopping](Google-Shopping) and [Local Inventory](Google-Local-Inventory-and-MSI).

### Use Qty Increments

When enabled, quantity increments participate in price calculation. Test products with non-default increments before applying this setting broadly.

### Use Stock Reservations

When enabled, reservations participate in quantity and availability calculations. Google Local Inventory enables this by default. Validate the result against the website stock and source configuration used by the selected store.

### Complex Product Context Prioritization

When enabled, simple products attached to configurable, grouped, or bundle products are prioritized for processing in their complex-product context. This includes Catalog, Search, and Catalog/Search visibility, so a Search-only child created before its parent can retain variant grouping. It adds work to generation. Measure it on large catalogs.

## Global settings

Open **Stores > Configuration > Mage-OS > Mage-OS Shopping Feed**.

### General Info

* **Versions installed** reports the module version information available to the Admin. The unreleased fix for [issue #17](https://github.com/mage-os-lab/module-shopping-feed/issues/17) displays the installed `mage-os/module-shopping-feed` Composer package version. Source installations under `app/code`, or packages without a known Composer version, fall back to the database schema version. That fallback can remain `1.0.0` across package releases.
* **Cron Enabled** controls the module's scheduled queue creation and processing. Direct CLI commands still work when module cron is disabled.

### Log Settings

* **Logging Level** sets the minimum log severity.
* **Log rotate (Kb)** controls when a feed log is archived based on size.

### Google

* **Enable Automatic Updates (Microdata)** adds schema.org offer data to product pages.
* **Enable Google Ads Dynamic Remarketing Events** permits a `view_item` event after a configurable selection resolves to a product.
* **Google Ads Destination ID** optionally adds a `send_to` value such as `AW-123456789`.

See [Automatic updates and schema.org](Automatic-Updates-and-Schema-org) and [Google Ads view_item events](Google-Ads-View-Item-Events) before enabling storefront behavior.

## Verify changes

After changing general settings:

1. Save and reload the feed.
2. Test a known product.
3. Confirm price, currency, availability, quantity, URL, and category context.
4. Generate a non-production file and compare row counts and values with the previous accepted file.

Text settings beginning with `[` or `{` remain text after saving and reloading. Structured array settings retain their array values. Version 1.0.0 also preserves malformed legacy text instead of failing while loading it as JSON.
