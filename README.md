[![Build Status](https://travis-ci.org/fmalk/codeigniter-phpunit.svg?branch=master)](https://travis-ci.org/fmalk/codeigniter-phpunit)

codeigniter-phpunit
===================

This project is a simple hack to make CodeIgniter **3.x** work seamlessly with PHPUnit. It aims to provide a way to use PHPUnit's standard methodologies for automating tests with CodeIgniter framework, which is notoriously test-unfriendly.

If you are looking for CodeIgniter **2.x** support, see branch/tag [2.x](https://github.com/fmalk/codeigniter-phpunit/tree/2.x).

Start Testing
-------------

The files provided here can just be overwritten on top of an existing, vanilla CI application. PHPUnit's `phpunit.xml` will sit besides your application's `index.php`, and hacked CI's *system/core* files should overwrite the vanilla CI ones.

After that, just put your own tests in *application/tests* folder. Utility classes `CITestCase.php` and `CIDatabaseTestCase.php` are provided to be used instead of `PHPUnit_Framework_TestCase`, but you can use PHPUnit's standard classes as well.

As an example, a unit test for CI's Email helper would be as follows:

```php
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
```

Just use PHPUnit's command line tool at your application root directory:

```bash
$> phpunit
```

How it Works
------------

### PHPUnit Config XML ###

The idea is to use CodeIgniter's own bootstrap file to bootstrap PHPUnit tests, as PHPUnit XML config file allows. So, we'll let CI start its framework as usual, complete with configuration and auto-loading as your application wants. We do this by using a base `phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<phpunit bootstrap="application/tests/bootstrap.php">
    <testsuites>
		<testsuite name="TestSuite">
			<directory>application/tests</directory>
		</testsuite>
	</testsuites>
	<php>
		<const name="PHPUNIT_TEST" value="1" />
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

1. Telling to use a custom bootstrap file
2. Telling PHPUnit to look for tests under *application/tests*
3. Creating a constant for a PHPUnit runtime test environment, `PHPUNIT_TEST`. This will be used to hack into CI bootstrap behaviour.
4. Providing a `$_SERVER['REMOTE_ADDR']` default value so CI's security checks won't break.
5. For code coverage analysis, we're filtering out CI *system* directory, and optionally your *application/libraries* directory (if you uncomment that line).

### Hacking CI system files ###

CodeIgniter 3.x needs fewer hacks than its predecessor. There's only a few checks to be made, and our `PHPUNIT_TEST` config variable is there to help with that.

In fact, if you use Hook: Display Override (`$hook['display_override']` in `application/config/config.php`), there's no need to hack `system/core/CodeIgniter.php`; and if you don't use PHPUnit's command line arguments, neither `system/core/URI.php`.

CI will start by autoloading our custom bootstrap file, reading config files, and most importantly, **load your default Controller**, since it has no routing information (no URI or CLI parameters). This is actually useful, since this is what makes `$CI =& get_instance()` possible in our tests.

#### Hacks: ####

>

> `CodeIgniter.php`
>>
>> *Line 531 changed to:*
>>
>>```php
>> if ($EXT->call_hook('display_override') === FALSE && !defined('PHPUNIT_TEST'))
>>```
>>
>> Prevent CI from outputting things before our tests.

> `URI.php`
>> *Lines 109 to 114 changed to:*
>>
>>```php
>> // If it's a PHPUnit test, ignore all command line arguments
>> if (defined('PHPUNIT_TEST')) {
>>     $uri = '';
>> }
>> // If it's a CLI request, ignore the configuration
>> else if (is_cli())
>>```
>>
>> This extra check for PHPUnit CLI environment makes sure CI ignores `phpunit` command line arguments not intended to be parsed by CI.

Testing your Controllers
------------

Testing Controllers are not straightfoward as Models, Libraries or Helpers in CodeIgniter, because CI
has tightly coupled dependencies with classes like Session, Cookies, Caches and Input. It is beyond scope
of this manual to teach how to (re)write CI applications with dependency injection in mind, but if you
have testable controllers, you can load them in tests by changing the `$CI` variable:

```php
require_once('CITestCase.php');

class MyTestController extends CITestCase
{
public function testHome()
{
	$this->requireController('Home'); // assuming you have a applications/controllers/Home.php
	$CI = new Home();
	$CI->index();
}
```

### About Redirects and URL Parameters ###

When run from the CLI with PHPUnit, your CI application won't have any information about URL parameters.
This is means your controller's methods won't have their arguments filled magically by parsing your URL, so
you have to pass them as if they're a common method:

```php
require_once('CITestCase.php');

class MyTestController extends CITestCase
{
public function testHomeShowUser()
{
	$this->requireController('Home'); // assuming you have a applications/controllers/Home.php
	$CI = new Home();
	$CI->showUser(2);
}
```

That also implies you shouldn't rely on any URL-based utility method when testing from PHPUnit: `base_url()`,
`redirect()`, `uri_string()`, and other functions from URL Helper, since they could either break your test or
behave unpredictably.
If you need to work around that, make use of helpers like `is_cli()`.

### Bootstrap will call your default route once ###

CI needs to bootstrap itself to be able to run, and since PHP is usually run from a HTTP request, this means
CI loads itself up and expects a route to follow, dictated by whatever URL was requested. PHPUnit won't supply
that information, **so CI will call your default route once**, before any test is run.
You should take special care of your default route then, because if it isn't testable (doing a redirect,
making difficult database queries, etc), it could mean your tests will break before starting.
Ideally, your default route should be light and fast.

Tips for Testing
================

### Using provided CITestCase class ###

The `CITestCase` file extends PHPUnit's `PHPUnit_Framework_TestCase` and provides a few common use cases for CodeIgniter tests, most importantly, loading controllers files (since there isn't a URL Router to load them for you).

The example given before would be changed to:

```php
require_once('CITestCase.php');

class MyTest extends CITestCase
{    
	public function setUp()
	{
		$this->CI->load->helper('email');
	}
	
	public function testEmailValidation()
	{
		$this->assertTrue(valid_email('test@test.com'));
		$this->assertFalse(valid_email('test#testcom'));
	}
}
```

It provides a property `$this->CI` with the default controller loaded. You can use it just as you'd use `$this` inside a controller.

### Using provided CIDatabaseTestCase class ###

The `CIDatabaseTestCase` file extends PHPUnit's `PHPUnit_Extensions_Database_TestCase` and provides database assertions.

The example given before would be changed to:

```php
require_once('CIDatabaseTestCase.php');

class MyDatabaseTest extends CIDatabaseTestCase
{    
	public function setUp()
	{
        $this->CI->load->model('contactmodel');
	}
    
    public function testContactsQty()
    {
        $qty = $this->CI->contactmodel->getContactsQty();
        $this->assertEquals($qty, $this->db->getRowCount('contacts'));
    }
}
```

It provides a property `$this->CI` with the default controller loaded, and another `$this->db` as a wrapper to a `PHPUnit_Extensions_Database_DB_IDatabaseConnection`.

**Considerations about this database connection:** it uses your application's database config file to initiate a PDO "fixture" **from your real database**. In other words, it is not a fixture, but a quick way for you to make assertions in your real database. As it is, you must define a `setUp()` call in your methods, or it will use PHPUnit's default database logic to truncate it after every test.

Make sure you understand [PHPUnit's Database Manual](http://www.phpunit.de/manual/3.7/en/database.html) completely before you use this database connection, and change it to your needs.

### Set $db['default']['db_debug'] to FALSE ###

If you set `$db['default']['db_debug'] = TRUE`, every error your test encounters will output database information and end the script. It is better to throw Exceptions and let your test handle it.

### Avoid die() and exit() ###

If you use them, they'll interrupt testing, as they end PHP execution. That's why `show_error()` and `show_404()` were changed to throw `Exception`, which are much easier to test.

### Change your environment to 'testing' ###

In CodeIgniter `index.php`, you can change the application environment from 'development' to 'production' or 'testing', which primarily avoid `error_reporting()` outputs from PHP. If you let `error_reporting()` output, you won't be able to use PHPUnit strict mode, and you'll have a hard time testing your own outputs.

### Test `show_error()` and `show_404()` using Exceptions ###

You can benefit from the hacks made at `bootstrap.php` by making this kind of tests:

```php
/**
 * @expectedException           PHPUnit_Framework_Exception
 * @expectedExceptionCode       403
 * @expectedExceptionMessage    forbidden
 */
public function testCreateNullName()
{
    // this should call show_error('forbidden', 403)
	$this->CI->resourcemodel->deleteResource(1);
}
```

Changelog
------------

3.0.2 (2015-10-15):
* Support for DSN-based database hosts

3.0.1 (2015-06-23):
* Improved README about Controllers (see #32)

3.0.0 (2015-05-15):
* CI 3.0.0 is official, so CI3 branch is now master
* Core folder renamed to mirror vanilla CI
* Bootstrap tests with fewer hacks
* Improved utility classes
* New tests
* Updated `phpunit.xml`
* Updated README

3.0 (2015-02-09):
* Initial support for CI 3.x.
* Merged from #22. (Thanks @feryardiant)
* README changed to reflect 3.x hacks.

2.0 (2015-02-09):
* Old CI 2.x support will now be called "2.0" version tag.
* README improvements.

1.4.2 (2014-07-25):
* README fixes

1.4.1 (2014-07-09):
* Small fix in autoload filepath

1.4 (2014-07-09):
* Added a hack to `tests\bootstrap.php` to autoload controllers inside subfolders (see #8)

1.3 (2014-06-16):
* Added a hack to `URI.php` to ignore `phpunit` command line arguments. (Thanks @ryan5500)

1.2.1 (2014-06-16):
* Tested with CI 2.2.0
* Added Travis CI build at https://travis-ci.org/fmalk/codeigniter-phpunit
* Updated README

1.2 (2014-01-24):
* Fixed a critical bug. Previous hack in `Utf8.php` is fundamental.
* Reverting changes in `phpunit.xml`.
* Reverting changes in README.

1.1 (2014-01-21):
* Reduced number of *system/core* file hacks needed (only 1 now)
* New bootstrap file (hacks into `system/core/Common.php`)
* Improved `phpunit.xml` (creates global `$CFG` so `system/core/Utf8.php` doesn't need hacking)
* Ensured compatibility with CI 2.1.4, probably compatible with all 2.1.x versions
* Updated README

1.0 (2013-04-03):
* Initial version
* Compatible with CI 2.1.3

