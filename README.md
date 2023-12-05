# Custom Action

[![Latest Version on Packagist](https://img.shields.io/packagist/v/comhon-project/custom-action.svg?style=flat-square)](https://packagist.org/packages/comhon-project/custom-action)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/comhon-project/custom-action/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/comhon-project/custom-action/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/comhon-project/custom-action/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/comhon-project/custom-action/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/comhon-project/custom-action.svg?style=flat-square)](https://packagist.org/packages/comhon-project/custom-action)

Laravel library that permits to create and customize actions in a very easy way.

## Installation

You can install the package via composer:

```bash
composer require comhon-project/custom-action
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="custom-action-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="custom-action-config"
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

-   [jean-philippe](https://github.com/comhon-project)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
