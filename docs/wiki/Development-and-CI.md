# Development and CI

The repository validates module identity, configuration integrity, PHP behavior, and supported Magento-family platforms. Run focused checks before requesting review.

> Documentation baseline: release 1.2.2 (`v1.2.2`); earlier acceptance is identified by version. Last reviewed: 2026-10-05.

## Unreleased Mage-OS 3.5.0 hardening

The prepared CI matrix now targets Mage-OS 3.5.0. The repository's `docs/reviews/2026-10-08-hardening-and-mageos-3.5-verification.md` records the isolated installation, regression tests, native feed checks, and remaining limits for the local changes. The published 1.2.2 package and its CI results remain separate from this work.

## 1.2.2 patch verification

The #14 description correction adds 17 cases. The core suite passes 863 tests on PHPUnit 9, 10, and 12, and 47 JavaScript checks pass. The merged runtime passed all 25 [post-merge CI jobs](https://github.com/mage-os-lab/module-shopping-feed/actions/runs/37332361658). See [Release 1.2.2](Release-1-2-2) and the dated release preparation record in the repository. Earlier store/browser results below keep their original scope.

## Unreleased editor implementation and evidence

The default forms in `9f07e46` live in `view/adminhtml/ui_component/mageos_shopping_feed_form.xml` and `mageos_shopping_feed_test_form.xml`, with providers under `Ui/DataProvider/Feed` and controls under `view/adminhtml/web/js/form`. Use `ShoppingFeedFormModifierPool` for custom metadata/data and the repository's `docs/ui-component-editor.md` for the contract. Previous form files remain for transition; their passing tests do not prove the replacement form. The legacy menu and category tree still have live callers, and renderer class names remain active configuration identifiers. Audit those dependencies and validator/test references before removing files.

Candidate `133af71` passes **809 PHP unit tests** on Mage-OS 3.5.0 and Magento Open Source 2.4.8/2.4.9. Assertions total 1,879 on Magento 2.4.8 and 2,158 on the other two frameworks. Both Docker platforms pass **21 official integration tests / 53 assertions** and production compilation. The stock, frontend scope, and currency regressions were observed failing before their corrections. The repository's `docs/reviews/2026-10-03-local-acceptance.md` records the current deployment, runtime evidence, and remaining checks.

The unchanged JavaScript/XML code retains **29 frontend tests**, **26 XML schema checks**, and five actual-framework date/null-value cases per platform. Nine frontend tests cover the active form; others cover retained legacy behavior and the storefront. Earlier four-test database regressions, six-role browser/route checks, and eight-preset form/output comparisons remain recorded in the linked reports. Do not substitute aggregate unit counts for those runtime checks.

Extended Docker checks cover scheduler edge cases, interrupted and competing workers, private FTP/SFTP delivery, semantic product modes, native configurable MSI, pricing, store-scoped frontend rendering, 5,000-product generation for every preset, and disable/re-enable rehearsals. Two real hourly cron cycles also pass with stable output and empty queues; the current record includes their observation window and final cleanup. External recipient and release acceptance remain separate gates.

## Local validation

From the module repository root:

```bash
composer validate --strict --no-check-publish
find . -path './.git' -prune -o -type f \( -name '*.php' -o -name '*.phtml' \) -print0 | xargs -0 -n1 php -l
php dev/tests/validate.php
php dev/tests/validate-wiki.php
node --test dev/tests/frontend/*.test.cjs
```

Run the unit suite with the PHPUnit installation from an existing Magento or Mage-OS checkout:

```bash
MAGENTO_ROOT=/path/to/magento /path/to/magento/vendor/bin/phpunit -c phpunit.xml.dist
```

Run `MAGENTO_ROOT=/path/to/magento node --test dev/tests/magento-ui-form.test.cjs` for promotion-date conversion and directive initialization against the installed platform's actual date, abstract-field, and value-link components. This separate suite needs a framework checkout and covers three Admin locale formats plus preservation of null directive parameters.

