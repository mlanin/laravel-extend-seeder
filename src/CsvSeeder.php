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
	protected $delimiter = ',';

	/**
	 * @var string
	 */
	protected $headers = [];

	/**
	 * @var string
	 */
	protected static $csvPath = '';

	/**
	 * Create a new Seeder.
	 */
	public function __construct()
	{
		if ( ! is_null($this->environment) && ! \App::environment($this->environment))
		{
			throw new \RuntimeException("You can seed this data only on [{$this->environment}] environment.");
		}
	}

	/**
	 * @param string $csvPath
	 */
	public static function setCsvPath($csvPath)
	{
		self::$csvPath = $csvPath;
	}

	/**
	 * @return string
	 */
	public static function getCsvPath()
	{
		return self::$csvPath;
	}

	/**
	 * Save seeding model.
	 *
	 * @param  Model  $model
	 * @return $this
	 */
	public function setModel(Model $model)
	{
		$this->model = $model;

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
	 * Seed the given model. Resolves model's table and fires associated seed class.
	 * User model will be seeded via UsersTableSeeder, etc.
	 *
	 * @param  Model  $model
	 */
	public function seedModel($model)
	{
		$model = is_string($model) ? new $model : $model;

		$class = studly_case($model->getTable()) . 'TableSeeder';

		$seeder = $this->resolve($class);

		if ($seeder instanceof \Lanin\CsvSeeder\CsvSeeder)
		{
			$seeder->setModel($model);
		}

		$seeder->run();
	}

	/**
	 * Seed model with CSV data.
	 * By default Seeder will try to find file in database/{$this->csvPath}/$database_$table.csv.
	 *
	 * @param  Model|null $model
	 * @param  string  $csvFile
	 */
	public function seedModelWithCsv(Model $model = null, $csvFile = '')
	{
		$model = $model ?: $this->getModel();

		$csvFile = $this->getCsvFile($model, $csvFile);
		$csvData = $this->csvToArray($csvFile, $this->delimiter);

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

		return $basePath . '/' . $filename . '.csv';
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
	 * @param  Model  $model
	 * @return string
	 */
	protected function getDatabaseName(Model $model)
	{
		return is_null($this->database) ? $model->getConnection()->getDatabaseName() : $this->database;
	}

	/**
	 * @param  Model  $model
	 * @param  string  $filename
	 * @return string
	 */
	protected function getCsvFilename(Model $model, $filename)
	{
		$database = $this->getDatabaseName($model);
		return $filename ?: $database . '_' . $model->getTable();
	}
}