# This is my package filament-impersonate

[![Latest Version on Packagist](https://img.shields.io/packagist/v/xlite-dev/filament-impersonate.svg?style=flat-square)](https://packagist.org/packages/xlite-dev/filament-impersonate)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/xlite-dev/filament-impersonate/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/xlite-dev/filament-impersonate/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/xlite-dev/filament-impersonate/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/xlite-dev/filament-impersonate/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/xlite-dev/filament-impersonate.svg?style=flat-square)](https://packagist.org/packages/xlite-dev/filament-impersonate)



This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require xlite-dev/filament-impersonate
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-impersonate-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-impersonate-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filament-impersonate-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$filamentImpersonate = new XliteDev\FilamentImpersonate();
echo $filamentImpersonate->echoPhrase('Hello, XliteDev!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Ion Caliman](https://github.com/icaliman)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
