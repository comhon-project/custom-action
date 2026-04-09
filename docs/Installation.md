# Installation

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

You can publish the policies files with:

```bash
php artisan vendor:publish --tag="custom-action-policies"
```