# Issue #17 local verification

The fix for [issue #17](https://github.com/mage-os-lab/module-shopping-feed/issues/17) displays the installed Composer package version in Admin's **Versions installed** field. It is prepared on `fix/issue-17-installed-version` in `/private/tmp/shopping-feed-issue-17`, based on current `main` commit `e58d93b2e402afe1061a39b2b05f9d5d69c15e4d`.

## Correction

`Block/Adminhtml/Info.php` previously displayed `ModuleResource::getDbVersion()`, which reads the database schema version. That value remains `1.0.0` across package releases. The core module now reads `Composer\InstalledVersions::getPrettyVersion('mage-os/module-shopping-feed')` when Composer reports the package installed and supplies a version.

The display adds `v` to numeric release versions, preserves an existing `v`, and leaves development names such as `dev-main` intact. Module labels are escaped for HTML. Source installations, unavailable Composer metadata, and related modules retain the schema-version fallback. If neither source supplies a version, the module name appears without an empty version suffix. The schema version and Composer requirements are unchanged.

Eleven new regression cases exercise the candidate block and Magento's HTML escaper against fixture module and Composer metadata. They cover prefixed and unprefixed releases, prereleases, development names, escaping, source installations, provided packages, null and empty pretty versions, missing schema versions, and independent related-module versions. Composer's in-process state is restored after each case.

With the final tests in place, restoring the unchanged block from `main` produces seven failures and no errors. Restoring the candidate passes all 11 tests and 39 assertions.

## Validation

| Check | Result |
| --- | --- |
| Mage-OS 3.5.0, PHP 8.5.9, PHPUnit 12.5.33 unit suite | 874 tests, 2,293 assertions passed |
| Focused regression suite | 11 tests, 39 assertions passed |
| Frontend, CI-policy, and installed-framework form checks | 47 JavaScript tests passed |
| Composer metadata | `composer validate --strict --no-check-publish` passed |
| Consolidated module validation | 26 XML files and eight feed types passed |
| Mage-OS-backed XML schema validation | 26 files passed |
| Wiki source validation | 41 pages and 41 sidebar targets passed |
| PHP 8.5 syntax | All 524 PHP/PHTML files passed |
| Focused Magento2 coding standard | Zero errors; three existing warnings identical to the original block; the new test has no warnings |
| Patch whitespace | `git diff --check` passed |

Run the unit suite from the candidate worktree:

```sh
MAGENTO_ROOT=/Users/matt/code/mageos-latest php /Users/matt/code/mageos-latest/vendor/bin/phpunit -c phpunit.xml.dist
```

Append `Test/Unit/Block/Adminhtml/InfoTest.php` to run the focused regression suite. Other checks use the commands in `CONTRIBUTING.md`, plus:

```sh
MAGENTO_ROOT=/Users/matt/code/mageos-latest node --test dev/tests/frontend/*.test.cjs dev/tests/ci/*.test.cjs dev/tests/magento-ui-form.test.cjs
php /Users/matt/code/mageos-latest/vendor/bin/phpcs --standard=phpcs.xml.dist Block/Adminhtml/Info.php Test/Unit/Block/Adminhtml/InfoTest.php
```

Regression, JavaScript, coding-standard, and syntax logs are retained under `/private/tmp/shopping-feed-issue-17-evidence`. The checks load framework code without installing the candidate or changing a store. Browser acceptance and full unit runs on other Magento versions were not performed. This record captures local verification before commit and pull request publication. Release and public wiki publication remain separate steps.
