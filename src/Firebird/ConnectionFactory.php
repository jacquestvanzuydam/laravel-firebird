<?php namespace Firebird;

use PDO;
use InvalidArgumentException;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Database\Connectors\SQLiteConnector;
use Illuminate\Database\Connectors\PostgresConnector;
use Illuminate\Database\Connectors\SqlServerConnector;

use Illuminate\Database\MySqlConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SqlServerConnection;


use Illuminate\Database\Connectors\ConnectionFactory as BaseConnectionFactory;

use Firebird\FirebirdConnector;
use Firebird\Connection as FirebirdConnection;

class ConnectionFactory extends BaseConnectionFactory {

  /**
   * Create a connector instance based on the configuration.
   *
   * @param  array  $config
   * @return \Illuminate\Database\Connectors\ConnectorInterface
   *
   * @throws \InvalidArgumentException
   */
  public function createConnector(array $config)
  {
    if ( ! isset($config['driver']))
    {
      throw new InvalidArgumentException("A driver must be specified.");
    }

    if ($this->container->bound($key = "db.connector.{$config['driver']}"))
    {
      return $this->container->make($key);
    }

    switch ($config['driver'])
    {
      case 'mysql':
        return new MySqlConnector;

      case 'pgsql':
        return new PostgresConnector;

      case 'sqlite':
        return new SQLiteConnector;

      case 'sqlsrv':
        return new SqlServerConnector;

      case 'firebird':
        return new FirebirdConnector;
    }

    throw new InvalidArgumentException("Unsupported driver [{$config['driver']}]".__FILE__);
  }

  /**
   * Create a new connection instance.
   *
   * @param  string   $driver
   * @param  \PDO     $connection
   * @param  string   $database
   * @param  string   $prefix
   * @param  array    $config
   * @return \Illuminate\Database\Connection
   *
   * @throws \InvalidArgumentException
   */
  protected function createConnection($driver, $connection, $database, $prefix = '', array $config = array())
  {
    if ($this->container->bound($key = "db.connection.{$driver}"))
    {
      return $this->container->make($key, array($connection, $database, $prefix, $config));
    }

    switch ($driver)
    {
      case 'mysql':
        return new MySqlConnection($connection, $database, $prefix, $config);

      case 'pgsql':
        return new PostgresConnection($connection, $database, $prefix, $config);

      case 'sqlite':
        return new SQLiteConnection($connection, $database, $prefix, $config);

      case 'sqlsrv':
        return new SqlServerConnection($connection, $database, $prefix, $config);

      case 'firebird':
        return new FirebirdConnection($connection, $database, $prefix, $config);
    }

    throw new InvalidArgumentException("Unsupported driver [$driver]");
  }
}
