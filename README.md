codeigniter-phpunit
===================

This project is a simple hack to make CodeIgniter 2.1.3 work seamlessly with PHPUnit 3.7. It aims to provide a way to use PHPUnit standard methodologies for automating tests with CodeIgniter framework, which is notoriously test-unfriendly.

Start Testing
-------------

The files provided here can just be overwritten on top of an existing, vanilla CI application. PHPUnit's `phpunit.xml` will sit besides your application's `index.php`, and the hacked CI's *system/core* files should overwrite the vanilla CI ones.

After that, just put your own tests in *application/tests* folder. A `CITest.php` file is provided as an util class, to be used instead of *PHPUnit_Framework_TestCase*, but you can go ahead and use PHPUnit standard classes as you like.

As an example, a unit test for CI's Email helper would be as follows:

```
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
		$this->assertFalse(valid_email('test#testcom'));
	}
}

?>
```

Just use PHPUnit's command line tool at your application root directory:

`$> phpunit`

How it Works
------------

### PHPUnit Config XML ###

The idea is to use CodeIgniter's own bootstrap file to bootstrap PHPUnit tests, as PHPUnit XML config file allows. So, we'll let CI start its framework as usual, complete with configuration and auto-loading as your application wants. We do this by using a base `phpunit.xml`:

```
<?xml version="1.0" encoding="UTF-8" ?>
<phpunit bootstrap="index.php">
    <testsuites>
		<testsuite name="TestSuite">
			<directory>application/tests</directory>
		</testsuite>
	</testsuites>
	<php>
		<const name="PHPUNIT_TEST" value="1" />
        <const name="PHPUNIT_CHARSET" value="UTF-8" />
		<server name="REMOTE_ADDR" value="0.0.0.0" />
	</php>
	<filter>
		<blacklist>
			<directory suffix=".php">system</directory>
			<!--directory suffix=".php">application/libraries</directory-->
		</blacklist>
	</filter>
</phpunit>
```

What this config file is doing:

1. Telling to use CI's own bootstrap file
2. Telling PHPUnit to look for tests under application/tests
3. Creating a constant for a PHPUnit runtime test environment, `PHPUNIT_TEST`. This will be used to hack into CI bootstrap behaviour.
4. Creating a constant `PHPUNIT_CHARSET` to be used instead of your `$config['charset']`.
4. Providing a `$_SERVER['REMOTE_ADDR']` default value so CI 2.1.3 security checks will still work.
5. For code coverage analysis, we're filtering out CI *system* directory, and optionally your *application/libraries* directory (if you uncomment that line).

### Hacking CI system files ###

CI relies a lot on superglobal variables, which are not easily available in a PHPUnit runtime. However, simple checks using the new `PHPUNIT_TEST` constant adapts CodeIgniter behaviour.

CI will start by doing autoloading, reading config files, and most importantly, **load your default Controller**, since it has no routing information (no URI or CLI parameters). This is actually useful, since this is what makes `$CI =& get_instance()` possible in our tests.

#### The hacks: ####

>

> `Utf8.php`
>> *Line 47 changed to:*
>>
>>```
>> AND (
>>    	(is_object($CFG) AND $CFG->item('charset') == 'UTF-8')
>>	    OR (defined('PHPUNIT_TEST') AND PHPUNIT_CHARSET == 'UTF-8')
>>		)
>> )
>>```
>>
>> Superglobal `$CFG` is not available here in PHPUnit, so this check prevents a fatal error, and using `PHPUNIT_CHARSET` prevents CI to disable UTF-8 support, if you're using it.

> `CodeIgniter.php`
>> *Line 325 changed to:*
>>
>>```
>> if (!defined('PHPUNIT_TEST')) { ... }
>>```
>>
>> This is the line which starts calling your controller's intended method from URI parameters, but in test we want to call the method ourselves. So this check skips the method call.
>>
>> *Line 386 changed to:*
>>
>>```
>> if ($EXT->_call_hook('display_override') === FALSE && !defined('PHPUNIT_TEST'))
>>```
>>
>> Same logic here, we have to prevent CI from outputting things before our tests.

