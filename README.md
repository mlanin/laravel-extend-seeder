# Laravel-CSV-Seeder
> Seed your Laravel project's DB with CSV data. 

## Installation

[PHP](https://php.net) 5.4+ or [HHVM](http://hhvm.com) 3.3+, and [Composer](https://getcomposer.org) are required.

To get the latest version of Laravel DevStatus, simply add the following line to the require block of your `composer.json` file.

```
"lanin/laravel-csv-seeder": "dev-master"
```

You'll then need to run `composer install` or `composer update` to download it and have the autoloader updated.

Once it is installed you don't have to register any ServiceProvider, Facade or publish any configs.

All you have to do is to extend your base Seeder class with `\Lanin\CsvSeeder\CsvSeeder` and you are good to go!

```php
class Seeder extends \Illuminate\Database\Seeder {

}
```

## Seeding

After you extended your base seeder, you will receive two additional methods:

public function seedModel($model);


## Contributing

Please feel free to fork this package and contribute by submitting a pull request to enhance the functionalities.