<?php namespace Lanin\CsvSeeder;

use Illuminate\Database\Eloquent\Model;

class CsvSeeder extends \Illuminate\Database\Seeder {

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
	 * @var string|null
	 */
	protected $database = null;

	/**
	 * @var string
	 */
	protected $headers = [];

	/**
	 * @var string
	 */
	protected static $delimiter = ',';

	/**
	 * @var string
	 */
	protected static $csvPath = '';

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
	 * Save seeding model.
	 *
	 * @param  mixed  $model
	 * @return $this
	 */
	public function setModel($model)
	{
		$this->model = $this->prepareModel($model);

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
		$class = studly_case($this->prepareModel($model)->getTable()) . 'TableSeeder';

		$seeder = $this->resolve($class);

		if ($seeder instanceof \Lanin\CsvSeeder\CsvSeeder)
		{
			$seeder->setModel($this->prepareModel($model));
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
		$model = ! is_null($model) ? $this->prepareModel($model) : $this->getModel();

		$csvFile = $this->getCsvFile($model, $csvFile);
		$csvData = $this->csvToArray($csvFile, self::getCsvDelimiter());

		$model->truncate();
		foreach (array_chunk($csvData, 200) as $chunk)
		{
			$model->insert($chunk);
		}

		if (isset($this->command))
		{
			$this->command->getOutput()->writeln(
				sprintf('<info>Seeded:</info> %s.%s (%d rows)', $this->getDatabaseName($model), $model->getTable(), count($csvData))
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
	 * Convert CSV file to array of rows.
	 *
	 * @param  string  $filename
	 * @param  string  $delimiter
	 * @return array
	 */
	protected function csvToArray($filename = '', $delimiter = ',')
	{
		$data 	= [];
		$header = $this->headers;

		if ( ! file_exists($filename) || ! is_readable($filename))
		{
			return $data;
		}

		$handle = $this->ifGzipped($filename) ? gzopen($filename, 'r') : fopen($filename, 'r');

		if ($handle !== false)
		{
			while (($row = fgetcsv($handle, 1000, $delimiter)) !== false)
			{
				if (empty($header))
				{
					$header = $row;
				}
				else
				{
					$data[] = array_combine($header, $row);
				}
			}
			fclose($handle);
		}

		return $data;
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
		return is_null($this->database) ? $model->getConnection()->getDatabaseName() : $this->database;
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
	protected function prepareModel($model)
	{
		switch (true)
		{
			case is_string($model) && class_exists($model):
				return new $model;
			case $model instanceof Model:
				return $model;
			case is_null($model) && ! is_null($this->model):
				return $model;
			default:
				break;
		}

		throw new \RuntimeException(
			sprintf("Can't seed model [%s] in seeder [%s].", $model, get_class($this))
		);
	}
}