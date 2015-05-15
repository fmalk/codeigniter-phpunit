<?php

class CliTest extends PHPUnit_Framework_TestCase
{
	/**
	 * This test will check if CLI is detected from CI
	 */
	public function testCliCheck()
	{
		$this->assertTrue(function_exists('is_cli'), 'is_cli() defined');
		$this->assertTrue(is_cli(), 'CLI detected');
	}
	
	/**
     * This test will check if CI show_error() is throwing Exceptions instead of outputting errors
	 * 
	 * @expectedException PHPUnit_Framework_Exception
     */
	public function testShowError()
	{
		show_error('Error');
	}
	
	/**
     * This test will check if CI show_404() is throwing Exceptions instead of outputting errors
	 * 
	 * @expectedException PHPUnit_Framework_Exception
     */
	public function testShow404()
	{
		show_404('InvalidPage');
	}
}

?>