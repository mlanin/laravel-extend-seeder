<?php

class CsvSeederTest extends \TestCase {

	/**
	 * Migrate database.
	 */
	public function runDatabaseMigrations()
	{
		// Create our testing DB tables
		$this->artisan('migrate', [
			'--path' => __DIR__ . '/migrations',
		]);
	}

	/**
	 * Setup the test environment.
	 *
	 * @return void
	 */
	public function setUp()
	{
		parent::setUp();

		// Use an in-memory DB
		$this->app['config']->set('database.default', 'csv_test');
		$this->app['config']->set('database.connections.csv_test', [
			'driver'   => 'sqlite',
			'database' => ':memory:',
			'prefix'   => '',
		]);
	}

	public function test_seeding()
	{

	}
}

class DatabaseSeeder extends \Lanin\CsvSeeder\CsvSeeder {

	/**
	 * Seed your database
	 */
	public function run()
	{
		$this->seedModel(Account::class);
	}
}

class AccountsTableSeeder extends \Lanin\CsvSeeder\CsvSeeder {

	/**
	 * Seed model with CSV.
	 *
	 * @param \Illuminate\Database\Eloquent\Model $model
	 */
	public function run(\Illuminate\Database\Eloquent\Model $model)
	{
		$this->seedModelWithCsv($model);
	}
}

class Account extends \Illuminate\Database\Eloquent\Model {

}
