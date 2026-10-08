# Remote CI and native theme acceptance

Date: 2026-10-08. Branch: `feat/mageos-3.5-hardening`. This follows the [hardening review](2026-10-08-hardening-and-mageos-3.5-verification.md) and records the optional-theme installation and browser checks separately from that earlier Luma/m137 run.

The native Hyvä storefront and Nebula listing passed the checks below on Mage-OS 3.5.0. Nebula editing, preview, and logs use the module's supported `Magento/backend` fallback. Native Nebula UI Bridge form rendering is not established by these results.

## Source and installation identity

| Item | Verified value |
| --- | --- |
| Initial hardening commit | `35c2eefcc63087cc1a936dc90123aba3af37f033` |
| PHPUnit compatibility correction | `399bd76d55f6df9e7de753117812f81474f6aef5` |
| Follow-up runtime change | Preserve active Nebula column filters in pagination and sorting URLs; retain explicit filter clearing |
| Distribution | Mage-OS 3.5.0 |
| PHP / database / search | PHP 8.4.26 / MySQL 8.4 / OpenSearch 3.8.0 |
| Composer lock SHA-256 | `03f0b553293ab2fc0549690ad8c80b3b96878e36e7b067b860146bb16d9e80a1` |
| Storefront theme and module | Hyvä 1.5.2, `Hyva/default` |
| Required layout reset | `hyva-themes/magento2-base-layout-reset` 2.0.5, enabled and resets generated |
| Admin theme | `qoliber/nebula-admin-theme` 0.9.0, `Qoliber/Nebula` |
| Nebula source revision | `89dc520cbebe0be4714ca8a4979e12688b597e7c` |
| Optional modules | Five Hyvä modules and all sixteen bundled Nebula modules enabled; Koti sample-data modules disabled |
| Runtime source parity | All 591 tracked PHP, PHTML, XML, XSD, JS, CJS, JSON, and YML files match the installed candidate; zero mismatches |
| Source manifest SHA-256 | `0b6804a4a2e35dffe3ff5ec15f96c3c2bfa50144c052f07daa63e82b53503663` |

The runtime manifest hashes ordered `path:sha256` newline records and excludes Markdown. It identifies the installed final source, including the follow-up adapter and its tests. Compare the workflow's head SHA with the commit being installed as a separate CI check.

The application was installed again with a new synthetic database, administrator, catalog, inventory, feeds, and configuration. The existing Composer-locked dependency tree was reused without changing its packages. The lab used its own Docker project and volumes and bound the web server only to `127.0.0.1:8156`. No shared-store database or configuration was changed.

## Remote CI

