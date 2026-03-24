---
name: wp-module-data
title: Development
description: Lint, test, and workflow.
updated: 2025-03-18
---

# Development

## Linting

- **PHP:** `composer run lint` (PHPCS), `composer run fix` (PHPCBF). Uses `phpcs.xml` and `newfold-labs/wp-php-standards`.

## Testing

- **PHPUnit + Codeception:** `composer run test` runs PHPUnit and Codeception wpunit.
- **Coverage:** `composer run test-coverage`; then open `tests/_output/html/index.html`.

## Day-to-day workflow

1. Make changes in `includes/` or `bootstrap.php` or `upgrades/`.
2. Run `composer run lint` and `composer run test` before committing.
3. When adding or changing the container contract (e.g. capabilities) or hooks, update [integration.md](integration.md) and [overview.md](overview.md).
4. When cutting a release, update **docs/changelog.md** and bump `NFD_DATA_MODULE_VERSION` in `bootstrap.php`.
