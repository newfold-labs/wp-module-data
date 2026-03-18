---
name: wp-module-data
title: Getting started
description: Prerequisites, install, and run.
updated: 2025-03-18
---

# Getting started

## Prerequisites

- **PHP** 7.4+ with **ext-json** (see `composer.json`).
- **Composer** for dependencies.
- **WordPress** (module runs inside a host plugin).

## Install

From the package root:

```bash
composer install
```

Pulls in wp-module-context, wp-module-loader, wp-forge packages, and wpscholar/url. Dev dependencies include WordPress, PHPUnit, Codeception, and optional Bluehost plugin zip for tests.

## Run tests

```bash
composer run test
```

Runs PHPUnit and Codeception wpunit. For coverage:

```bash
composer run test-coverage
```

Then open `tests/_output/html/index.html` to view the report.

## Lint and fix

```bash
composer run lint
composer run fix
```

## Using in a host plugin

1. Depend on `newfold-labs/wp-module-data` via Composer.
2. Ensure the loader and container are set before `plugins_loaded` (the data module registers on `plugins_loaded`).
3. On `newfold_container_set`, the module registers `capabilities` in the container and activation/deactivation hooks. The runtime and other modules use `container()->get( 'capabilities' )` to read site capabilities.

See [integration.md](integration.md) for details.
