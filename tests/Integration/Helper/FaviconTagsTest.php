<?php

declare(strict_types=1);

namespace Contenir\Brand\Test\Integration\Helper;

use Contenir\Brand\Helper\FaviconTags;
use Contenir\Brand\Module;
use Contenir\Brand\Test\Trait\TemporaryDirectoryTrait;
use Laminas\View\Renderer\PhpRenderer;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function chdir;
use function getcwd;
use function mkdir;
use function touch;

/**
 * The helper against real files in a temporary web root, rendered through a
 * PhpRenderer whose Settings helper returns the given CMS settings.
 */
#[Group('integration')]
final class FaviconTagsTest extends TestCase
{
    use TemporaryDirectoryTrait;

    private const string THEME_DEFAULT = "<meta name=\"theme-color\" content=\"#ffffff\">\n";

    private string $originalCwd = '';

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function colourProvider(): array
    {
        return [
            'theme colour from settings'      => [
                ['theme_color' => '#123456'],
                "<meta name=\"theme-color\" content=\"#123456\">\n",
            ],
            'tile colour when set'            => [
                ['tile_color' => '#da532c'],
                self::THEME_DEFAULT . "<meta name=\"msapplication-TileColor\" content=\"#da532c\">\n",
            ],
            'empty tile colour'               => [['tile_color' => ''], self::THEME_DEFAULT],
            'attribute-breaking characters'   => [
                ['theme_color' => "#fff\"><script>&\r\n"],
                "<meta name=\"theme-color\" content=\"#fffscript\">\n",
            ],
            'non-scalar setting uses default' => [['theme_color' => ['#123456']], self::THEME_DEFAULT],
            'numeric setting'                 => [['theme_color' => 0], "<meta name=\"theme-color\" content=\"0\">\n"],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function iconProvider(): array
    {
        return [
            'svg'              => ['favicon.svg', '<link rel="icon" href="/favicon.svg" type="image/svg+xml">'],
            'png 96'           => [
                'favicon-96x96.png',
                '<link rel="icon" href="/favicon-96x96.png" type="image/png" sizes="96x96">',
            ],
            'png 48'           => [
                'favicon-48x48.png',
                '<link rel="icon" href="/favicon-48x48.png" type="image/png" sizes="48x48">',
            ],
            'png 32'           => [
                'favicon-32x32.png',
                '<link rel="icon" href="/favicon-32x32.png" type="image/png" sizes="32x32">',
            ],
            'png 16'           => [
                'favicon-16x16.png',
                '<link rel="icon" href="/favicon-16x16.png" type="image/png" sizes="16x16">',
            ],
            'ico'              => ['favicon.ico', '<link rel="shortcut icon" href="/favicon.ico">'],
            'apple touch icon' => [
                'apple-touch-icon.png',
                '<link rel="apple-touch-icon" href="/apple-touch-icon.png" sizes="180x180">',
            ],
            'manifest'         => ['site.webmanifest', '<link rel="manifest" href="/site.webmanifest">'],
            'mask icon'        => [
                'safari-pinned-tab.svg',
                '<link rel="mask-icon" href="/safari-pinned-tab.svg" color="#000000">',
            ],
        ];
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function missingBrandProvider(): array
    {
        return [
            'no site key'       => [(object) []],
            'no brand key'      => [(object) ['site' => (object) []]],
            'brand is an array' => [(object) ['site' => (object) ['brand' => ['theme_color' => '#123456']]]],
        ];
    }

    #[Test]
    #[DataProvider('iconProvider')]
    public function emitsALinkForAnIconFileThatExists(string $file, string $link): void
    {
        $this->createFiles($file);

        static::assertSame(self::THEME_DEFAULT . "{$link}\n", $this->render([]));
    }

    #[Test]
    public function emitsOnlyTheThemeColourWithoutIconFiles(): void
    {
        static::assertSame(self::THEME_DEFAULT, $this->render([]));
    }

    #[Test]
    public function emitsTheFullSetInHeadOrder(): void
    {
        $this->createFiles(
            'site.webmanifest',
            'safari-pinned-tab.svg',
            'apple-touch-icon.png',
            'favicon.ico',
            'favicon-16x16.png',
            'favicon-32x32.png',
            'favicon-48x48.png',
            'favicon-96x96.png',
            'favicon.svg',
        );

        static::assertSame(
            "<meta name=\"theme-color\" content=\"#ffffff\">\n"
                . "<link rel=\"icon\" href=\"/favicon.svg\" type=\"image/svg+xml\">\n"
                . "<link rel=\"icon\" href=\"/favicon-96x96.png\" type=\"image/png\" sizes=\"96x96\">\n"
                . "<link rel=\"icon\" href=\"/favicon-48x48.png\" type=\"image/png\" sizes=\"48x48\">\n"
                . "<link rel=\"icon\" href=\"/favicon-32x32.png\" type=\"image/png\" sizes=\"32x32\">\n"
                . "<link rel=\"icon\" href=\"/favicon-16x16.png\" type=\"image/png\" sizes=\"16x16\">\n"
                . "<link rel=\"shortcut icon\" href=\"/favicon.ico\">\n"
                . "<link rel=\"apple-touch-icon\" href=\"/apple-touch-icon.png\" sizes=\"180x180\">\n"
                . "<link rel=\"mask-icon\" href=\"/safari-pinned-tab.svg\" color=\"#5bbad5\">\n"
                . "<link rel=\"manifest\" href=\"/site.webmanifest\">\n",
            $this->render(['mask_color' => '#5bbad5']),
        );
    }

    #[Test]
    public function looksInThePublicDirectoryOfTheWorkingDirectoryByDefault(): void
    {
        mkdir("{$this->tmpDir}/public");
        touch("{$this->tmpDir}/public/favicon.ico");
        chdir($this->tmpDir);

        static::assertSame(
            self::THEME_DEFAULT . "<link rel=\"shortcut icon\" href=\"/favicon.ico\">\n",
            (new FaviconTags())(),
        );
    }

    #[Test]
    public function maskIconOmitsAnEmptyMaskColour(): void
    {
        $this->createFiles('safari-pinned-tab.svg');

        static::assertSame(
            self::THEME_DEFAULT . "<link rel=\"mask-icon\" href=\"/safari-pinned-tab.svg\">\n",
            $this->render(['mask_color' => '']),
        );
    }

    #[Test]
    public function moduleRegistersTheHelperForTheRenderer(): void
    {
        $renderer = new PhpRenderer();
        $renderer->getHelperPluginManager()->configure((new Module())->getConfig()['view_helpers']);

        static::assertInstanceOf(FaviconTags::class, $renderer->plugin('faviconTags'));
    }

    /**
     * @param array<string, mixed> $brand
     */
    #[Test]
    #[DataProvider('colourProvider')]
    public function rendersColourMetaFromBrandSettings(array $brand, string $expected): void
    {
        static::assertSame($expected, $this->render($brand));
    }

    #[Test]
    #[DataProvider('missingBrandProvider')]
    public function usesDefaultsWhenTheSettingsHaveNoBrand(mixed $settings): void
    {
        static::assertSame(self::THEME_DEFAULT, $this->renderWithSettings($settings));
    }

    #[Test]
    public function usesDefaultsWithoutAPhpRenderer(): void
    {
        static::assertSame(self::THEME_DEFAULT, (new FaviconTags($this->tmpDir))());
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpTemporaryDirectory();
        $this->originalCwd = (string) getcwd();
    }

    #[Override]
    protected function tearDown(): void
    {
        chdir($this->originalCwd);
        $this->tearDownTemporaryDirectory();
    }

    /**
     * @param array<string, mixed> $brand
     */
    private function render(array $brand): string
    {
        return $this->renderWithSettings((object) ['site' => (object) ['brand' => (object) $brand]]);
    }

    private function renderWithSettings(mixed $settings): string
    {
        $renderer = new PhpRenderer();
        $renderer->getHelperPluginManager()->setService('Settings', static fn(): mixed => $settings);

        $helper = new FaviconTags($this->tmpDir);
        $helper->setView($renderer);

        return $helper();
    }
}
