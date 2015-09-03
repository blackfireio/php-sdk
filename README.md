Blackfire PHP SDK
=================

Install the Blackfire PHP SDK via Composer:

    $ composer require blackfire/php-sdk

Blackfire Client
----------------

Your code is instrumented automatically when using the Blackfire CLI or the
companion. Using the PHP SDK allows you to finely instrument your code:

```php
$bf = new \Blackfire\Client();
```

It's then up to you to instrument some code:

```php
$request = $bf->enable();

// call some interesting PHP code

$profile = $bf->close($request);
```

Have a look at the `Profile` class to learn more about which features it gives
you access to.

The `enable()` and `profile()` methods also takes an optional Configuration
object that allows you to configure Blackfire:

```php
$config = new \Blackfire\Configuration();
$config->defineMetric('sami.storage.save_calls', array('=JsonStore::writeClass'));
$config->assert('metrics.sami.storage.save_calls.count == 0', 'No storage writes...');
$config->assert('metrics.output.network_out > 40k', 'Output is big...');
$config->assert('metrics.sql.queries.count > 50', 'I want many SQL requests...');
```

By default, profiles are sent to your personal profiles, but you can change the
application via a call to `setApp()`:

```php
$config->setApp('symfony');
```

PhpUnit Integration
-------------------

The Client eases using Blackfire in unit tests:

```php
$bf = new \Blackfire\Client();
$profile = $bf->assertPhpUnit($this, $config, function () use ($sami) {
    // code that needs to be profiled
});
```

where `$this` is an instance of `PHPUnit_Framework_TestCase`.

Proxy
-----

If you want to inspect the traffic between profiled servers and blackfire's
servers, you can use a small proxy script provided in this repository. Please
read the instructions in `./bin/blackfire-io-proxy.php` to do so.

PHP Probe
---------

This repository provides a [Blackfire](https://blackfire.io/) PHP Probe
implementation that should only be used under the following circumstancies:

 * You already have XHProf installed and cannot install the Blackfire PHP
   extension (think PHP 5.2 or HHVM);

 * You want a fallback in case the Blackfire PHP extension is not installed on
   some machines (manual instrumentation will be converted to noops).

Read more about using this PHP Probe in this [Blackfire blog
post](http://blog.blackfire.io/blackfire-for-xhprof-users.html).

**WARNING**: This code should only be used when installing the Blackfire PHP
extension is not possible.
