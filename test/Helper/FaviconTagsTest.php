<?php

declare(strict_types=1);

namespace Contenir\Brand\Test\Helper;

use Contenir\Brand\Helper\FaviconTags;
use Laminas\View\Renderer\PhpRenderer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function glob;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function touch;
use function uniqid;
use function unlink;

#[Group('unit')]
final class FaviconTagsTest extends TestCase
{
    private string $public;

    protected function setUp(): void
    {
        parent::setUp();
        $this->public = sys_get_temp_dir() . '/brand_' . uniqid('', true);
        mkdir($this->public, 0o777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->public . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->public);
        parent::tearDown();
    }

    /**
     * @param array<string, string> $brand
     * @param list<string>          $files
     */
    private function render(array $brand, array $files): string
    {
        foreach ($files as $file) {
            touch($this->public . '/' . $file);
        }

        $renderer = new PhpRenderer();
        $renderer->getHelperPluginManager()->setService(
            'Settings',
            static fn (): object => (object) ['site' => (object) ['brand' => (object) $brand]],
        );

        $helper = new FaviconTags($this->public);
        $helper->setView($renderer);

        return $helper();
    }

    public function testEmitsThemeColourMetaFromSettings(): void
    {
        $html = $this->render(['theme_color' => '#123456'], []);

        self::assertStringContainsString('<meta name="theme-color" content="#123456">', $html);
    }

    public function testEmitsOnlyIconsThatExistOnDisk(): void
    {
        $html = $this->render(
            ['theme_color' => '#ffffff'],
            ['favicon.ico', 'favicon-32x32.png', 'favicon-16x16.png', 'apple-touch-icon.png', 'site.webmanifest'],
        );

        self::assertStringContainsString('<link rel="shortcut icon" href="/favicon.ico">', $html);
        self::assertStringContainsString('<link rel="icon" href="/favicon-32x32.png" type="image/png" sizes="32x32">', $html);
        self::assertStringContainsString('<link rel="icon" href="/favicon-16x16.png" type="image/png" sizes="16x16">', $html);
        self::assertStringContainsString('<link rel="apple-touch-icon" href="/apple-touch-icon.png" sizes="180x180">', $html);
        self::assertStringContainsString('<link rel="manifest" href="/site.webmanifest">', $html);

        // Files not on disk must not be emitted.
        self::assertStringNotContainsString('favicon.svg', $html);
        self::assertStringNotContainsString('favicon-96x96', $html);
    }

    public function testEmitsModernSvgSetWhenPresent(): void
    {
        $html = $this->render(['theme_color' => '#000000'], ['favicon.svg', 'favicon-96x96.png']);

        self::assertStringContainsString('<link rel="icon" href="/favicon.svg" type="image/svg+xml">', $html);
        self::assertStringContainsString('<link rel="icon" href="/favicon-96x96.png" type="image/png" sizes="96x96">', $html);
    }

    public function testMaskIconCarriesMaskColour(): void
    {
        $html = $this->render(['theme_color' => '#fff', 'mask_color' => '#5bbad5'], ['safari-pinned-tab.svg']);

        self::assertStringContainsString('<link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">', $html);
    }

    public function testTileColourMetaOnlyWhenSet(): void
    {
        $with = $this->render(['theme_color' => '#fff', 'tile_color' => '#da532c'], []);
        self::assertStringContainsString('<meta name="msapplication-TileColor" content="#da532c">', $with);

        $without = $this->render(['theme_color' => '#fff'], []);
        self::assertStringNotContainsString('msapplication-TileColor', $without);
    }

    public function testStripsAttributeBreakingCharactersFromColour(): void
    {
        $html = $this->render(['theme_color' => '#fff"><script>'], []);

        self::assertStringContainsString('<meta name="theme-color" content="#fffscript">', $html);
        self::assertStringNotContainsString('<script>', $html);
    }
}
