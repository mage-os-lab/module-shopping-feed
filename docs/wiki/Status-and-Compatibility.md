# Status and compatibility

Mage-OS Shopping Feed 1.2.2 is the current stable release. See [Release 1.2.2](Release-1-2-2) for changes and recorded verification. Users migrating from Rocket Shopping Feeds can install the separate [Rocket Web migration companion](Rocket-Web-Migration); installation alone does not import data.

> Documentation baseline: release 1.2.2 (`v1.2.2`); earlier acceptance is identified by version. Last reviewed: 2026-10-05.

Version 1.2.2 removes tags with stray attribute quotes without losing following description text. Its local unit suite passes 863 tests on Mage-OS 3.5, Magento 2.4.8, and Magento 2.4.7-p10, plus 47 JavaScript checks. The merged runtime passed all 25 post-merge CI jobs. These results do not replace existing-store or recipient acceptance.

Version 1.2.1 fixes required-option pricing, inherited/configurable backorders, and Page Builder description cleanup, and adds optional Rocket Web migration discovery. Its local suite passes 846 tests on Mage-OS 3.5 and Magento 2.4.8, plus 47 JavaScript checks. The [1.2.1 release summary](Release-1-2-1) distinguishes these checks from the earlier store acceptance below.

The [UI Component editor](Admin-UI-Component-Forms) in 1.2.0 keeps the Composer requirements. The 1.2.0 runtime `133af71` is deployed on `mageos-latest` and matches both Magento Open Source 2.4.8/2.4.9 Docker installations. The 1.2.0 suite passes 809 unit tests on each framework and 21 official integration tests per Docker version, plus production compilation. Earlier eight-preset browser/output and six-role permission checks cover the unchanged controls; the final currency provider also passes an unchanged browser save/output comparison. The repository's `docs/reviews/2026-10-03-local-acceptance.md` records current product, MSI, pricing, operations, frontend, and cleanup evidence. Native Nebula bridge rendering and external recipients remain separate acceptance targets.

For the 1.2.0 scope and upgrade requirements, see [Release 1.2.0](Release-1-2-0). Platform requirements remain unchanged; custom editor integrations and explicit constructor overrides require review.

## Package identity

| Surface | Value |
| --- | --- |
| Composer package | `mage-os/module-shopping-feed` |
| Magento module | `MageOS_ShoppingFeed` |
| PHP namespace | `MageOS\ShoppingFeed` |
| License | OSL-3.0 |
| Configuration section | `mageos_shopping_feed` |
| Admin route | `mageos_shopping_feed` |
| Cron group | `mageos_shopping_feed` |
| CLI prefix | `mage-os:shopping-feed` |

## Platform requirements

The package currently declares:

* PHP 8.1 through PHP 8.5
* `magento/framework` 103.0.6-p15 or later in the 103.x series
* A PHP version supported by the selected Mage-OS or Magento Open Source release
* Magento cron for scheduled queue creation and processing
* Magento Inventory APIs for source-level Local Inventory output

