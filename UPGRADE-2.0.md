# Upgrading from 0.x to 2.0

2.0 renders the same tags as 0.1 for the same files and settings.

| | 0.x | 2.0 |
| --- | --- | --- |
| PHP | ^8.1 | 8.3, 8.4 or 8.5 |
| laminas/laminas-view | ^2.35 | ^2.35 |
| laminas/laminas-servicemanager | indirect | ^3.22, required directly |

```bash
composer require contenir/contenir-brand:^2.0
```

Projects that must stay on PHP 8.1 or 8.2 can keep using `^0.1`, which is
maintained on the `0.x` branch.

## `Module` is final

`Contenir\Brand\Module` is framework wiring and can no longer be extended.
To change the helper registration, override the `view_helpers` keys in your
application config instead:

```php
// 0.x
final class MyBrandModule extends \Contenir\Brand\Module
{
    public function getConfig(): array
    {
        $config = parent::getConfig();
        $config['view_helpers']['aliases']['favicons'] = FaviconTags::class;

        return $config;
    }
}

// 2.0: config/autoload/brand.global.php
return [
    'view_helpers' => [
        'aliases' => ['favicons' => \Contenir\Brand\Helper\FaviconTags::class],
    ],
];
```

## Missing or malformed settings use the defaults

0.1 failed when the helper had no PHP renderer (`Error`: method call on
null) or when `site.brand` was an array, and rendered `Array` for a
non-scalar colour. 2.0 uses the default colours in each case
(`theme_color` `#ffffff`, no tile colour, `mask_color` `#000000`). A missing
`Settings` view helper still throws, as before.

## Removed

- `phpcs.xml` and the laminas-coding-standard dev dependency, replaced by Mago
  through `php-db/phpdb-qa-tools`.
