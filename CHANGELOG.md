# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

## [2.0.0] - Unreleased

The helper's API is unchanged. The major version marks the move to PHP 8.3+
and the php-db QA toolchain shared by all Contenir 2.x packages. See
[UPGRADE-2.0.md](UPGRADE-2.0.md).

### Changed

- Requires PHP 8.3, 8.4 or 8.5. PHP 8.1 and 8.2 stay on 0.x (`0.x` branch).
- `laminas/laminas-servicemanager` is a direct requirement (`Module` uses its
  `InvokableFactory`; it was already installed through laminas-view).
- `Module` is `final`.
- `FaviconTags` falls back to the default colours when it has no PHP renderer
  or the settings' `site.brand` is not an object, instead of failing.

### Fixed

- A non-scalar colour setting (for example an array) was rendered as
  `Array` with an "Array to string conversion" warning; it now uses the
  default.

### Added

- Continuous integration on PHP 8.3, 8.4 and 8.5 against lowest, locked and
  latest dependencies, with coverage reported to Codecov.
- Separate unit and integration (real files in a temporary web root) test
  suites, with 100% line and branch coverage.

### Removed

- `phpcs.xml` and laminas-coding-standard, replaced by Mago via
  `php-db/phpdb-qa-tools`. PHPUnit 10 is replaced by PHPUnit 11.

## [0.1.0]

- Initial release: file-driven `FaviconTags` head helper with theme, tile and
  mask colours from the `site.brand` settings.
