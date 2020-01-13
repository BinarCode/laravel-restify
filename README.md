# Laravel magic REST API builder

[![Latest Version on Packagist](https://img.shields.io/packagist/v/binaryk/laravel-restify.svg?style=flat-square)](https://packagist.org/packages/binaryk/laravel-restify)
[![Build Status](https://img.shields.io/travis/binaryk/laravel-restify/master.svg?style=flat-square)](https://travis-ci.org/binaryk/laravel-restify)
[![Quality Score](https://img.shields.io/scrutinizer/g/binaryk/laravel-restify.svg?style=flat-square)](https://scrutinizer-ci.com/g/binaryk/laravel-restify)
[![Test Coverage](https://img.shields.io/scrutinizer/coverage/g/binaryk/laravel-restify.svg?style=flat-square)](https://scrutinizer-ci.com/g/binaryk/laravel-restify)
[![Total Downloads](https://img.shields.io/packagist/dt/binaryk/laravel-restify.svg?style=flat-square)](https://packagist.org/packages/binaryk/laravel-restify)

This package will generate the API resources "CRUD" for you. By using Laravel buit in Policies and Eloquent Resource API the implementation become a pleasure.

The response is made according with [JSON:API](https://jsonapi.org/format/) standard.

## Installation

You can install the package via composer:

```bash
composer require binaryk/laravel-restify
```

## Key havings

- "CRUD" over resources with 0 (zero) extra custom code
- Passport authentication (use `php artisan restify:check-passport` for it's setup)
- Auth module (register, verify, login, reset + forgot password)
- Beautiful response maker
- Powerful and configurable searching/filtering over entities
- API friendly Exception Handler

## Quick start

Setup package:

```bash
php artisan restify:setup
```

Generate repository:

```bash
php artisan restify:repository Post
```

## Usage

See the [official documentation](https://binaryk.github.io/laravel-restify/).

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email eduard.lupacescu@binarcode.com instead of using the issue tracker.

## Credits

- [Eduard Lupacescu](https://github.com/binaryk)
- [Koen Koenster](https://github.com/Koenster)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