The [25-job CI run for `399bd76`](https://github.com/mage-os-lab/module-shopping-feed/actions/runs/37786796168) completed successfully. It covers the explicit Mage-OS 3.5.0 profile and Magento Open Source 2.4.8-p5, 2.4.9, and the dedicated 2.4.7-p10 compatibility profile.

The first [hardening run](https://github.com/mage-os-lab/module-shopping-feed/actions/runs/37785989327) exposed two new tests that used PHPUnit attributes without the annotations required by the older runner. `399bd76` adds the missing data-provider annotations. The complete local suite passed again before the correction was pushed.

The native navigation follow-up adds two further regressions and runs the same workflow. Its CI result is available in the [hardening branch's workflow runs](https://github.com/mage-os-lab/module-shopping-feed/actions/workflows/ci.yml?query=branch%3Afeat%2Fmageos-3.5-hardening). CI status belongs to the exact run and head SHA; the earlier green run does not substitute for this follow-up's result.

## Native browser acceptance

| Surface | Accepted behavior | Evidence filename |
| --- | --- | --- |
| Hyvä simple options | URL fragment selects dropdown, multiselect, radio, and checkbox values; price becomes 26.99 | `hyva-simple.json` |
| Hyvä price update | Changing the dropdown updates the price to 27.99 | `hyva-simple-change.json` |
| Hyvä cart | Actual add-to-cart retains all four option selections and the 27.99 unit price | `hyva-cart-initial.json` |
| Hyvä configurable | Numeric attribute fragment selects the related child; visible price and server microdata are 20.00 with SKU `FEED-CFG-1` | `hyva-configurable-selected.json` |
| Hyvä configurable change | Selecting the other option changes the visible price to 21.00 | `hyva-configurable-change.json` |
| Hyvä hostile label | Closing-script label remains text and does not execute its payload | `hyva-configurable-selected.json` |
| Hyvä associated identity | An unrelated `aid` does not disclose the unrelated product's SKU | `hyva-invalid-child.json` |
| Hyvä replacement microdata | Simple product without options emits `FEED-SECOND` / 19.99; title itemprop removal follows the selected replacement | `hyva-price-and-microdata.json`, `hyva-final-confirmation.json` |
| Hyvä without replacement | Native title retains `itemprop="name"`; replacement SKU meta is absent | `hyva-no-replacement.json` |
| Hyvä JavaScript | `require` is undefined, Alpine and Hyvä are present, and the fresh session has no page errors | `hyva-final-errors.json` |
| Nebula listing | Actual Qoliber theme assets and native grid; hostile feed name remains literal text | `nebula-grid-initial.json` |
| Nebula pagination | Name filter spans twenty rows on page one and five distinct rows on page two | `nebula-page-one.json`, `nebula-page-two.json` |
| Nebula sort/export | Sorting preserves the filter; CSV includes all twenty-five matching rows rather than only the visible page | `nebula-sort-and-export.json` |
| Nebula combined filters | Store, Google Shopping type, and Disabled status select exactly the expected feed | `nebula-type-status-store-filter.json` |
| Editor create/reopen | Native Create Generic reaches the supported fallback; saved name, file, store, and USD currency survive reopen | `nebula-editor-reopened.json` |
| Editor literal parameter | Bracket text and script markup survive save/reopen without execution | `nebula-literal-parameter.json` |
| Google editor | All twelve sections, including Promotions, are present; USD/store selection is retained; no Nebula scripts leak into the fallback | `nebula-google-editor.json` |
| Preview | Actual Test Now submission generates the native SKU, UTF-8 description, 19.99 price, and escaped literal URL parameter | `nebula-preview.json` |
| Native clone | One selected record creates exactly one disabled copy | `nebula-cloned.json` |
| Native enable/disable | Only the selected original changes status; the unselected copy remains disabled | `nebula-enabled.json`, `nebula-disabled.json` |
| Native delete | Only the selected copy is deleted; original and unrelated editor feed remain | `nebula-deleted.json` |
| Native Run Now | Valid POST creates one pending queue entry for the selected feed | `nebula-runnow.json`, `native-db-before.json` |
| Queued generation | Processing that feed publishes three complete seventeen-column rows and removes its queue entry | `native-queued-generation.log`, `native-queued-output.json`, `native-db-after.json` |
| View Log | Native action opens the supported log layout and shows the current generation and completion | `nebula-viewlog.json` |
| Read-only role | Native listing remains available; mutation controls are absent; Save, Generate, Enable, Disable, Clone, and Delete POSTs with a valid form key all return HTTP 403 | `nebula-readonly-acl.json` |

The cart contains an independent synthetic ten-percent cart rule. Its discounted grand total is 25.19; the accepted product unit price is 27.99. URL fragments are client-side option selectors and do not update the server-rendered microdata price. Matching simple-product microdata pricing was checked on the product without options. Changing a configurable selection verifies native selection and visible pricing, not Google Ads event delivery.

The global Nebula keyword-search path and a strict Hyvä CSP theme were not part of these browser checks. The native grid checks exercise column filters. Chrome recorded cross-document ViewTransition opt-in aborts while navigating between the two admin themes. Direct navigation and rendered-page assertions established the actual form behavior; this record does not claim an entirely error-free Nebula browser log.

## Defect found during acceptance

Nebula 0.9's `getGridUrl()` supplies sort and page values but omits the active column filters. A native twenty-five-record Name filter showed unrelated records after Next Page. The module's URL adapter now supplies the active filters unless the caller explicitly supplies them. A null override still clears the filters.

The new regression failed on the original adapter because the outgoing filter was null. The corrected adapter passed the regression, all native pagination/sort/export checks above, and the complete PHP suite: **911 tests and 2,442 assertions** on PHP 8.4.26, without PHPUnit notices. Evidence: `nebula-pagination-regression-red.log`, `nebula-pagination-regression-green.log`, and `unit-native-pagination-final.log`.

## Installation and execution checks

- Fresh schema installation completed all 1,425 setup steps and eleven indexers.
- Full dependency-injection compilation completed all nine stages with Hyvä and Nebula enabled. Compilation was repeated for the final navigation candidate under maintenance protection.
- Native theme static assets were deployed for Hyvä, Nebula, and the Magento editor fallback.
- The native backend fixtures passed thirty-eight stock, eight-preset, promotion, and cache assertions, plus eight unscheduled checkpoint/resume assertions before the browser fixtures were added.
- The final runtime parity check covered all 591 tracked source/configuration/test files.

The initial install required the lab database's trigger-creation setting. The native inventory fixture reset the core stock-resolution cache after changing website-stock links inside its setup request. The first storefront attempt omitted Hyvä's required BaseLayoutReset module; enabling it, generating the resets, recompiling, deploying assets, and clearing caches produced the accepted native storefront. These were lab setup corrections, not additional module defects.

The original [review's local checks](2026-10-08-hardening-and-mageos-3.5-verification.md#final-verification), including official integration tests, XML, JavaScript, syntax, and coding-standard gates, remain separate evidence. The remote workflow repeats the relevant compatibility gates against its own installed frameworks.

## Evidence, cleanup, and limits

Private scripts and evidence are under `/private/tmp/shopping-feed-hardening-20261008/scripts` and `evidence-native-themes`. Package identity, source parity, fixture IDs, database state, generated-file hash, redacted build logs, and browser observations are retained there. Generated administrator and database credentials are not included in this report.

All three named browser sessions were closed. Cleanup removed only the native acceptance project's containers and synthetic volumes; its remaining container, volume, and network counts are zero. The verification record is `cleanup-native.log` and `cleanup-native-verification.json`. Local source and evidence remain available.

This work is committed on the hardening branch for remote CI. No merge, published release, package-index update, deployment, external upload, merchant ingestion, or production acceptance is implied. The supplied review remains an untracked user file. Native Nebula UI Bridge rendering, Google Ads on Hyvä, external recipient acceptance, and production capacity remain separate gates. The two informational issues retained in the original hardening report remain unchanged.
