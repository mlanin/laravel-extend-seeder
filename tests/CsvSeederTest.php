<?php namespace Lanin\ExtendSeeder\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Lanin\ExtendSeeder\Seeder;
use Schema;

class CsvSeederTest extends TestCase
{
	/** @test */
	public function test_seeding()
	{
		$migration = new CreateAccountsTable();
		$migration->up();

		$this->seed(CsvSeederDatabaseSeeder::class);

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

		self::setCsvPath(realpath(dirname(__DIR__) . '/tests/fixture/csv'));
	}

	/**
	 * Seed your database.
	 */
	public function run()
	{
		$this->seedModel(Accounts::class);
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

class Accounts extends Model
{
	protected $table = 'accounts';
}

class CreateAccountsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('accounts', function(Blueprint $table) {
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
		Schema::drop('accounts');
	}

}