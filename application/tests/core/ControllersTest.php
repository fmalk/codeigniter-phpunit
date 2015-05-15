<?php

require_once(__DIR__.'/../CITestCase.php');

class ControllersTest extends CITestCase
{
	/**
	 * This test will create a controller stub file, and check if requireController loads correctly.
	 * It skips the test if the environment doesn't grant enough permissions
	 * to create folder and file.
	 */
	public function testLoadController()
	{
		$folder = APPPATH.'controllers/';
		
		$this->assertTrue(is_dir($folder), 'Controllers folder found');
		
		// check if we can run the test
		if (!is_writable($folder))
			$this->markTestSkipped('Cannot write in controllers folder');
		
		// create a test controller
		if (!is_writable($folder.'/Stub.php')) {
			// create stub file
			$success = file_put_contents($folder.'/Stub.php',
									'<?php class Stub extends CI_Controller { public function index(){} } ?>');
			if (!$success)
				$this->markTestSkipped('Cannot create test controller file');
		}
		
		$this->requireController('Stub');
		$this->assertTrue(class_exists('Stub'), 'Class is loadable');
		$this->CI = new Stub();
		$this->assertInstanceOf('CI_Controller', $this->CI, 'Controller was called correctly');
		$this->CI->index();
		
		// remove stub
		unlink($folder.'/Stub.php');
	}
	
	/**
	 * This test will create a controller subfolder with a stub file.
	 * It skips the test if the environment doesn't grant enough permissions
	 * to create folder and file.
	 */
	public function testLoadControllerFromSubfolder()
	{
		$folder = APPPATH.'controllers/testsubfolder';
		
		// check if we can run the test
		if (!is_dir($folder)) {
			// create subfolder
			$success = mkdir(APPPATH.'controllers/testsubfolder');
			if (!$success)
				$this->markTestSkipped('Cannot create subfolder');
		}
		if (!is_writable($folder))
			$this->markTestSkipped('Cannot write in subfolder');
		
		// create a test controller
		if (!is_writable($folder.'/Stub.php')) {
			// create stub file
			$success = file_put_contents($folder.'/Stub.php',
									'<?php class Stub extends CI_Controller { public function index(){} } ?>');
			if (!$success)
				$this->markTestSkipped('Cannot create test controller file');
		}
		
		$this->requireController('Stub');
		$this->assertTrue(class_exists('Stub'), 'Class is loadable');
		$this->CI = new Stub();
		$this->assertInstanceOf('CI_Controller', $this->CI, 'Controller was called correctly');
		$this->CI->index();
		
		// remove stub
		unlink($folder.'/Stub.php');
		rmdir($folder);
	}
	
	/**
	 * This test will check if our bootstrap autoload won't make an
	 * inexistent class suddenly be loadable
	 */
	public function testLoadInexistentController()
	{
		$this->requireController('InexistentStub');
		$this->assertFalse(class_exists('InexistentStub'), 'Inexistent class is not loadable');
	}
	
	/**
	 * This test will create a model stub file, and check if a controller can load it correctly.
	 * It skips the test if the environment doesn't grant enough permissions
	 * to create folder and file.
	 */
	public function testLoadModel()
	{
		$folder = APPPATH.'models/';
		
		$this->assertTrue(is_dir($folder), 'Models folder found');
		
		// check if we can run the test
		if (!is_writable($folder))
			$this->markTestSkipped('Cannot write in models folder');
		
		// create a test controller
		if (!is_writable($folder.'/Stubmodel.php')) {
			// create stub file
			$success = file_put_contents($folder.'/Stubmodel.php',
									'<?php class Stubmodel extends CI_Model { public function check(){ return true; } } ?>');
			if (!$success)
				$this->markTestSkipped('Cannot create test model file');
		}
		
		$this->CI->load->model('stubmodel');
		$this->assertInstanceOf('CI_Model', $this->CI->stubmodel, 'Model was loaded correctly');
		$this->assertTrue($this->CI->stubmodel->check(), 'Stub check method returns true');
		
		// remove stub
		unlink($folder.'/Stubmodel.php');
	}
}

?>