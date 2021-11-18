<p align="center"><img src="/docs-v2/static/logo.png"></p>

<p align="center">
    <a href="https://github.com/BinarCode/laravel-restify/actions"><img src="https://github.com/BinarCode/laravel-restify/workflows/tests/badge.svg" alt="Build Status"></a>
    <a href="https://packagist.org/packages/binaryk/laravel-restify"><img src="https://poser.pugx.org/binaryk/laravel-restify/d/total.svg" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/binaryk/laravel-restify"><img src="https://poser.pugx.org/binaryk/laravel-restify/v/stable.svg" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/binaryk/laravel-restify"><img src="https://poser.pugx.org/binaryk/laravel-restify/license.svg" alt="License"></a>
</p>

The first fully customizable Laravel [JSON:API](https://jsonapi.org) builder. "CRUD" and protect your resources with 0 (zero) extra line of code.

## Installation

You can install the package via composer:

```bash
composer require binaryk/laravel-restify
```

## Videos

If you are a visual learner, checkout [our video course](https://www.binarcode.com/learn/restify) for the Laravel Restify.

## Quick start

Setup package:

```bash
php artisan restify:setup
```

Generate repository:

```bash
php artisan restify:repository Dream --all
```

Now you have the REST CRUD over dreams and this beautiful repository:

<p align="center"><img src="/docs-v2/static/tile.png"></p>

Now you can go into Postman and check it out: 

```bash
GET: http://laravel.test/api/restify/dreams
```

```bash
POST: http://laravel.test/api/restify/dreams
```

```bash
GET: http://laravel.test/api/restify/dreams/1
```

```bash
PUT: http://laravel.test/api/restify/dreams/1
```

```bash
DELETE: http://laravel.test/api/restify/dreams/1
```

## Usage

See the [official documentation](https://restify.binarcode.com).

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

