# Filament Impersonate is a plugin that allows you to authenticate as your users.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/xlitedev/filament-impersonate.svg?style=flat-square)](https://packagist.org/packages/xlitedev/filament-impersonate)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/xlitedev/filament-impersonate/run-tests?label=tests)](https://github.com/xlitedev/filament-impersonate/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/xlitedev/filament-impersonate/Check%20&%20fix%20styling?label=code%20style)](https://github.com/xlitedev/filament-impersonate/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/xlitedev/filament-impersonate.svg?style=flat-square)](https://packagist.org/packages/xlitedev/filament-impersonate)



This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require xlitedev/filament-impersonate
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
$filament-impersonate = new XliteDev\FilamentImpersonate();
echo $filament-impersonate->echoPhrase('Hello, XliteDev!');
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
