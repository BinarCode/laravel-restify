# Installation 1.0

[[toc]]

## Requirements

Laravel Restify has a few requirements you should be aware of before installing:

- Composer
- Laravel Framework 5.5+

## Installing Laravel Restify

```bash
composer require binaryk/laravel-restify
```

## Setup Laravel Restify
After the instalation, the package requires a setup process, this will publish configuration, provider and will create the 
`app/Restify` directory with an abstract `Repository` and scaffolding a `User` repository you can play with:

```shell script
php artisan restify:setup
```

:::tip Package Stability

If you are not able to install Restify into your application because of your `minimum-stability` setting,
 consider setting your `minimum-stability` option to `dev` and your `prefer-stable` option to `true`. 
 This will allow you to install Laravel Restify while still preferring stable package 
 releases for your application.
:::

## Quick start

Having the package setup and users table migrated, you should be good to perform the first API request:

```http request
GET: /restify-api/users?perPage=10
```

This should return the users list paginated and formatted according to [JSON:API](https://jsonapi.org/format/) standard.

## Generate repository

Creating a new repository can be done via restify command: 

```shell script
php artisan restify:repository Post
```

If you want to generate the Policy, Model and migration as well, then you can use the `--all` option:

```shell script
php artisan restify:repository Post --all
```
## Generate policy 

Since the authorization is done through the Laravel Policies, a good way of generating a complete policy for an entity
is by using the restify command:

```shell script
php artisan restify:policy Post
```
