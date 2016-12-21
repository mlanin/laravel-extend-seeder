<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Lanin\ExtendSeeder\Seeder;

class CsvSeederTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $migration = new LesCreateAccountsTable();
        $migration->up();
    }

    /** @test */
    public function it_can_run_seed_model_from_csv_file()
    {
        $this->seed('LesCsvDatabaseSeeder');

        $this->seeInDatabase('les_accounts', ['login' => 'john.doe']);
    }
}

/**
 * Main seeder class.
 *
 * @package Lanin\ExtendSeeder\Tests
 */
class LesCsvDatabaseSeeder extends Seeder
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
        $this->seedModel('LesAccount');
    }
}

/**
 * Seeder for Accounts model.
 *
 * @package Lanin\ExtendSeeder\Tests
 */
class LesAccountsTableSeeder extends Seeder
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
class LesAccount extends Model
{
    protected $table = 'les_accounts';
}

/**
 * Migration for accounts table.
 *
 * @package Lanin\ExtendSeeder\Tests
 */
class LesCreateAccountsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Schema::create('les_accounts', function(Blueprint $table) {
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
