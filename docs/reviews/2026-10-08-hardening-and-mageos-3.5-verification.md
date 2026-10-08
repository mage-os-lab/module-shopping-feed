# Shopping Feed hardening and Mage-OS 3.5 verification

Date: 2026-10-08. Base: `e58d93b2e402afe1061a39b2b05f9d5d69c15e4d` (1.2.2, `main`). This record covers local verification before commit and remote CI.

Eleven of the thirteen findings in the supplied `2026-10-08-codebase-review.md` are addressed. Two informational findings remain unchanged for the reasons below. Native Mage-OS testing also exposed feed construction, promotion persistence, state-saving, and configurable initialization defects; those are corrected in this change. The supplied review file is preserved outside this commit.

Mage-OS [lists 3.5.0 as its latest release](https://mage-os.org/product/releases/). The [3.5.0 release announcement](https://mage-os.org/releases/2026-09-08-mage-os-3-5-0-release/) identifies its Magento Open Source 2.4.9 base. This review used an isolated 3.5.0 installation and the final module source, rather than inferring compatibility from the upstream version.

## Findings and dispositions

| Finding | Disposition | Change and verification |
| --- | --- | --- |
| H1: reservations deducted per source | Fixed | Resolve the feed website stock and apply its complete reservation sum once to the aggregate. Individual Local Inventory rows retain physical-source quantities. Native fixtures cover two sources, two website stocks sharing a source, compensation events, and an aggregate reservation larger than either source quantity. |
| M1: configurable JSON in an inline script | Hardened; original exploit claim qualified | Decode and HEX-encode the complete configuration while retaining JSON object/array shape. Mage-OS's default serializer already escapes `/`, producing `<\/script>` rather than a literal closing tag; the supplied report's default-encoder stored-XSS claim was not established. The new boundary also protects custom encoders that emit unescaped closing tags. A malicious option label remained inert in the actual storefront. |
| M2: native microdata removed without a replacement feed | Fixed | Title/SKU removal now shares the store configuration and selected-feed gate used for price markup. Native integration and browser checks cover both configurations. |
| M3: automatic batching with no schedules | Fixed | Persist schedule settings only when a schedule exists; queue checkpoint state still saves. A real queued manual feed with zero schedules checkpoints and resumes to a complete, distinct-row output. |
| L1: overwritten skip IDs | Fixed | Check the reason key rather than searching array values; retain every skipped child ID. Regression test covers repeated reasons. |
| L2: malformed Generate/View Log identities | Fixed | Catch the builder's localized validation error and return to the grid. Browser requests with `id=abc` show the expected error, return HTTP 200 after navigation, and create no queue. |
| L3: promotion cache rewritten per product | Fixed | Buffer cache entries across each generator run and atomically flush at the batch boundary, including checkpoints and exceptions. Reassigning the same feed preserves the warm cache. A unit test proves 100 entries produce one write/rename; native generation persists both product entries. |
| L4: promotion titles break TSV rows | Fixed | Normalize tabs, CR/LF, and excess spaces; missing/null titles fall back to the rule name. Native output has one promotion row with the same eleven cells as its header. |
| L5: unfinished HTML fragments remain | Fixed | Strip unfinished tag-shaped fragments before the existing cleaner. Regression cases preserve following text, comparison text, and the previous malformed-quote correction. All eight native presets produce clean descriptions. |
| L6: spreadsheet formula interpretation | Unchanged, informational | Prefixing machine-feed values would change exact identifiers, legitimate negative values, and recipient data. A spreadsheet-safe export should be a separate explicit feature. Opening merchant-controlled feeds in spreadsheet applications retains this risk. |
| L7: public temporary feed files | Unchanged, informational | The final feed remains atomically published by rename, but its partial `.tmp` file remains reachable during generation. The default feed data is intentionally public. Moving working files to private storage or enforcing web-server denial needs a separate output/publication design and compatibility review. |
| L8: dead empty-column check | Removed | Remove the check on the list of rows; the existing per-row `afterMap()` check remains authoritative. Existing adapter coverage passes. |
| L9: unguarded inheritance/ACL values | Fixed | Unknown inheritance modes produce a localized error. Nebula actions without an ACL resource are filtered out. Regression tests cover both inputs. |

## Additional defects found and corrected

- Passing a feed type to the constructor called `setDefaultConfig()` before the configuration object existed. Construction now uses `setType()`. The new regression failed on the original implementation and passes on the final source.
- Promotion configuration creation injected a resource-model factory, whose instances cannot perform the model's `addData()`/`save()` flow. It now injects the feed configuration model factory. Native integration creates a missing row, updates it, and confirms exactly one row remains.
- Status/message saves left `no_after_save` set on the feed object, silently suppressing later configuration saves. The flag now restores in `finally`, including failure paths. Native integration verifies later configuration persistence on the same instance.
- Status saves also left the shared resource's timestamp suppression enabled. It now resets after the scoped save. Native integration confirms status changes preserve the existing timestamp and a later real edit updates it.
- Native configurable dropdowns can populate after the module's initial DOM-ready callback. A real query-string deep link reproduced an empty selection. The module now reapplies selection on Magento's configurable/swatches initialization events; the rendered page selects the intended child and emits the correct subsequent `view_item` event.
- Inventory totals preserve fractional source quantities, exclude disabled physical sources/source items, and sum all reservation events without depending on the formatting or type of their metadata. An explicitly excluded promotion no longer enters the rule query or appears in product IDs.
- The explicit CI target moves from Mage-OS 3.4.0 to 3.5.0. This is prepared configuration; remote CI for these uncommitted changes has not run.

## Installation and source identity

The disposable installation lives under `/private/tmp/shopping-feed-hardening-20261008`. Its database, configuration, catalog fixtures, administrator, and media were created for this review. The Composer-locked dependency tree was copied read-only from the existing Mage-OS 3.5.0 checkout; this was a new application/database installation, not a new dependency download. Existing credentials, application configuration, catalog data, and user media were excluded.

| Item | Verified value |
| --- | --- |
| Distribution | Mage-OS 3.5.0 |
| Installed core packages | `mage-os/product-community-edition`, `framework`, `module-configurable-product`, `module-inventory`, `module-inventory-sales`, `module-theme`, and `module-ui`: 3.5.0 |
| Composer lock SHA-256 | `03f0b553293ab2fc0549690ad8c80b3b96878e36e7b067b860146bb16d9e80a1` |
| Application runtime | PHP 8.4.26, MySQL 8.4, OpenSearch 3.8.0 |
| Browser surfaces | Magento Luma storefront and Mage-OS m137 Admin theme |
| Optional integrations | Hyva and Nebula packages were present in the dependency tree but their modules were disabled |
| Network binding | Disposable web service bound to `127.0.0.1:8155` |
| Source parity | All 591 PHP, PHTML, XML, XSD, JS, CJS, JSON, and YML files in the manifest match the installed module; zero mismatches |
| Source manifest SHA-256 | `e809fda195d37b8d86d2ee0689e16397acaeb24db8da782a730dcc92c6eca2a5` |

The source manifest hashes ordered `path:sha256` newline records. Markdown documentation is excluded from this runtime manifest. The release commit above identifies the starting point; the manifest identifies the final local source including tests and CI configuration.

## Final verification

| Check | Result | Evidence filename |
| --- | --- | --- |
| Complete unit suite, Mage-OS framework, PHP 8.4.26 | 909 tests, 2,429 assertions pass | `unit-final-php84.log` |
| Complete unit suite, same framework, host PHP 8.5.9 | 909 tests, 2,429 assertions pass | `unit-final-host.log` |
| Official Magento application integration harness, separate synthetic database | 27 tests, 70 assertions pass | `integration-final.log` |
| Frontend/Admin/CI JavaScript checks, including real framework date/value components | 49 checks pass | `javascript-final.log` |
| Native MSI, eight presets, promotions, and cache assertions | 38 assertions pass | `native-final.log`, `native-results.json` |
| Native unscheduled queue checkpoint and resume | 8 assertions pass | `extended-final.log`, `extended-results.json` |
| Magento DI compilation | All 9 stages pass | `di-final.log` |
| PHP 8.1 syntax | 532 PHP/PHTML files pass | `php81-syntax.log` |
| Composer metadata | Strict validation passes | `repository-validation.log` |
| Consolidated XML/presets | 26 XML files and all 8 presets pass | `repository-validation.log` |
| Installed Mage-OS XML schemas | All 26 XML files pass | `magento-xml-final.log` |
| Wiki validation | 41 pages and 41 sidebar entries pass | `repository-validation.log` |
| Repository Magento coding-standard gate | 0 errors; 3,630 warnings retained under the existing warning-tolerant ruleset | `phpcs-final.json` |
| Whitespace and final diff | `git diff --check` passes | `repository-validation.log` |

Regressions were exercised against the original implementation before the fixes. Failure logs are retained alongside the final results. The changed high-risk methods have nonzero measured execution in `coverage-final.xml` and `changed-method-coverage.json`; promotion persistence and timestamp behavior also have native database regressions. New data-provider tests retain annotations for the older PHPUnit runners supported by the repository, although this run used PHPUnit 12.5.33.

### Native inventory and output checks

Two enabled physical sources hold ten units each. A reservation of minus one in website stock 2 yields an aggregate of nineteen and individual source quantities of ten. Stock 3 shares one physical source but has its own minus-seven reservation; its result remains isolated. An additional fractional reservation with spaced, non-order metadata is included in stock 2's sum. Disabling a source excludes its quantity; restoring it and applying compensation restores the expected total.

With a minus-fifteen reservation, aggregate stock is five while both Local Inventory rows remain quantity ten and `in_stock`. This proves stock-level reservations are not attributed to a physical store.

Each of the eight presets generates the expected synthetic output: two product rows, or four source rows for Local Inventory. Output file hashes and row counts are retained in `native-results.json`. The fixtures exercise simple product generation; the later configurable fixtures support the browser checks below.

A native cart rule generates product promotion IDs, a correctly structured companion TSV, and a persisted two-product cache. Explicit exclusion produces no promotion IDs. The zero-schedule checkpoint uses an injected memory-limit reading to trigger the existing limit boundary; it is a recovery-path simulation, not an actual out-of-memory or production capacity test. Resume produces a header and two distinct rows and removes the queue.

### Admin and storefront checks

- Create, save, and reopen an Admin feed; verify its store, currency, reservation setting, and microdata setting survive.
- Run Test Now for a native SKU; verify its exact ID, UTF-8 description, cleaned markup, and 19.99 price.
- Submit malformed Generate and View Log identities; verify grid recovery and validation messages without an exception page or queue mutation.
- Enable microdata without choosing a replacement feed; verify native name/SKU itemprops remain. Select a replacement; verify native attributes are removed and replacement name, SKU, and price are present.
- Render a configurable option label containing a closing script tag and a script payload; verify HEX encoding, no injected execution, and no browser errors.
- Follow the query-string deep link; verify option 4 selects child 3. Change the selection; verify child 4 and its SKU/21.00 price in `view_item`.
- Verify related `aid` metadata uses the selected child's SKU/price while an unrelated `aid` retains the parent identity.
- Repeat the deep-link and microdata assertion after final DI compilation. The compiled page still selects child 3, reports `FEED-CFG-1`/20.00, and executes no injected script.

Browser observations are retained as the `browser-*.json`, `storefront-*.json`, and `compiled-storefront.json` files in the evidence directory.

## Reproduction and evidence

From the module checkout, with `MAGENTO_ROOT` pointing at the isolated installation:

```sh
MAGENTO_ROOT=/path/to/mageos php /path/to/mageos/vendor/bin/phpunit -c phpunit.xml.dist
MAGENTO_ROOT=/path/to/mageos node --test dev/tests/frontend/*.test.cjs dev/tests/ci/*.test.cjs dev/tests/magento-ui-form.test.cjs
composer validate --strict --no-check-publish
php dev/tests/validate.php
php dev/tests/validate-wiki.php
MAGENTO_ROOT=/path/to/mageos php dev/tests/validate-magento-xml.php
php /path/to/mageos/vendor/bin/phpcs --standard=phpcs.xml.dist
git diff --check
```

The application integration run uses `dev/tests/integration/phpunit.xml` in the disposable installation, a separate integration database, and `--testsuite ShoppingFeed`. Production compilation uses `bin/magento setup:di:compile` in that installation. The native scripts and redacted logs remain under `/private/tmp/shopping-feed-hardening-20261008/scripts` and `/private/tmp/shopping-feed-hardening-20261008/evidence`. They require the synthetic fixtures and private generated local configuration. Credentials are not included in this report.

## Remaining limits and completion states

- Remote CI, other Magento/PHPUnit framework versions, native Hyva/Nebula rendering, external provider ingestion, and FTP/SFTP delivery were not newly exercised. Historical results keep their original scope.
- This run does not establish production capacity, concurrent editing acceptance, or customer-store deployment readiness. The two informational findings above remain present.
- The inventory helper's legacy source-only/unscoped reservation forms remain available for third-party callers. Module calls now supply the website; callers sharing a source across stocks should supply the optional website code too.
- GitHub issue [#17](https://github.com/mage-os-lab/module-shopping-feed/issues/17) already has separate open [PR #18](https://github.com/mage-os-lab/module-shopping-feed/pull/18) for version display. That change was not duplicated or merged into this work.
- Only the disposable installation was mutated. The original shared Mage-OS checkout and deployed stores were left untouched. No uploads or external feed submissions were made.
- At the end of this local verification phase, the changes were prepared and locally verified. Commit, push, remote CI, and native optional-theme acceptance are subsequent steps; this record does not establish those states or publication.
- Disposable browser sessions and Docker services are closed after acceptance. Only this review project's synthetic volumes are removed; local source, scripts, and evidence are retained. Cleanup is recorded in `cleanup.log`.
