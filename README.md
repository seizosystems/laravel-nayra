# Laravel Nayra

This is a package to initialize [ProcessMaker Nayra](https://github.com/ProcessMaker/nayra) BPMN engine in Laravel.

## Installation

1. Install the package via composer:

```bash
composer require viezel/laravel-nayra
php artisan vendor:publish --provider="Viezel\Nayra\NayraServiceProvider" --tag="migrations"
php artisan migrate
```


## Credits

- [Mads Møller](https://github.com/viezel)
- [David Callizaya](https://github.com/caleeli)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
