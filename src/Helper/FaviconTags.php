<?php

declare(strict_types=1);

namespace Contenir\Brand\Helper;

use Laminas\View\Helper\AbstractHelper;

use function getcwd;
use function is_file;
use function sprintf;
use function str_replace;

/**
 * Render the favicon `<link>` set + theme/tile colour `<meta>` for the document
 * head, driven by the Branding settings written by the CMS.
 *
 * File-existence driven: each candidate icon is emitted only when the file
 * actually exists in the web root, so a single helper serves both the modern
 * RealFaviconGenerator set (favicon.svg, favicon-96x96.png) and the legacy set
 * (favicon-16x16.png, favicon-32x32.png, safari-pinned-tab.svg) without
 * per-site forks.
 *
 * Returns a raw markup string:
 *
 *   <?= $this->faviconTags() ?>
 *
 * Icon hrefs are fixed root paths from {@see ICONS} (no user input); colour
 * values from settings are stripped of attribute-breaking characters.
 */
final class FaviconTags extends AbstractHelper
{
    /**
     * Candidate icon files probed in the web root, in head order. Each entry is
     * [filename, rel, type|null, sizes|null].
     *
     * @var list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private const ICONS = [
        ['favicon.svg', 'icon', 'image/svg+xml', null],
        ['favicon-96x96.png', 'icon', 'image/png', '96x96'],
        ['favicon-48x48.png', 'icon', 'image/png', '48x48'],
        ['favicon-32x32.png', 'icon', 'image/png', '32x32'],
        ['favicon-16x16.png', 'icon', 'image/png', '16x16'],
        ['favicon.ico', 'shortcut icon', null, null],
        ['apple-touch-icon.png', 'apple-touch-icon', null, '180x180'],
    ];

    /**
     * @param string|null $publicPath Web root holding the generated favicons;
     *                                defaults to <cwd>/public. Injectable for tests.
     */
    public function __construct(private ?string $publicPath = null)
    {
    }

    public function __invoke(): string
    {
        $brand  = $this->getView()->Settings()->site->brand ?? null;
        $public = $this->publicPath ?? getcwd() . '/public';

        $themeColor = $this->colour((string) ($brand?->theme_color ?? '#ffffff'));
        $tileColor  = $this->colour((string) ($brand?->tile_color ?? ''));
        $maskColor  = $this->colour((string) ($brand?->mask_color ?? '#000000'));

        $out = $this->meta('theme-color', $themeColor);
        if ($tileColor !== '') {
            $out .= $this->meta('msapplication-TileColor', $tileColor);
        }

        foreach (self::ICONS as [$file, $rel, $type, $sizes]) {
            if (is_file($public . '/' . $file)) {
                $out .= $this->link($rel, '/' . $file, $type, $sizes, null);
            }
        }

        if (is_file($public . '/safari-pinned-tab.svg')) {
            $out .= $this->link('mask-icon', '/safari-pinned-tab.svg', null, null, $maskColor);
        }
        if (is_file($public . '/site.webmanifest')) {
            $out .= $this->link('manifest', '/site.webmanifest', null, null, null);
        }

        return $out;
    }

    private function meta(string $name, string $content): string
    {
        return sprintf('<meta name="%s" content="%s">' . "\n", $name, $content);
    }

    private function link(string $rel, string $href, ?string $type, ?string $sizes, ?string $color): string
    {
        $attr = sprintf('rel="%s" href="%s"', $rel, $href);
        if ($type !== null) {
            $attr .= sprintf(' type="%s"', $type);
        }
        if ($sizes !== null) {
            $attr .= sprintf(' sizes="%s"', $sizes);
        }
        if ($color !== null && $color !== '') {
            $attr .= sprintf(' color="%s"', $color);
        }

        return '<link ' . $attr . '>' . "\n";
    }

    /**
     * Strip characters that could break out of an HTML attribute. Colours are
     * hex / CSS keywords / rgb() and contain none of these, so valid values pass
     * through unchanged.
     */
    private function colour(string $value): string
    {
        return str_replace(['"', '<', '>', '&', "\n", "\r"], '', $value);
    }
}
