# Laravel-CSV-Seeder
> Seed your Laravel project's DB with CSV data. 

## Installation

[PHP](https://php.net) 5.4+ or [HHVM](http://hhvm.com) 3.3+, [Composer](https://getcomposer.org) and [Laravel](http://laravel.com) 5.0+ are required.

To get the latest version of Laravel DevStatus, simply add the following line to the require block of your `composer.json` file.

```
"lanin/laravel-csv-seeder": "dev-master"
```

You'll then need to run `composer install` or `composer update` to download it and have the autoloader updated.

Once it was installed you don't have to register any ServiceProvider, Facade or publish any configs.

All you have to do is to extend your base Seeder class with `\Lanin\CsvSeeder\CsvSeeder` and you are good to go!

```php
class Seeder extends \Lanin\CsvSeeder\CsvSeeder { }
```

## Seeding

After you extended your base seeder, you will receive two additional methods

#### seedModel($model)

The best place for this method is the `run` method of your main DatabaseSeeder. 

By default you have to fire `call` method that will resolve a specified seeder as `{$tableName}TableSeeder` and launch it, and there resolve your models and do all the stuff.

This method will do it for you. It will find related seeder by model's table and pass your model to it.

**Hint:** This method returns resolved seeder object, so you can chain your seed methods.

**Example:**

```php
class Account extends \Illuminate\Database\Eloquent\Model {
	protected $table = 'accounts';
}

class DatabaseSeeder extends Seeder {

	/**
	 * Seed your database.
	 */
	public function run()
	{
		$this->seedModel(\App\Account::class);
	}
}

class AccountsTableSeeder extends Seeder {

	/**
	 * Seed model.
	 */
	public function run()
	{
		$this->getModel()->truncate();
	}
}
```

#### seedWithCsv($csvFile = '', $model = null)

This method seeds your database with data from the related csv files.

By default, it tries to find them in your `/database/seeds/csv` directory.

Files have to be names as `{$databaseName}_{$tableName}.csv`, but you can always overwrite this behaviour.

If you are calling this method via table seeder, that was called via `seedModel`, it already knows everything about your model and can resolve related csv file iteslf.

**Example:**
```php
class AccountsTableSeeder extends Seeder {

	/**
	 * Seed model with CSV.
	 */
	public function run()
	{
		$this->seedWithCsv();
	}
}
```

Full example you can find in the `tests/CsvSeederTest.php`

## Hacks

You can leave TableSeeders empty and chain csv import via basic seeder.

```php
class DatabaseSeeder extends Seeder {

	/**
	 * Seed your database.
	 */
	public function run()
	{
		$this->seedModel(\App\Account::class)->seedWithCsv();
	}
}

class AccountsTableSeeder extends Seeder {

	/**
	 * Seed model.
	 */
	public function run()
	{

	}
}
```

You don't even have to create TableSeeders and seed right in your basic seeder.

```php
class DatabaseSeeder extends Seeder {

	/**
	 * Seed your database.
	 */
	public function run()
	{
		$this->setModel(\App\Account::class)->seedWithCsv();
	}
}
```

If you want to have your csv files to be set in another location you can use static method `setCsvPath($csvPath)`, where you can specify relative from your base_path path.

## Contributing

Please feel free to fork this package and contribute by submitting a pull request to enhance the functionalities.