The consolidated validation checks package and module identity, feed configuration, Magento XML, schema whitelist alignment, isolated runtime identifiers, storefront integration markers, and selected regression-sensitive behaviors. Wiki validation checks navigation, page baselines, and known legacy instructions.

## Disposable database regressions

The persistence tests use the actual Magento resource load/save path with session-local temporary tables and a synthetic encryption key. They do not bootstrap a store or read its database credentials. Start a disposable MariaDB container with database `shopping_feed_test`, an empty test-only root password, and port 3306 mapped to a random **127.0.0.1** port. Run:

```bash
SHOPPING_FEED_TEST_DB_PORT=<mapped-port> MAGENTO_ROOT=/path/to/magento \
  php /path/to/magento/vendor/bin/phpunit --bootstrap Test/Unit/bootstrap.php Test/Database
```

Stop and remove the disposable container afterward. The tests check raw ciphertext after an Admin-style masked save, repeated saves, passwords whose encrypted form exceeds 255 bytes, and same-day interrupted queue recovery. They complement the full Magento integration suite and do not replace an installed-store schema upgrade check.

## CI coverage

The GitHub Actions workflow runs:

* Composer metadata validation
* PHP syntax checks across supported PHP versions
* Consolidated module and wiki validation
* Hyva and Luma storefront auto-selection checks
* Magento Open Source compatibility checks
* Mage-OS 3.5.0 compatibility checks in the unreleased configuration

The exact supported PHP constraint remains authoritative in `composer.json`. The current [Status and compatibility](Status-and-Compatibility) page translates that metadata for users.

The earlier Google/custom-feed acceptance profile on Mage-OS 3.5.0 passed 413 unit tests with 955 assertions, 10 application integration tests with 26 assertions, 14 frontend tests, and 33 generation checks. See [Release 1.1.0](Release-1-1-0) for the separate earlier Nebula, no-Nebula, and storefront runs. PHPUnit 9, 10, and 12 use the same data-provider coverage; compatibility fixtures explicitly configure optional arguments and date modification. The follow-up issue review passed 433 unit tests, 19 JavaScript tests, and 42 feed/stock checks and is recorded in the [issue acceptance report](https://github.com/mage-os-lab/module-shopping-feed/blob/v1.1.0/docs/reviews/2026-09-28-github-issues.md). Those historical local results do not establish CI or recipient acceptance for the unreleased changes. Use the [CI run history](https://github.com/mage-os-lab/module-shopping-feed/actions/workflows/ci.yml) for the status of the exact release commit.

## Documentation changes

Update documentation in `docs/wiki`, not directly in the public wiki. Follow `docs/WIKI-MAINTENANCE.md` for source precedence, page conventions, drift checks, and the separate publication approval boundary.

When behavior changes, update the implementation, tests, relevant wiki page, and [release acceptance](Release-Acceptance) evidence in the same reviewable change. Do not carry historical instructions forward when current code disagrees.

## Review expectations

Provide:

1. The exact behavior or documentation claim changed
2. The current-code evidence for it
3. Focused validation output
4. Platform or runtime evidence when behavior depends on Magento
5. Any unverified external service behavior or remaining limitation

Passing local checks is not evidence that a wiki was published or that an external feed recipient accepted an output file.

## Magento 2.4.7-p10 test profile

The dedicated PHP 8.3 job tests this exact platform without changing other platform jobs. Its temporary root project permits resolution of one Flysystem advisory while retaining it in `composer audit`; additional advisories fail the job. It also tests the optional security backport in an isolated library copy. See [Status and compatibility](Status-and-Compatibility) for the dependency guidance and acceptance record. The extension package does not distribute a Composer audit exception.

Magento 2.4.7 uses PHPUnit 9. Keep `@dataProvider` annotations alongside `DataProvider` attributes so parameterized tests run on both older and newer frameworks. Run the CI-policy checks with `node --test dev/tests/ci/*.test.cjs`; run actual-framework form checks with `MAGENTO_ROOT=/path/to/magento node --test dev/tests/magento-ui-form.test.cjs`.
