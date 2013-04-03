<?php

class EmailHelperTest extends PHPUnit_Framework_TestCase
{
	private $CI;
	
	public static function setUpBeforeClass()
	{
		$CI =& get_instance();
		$CI->load->helper('email');
	}
	
	public function testEmailValidation()
	{
		$this->assertTrue(valid_email('test@test.com'));
		$this->assertFalse(valid_email('test#test.com'));
	}
}

?>