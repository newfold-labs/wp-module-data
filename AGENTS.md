# Agent guidance – wp-module-data

This file gives AI agents a quick orientation to the repo. For full detail, see the **docs/** directory.

## What this project is

- **wp-module-data** – Newfold Data Module: shared data, telemetry, and site capabilities for Newfold WordPress brand plugins. It registers with the loader as the `data` module (hidden), provides `SiteCapabilities` to the container (used by runtime and other modules), manages an event queue and Hiive connection, and runs versioned upgrades. Maintained by Newfold Labs.

- **Stack:** PHP 7.4+, `ext-json`. Depends on wp-module-context, wp-module-loader, wp-forge/helpers, wp-forge/wp-query-builder, wp-forge/wp-upgrade-handler, wpscholar/url.

- **Architecture:** Registers on `plugins_loaded` via the loader; on `newfold_container_set` it registers activation/deactivation hooks and sets `capabilities` in the container (SiteCapabilities instance). Data module starts Hiive connection and event manager; capabilities are cached in transients.

## Key paths

| Purpose | Location |
|---------|----------|
| Bootstrap & registration | `bootstrap.php` – version, upgrades, register module, container set, filters |
| Main module logic | `includes/Data.php` – start(), Hiive, EventManager |
| Site capabilities | `includes/SiteCapabilities.php` – get/all, transient cache |
| Event queue / Hiive | `includes/Event.php`, `EventManager.php`, `HiiveConnection.php`, `HiiveWorker.php` |
| Upgrades | `upgrades/` |
| Tests | `tests/` (PHPUnit, Codeception wpunit) |

## Essential commands

```bash
composer install
composer run lint
composer run fix
composer run test
composer run test-coverage
```

## Documentation

- **Full documentation** is in **docs/**. Start with **docs/index.md**.
- **CLAUDE.md** is a symlink to this file (AGENTS.md).

---

## Keeping documentation current

**When you change code, features, or workflows, update the docs so they stay accurate.** Keep **docs/index.md** current: when you add, remove, or rename doc files, update the table of contents (and quick links if present).

- Keep all docs current. When adding or changing dependencies, update **dependencies.md**. When cutting a release, update **docs/changelog.md**.
