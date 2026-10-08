# Mage-OS Shopping Feed

[![CI on main](https://github.com/mage-os-lab/module-shopping-feed/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/mage-os-lab/module-shopping-feed/actions/workflows/ci.yml?query=branch%3Amain)

`MageOS_ShoppingFeed` generates product feeds for Mage-OS and Magento Open Source.

**Version 1.2.2** fixes description cleanup when catalog HTML contains stray attribute quotes, preserving the text that follows. It includes the required-option pricing, backorder, and Page Builder fixes from 1.2.1. Read the [1.2.2 release notes](docs/releases/1.2.2.md).

**Migrating from Rocket Shopping Feeds?** Use the separately installed [Rocket Web migration companion](https://github.com/rocketweb/module-shopping-feed-migration-rocketweb). It previews existing feed configuration and imports it into disabled Mage-OS feeds for review before activation. See [installation and migration steps](docs/wiki/Rocket-Web-Migration.md).

Version 1.2.0 introduced Magento UI Component forms, five catalog presets, and corrections to stock selection, configurable Local Inventory, frontend configuration scope, and existing-feed currency. Read the [1.2.0 release notes](docs/releases/1.2.0.md), [upgrade checklist](docs/wiki/Installation-and-Upgrade.md#upgrading-from-11-to-120), and [compatibility results](docs/reviews/2026-10-03-magento-247-acceptance.md). Version [1.1.0](docs/releases/1.1.0.md) remains documented for existing installations.

Version 1.2.0 includes [Meta Catalog](docs/wiki/Meta-Catalog.md), [Microsoft Merchant Center](docs/wiki/Microsoft-Merchant-Center.md), [TikTok Catalog](docs/wiki/TikTok-Catalog.md), [Pinterest Catalog](docs/wiki/Pinterest-Catalog.md), and [OpenAI / ChatGPT Google-compatible beta](docs/wiki/OpenAI-ChatGPT.md). Local validation and generation pass; external provider ingestion remains unverified. The OpenAI profile requires confirmation during onboarding and does not establish account access or checkout support.

This repository consolidates four related Rocket Web modules into one independently named Mage-OS module:

- Generic product feeds
- Google Shopping feeds
- Google Local Inventory feeds, including optional Multi-Source Inventory support
- Google Promotions feeds

Current integrations include configurable-product deep links, schema.org offer data for Automatic Item Updates, Google Ads `view_item` events, gzip transfer over FTP or SFTP, reservation-aware MSI quantities, and explicit MSI source-to-Google-store mapping.

The package has its own Composer name, PHP namespace, Magento module name, database tables, configuration paths, routes, cron group, event names, JavaScript aliases, and default output names. It does not replace or mutate an installed Rocket Web package.

## Status

Version [1.2.2](https://github.com/mage-os-lab/module-shopping-feed/releases/tag/v1.2.2) is the current stable release. Its [verification record](docs/reviews/2026-10-05-release-1.2.2-preparation.md) separates local checks, merged-runtime CI, and publication. Existing Rocket Web installations are not migrated automatically. Read [MIGRATION.md](MIGRATION.md) and the [migration companion guide](docs/wiki/Rocket-Web-Migration.md) before evaluating it on a store that already uses Rocket Shopping Feeds.

Unreleased hardening corrects website-stock reservations, physical-source Local Inventory quantities, promotion persistence and caching, and several Admin and storefront edge cases. The [Mage-OS 3.5.0 review](docs/reviews/2026-10-08-hardening-and-mageos-3.5-verification.md) records the original local checks. The [CI and native theme follow-up](docs/reviews/2026-10-08-remote-ci-and-native-theme-acceptance.md) covers Hyvä, the Nebula grid and supported editor fallback, and an additional filter-preservation fix. These changes are not part of the published 1.2.2 package.

Version 1.2.0 makes Magento UI Component forms the default for New/Edit Feed and Test Feed. It retains the existing schema and save routes, but changes the editor customization API. See the [Admin form guide](docs/wiki/Admin-UI-Component-Forms.md) and [developer migration guide](docs/ui-component-editor.md).

The 1.2.0 runtime was accepted at `133af71` on `mageos-latest`. It includes the UI Component forms, permission-filtered grid, preview repairs, feed-controlled stock selection, configurable Local Inventory corrections, store-scoped frontend settings, and preservation of existing feeds' effective currency. The [extended local acceptance record](docs/reviews/2026-10-03-local-acceptance.md) is the current source for results and remaining checks. All eight presets passed save/reopen and unchanged-save/output comparisons in the [earlier Mage-OS deployment](docs/reviews/2026-10-02-mageos-latest-deployment-acceptance.md) and [Magento 2.4.8/2.4.9 Docker run](docs/reviews/2026-10-02-magento-docker-acceptance.md). The final PHP suite passes 809 tests on all three frameworks; both Docker versions pass 21 official integration tests and production compilation. Nebula remains disabled on `mageos-latest`.

Operational coverage includes scheduling edge cases, interrupted-worker recovery, private FTP/SFTP delivery, 26 semantic catalog scenarios, 15 native configurable MSI scenarios, pricing, store-scoped microdata, and eight 5,000-product preset generations per Magento version. Two real hourly cron cycles also pass with stable output and empty queues; the [current record](docs/reviews/2026-10-03-local-acceptance.md) records final cleanup and exact scope. External recipient and native Nebula bridge acceptance remain separate.

Version 1.1.0 retains custom feed mapping and Hyva/Luma deep links while correcting identifier defaults, backorder dates, Local Inventory statuses, and CSV serialization. Existing Google mappings need review, comma-feed recipients must accept quoted CSV, and `setup:upgrade` is required for the upload-password column and feed-search index. See the [changelog](CHANGELOG.md), [Google/custom-feed acceptance report](docs/reviews/2026-09-28-google-custom-feed-fixes.md), and [issue acceptance report](docs/reviews/2026-09-28-github-issues.md). Historical [1.0.0 notes](docs/releases/1.0.0.md) remain available.

Run [ACCEPTANCE-TEST-PLAN.md](ACCEPTANCE-TEST-PLAN.md) against the exact release before enabling production schedules or uploads.

## Requirements

- A currently supported Mage-OS or Magento Open Source release with `magento/framework` 103.0.6-p15 or later in the 103.x series
- A PHP version supported by the selected platform release, within PHP 8.1 through PHP 8.5
- Magento cron when scheduled feed generation is enabled
- Magento Multi-Source Inventory APIs for source-level Local Inventory feeds

Magento Open Source 2.4.7-p10 passes the dedicated compatibility profile and local eight-preset Admin/output acceptance. Its upstream Flysystem dependency remains affected by a security advisory. See the [2.4.7-p10 guidance](docs/compatibility/magento-2.4.7-p10.md) and [acceptance record](docs/reviews/2026-10-03-magento-247-acceptance.md) for the tested scope, scoped CI exception, and optional tested backport. The module does not weaken a store's Composer security settings.

The unreleased hardening branch targets Mage-OS 3.5.0, based on Magento Open Source 2.4.9, in CI. Its checks install the package into a Mage-OS 3.5.0 project, then run the unit and integration suites, Magento coding standard, and dependency-injection compilation. The [CI and native theme record](docs/reviews/2026-10-08-remote-ci-and-native-theme-acceptance.md) separates completed CI from each follow-up candidate; check the workflow's exact head SHA when evaluating a commit.

Mage-OS 3.5.0 on PHP 8.4.24 was also verified locally on Magebox with Hyva: a complete 160-row storefront feed, all exported prices and stock values, and all 38 available configurable deep links passed the recorded checks. This local evidence is separate from CI and Merchant Center acceptance.

The module supports Magento's standard Admin grid and an optional native Nebula Admin grid. With Nebula enabled, feed editing, previews, and logs open in the standard Admin layout so the complete existing editor remains available; returning to the list restores Nebula. No Nebula dependency is required for standard installations. See the [Admin compatibility acceptance report](docs/reviews/2026-09-25-admin-compatibility.md) for the tested versions and limits.

A fresh Mage-OS 3.5.0 installation with no Nebula packages also passed Composer installation, schema upgrades, DI compilation, production-mode generation, and standard Admin browser checks. See the [installation without Nebula report](docs/reviews/2026-09-28-without-nebula-acceptance.md).

## Installation

Install the stable 1.2 release line from [Packagist](https://packagist.org/packages/mage-os/module-shopping-feed):

```bash
composer require 'mage-os/module-shopping-feed:^1.2.2'
bin/magento module:enable MageOS_ShoppingFeed
bin/magento setup:upgrade
bin/magento cache:clean
```

For a source checkout, place or symlink the repository at `app/code/MageOS/ShoppingFeed`, then run the Magento commands above without `composer require`.

## Migrating from Rocket Shopping Feeds

The optional [Rocket Web migration companion](https://github.com/rocketweb/module-shopping-feed-migration-rocketweb) is published separately as `rocketweb/module-shopping-feed-migration-rocketweb` 1.0.0 on [Packagist](https://packagist.org/packages/rocketweb/module-shopping-feed-migration-rocketweb). Stores without legacy feeds only need the main module.

On staging, keep the legacy modules installed and enabled during schema upgrades, then install both packages:

```sh
composer require 'mage-os/module-shopping-feed:^1.2.2' \
  'rocketweb/module-shopping-feed-migration-rocketweb:^1.0' --no-update
composer update mage-os/module-shopping-feed \
  rocketweb/module-shopping-feed-migration-rocketweb --with-dependencies
bin/magento module:enable MageOS_ShoppingFeed RocketWeb_ShoppingFeedMigration
bin/magento setup:upgrade
```

Use the [migration guide](docs/wiki/Rocket-Web-Migration.md) for backups, permissions, preview/import, output comparison, activation, and rollback. Installing the packages does not migrate data. Imports start disabled, with schedules and uploads held until explicit activation. Custom PHP, shared settings, and recipient URLs need manual review.

## Use

Manage feeds in the Admin under **Catalog > Mage-OS Shopping Feed > Feeds Management**.

Global settings are under **Stores > Configuration > Mage-OS > Mage-OS Shopping Feed**.

The module registers two CLI commands:

```bash
# Build queued feeds, a specific feed, or one test SKU
bin/magento mage-os:shopping-feed:generate [feed_id] [test_sku]

# Create feed-generation queue entries from configured schedules
bin/magento mage-os:shopping-feed:schedule
```

The dedicated `mageos_shopping_feed` cron group schedules feeds hourly and processes its queue every minute by default.

Feed output is restricted to `pub/media/mageos-shopping-feed` and its safe subdirectories. Files are publicly downloadable for recipient fetches; default filenames contain the feed ID and are predictable. FTP or SFTP upload leaves that public local copy in place. Export only data intended for public distribution. Per-feed logs are restricted to `var/log` and use `mageos_shopping_feed_*.log` by default.

When inspecting CSV or TSV feeds in a spreadsheet, import the columns as text and disable formula evaluation. Feed values beginning with `=`, `+`, `-`, or `@` can be interpreted as formulas by spreadsheet software. The module preserves these values for feed recipients without adding apostrophes or tabs.

## Documentation

For setup and troubleshooting, use the [Shopping Feed support bot on Rocket Web](https://rocketweb.com/rocket-shopping-feeds). Select **Open support chat** on the product page.

The [public GitHub Wiki](https://github.com/mage-os-lab/module-shopping-feed/wiki) is published separately; it does not automatically update when this repository changes. The reviewable GitHub Wiki source is under [`docs/wiki`](docs/wiki), starting with [`Home.md`](docs/wiki/Home.md). Documentation contributors should update that source and follow [`docs/WIKI-MAINTENANCE.md`](docs/WIKI-MAINTENANCE.md) rather than editing the public wiki independently.

For version 1.2.0, start with the [operating guide](docs/wiki/Admin-UI-Component-Forms.md), [implementation and customization contract](docs/ui-component-editor.md), [earlier platform checks](docs/ui-component-editor-acceptance.md), and [completed local acceptance](docs/reviews/2026-10-03-local-acceptance.md). Read dated acceptance reports in order; passing automated or platform checks do not override a later reproduced failure.

## Development validation

Run the dependency-free consolidation checks and PHP syntax checks from the repository root:

```bash
composer validate --strict --no-check-publish
php dev/tests/validate.php
php dev/tests/validate-wiki.php
find . -path './.git' -prune -o -type f \( -name '*.php' -o -name '*.phtml' \) -print0 | xargs -0 -n1 php -l
```

Validate every Magento XML file against the schemas from an existing Magento or Mage-OS checkout:

```bash
php dev/tests/validate-magento-xml.php /path/to/magento
```

Run the imported and modernized unit suite with the PHPUnit installation from that checkout:

```bash
MAGENTO_ROOT=/path/to/magento /path/to/magento/vendor/bin/phpunit -c phpunit.xml.dist
```

The unit-test bootstrap loads Magento's test framework and the module directly, so the module does not need to be installed in the validation checkout. The prepared CI configuration also installs the package into currently supported Magento Open Source releases and explicitly into Mage-OS 3.5.0, runs the unit and integration suites, checks the Magento coding standard, and compiles dependency injection.

## Provenance and license

The consolidated source and exact import revisions are documented in [PROVENANCE.md](PROVENANCE.md). Original Rocket Web copyright and author notices are retained in source files.

The package uses the [Open Software License 3.0](LICENSE.txt). Composer metadata, source notices, and the bundled license are aligned on OSL-3.0. See [LICENSING.md](LICENSING.md).
