Blackfire PHP SDK 
=================

Only use the [Blackfire](https://blackfire.io/) PHP SDK when:

 * You already have XHProf installed and cannot install the Blackfire PHP
   extension (think PHP 5.2);

 * You want a fallback in case the Blackfire PHP extension is not installed on
   some machines (manual instrumentation will be converted to noops).

Read more about using this PHP SDK in this [Blackfire blog
post](http://blog.blackfire.io/blackfire-for-xhprof-users.html).

**WARNING**: This code should only be used if XHProf is already deployed on
your infrastructure and only if installing the Blackfire PHP extension is not
possible.
