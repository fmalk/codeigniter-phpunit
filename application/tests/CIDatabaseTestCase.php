<?php

/**
 * Base class for CodeIgniter integration tests
 *
 * This class wraps $CI reference for communicating with CodeIgniter,
 * as well as helping to load controllers and initializing database connection for assertions
 *
 * @author		Fernando Piancastelli
 * @link		https://github.com/fmalk/codeigniter-phpunit
 * @link		http://www.phpunit.de/manual/3.7/en/database.html
 *
 * @property-read resource	$db		Reference to database
 */
abstract class CIDatabaseTestCase extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * Reference to CodeIgniter
	 *
	 * @var resource
	 */
	protected $CI;

	/**
	 * Only instantiate pdo once for test clean-up/fixture load
	 *
	 * @internal
	 * @var resource
	 */
    static private $pdo = null;

	/**
	 * Only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
	 *
	 * @internal
	 * @var resource
	 */
    private $conn = null;

	/**
	 * Call parent constructor and initialize reference to CodeIgniter
	 *
	 * @internal
	 */
	public function __construct($name = NULL, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
		$this->CI =& get_instance();
    }

	/**
	 * Load a controller from your application/controllers folder, like CI Router would do.
	 * Called with your controller class name.
	 * In case of subfolders, prefix them.
	 *
	 * @param string $class
	 * @param string $prefix  Optional.
	 * @return void
	 */
	public function requireController($class, $prefix = null)
	{
		$filepath = APPPATH.'controllers'.DIRECTORY_SEPARATOR.$prefix.DIRECTORY_SEPARATOR.$class.'.php';
		if (file_exists($filepath))
		{
			require_once($filepath);
		}
	}

    /**
	 * Initialize database connection (same one used by CodeIgniter)
	 *
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    final public function getConnection()
    {
        if ($this->conn === null) {
            if (self::$pdo == null) {
            	$dsn = $this->CI->db->dbdriver.':dbname='.$this->CI->db->database.';host='.$this->CI->db->hostname;
            	if ($this->CI->db->dsn) {
            		$dsn = $this->CI->db->dsn;
            	}
                self::$pdo = new PDO($dsn,$this->CI->db->username, $this->CI->db->password);
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo, $this->CI->db->database);
        }

        return $this->conn;
    }

	/**
	 * @internal
	 */
	public function __get($name)
	{
		if ($name == 'db')
		{
			return $this->getConnection();
		}
	}

    /**
	 * Returns the DataSet
	 *
	 * Important: the returned DataSet is the current database state, meaning
	 * this function does NOT behave as a fixture: the intended usage of this
	 * current state connection is to do integration testing.
	 * If you want to use fixtures, check PHPUnit's database manual.
	 *
	 * @link		https://github.com/fmalk/codeigniter-phpunit	 *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
         return $this->getConnection()->createDataSet();
    }
}
