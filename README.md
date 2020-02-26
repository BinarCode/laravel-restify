<p align="center"><img src="https://BinarCode.github.io/laravel-restify/assets/img/logo.png"></p>

<p align="center">
    <a href="https://travis-ci.org/BinarCode/laravel-restify.svg?branch=master"><img src="https://travis-ci.org/BinarCode/laravel-restify.svg?branch=master" alt="Build Status"></a>
    <a href="https://packagist.org/packages/binaryk/laravel-restify"><img src="https://poser.pugx.org/binaryk/laravel-restify/d/total.svg" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/binaryk/laravel-restify"><img src="https://poser.pugx.org/binaryk/laravel-restify/v/stable.svg" alt="Latest Stable Version"></a>
    <a href="https://scrutinizer-ci.com/g/BinarCode/laravel-restify"><img src="https://img.shields.io/scrutinizer/g/BinarCode/laravel-restify.svg" alt="Quality"></a>
    <a href="https://packagist.org/packages/binaryk/laravel-restify"><img src="https://poser.pugx.org/binaryk/laravel-restify/license.svg" alt="License"></a>
</p>

The first fully customizable Laravel [JSON:API](https://jsonapi.org) builder. "CRUD" and protect your resources with 0 (zero) extra line of code.

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

See the [official documentation](https://binarcode.github.io/laravel-restify/docs/2.0/).

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

