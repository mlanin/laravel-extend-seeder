<?php namespace Lanin\ExtendSeeder;

use Illuminate\Database\Eloquent\Model;

class Seeder extends \Illuminate\Database\Seeder {

	/**
	 * @var string
	 */
	protected $environment = null;

	/**
	 * @var Model|null
	 */
	protected $model = null;

	/**
	 * @var int
	 */
	protected $chunkSize = 200;

	/**
	 * @var string
	 */
	protected $headers = [];

	/**
	 * @var string|null
	 */
	protected static $database = null;

	/**
	 * @var string
	 */
	protected static $delimiter = ',';

	/**
	 * @var string
	 */
	protected static $csvPath = '';

	/**
	 * @var bool
	 */
	protected static $truncate = false;

	/**
	 * Create a new Seeder.
	 */
	public function __construct()
	{
		$this->boot();
	}

	/**
	 * Boot seeder.
	 */
	protected function boot()
	{
		$this->assertCanSeed();

		\DB::disableQueryLog();
		Model::unguard();
	}

	/**
	 * Check if seeder can be launched.
	 *
	 * @return bool
	 */
	protected function assertCanSeed()
	{
		if ( ! is_null($this->environment) && ! \App::environment($this->environment))
		{
			throw new \RuntimeException("You can seed this data only on [{$this->environment}] environment.");
		}
	}

	/**
	 * Set default database name. Useful for seeding sqlite db.
	 *
	 * @param string $database
	 */
	public static function setDatabaseName($database)
	{
		self::$database = $database;
	}

	/**
	 * Change path where csv files are stored.
	 *
	 * @param string $csvPath
	 */
	public static function setCsvPath($csvPath)
	{
		self::$csvPath = base_path($csvPath);
	}

	/**
	 * Get path where csv files are stored.
	 *
	 * @return string
	 */
	public static function getCsvPath()
	{
		return self::$csvPath;
	}

	/**
	 * Change cvs delimiter symbol.
	 *
	 * @param string $delimiter
	 */
	public static function setCsvDelimiter($delimiter)
	{
		self::$delimiter = $delimiter;
	}

	/**
	 * Get cvs delimiter symbol.
	 *
	 * @return string
	 */
	public static function getCsvDelimiter()
	{
		return self::$delimiter;
	}

	/**
	 * Get cvs delimiter symbol.
	 *
	 * @return string
	 */
	public static function useTruncate()
	{
		return self::$truncate = true;
	}

	/**
	 * Save seeding model.
	 *
	 * @param  mixed  $model
	 * @return $this
	 */
	public function setModel($model)
	{
		$this->model = $this->resolveModel($model);

		return $this;
	}

	/**
	 * Return seeding model.
	 *
	 * @return Model
	 */
	public function getModel()
	{
		return $this->model;
	}

	/**
	 * Launch seeder associated with the specified model.
	 * E.g. User model will be seeded via UsersTableSeeder, etc.
	 *
	 * @param  mixed $model
	 * @return \Illuminate\Database\Seeder
	 */
	public function seedModel($model)
	{
		$class = studly_case($this->resolveModel($model)->getTable()) . 'TableSeeder';

		$seeder = $this->resolve($class);

		if ($seeder instanceof \Lanin\ExtendSeeder\Seeder)
		{
			$seeder->setModel($this->resolveModel($model));
		}

		$seeder->run();

		return $seeder;
	}

	/**
	 * Seed model with CSV data.
	 * By default Seeder will try to find file in database/{$this->csvPath}/$database_$table.csv.
	 *
	 * @param  string  $csvFile
	 * @param  null  $model
	 */
	public function seedWithCsv($csvFile = '', $model = null)
	{
		$model = $this->resolveModel($model);

		$this->clearTable($model);

		$csvFile  = $this->getCsvFile($model, $csvFile);
		$inserted = $this->parseAndSeed($model, $csvFile, self::getCsvDelimiter());

		if (isset($this->command))
		{
			$this->command->getOutput()->writeln(
				sprintf('<info>Seeded:</info> %s.%s (%d rows)', $this->getDatabaseName($model), $model->getTable(), $inserted)
			);
		}
	}

	/**
	 * Find CSV file via model.
	 *
	 * @param  Model  $model
	 * @param  string  $filename
	 * @return string
	 */
	protected function getCsvFile(Model $model, $filename = '')
	{
		$filename = $this->getCsvFilename($model, $filename);

		$basePath = self::getCsvPath() ?: database_path('seeds/csv');

		return $basePath . DIRECTORY_SEPARATOR . $filename;
	}

	/**
	 * Convert CSV file to array of rows and seed them into the model.
	 *
	 * @param  Model  $model
	 * @param  string  $filename
	 * @param  string  $delimiter
	 * @return array
	 */
	protected function parseAndSeed(Model $model, $filename, $delimiter = ',')
	{
		$data 	  = [];
		$inserted = 0;
		$header   = $this->headers;

		if ( ! file_exists($filename) || ! is_readable($filename))
		{
			throw new \RuntimeException(
				sprintf("Can't find csv file [%s] for seeder [%s].", $filename, get_class($this))
			);
		}

		$handle = $this->ifGzipped($filename) ? gzopen($filename, 'r') : fopen($filename, 'r');

		if ($handle !== false)
		{
			$i = 0;
			while (($row = fgetcsv($handle, 0, $delimiter)) !== false)
			{
				if (empty($header))
				{
					$header = $row;
				}
				else
				{
					$data[] = array_combine($header, $row);
				}

				$i++;

				if ($i == $this->chunkSize)
				{
					$model->insert($data);

					$inserted += $this->chunkSize;
					$data = [];
					$i = 0;
				}
			}

			$model->insert($data);
			$inserted += count($data);

			fclose($handle);
		}

		return $inserted;
	}



	/**
	 * Check if csv file was gzipped.
	 *
	 * @param  string  $file
	 * @return bool
	 */
	private function ifGzipped($file)
	{
		$fileInfo = finfo_open(FILEINFO_MIME_TYPE);
		$file_mime_type = finfo_file($fileInfo, $file);
		finfo_close($fileInfo);

		return strcmp($file_mime_type, "application/x-gzip") == 0;
	}

	/**
	 * Retrieve database name.
	 *
	 * @param  Model  $model
	 * @return string
	 */
	protected function getDatabaseName(Model $model)
	{
		return self::$database ?: $model->getConnection()->getDatabaseName();
	}

	/**
	 * Prepares csv file name.
	 *
	 * @param  Model  $model
	 * @param  string  $filename
	 * @return string
	 */
	protected function getCsvFilename(Model $model, $filename)
	{
		$database = $this->getDatabaseName($model);
		return $filename ?: $database . '_' . $model->getTable() . '.csv';
	}

	/**
	 * Return model instance.
	 *
	 * @param  mixed  $model
	 * @return Model|null
	 * @throws \RuntimeException
	 */
	protected function resolveModel($model)
	{
		switch (true)
		{
			case is_null($model) && ! is_null($this->model):
				return $this->model;
			case is_string($model) && class_exists($model):
				return new $model;
			case $model instanceof Model:
				return $model;
			default:
				break;
		}

		throw new \RuntimeException(
			sprintf("Can't seed model [%s] in seeder [%s].", $model, get_class($this))
		);
	}

	/**
	 * Erase all data in the table.
	 *
	 * @param  Model  $model
	 */
	protected function clearTable(Model $model)
	{
		if (self::$truncate)
		{
			$model->truncate();
		}
		else
		{
			\DB::table($model->getTable())->delete();
		}
	}
}
