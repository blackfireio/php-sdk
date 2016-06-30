Blackfire PHP SDK
=================

Install the Blackfire PHP SDK via Composer:

    $ composer require blackfire/php-sdk

Blackfire Client
----------------

See https://blackfire.io/docs/reference-guide/php-sdk

PhpUnit Integration
-------------------

See https://blackfire.io/docs/integrations/phpunit

Proxy
-----

If you want to inspect the traffic between profiled servers and blackfire's
servers, you can use a small proxy script provided in this repository. Please
read the instructions in `./bin/blackfire-io-proxy.php` to do so.

PHP Probe
---------

**WARNING**: This code should only be used when installing the Blackfire PHP
extension is not possible.

This repository provides a [Blackfire](https://blackfire.io/) PHP Probe
implementation that should only be used under the following circumstancies:

 * You already have XHProf installed and cannot install the Blackfire PHP
   extension (think PHP 5.2 or HHVM);

 * You want a fallback in case the Blackfire PHP extension is not installed on
   some machines (manual instrumentation will be converted to noops).

[Read more](https://blog.blackfire.io/blackfire-for-xhprof-users.html) about
how to use this feature on Blackfire's blog.
