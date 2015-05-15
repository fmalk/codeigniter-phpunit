<?php

/**
 * Base class for CodeIgniter unit tests
 * 
 * This class wraps $CI reference for communicating with CodeIgniter,
 * as well as helping to load controllers and models
 * 
 * @author		Fernando Piancastelli
 * @link		https://github.com/fmalk/codeigniter-phpunit
 */
abstract class CITestCase extends PHPUnit_Framework_TestCase
{
	/**
	 * Reference to CodeIgniter
	 * 
	 * @var resource
	 */
	protected $CI;
	
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
}