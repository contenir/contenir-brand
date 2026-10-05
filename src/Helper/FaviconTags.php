<?php

declare(strict_types=1);

namespace Contenir\Brand\Helper;

use Laminas\View\Helper\AbstractHelper;
use Laminas\View\Renderer\PhpRenderer;

use function getcwd;
use function is_file;
use function is_object;
use function is_scalar;
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
 *
 * @api
 *
 * @mago-expect analysis:deprecated-class laminas-view 2.x injects the renderer, which reads the Settings helper, only into AbstractHelper subclasses.
 */
final class FaviconTags extends AbstractHelper
{
    /**
     * Candidate icon files probed in the web root, in head order. Each entry is
     * [filename, rel, type|null, sizes|null].
     *
     * @var list<array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private const array ICONS = [
        ['favicon.svg',          'icon',             'image/svg+xml', null],
        ['favicon-96x96.png',    'icon',             'image/png',     '96x96'],
        ['favicon-48x48.png',    'icon',             'image/png',     '48x48'],
        ['favicon-32x32.png',    'icon',             'image/png',     '32x32'],
        ['favicon-16x16.png',    'icon',             'image/png',     '16x16'],
        ['favicon.ico',          'shortcut icon',    null,            null],
        ['apple-touch-icon.png', 'apple-touch-icon', null,            '180x180'],
    ];

    /**
     * @param string|null $publicPath Web root holding the generated favicons;
     *                                defaults to <cwd>/public. Injectable for tests.
     */
    public function __construct(
        private ?string $publicPath = null,
    ) {}

    /**
     * A scalar brand setting as a string, or the default when it is missing
     * or not scalar.
     *
     * @mago-expect analysis:mixed-assignment Brand settings are untyped CMS data; checked with is_scalar().
     * @mago-expect analysis:string-member-selector The setting name is one of three fixed keys.
     */
    private static function setting(?object $brand, string $name, string $default): string
    {
        $value = $brand->{$name} ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * The `site.brand` settings object from the renderer's Settings helper,
     * or null without a PHP renderer or when the settings have no brand.
     *
     * @mago-expect analysis:mixed-assignment The Settings helper returns untyped CMS settings.
     * @mago-expect analysis:non-documented-method Settings is a view helper, called through PhpRenderer::__call().
     */
    private function brandSettings(): ?object
    {
        $view = $this->getView();
        if (! $view instanceof PhpRenderer) {
            return null;
        }

        $brand = $view->Settings()->site->brand ?? null;

        return is_object($brand) ? $brand : null;
    }

    /**
     * Strip characters that could break out of an HTML attribute. Colours are
     * hex / CSS keywords / rgb() and contain none of these, so valid values pass
     * through unchanged.
     */
    private function colour(string $value): string
    {
        return str_replace(['"', '<', '>', '&', "\n", "\r"], replace: '', subject: $value);
    }

    private function link(
        string $rel,
        string $href,
        ?string $type = null,
        ?string $sizes = null,
        string $color = '',
    ): string {
        $attr = sprintf('rel="%s" href="%s"', $rel, $href);
        if (null !== $type) {
            $attr .= sprintf(' type="%s"', $type);
        }

        if (null !== $sizes) {
            $attr .= sprintf(' sizes="%s"', $sizes);
        }

        if ('' !== $color) {
            $attr .= sprintf(' color="%s"', $color);
        }

        return "<link {$attr}>\n";
    }

    private function meta(string $name, string $content): string
    {
        return sprintf('<meta name="%s" content="%s">' . "\n", $name, $content);
    }

    public function __invoke(): string
    {
        $brand  = $this->brandSettings();
        $public = $this->publicPath ?? (string) getcwd() . '/public';

        $themeColor = $this->colour(self::setting($brand, 'theme_color', default: '#ffffff'));
        $tileColor  = $this->colour(self::setting($brand, 'tile_color', default: ''));
        $maskColor  = $this->colour(self::setting($brand, 'mask_color', default: '#000000'));

        $out = $this->meta('theme-color', $themeColor);
        if ('' !== $tileColor) {
            $out .= $this->meta('msapplication-TileColor', $tileColor);
        }

        foreach (self::ICONS as [$file, $rel, $type, $sizes]) {
            if (! is_file("{$public}/{$file}")) {
                continue;
            }

            $out .= $this->link($rel, "/{$file}", $type, $sizes);
        }

        if (is_file("{$public}/safari-pinned-tab.svg")) {
            $out .= $this->link('mask-icon', '/safari-pinned-tab.svg', color: $maskColor);
        }

        if (is_file("{$public}/site.webmanifest")) {
            $out .= $this->link('manifest', '/site.webmanifest');
        }

        return $out;
    }
}
