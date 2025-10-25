# Changelog

All notable changes to `elastic-bridge` will be documented in this file.

## [Unreleased]

### Added
- Laravel 12.x support
- PHP 8.3 support
- Carbon 3.8.4+ support for Laravel 12
- Orchestra Testbench 10.x support for Laravel 12

### Changed
- Updated GitHub Actions workflow to dynamically handle Laravel 12 dependencies
- Updated composer.json to support Laravel 10.x, 11.x, and 12.x
- Updated phpunit.xml.dist schema to PHPUnit 11.5 (backward compatible with PHPUnit 10.5)
- Improved version constraints for better compatibility across Laravel versions
- Base package uses PHPStan 1.x (compatible with Rector), CI upgrades to PHPStan 2.1+ for Laravel 12 testing

### Notes
- For Laravel 12 development, PHPUnit 11.5.3+, Larastan 3.0, and PHPStan 2.1+ are required
- CI/CD pipeline automatically installs correct versions based on Laravel version being tested
- Base package remains compatible with Laravel 10 and 11 out of the box

