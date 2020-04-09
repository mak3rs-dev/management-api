# How install this project:

It is a Laravel Project and it is used as Backend with API Rest Json.

## Laradock

Laradock is a full PHP development environment based on Docker.
You can use it for php, mysql, etc.

Install from
https://laradock.io/

## Installing this project for first time

You need uncomment these lines:
database/seeds/DatabaseSeeder.php:14
database/seeds/DatabaseSeeder.php:15

Then, exec from php:
```php
composer install
php artisan migrate
php artisan db:seed
```
