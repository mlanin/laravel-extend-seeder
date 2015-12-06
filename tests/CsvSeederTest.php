<?php namespace Lanin\ExtendSeeder\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Lanin\ExtendSeeder\Seeder;

class CsvSeederTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();

		$migration = new CreateAccountsTable();
		$migration->up();
	}

	/** @test */
	public function it_can_run_seed_model_from_csv_file()
	{
		$this->seed('Lanin\ExtendSeeder\Tests\CsvSeederDatabaseSeeder');

		$this->seeInDatabase('accounts', ['login' => 'john.doe']);
	}
}

/**
 * Main seeder class.
 *
 * @package Lanin\ExtendSeeder\Tests
 */
class CsvSeederDatabaseSeeder extends Seeder
{

	/**
	 * Boot seeder.
	 */
	protected function boot()
	{
		parent::boot();

        self::$csvHasHeaders = true;
		self::setCsvPath(realpath(dirname(__DIR__) . '/tests/fixture/csv'));
	}

	/**
	 * Seed your database.
	 */
	public function run()
	{
		$this->seedModel('Lanin\ExtendSeeder\Tests\Accounts');
	}
}

/**
 * Seeder for Accounts model.
 *
 * @package Lanin\ExtendSeeder\Tests
 */
class AccountsTableSeeder extends Seeder
{
	/**
	 * Overwrite database name.
	 *
	 * @var string
	 */
	protected static $database = 'tests';

	/**
	 * Seed model with CSV.
	 */
	public function run()
	{
		$this->seedWithCsv();
	}
}

/**
 * Model Accounts.
 *
 * @package Lanin\ExtendSeeder\Tests
 */
class Accounts extends Model
{
	protected $table = 'accounts';
}

/**
 * Migration for accounts table.
 *
 * @package Lanin\ExtendSeeder\Tests
 */
class CreateAccountsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		\Schema::create('accounts', function(Blueprint $table) {
			$table->increments('id');
			$table->string('login', 40)->unique();
			$table->boolean('active');
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		\Schema::drop('accounts');
	}

}