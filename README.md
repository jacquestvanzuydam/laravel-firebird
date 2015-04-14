# laravel-firebird

To use this package:

Install the Firebird PDO driver for PHP.

Mariuz's Blog has a very good step by step on this:
http://mapopa.blogspot.com/2009/04/php5-and-firebird-pdo-on-ubuntu-hardy.html

Update the app config file with FirebirdServiceProvider.

You can remove the original DatabaseServiceProvider, as the original connection factory has also been extended.

Declare your connection in the database config, using 'firebird' as the
connecion type.
Other keys that are needed:
<code>
'driver' => 'firebird',
'host' => env('DB_HOST', 'localhost'),
'database' => env('DB_DATABASE','/storage/firebird/APPLICATION.FDB'),
'username' => env('DB_USERNAME', 'sysdba'),
'sysdbapassword' => env('DB_PASSWORD', 'masterkey'),
</code> 

This package is still in it's infancy and I wouldn't yet recommend using
it for large projects, or without backing up your database regurlary.

Any comments or contributions are welcome.
