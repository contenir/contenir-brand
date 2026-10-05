<?php

declare(strict_types=1);

namespace Contenir\Brand\Test\Unit;

use Contenir\Brand\Helper\FaviconTags;
use Contenir\Brand\Module;
use Laminas\ServiceManager\Factory\InvokableFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ModuleTest extends TestCase
{
    #[Test]
    public function registersTheFaviconHelperUnderBothAliases(): void
    {
        static::assertSame(
            [
                'view_helpers' => [
                    'aliases'   => [
                        'faviconTags' => FaviconTags::class,
                        'FaviconTags' => FaviconTags::class,
                    ],
                    'factories' => [
                        FaviconTags::class => InvokableFactory::class,
                    ],
                ],
            ],
            (new Module())->getConfig(),
        );
    }
}