> `Common.php`
>> *Lines 308 and 332 changed to:*
>>
>>```
>> if (defined('PHPUNIT_TEST'))
>> throw new PHPUnit_Framework_Exception($message, $status_code);
>>```
>>
>> These hacks are technically optional, but are very useful. These changes are made to `show_error()` and `show_404()` functions, because their use is encouraged when creating controllers, but their behaviours are terrible for testing purposes: they send output directly and *terminate* the PHP script right there. It's much more useful when testing to throw exceptions.

Tips for Testing
================

### Using provided CITestCase class ###

The `CITestCase` file extends PHPUnit's `PHPUnit_Extensions_Database_TestCase` and provides a few common use cases for CodeIgniter tests, most importantly, database assertions.

The example `EmailHelperTest` provided before would be changed to:

```
<?php

class EmailHelperTest extends CITestCase
{    
	public function setUp()
	{
		$this->CI->load->helper('email');
        $this->CI->load->model('contactmodel');
	}
	
	public function testEmailValidation()
	{
		$this->assertTrue(valid_email('test@test.com'));
		$this->assertFalse(valid_email('test#testcom'));
	}
    
    public function testContactsQty()
    {
        $qty = $this->CI->contactmodel->getContactsQty();
        $this->assertEquals($qty, $this->db->getRowCount('contacts'));
    }
}

?>
```

It provides a property `$this->CI` with the default controller loaded, and another `$this->db` as a wrapper to a `PHPUnit_Extensions_Database_DB_IDatabaseConnection`. If your test does not use database connections, it will not be loaded.

**Carefully considerations about this database connection:** it uses you application's database config file to initiate a PDO "fixture" **from you real database**. In other words, it is not a fixture, but a quick way for you to make assertions in your real database. As it is, you must define a `setUp()` call in your methods, or it will use PHPUnit's default database logic to truncate it after every test.

Make sure you understand [PHPUnit's Database Manual](http://www.phpunit.de/manual/3.7/en/database.html) completely before you use this database connection, and change it to your needs.

### Set $db['default']['db_debug'] to FALSE ###

If you set `$db['default']['db_debug'] = TRUE`, every error your test encounters will output database information and end the script. It is better to throw Exceptions and let you test handle it.

### Avoid die() and exit() ###

If you use them, it will interrupt testing, as it ends PHP execution. That's why `show_error()` and `show_404` were changed to throw Exceptions, which are much easier to test.

### Change your environment to 'testing' ###

In CodeIgniter `index.php`, you can change the application environment from 'development' to 'production' or 'testing', which primarily avoid `error_reporting()` outputs from PHP. If you let `error_reporting()` outputs, you won't be able to use PHPUnit strict mode, and you'll have a hard time testing your own outputs.

### Test show_error() and show_404() using Exceptions ###

You can benefit from the hacks made at CI's core `Common.php` file by making this kind of tests:

```
/**
 * @expectedException           PHPUnit_Framework_Exception
 * @expectedExceptionCode       403
 * @expectedExceptionMessage    forbidden
 */
public function testCreateNullName()
{
    // this should call show_error('forbidden', 402)
	$this->CI->resourcemodel->deleteResource(1);
}
```

### Testing your Controllers ###

Testing Controllers are not straightfoward as Models, Libraries or Helpers in CodeIgniter, because CI
has tightly coupled dependencies with classes like Session, Cookies, Caches or Input. It is beyond scope
of this manual to teach how to (re)write CI applications with dependency injection in mind, but if you
have testable controllers, you can load them in tests by changing the `$CI` variable:

```
public function testHomeController()
{
	$CI =& get_instance();
	$CI = new Home();
	$CI->index();
}
```