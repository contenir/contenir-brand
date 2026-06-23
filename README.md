# contenir/contenir-brand

Brand head tags for Contenir/Laminas MVC sites — the favicon `<link>` set plus
theme/tile colour `<meta>`, driven by the `site.brand` Branding settings the CMS
writes.

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

Icon files are expected at the web root (`public/`): `favicon.ico`,
`favicon.svg`, `favicon-16x16.png`/`-32x32`/`-48x48`/`-96x96`,
`apple-touch-icon.png`, `safari-pinned-tab.svg`, `site.webmanifest`. Generate
them from a single source in the Contenir CMS Branding panel.