The unreleased hardening branch adds Mage-OS 3.5.0 as an explicit CI target. CI also installs the module into supported Magento Open Source projects, runs unit and integration tests, checks Magento coding standards, and compiles dependency injection. See the [CI and native theme record](https://github.com/mage-os-lab/module-shopping-feed/blob/feat/mageos-3.5-hardening/docs/reviews/2026-10-08-remote-ci-and-native-theme-acceptance.md) and verify the workflow's exact head SHA when evaluating a candidate.

Mage-OS 3.5.0 on PHP 8.4.24 was also verified locally on Magebox with Hyva, including full-store Google Shopping generation, price and stock comparisons, and configurable deep links. See [Release 1.0.0](Release-1-0-0) for that historical scope and its limits. The October 8 hardening candidate has separate PHP 8.4.26, Hyvä 1.5.2, and Nebula 0.9.0 acceptance evidence.

Compatibility in CI is not a production acceptance result. Test the exact module commit against a representative store, catalog, inventory setup, and external destination before enabling production schedules or uploads.

Magento Open Source 2.4.7-p10 uses a dedicated PHP 8.3 CI profile with a single visible upstream Flysystem advisory exception. Local acceptance passes 809 unit tests, 21 native integration tests, production compilation, all eight preset save/output comparisons, and six Admin role profiles. No runtime correction was needed. Other platform jobs retain their normal security policy. The repository's [2.4.7-p10 guidance](https://github.com/mage-os-lab/module-shopping-feed/blob/v1.2.0/docs/compatibility/magento-2.4.7-p10.md) covers the scope and optional tested backport; its [acceptance record](https://github.com/mage-os-lab/module-shopping-feed/blob/v1.2.0/docs/reviews/2026-10-03-magento-247-acceptance.md) distinguishes compatibility from platform security approval. Extended catalog, transfer, and recurring-cron checks on 2.4.8/2.4.9 were not repeated on this profile.

## Admin theme compatibility

Released version 1.1.0 retains Magento's standard Admin grid and provides an optional native grid for Nebula Admin. Nebula installations use its native filtering, sorting, selection, and pagination controls. Feed editing, product previews, and logs open in Magento's standard Admin layout, with the existing configuration tabs and widgets. Back and Save return to the Nebula list. This is a native grid integration with the standard editor, not a replacement editor built with Nebula forms.

The integration activates automatically when the Nebula modules and theme are active. It adds no required Nebula package and does not change unrelated Admin screens. Mass actions retain the module's existing POST, form-key, and ACL checks. The grid excludes feed configuration and upload credentials.

The tested local combination and remaining limits are recorded in the [Admin compatibility report](https://github.com/mage-os-lab/module-shopping-feed/blob/v1.1.0/docs/reviews/2026-09-25-admin-compatibility.md). Storefront Hyva support is a separate feature.

A separate fresh Mage-OS 3.5.0 installation with no Nebula packages passed Composer installation, schema updates, DI compilation, production-mode generation, and standard Admin browser checks. This covered both the default Mage-OS Admin grid and Magento's classic Admin theme. See the [installation without Nebula report](https://github.com/mage-os-lab/module-shopping-feed/blob/v1.1.0/docs/reviews/2026-09-28-without-nebula-acceptance.md) for the exact scope and the additional keyword-search and editor-initialization fixes.

## Feed support

| Capability | Status |
| --- | --- |
| Meta, Microsoft, TikTok, Pinterest | New in the 1.2.0; external ingestion unverified |
| OpenAI Google-compatible | New in the 1.2.0, beta; onboarding confirmation required |
| Generic feeds | Included |
| Google Shopping | Included |
| Google Local Inventory | Included |
| Multi-Source Inventory | Optional, required for source-level rows |
| Google Promotions | Included as part of a Google Shopping feed |
| FTP and SFTP upload | Included |
| Gzip upload | Included |
| Rocket Web migration companion | Optional, separately installed; preview and explicit import/activation |

## Distribution status

The package is listed on [Packagist](https://packagist.org/packages/mage-os/module-shopping-feed). Install the stable 1.2 line with `composer require 'mage-os/module-shopping-feed:^1.2.2'`, or use the tagged source installation described in [Installation and upgrade](Installation-and-Upgrade). The optional [migration companion](https://packagist.org/packages/rocketweb/module-shopping-feed-migration-rocketweb) has its own 1.0.0 release.

Do not infer release availability from the presence of source code alone. Check the repository's releases and the configured Composer repository at the point of installation.

## Storefront assumptions

Simple-product custom-option deep links use RequireJS on Luma. On Hyvä, a [`hyva_` layout handle](https://docs.hyva.io/hyva-themes/writing-code/layout-and-templates/the-hyva_-layout-handles.html) selects a native JavaScript template that waits for Alpine initialization and dispatches option change events. Dropdown, multiselect, radio, and checkbox options use the existing `#optionId=valueId` URL format. This integration requires [`hyva.alpineInitialized`](https://docs.hyva.io/hyva-themes/writing-code/the-window-hyva-object.html#hyvaalpineinitializedcallback), available since Hyvä 1.2.8 and 1.3.4, and registers the inline script with Hyvä CSP when that helper is available.

Configurable deep links include numeric attribute IDs for Hyva's native selection handling and retain attribute codes for legacy swatch renderers. All 38 available variants in the local acceptance feed selected correctly with matching prices. See [Configurable product deep links](Configurable-Product-Deep-Links).

These are Hyva compatibility corrections, not confirmed Mage-OS 3.5 regressions. The Google Ads event bridge still uses RequireJS and needs a separate Hyva integration; native variant selection does not imply Google Ads event delivery.

Run the focused frontend checks with `node --test dev/tests/frontend/*.test.cjs` (Node.js 22+ and PHP with SimpleXML). Validate the exact product page against the storefront theme in use. A passing backend feed generation test does not prove that microdata, configurable deep links, or Google Ads events work in a customized theme.
