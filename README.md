# Import Map Shim (`local_importmapshim`)

A Moodle local plugin that injects the [es-module-shims](https://github.com/guybedford/es-module-shims) polyfill into every page, enabling import map support for browsers that do not natively support the feature.

## Why this plugin exists

Moodle 5.2+ adopted **ES module import maps** as the foundation for its React-based UI. An import map is a JSON block in the HTML page that tells the browser how to resolve bare module specifiers like:

```js
import React from 'react'; // browser needs to know where 'react' actually lives
```

Moodle core added the import map mechanism but did not ship a polyfill for browsers that lack native support. Older browsers — and some versions of Firefox until recently — do not support import maps natively, causing the entire React-based UI to silently break for those users.

This plugin injects [es-module-shims](https://github.com/guybedford/es-module-shims) into every Moodle page **before** any import map or ES module is parsed. The shim works transparently: on browsers that already support import maps natively it steps aside and does nothing; on older browsers it intercepts module loading and emulates the feature.

| Layer | Who provides it |
|---|---|
| Import map definition + React modules | Moodle core |
| Native import map support | Modern browsers |
| Import map support on older browsers | This plugin (via es-module-shims) |

## Requirements

- Moodle 5.2 or later
- PHP 8.1 or later

## Installation

### Using Git

From your Moodle root directory, clone the plugin into the `public/local/` subdirectory:

```bash
cd /path/to/moodle
git clone https://github.com/meirza/moodle-local_importmapshim public/local/importmapshim
```

Then visit **Site administration > Notifications** to complete the installation.

### Manual

1. Download the plugin archive.
2. Extract it so the plugin folder is at `<moodleroot>/public/local/importmapshim/`.
3. Log in as an administrator and visit **Site administration > Notifications**.

## How it works

The plugin hooks into Moodle's `before_standard_top_of_body_html_generation` hook (priority 1000) to inject a `<script>` tag that loads the `es-module-shims` polyfill early in the page lifecycle — before any import map or ES module scripts are evaluated.

The polyfill is served by a dedicated PSR-7 route controller (`/shims/{revision}`) that applies appropriate HTTP cache headers:

| Mode        | Behaviour                                              |
|-------------|--------------------------------------------------------|
| Development (`$CFG->cachejs = false`) | Short-lived headers, no ETag, 2-second expiry |
| Production  | `Cache-Control: public, max-age=31536000, immutable` with ETag and 304 support |

## Updating the bundled polyfill

The `es-module-shims` library can be updated by running the helper script:

```bash
node local/importmapshim/scripts/update-esm-shims.mjs <version>
```

For example:

```bash
node local/importmapshim/scripts/update-esm-shims.mjs 2.7.0
```

This downloads the specified release, updates `js/es-module-shims.js`, and updates the version metadata in `thirdpartylibs.xml`.

## License

This plugin is licensed under the [GNU General Public License v3 or later](LICENSE.md).
