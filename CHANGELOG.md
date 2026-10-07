# Changelog

All notable changes to this project will be documented here.

## Unreleased

### Fixed

- Show the installed Composer package version in Admin's **Versions installed** field instead of the database schema version. Preserve the schema-version fallback for source installations and related modules (#17).

## 1.2.2 - 2026-10-05

[Release 1.2.2 notes and upgrade requirements](docs/releases/1.2.2.md).

### Fixed

- Remove HTML tags containing stray attribute quotes without losing the following description text. Preserve literal comparisons, quoted comparison attributes, escaped markup cleanup, and limits applied after cleaning (#14).

### Documentation

- Make the separately installed [Rocket Web migration companion](https://github.com/rocketweb/module-shopping-feed-migration-rocketweb) prominent for users migrating from Rocket Shopping Feeds. Document direct Packagist installation, replace outdated future-importer guidance, and retain explicit preview, import, activation, and rollback requirements.

## 1.2.1 - 2026-10-04

[Release 1.2.1 notes and upgrade requirements](docs/releases/1.2.1.md).

### Fixed

- Keep free required option values in configurable-child minimum pricing, resolve percentage prices through Magento, and preserve explicitly selected defaults independently of value order (#9).
- Honor inherited backorder settings. Aggregate configurable availability in the order in stock, backorder, preorder, then out of stock, while retaining an explicitly out-of-stock parent. A positive MSI source cannot be overridden by a later backordered source (#10).
- Clean raw and escaped Page Builder markup, remove style/script content, decode double-encoded entities, and apply column length limits to the cleaned text. Preserve literal comparisons, quoted attributes, and delimiter safety (#11 and its follow-up).

### Added

- Optional Rocket Web migration companion discovery on the feed management screen, with an ACL-controlled link to the installed tool or its installation guide. Stores without legacy data see no notice.
- Composer suggestion and migration guidance for `rocketweb/module-shopping-feed-migration-rocketweb`, prepared for its separate 1.0.0 launch. Installation, preview, import, and activation remain explicit operations.

## 1.2.0 - 2026-10-03

[Release 1.2.0 notes and upgrade requirements](docs/releases/1.2.0.md).

### Compatibility

- Add an exact Magento Open Source 2.4.7-p10 / PHP 8.3 CI profile with one visible upstream Flysystem advisory exception, rejection of additional advisories, and a separately tested optional Flysystem 2.5.0 backport. See the [platform guidance](docs/compatibility/magento-2.4.7-p10.md).
- Restore PHPUnit 9 data-provider annotations alongside attributes in 21 test methods so the same unit suite runs on older and newer supported test frameworks. No runtime or Composer requirement change is introduced by this compatibility work.

### Changed: Admin editor

- New/Edit Feed and Test Feed now use Magento UI Component forms, with collapsible sections, declarative dependencies, DynamicRows, explicit mapping order, and custom category/parameter controls. Routes, feed types, database schema, and server-side save permissions are retained.
- UI data-provider modifiers and declarative parameter definitions replace legacy PHP form observers, tab plugins, and PHTML parameter renderers on the default editor. Existing site-specific editor customizations require migration; see [the developer guide](docs/ui-component-editor.md). Retained legacy files are not an alternate editor mode.
- Form data preserves structured parameters, empty collections, zero/false values, and literal template-like text. Upload passwords are masked before provider serialization; failed saves require re-entry of new or changed passwords.
- The shared form reduces dependence on legacy editor rendering. The optional Nebula grid, standard-theme editor fallback, and menu handling remain; no native Nebula UI Bridge acceptance or removal of the grid integration is claimed.

### Fixed: Admin forms and previews

- Include category IDs in new mappings, validate submitted category rows, and recover missing embedded IDs from existing map keys before generator sorting.
- Keep promotion dates in `Y/m/d` storage while displaying the Admin locale's format. All four dates persist through reopening and an unchanged second save.
- Reject control characters in column names at model save without coercing literal values or structured parameters.
- Validate preview lookup modes, SKU shapes, and positive product IDs with recoverable errors; preserve numeric SKU identity.
- Default new non-Google feeds to `use_microdata=0`, preserving existing and explicitly selected values.
- Preserve null directive parameters through UI initialization and save. Magento's initial value links and base input defaults otherwise changed null to an empty string, removing Generic feeds' default shipping-weight unit. Docker browser/output checks reproduced the failure on Magento 2.4.8 and 2.4.9 and verified the fix.
- Hide standard-grid mutation controls when the Admin role lacks their save, generate, or delete permission. Read-only users retain Test Feed, View Log, and Export; an empty bulk-action menu is omitted. Six role profiles passed browser checks on Magento 2.4.8 and 2.4.9. See the [permission follow-up](docs/reviews/2026-10-02-grid-permission-acceptance.md).

- Treat a null product-URL query parameter as an empty query string, avoiding a PHP 8.4 deprecation that Magento CLI turns into a failed preview.
- Prevent Test Now from also executing Magento's default button navigation. The UI-component preview submission now reaches its controller instead of redirecting to a missing page.

The [deployment report](docs/reviews/2026-10-02-mageos-latest-deployment-acceptance.md) records the repaired candidate on `mageos-latest`, including two follow-ups included in this revision found by real CLI and browser checks. All eight presets preserve configuration and normalized generated output through unchanged saves. No data migration recovers already-erased dates. Intentional custom configuration support and retained classes with live callers are preserved.

### Fixed: inventory, frontend scope, and currency

- Apply feed stock settings independently of Magento's storefront out-of-stock visibility for simple, configurable, grouped, and bundle collections.
- Derive configurable Local Inventory parent sources from their children, and require an enabled child source item at the same source before reporting the parent in stock. See the [stock regression report](docs/reviews/2026-10-02-stock-acceptance-fixes.md). Custom adapter subclasses overriding constructors must forward the new linked-product collection factory dependency.
- Honor website and store-view overrides for microdata, native price-schema suppression, Google Ads event enablement, and the optional destination ID. Existing scoped settings now take effect; review overrides and clear applicable caches. See the [frontend scope report](docs/reviews/2026-10-03-frontend-scope-fix.md).
- Preserve an existing feed's effective generation currency when its saved currency is blank, so opening and saving the UI Component form does not substitute a different store default. New feeds still use the store default, and explicit selections remain unchanged. See the [currency correction](docs/reviews/2026-10-03-existing-feed-currency-fix.md).

### Fixed: validation and security

- Hardened legacy editor escaping, request ID validation, schedule/status/file grid output, malformed URL handling, and log-rotation configuration.
- Corrected recursive promotion minimum-purchase condition handling and incomplete configuration handling; invalid legacy non-JSON rule conditions produce a recoverable error.
- Pinned direct CI workflow/action references to full commit hashes. See [the September 30 fix verification](docs/reviews/2026-09-30-review-fixes.md) for reproduced defects, accepted limits, and installed-runtime checks. The subsequent editor repairs are recorded separately above.

### Added: catalog presets

- OpenAI / ChatGPT (Google-compatible, beta) TSV preset with required brand, explicit identifier exemptions, GTIN checksums, availability dates, configurable grouping, and sale validation. Includes the mobile-subscription zero-price exception, short expiration metadata, and onboarding guidance. OpenAI ingestion acceptance remains unverified.

- Pinterest Catalog preset with quoted UTF-8 TSV, native availability values, configurable grouping checks, five-level product category paths, and field validation. Includes primary and supplemental feed setup guidance. Pinterest import acceptance remains a release check.
- TikTok Catalog preset with quoted UTF-8 CSV, required `sku_id`, native availability values, configurable variants, comma-separated additional images, and field validation. TikTok import acceptance remains a release check.
- Microsoft Merchant Center preset with UTF-8 tab-delimited TXT, native availability and sale-price formatting, configurable variants, identifier mappings, and field validation. The required ID is emitted last to avoid trailing tabs. Microsoft import acceptance remains a release check.
- Meta Catalog (Facebook and Instagram) template with quoted UTF-8 TSV, currency prices, configurable variants, Google taxonomy, and editable identifier mappings.
- A reusable value-map formatter with Meta product-feed availability defaults. Backorders and preorders export as `out of stock` until available.
- Meta required-field (including brand), condition, availability, price-format, and URL validation in generation and Test Feed. Invalid rows are skipped with reasons; missing identifiers produce warnings.
- Meta setup guidance in the Admin and a catalog feed guide. Commerce Manager acceptance remains a release check.

## 1.1.0 - 2026-09-28

### Added

- Optional native Nebula Admin feed grid, with the existing standard Admin editor, preview, and logs retained. Standard installations require no Nebula package.
- Fresh Mage-OS 3.5.0 installation and browser acceptance without Nebula, plus Google and custom-feed regression coverage.

### Fixed

- Stopped inferring `identifier_exists=FALSE` from incomplete catalog identifiers; absence now requires explicit confirmation and no supplied brand, GTIN, or MPN.
- Replaced new Google feeds' default SKU-to-MPN mapping with empty MPN and GTIN attribute mappings. Existing saved mappings are preserved.
- Added an `availability_date` placeholder to new Google feeds and skip backorder/preorder rows with missing, invalid, expired, or more-than-one-year-ahead dates, with a log warning and skipped count.
- Prevented online backorders from overriding Local Inventory quantities and respected disabled source items.
- Serialized Generic comma-delimited feeds as quoted CSV, preserving embedded commas and escaping double quotes.
- Fixed Local Inventory configurable-child availability when the parent has zero legacy quantity. Parent inheritance uses salability under default stock (#3).
- Included guest and all-group single-unit tier discounts in sale detection and price calculation, without applying bulk-only tiers. The Tier Price directive respects quantity one and preserves the product's customer-group context (#4).
- Included Search-only simple products in Complex Product Context Prioritization so their grouping does not depend on product ID order (#5).
- Read the declared enclosure, escape, and empty-value configuration keys. Added Generic Admin controls and preserved embedded delimiters when enclosure is configured (#6).
- Rendered safe tab-notice links and formatting, restored tab navigation after sanitization, and corrected notice typos (#7).

- Preserved encrypted upload credentials through masked and repeated saves, rejected unreadable credentials, and expanded ciphertext storage from `varchar(255)` to `text`.
- Scoped saved upload and schedule IDs to the current feed.
- Recovered interrupted queue rows on the next worker run, with lock rechecks and restart from the beginning to avoid duplicated partial output.
- Included multiple selected promotion rules correctly and fixed the Admin promotion counter's initialization.
- Counted UTF-8 characters without splitting multibyte text when applying column limits.
- Required an explicitly selected microdata feed and preserved native price metadata when no eligible feed is available.
- Treated storefront option values as literal values, with safe handling of malformed URL fragments.
- Escaped preview values without translating product data and kept generation traces out of the Admin response.
- Added the full-text feed-name index for standard-grid keyword search and corrected category-map form initialization.
- Corrected optional Nebula grid filtering, pagination, exports, ACL-controlled actions, and standard-editor routing.
- Made regression data providers and mock responses compatible with PHPUnit 9, 10, and 12.

### Upgrade

- Run `bin/magento setup:upgrade` for the password column and feed-name index.
- Review existing identifier mappings and add real availability dates for Google backorders/preorders. Saved column maps are preserved.
- Check custom recipients before resuming uploads. Comma output uses CSV quoting by default; saved enclosure, escape, and empty-value settings now take effect. Review feeds using Tier Price for previous bulk-only values.

### Documentation

- Added 1.1.0 release notes, wiki navigation, and a complete upgrade checklist.
- Documented identifier confirmation, real availability dates, local inventory status, CSV parsing, and the settings to review when upgrading existing feeds.

## 1.0.0 - 2026-09-09

### Added

- New `MageOS_ShoppingFeed` module and `mage-os/module-shopping-feed` package identity
- Consolidated Generic, Google Shopping, Google Local Inventory, and Google Promotions feed support
- Isolated database, configuration, route, cron, CLI, event, layout, UI, JavaScript, log, and output identifiers
- Isolated dependency-injection array keys and application cache identifiers
- Isolated feed output, promotion cache, process lock, and log paths
- Isolated Admin session keys used when restoring failed form submissions
- Declarative schema whitelist regenerated from the consolidated database schema
- Repository validation and CI checks for identity isolation, merged feed definitions, XML, Composer metadata, and PHP syntax
- Portable Magento test bootstrap and an expanded regression suite
- Integration coverage for module dependency wiring and feed queue database contracts
- CI installation, unit, integration, coding-standard, and dependency-injection compilation checks across supported Magento Open Source and Mage-OS releases
- An explicit Mage-OS 3.4.0 compatibility matrix while the upstream matrix provider still reports an older Mage-OS release
- Private vulnerability reporting policy
- Explicit MSI source-code to Google store-code mapping for Local Inventory
- Streaming gzip generation for FTP and SFTP uploads
- Current Google Ads `view_item` events for selected configurable variants
- Production acceptance plan covering store, feed, MSI, delivery, recovery, and release checks

### Fixed

- Initialized simple-product custom options on Hyva without RequireJS, including option price updates
- Included numeric attribute IDs in configurable swatch URLs so Hyva selects the advertised variant while retaining legacy attribute codes
- Used parent salability instead of parent quantity when inheriting configurable stock status
- Restricted request-selected microdata to enabled children of the current configurable product on the current website
- Isolated queue queries so an already queued feed does not prevent other due feeds from being scheduled
- Sanitized encoded tabs and line breaks after HTML entity decoding to preserve feed column alignment
- Stopped FTP/SFTP validation and upload when the configured remote directory cannot be entered
- Preserved nonstandard ports in simple and grouped product URLs
- Preserved JSON-looking scalar settings and structured arrays as distinct types, including malformed legacy text
- Normalized empty column defaults before sanitization so saving feeds does not emit PHP 8.1+ deprecation notices
- Removed the duplicated Google Shopping `shipping_weight` default column
- Added the required encoding to the Local Inventory feed definition
- Replaced legacy DoubleClick remarketing pixels and globals with Google tag events
- Made configurable deep links work independently of the dynamic remarketing setting
- Added support for configurable dropdown deep links as well as swatches
- Updated Promotions enums and repeated destinations for Shopping ads and free listings
- Updated schema.org offer URLs to HTTPS
- Corrected cached uploader reuse so each FTP or SFTP destination uses its own credentials
- Applied the saved gzip setting to product and Promotions uploads with cleanup after transfer
- Fixed post-upload event data so Promotions uploads receive the destination object instead of a Boolean result
- Removed the original paid-module product skip attribute from the new module's runtime behavior
- Excluded historical data patches that could inspect or move legacy-package data and log files
- Fixed PHP 8 failures in filter sorting, empty delimiter handling, generator state restoration, and additional image mapping
- Hardened price formatting against nonnumeric strings and restored label mapping for array-valued select attributes
- Passed modern configurable-product dependencies explicitly to avoid runtime service-locator fallbacks
- Replaced the service-locator serializer wrapper with Magento's serializer interface
- Made stock handling safe when no legacy stock item is returned and avoided duplicate stock-status reads
- Made empty-column replacement and option concatenation safe for incomplete configuration data
- Corrected log-handler replacement so stale handlers are not retained
- Restricted feed, Promotions, and log output to approved Magento directories and safe file extensions
- Added explicit Admin ACL resources and POST-only contracts for feed mutations
- Protected grid AJAX and export data sources with the feed-view ACL
- Made manual and scheduled queue creation share the queue model's persistence invariants
- Made required feed and queue database fields safe for new manual queue entries
- Secured Google taxonomy downloads with HTTPS, locale validation, response-status checks, and deterministic connection cleanup
- Guaranteed cron generation locks are released and closed after all PHP failures
- Made the generation CLI return a failure exit code when queue processing fails
- Made both CLI commands compatible with Symfony Console 7 return-type contracts
- Made unit and integration tests compatible with PHPUnit 9 through 12
- Made Magento XML validation safe when the module is already installed in the validation checkout
- Made the shared Promotions cache hash-aware and atomically replaceable across concurrent feed processes
- Removed PHP 8.2 dynamic-property deprecations from the legacy unit-test fixtures
- Made catalog-rule sale dates use Magento's configured store timezone instead of the server timezone
- Made edited schedules eligible to run again on the same store day
- Preserved associated-product inventory context while generating source-specific Local Inventory rows
- Removed Local Inventory's production test-mode workaround and skipped duplicate rechecks only during source remapping
- Removed PHPUnit 12 mock notices from the unit suite
- Corrected the Google Shopping promotion header from `promotions_id` to `promotion_id`
- Added current Google item-group titles, variant options, and standard variant attributes to Shopping feeds
- Kept Google rows aligned with their headers without trailing tabs
- Preserved Google sale-price column names while writing feed headers
- Omitted Google sale prices and effective dates when the rendered sale price is not lower than the regular price
- Skipped shipping mapping when `shipping_country` is not an array instead of passing invalid configuration to `array_filter()`

### Migration

- No automatic migration from the Rocket Web packages is performed
- Existing package installations and data remain untouched

### Changed

- Aligned Composer metadata, source notices, and the bundled license on OSL-3.0
- Split Magento Open Source and Mage-OS CI matrices so each distribution installs from its official Composer repository
- Updated the reusable Magento extension checks to `graycoreio/github-actions-magento2` 8.9.0
