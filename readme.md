# Cumulus

A ~~set~~ pair of utilities for modern development.


## UrlConfig

- replace multiple ENV variables for single URL DSN strings
- ideal for applications using multiple cloud services

Class `UrlConfig` is useful for connection configurations that use URL DSNs
when an app needs separate config fields or PDO DSN, or when you simply want to squash 6 ENV variables into one.

Examples:
- JawsDB MySQL or JawsDB MariaDB on Heroku

What you'll get (pseudo):
```
 "mysql://john:secret@localhost:3306/my_db" => [
 		username => john
 		password => secret
 		database => my_db
 		port => 3306
 		host => localhost
 		driver => mysql
 		pdo => "mysql:host=localhost;dbname=my_db"
 ]
```

Integrates well with [DiBi]( https://github.com/dg/dibi ) or Laravel configurations.

In Laravel, instead of
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=homestead
DB_USERNAME=homestead
DB_PASSWORD=secret
```
one's `.env` file (or server configuration) can simply contain
```
DB_DSN=mysql://homestead:secret@127.0.0.1:3306/homestead
```

Then, your `database.php` can contain a section like this:
```php
$dbc = new Dakujem\Cumulus\UrlConfig(env('DB_DSN'));
return [
	'connections' => [
		'mysql' => [
			'driver' => $dbc->get('driver'),
			'host' => $dbc->get('host', '127.0.0.1'),
			'port' => $dbc->get('port', '3306'),
			'database' => $dbc->get('database', 'forge'),
			'username' => $dbc->get('username', 'forge'),
			'password' => $dbc->get('password', ''),
			// ...
		],
	]
];
```


## LazyIterator

- when an iterable collection must be passed but it has not yet been fetched
- when mapping of the elements of the set is needed, but the set is lazy-loaded itself (may save memory)
- useful for wrapping api calls (in certain cases)

Good, for example, when you need to pass a result of an API call
to a component iterating over the returned collection only on certain conditions
that are not directly managed at the moment of passing of the result.
In traditional way the call could be wasted.

With `LazyIterator` you can wrap the call to a callable and create LazyIterator
that is then passed to the component for rendering.
You can be sure the API only gets called when the result is actually needed.

Furthermore, you can apply a number of mapping functions in a manner similar to `array_map` function.



## Tests

Run unit tests using the following command:

`$` `composer test`

