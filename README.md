# contenir/contenir-brand

[![Continuous Integration](https://github.com/contenir/contenir-brand/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/contenir/contenir-brand/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/contenir/contenir-brand/graph/badge.svg)](https://codecov.io/gh/contenir/contenir-brand)

Brand head tags for Contenir/Laminas MVC sites — the favicon `<link>` set plus
theme/tile colour `<meta>`, driven by the `site.brand` Branding settings the CMS
writes.

## Requirements

- PHP 8.3, 8.4 or 8.5
- laminas/laminas-view ^2.35, laminas/laminas-servicemanager ^3.22
- A `Settings` view helper returning the CMS settings object (provided by
  the Contenir CMS site packages). Without settings, or without a `site.brand`
  object, the defaults below apply.

The 0.x releases, which support PHP 8.1, remain available from the `0.x`
branch and the `v0.1.0` tag; see [UPGRADE-2.0.md](UPGRADE-2.0.md).

## Install

```sh
composer require contenir/contenir-brand
```

The Laminas component installer registers the `Contenir\Brand` module
automatically (it only provides view helpers — no routes or services).

## Usage

Replace the hand-built favicon block in your head/meta partial with:

```php
<?= $this->faviconTags() ?>
```

`FaviconTags` is **file-existence driven**: it probes the web root and emits a
`<link>` only for the icons that actually exist, so one helper serves both the
modern RealFaviconGenerator set (`favicon.svg`, `favicon-96x96.png`) and the
legacy set (`favicon-16x16.png`, `favicon-32x32.png`, `safari-pinned-tab.svg`).
It always emits `<meta name="theme-color">`, and `msapplication-TileColor` /
`mask-icon color` when those colours are configured.

## Settings

```php
'site' => [
    'brand' => [
        'theme_color' => '#ffffff', // <meta name="theme-color">
        'tile_color'  => '#da532c', // optional — <meta name="msapplication-TileColor">
        'mask_color'  => '#5bbad5', // optional — <link rel="mask-icon" color="…">
    ],
],
```

Each colour defaults as shown when missing or not a scalar (`theme_color`
`#ffffff`, `tile_color` none, `mask_color` `#000000`). The characters
`"`, `<`, `>`, `&` and line breaks are stripped from the values.

Icon files are expected at the web root (`public/`): `favicon.ico`,
`favicon.svg`, `favicon-16x16.png`/`-32x32`/`-48x48`/`-96x96`,
`apple-touch-icon.png`, `safari-pinned-tab.svg`, `site.webmanifest`. Generate
them from a single source in the Contenir CMS Branding panel.

## Public API

| Class | Description |
| --- | --- |
| `Contenir\Brand\Module` | Registers the helper under `view_helpers` (aliases `faviconTags`, `FaviconTags`) |
| `Contenir\Brand\Helper\FaviconTags` | `__invoke(): string` renders the tags. `__construct(?string $publicPath = null)` sets the web root, default `<cwd>/public` |

Emitted in this order, each only when its file exists: `theme-color` meta
(always), `msapplication-TileColor` meta (when set), `favicon.svg`,
`favicon-96x96.png`, `favicon-48x48.png`, `favicon-32x32.png`,
`favicon-16x16.png`, `favicon.ico` (`shortcut icon`), `apple-touch-icon.png`
(`180x180`), `safari-pinned-tab.svg` (`mask-icon` with the mask colour),
`site.webmanifest`.

## Development

The QA toolchain is [php-db/phpdb-qa-tools](https://github.com/php-db/phpdb-qa-tools).
[Mago](https://mago.carthage.software/) is a standalone binary, installed
separately (`brew install mago`).

```bash
composer check             # everything below
composer cs-check          # mago format --check && mago lint
composer static-analysis   # mago analyze
composer test              # unit suite: module configuration, no I/O
composer test-integration  # integration suite: real icon files in a temp directory
composer test-coverage     # both suites, clover.xml for Codecov
```

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).
