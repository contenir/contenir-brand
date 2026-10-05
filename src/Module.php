<?php

declare(strict_types=1);

namespace Contenir\Brand;

use Laminas\ServiceManager\Factory\InvokableFactory;

/**
 * Laminas component wiring. Registers the brand view helpers; the favicon set is
 * driven by the files actually present in the web root plus the `site.brand`
 * colour settings, so one helper serves every site.
 *
 * @api
 */
final class Module
{
    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return [
            'view_helpers' => [
                'aliases'   => [
                    'faviconTags' => Helper\FaviconTags::class,
                    'FaviconTags' => Helper\FaviconTags::class,
                ],
                'factories' => [
                    Helper\FaviconTags::class => InvokableFactory::class,
                ],
            ],
        ];
    }
}
