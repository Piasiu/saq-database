# Saq-Database

Extension to the [Saq](https://github.com/Piasiu/saq) framework for database support.

The extension offers an [adapter interface](src/AdapterInterface.php) allowing you to use your favorite database.
For your convenience, the extension includes [PdoAdapter](src/Adapter/PDOAdapter.php) and two popular database adapters:
- [My SQL](src/Adapter/MySqlAdapter.php),
- [SQLite](src/Adapter/SQLiteAdapter.php). 

The extension also offers a SQL query builder.
  
# Installation

It's recommended that you use [Composer](https://getcomposer.org/) to install Saq-Database.
```bash
$ composer require piasiu/saq-db
```
This will install Saq-Database and all required dependencies. Saq-Database requires PHP 8 or newer.

## License

The Saq-Database is licensed under the MIT license. See [License File](LICENSE.md) for more information.
