<?php namespace Lanin\ExtendSeeder;

use Illuminate\Database\Eloquent\Model;
use ReflectionException;

abstract class Seeder extends \Illuminate\Database\Seeder
{

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
        self::$csvPath = $csvPath[0] == '/' ? $csvPath : base_path($csvPath);
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
     * Use truncating of the table instead if deleting all rows.
     *
     * @return bool
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
        $model = $this->resolveModel($model);
        $seederClass = studly_case($model->getTable()) . 'TableSeeder';

        try
        {
            $seeder = $this->resolve($seederClass);
        }
        catch (ReflectionException $e)
        {
            $reflection = new \ReflectionClass($model);
            $namespace  = $reflection->getNamespaceName();
            $seederClass = $namespace . '\\' . $seederClass;
            $seeder = $this->resolve($seederClass);
        }

        if ($seeder instanceof \Lanin\ExtendSeeder\Seeder)
        {
            $seeder->setModel($model);
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
     * @return integer
     */
    protected function parseAndSeed(Model $model, $filename, $delimiter = ',')
    {
        $data 	  = [];
        $headers  = [];
        $inserted = 0;

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
                if (empty($headers))
                {
                    $headers = $row;
                    continue;
                }

                $data[] = $this->prepareRow($row, $headers);

                $i++;

                if ($i == $this->chunkSize)
                {
                    $model->insert($data);

                    $i = 0;
                    $data = [];
                    $inserted += $this->chunkSize;
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
        $fileMimeType = finfo_file($fileInfo, $file);
        finfo_close($fileInfo);

        return strcmp($fileMimeType, "application/x-gzip") == 0;
    }

    /**
     * Replace NULL string with real null value.
     *
     * @param  array $row
     * @return array
     */
    protected function fixNullValues($row)
    {
        array_walk($row, function(&$value)
        {
            if ($value === 'NULL' || $value === 'null')
            {
                $value = null;
            }
        });

        return $row;
    }

    /**
     * Prepare data row to insert.
     *
     * @param  array $row
     * @param  array $headers
     * @return array
     */
    protected function prepareRow(array $row, array $headers)
    {
        $row = array_combine($headers, $row);
        return $this->fixNullValues($row);
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
        $database = str_replace([':'], '', $this->getDatabaseName($model));
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
