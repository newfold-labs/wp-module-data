---
name: wp-module-data
title: Overview
description: What the module does and who maintains it.
updated: 2025-03-18
---

# Overview

## What the module does

**wp-module-data** (Newfold Data Module) provides shared data and telemetry for Newfold WordPress brand plugins. It:

- **Registers** with the Newfold Module Loader as the `data` module (hidden). Its callback receives the container’s plugin instance and starts the Data module (Hiive connection, event manager).
- **Provides capabilities** – On `newfold_container_set`, it registers `capabilities` in the container as a `SiteCapabilities` instance. That object fetches and caches site capabilities (e.g. from Hiive or defaults), and exposes `all()` and `get( $capability )` so the runtime and other modules can gate features.
- **Event queue** – Maintains an event queue table and Hiive worker for sending events.
- **Upgrades** – Uses `WP_Forge\UpgradeHandler` with version `NFD_DATA_MODULE_VERSION` and upgrade scripts in `upgrades/`.
- **Token encryption** – Filters for `nfd_data_token` option encrypt on save and decrypt on read.

## Who maintains it

- **Newfold Labs** (Newfold Digital). Distributed via Newfold Satis and used by all Newfold WordPress brand plugins.

## High-level features

- Site capabilities (cached in transient `nfd_site_capabilities` or similar).
- Hiive connection and event queue.
- Activation/deactivation hooks to create/drop the event queue table and set/clear version.
