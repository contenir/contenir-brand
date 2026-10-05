<?php

declare(strict_types=1);

namespace Contenir\Brand\Test\Trait;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_reverse;
use function is_dir;
use function iterator_to_array;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function touch;
use function uniqid;
use function unlink;

/**
 * A private scratch directory per test, removed afterwards with everything
 * a test created in it.
 */
trait TemporaryDirectoryTrait
{
    private string $tmpDir;

    protected function setUpTemporaryDirectory(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/contenir-brand-' . uniqid(more_entropy: true);
        mkdir($this->tmpDir, permissions: 0o777, recursive: true);
    }

    protected function tearDownTemporaryDirectory(): void
    {
        if (! is_dir($this->tmpDir)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tmpDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        /** @var list<SplFileInfo> $found */
        $found = iterator_to_array($items, preserve_keys: false);
        foreach (array_reverse($found) as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($this->tmpDir);
    }

    private function createFiles(string ...$names): void
    {
        foreach ($names as $name) {
            touch("{$this->tmpDir}/{$name}");
        }
    }
}
