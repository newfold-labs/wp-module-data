# Integration

## How the module registers

The data module registers with the Newfold Module Loader on `plugins_loaded`:

- **name:** `data`
- **label:** Data (translatable)
- **callback:** Instantiates `Data` with `container()->plugin()` and calls `$module->start()`.
- **isActive:** true
- **isHidden:** true

So it does not appear in the UI as a toggleable module; it always runs when the loader loads active modules.

## Container: capabilities

On the action `newfold_container_set`, the bootstrap registers in the container:

```php
$container->set( 'capabilities', $container->service( function () {
    return new SiteCapabilities();
} ) );
```

So `container()->get( 'capabilities' )` returns a `SiteCapabilities` instance. That class:

- Caches capabilities in a transient (e.g. `nfd_site_capabilities`).
- Exposes `all()` (array of capability name => bool) and `get( $capability )` (bool).
- Is used by wp-module-runtime (for `window.NewfoldRuntime.capabilities`) and by other modules (e.g. AI, ECommerce, HelpCenter, Onboarding) to gate features.

## Activation and deactivation

The bootstrap registers activation and deactivation hooks on the plugin file (from the container’s plugin instance):

- **Activation:** Create the event queue table (`nfd_create_event_queue_table`) and set a transient to signal plugin activation.
- **Deactivation:** Delete the data module version option and drop the event queue table.

## Filters

- **pre_update_option_nfd_data_token** / **option_nfd_data_token** – Encrypt on save, decrypt on read (via `Encryption` helper).
- **pre_set_transient_nfd_site_capabilities** – Optional override for capabilities (e.g. default for bluehost brand when transient is empty).

## Event queue and Hiive

The `Data` class starts a Hiive connection and event manager. Events can be queued and sent to Hiive; see `EventManager`, `HiiveConnection`, and `HiiveWorker` for implementation details.
