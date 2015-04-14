<?php namespace Firebird;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use Illuminate\Database\Connectors\Connector;
use Illuminate\Database\Connectors\ConnectorInterface;

class FirebirdConnector extends Connector implements ConnectorInterface {

  /**
   * Establish a database connection.
   *
   * @param  array  $config
   * @return \PDO
   *
   * @throws \InvalidArgumentException
   */
  public function connect(array $config)
  {
    $options = $this->getOptions($config);

    $path = realpath($config['database']);

    // Here we'll verify that the Firebird database exists before going any further
    // as the developer probably wants to know if the database exists and this
    // Firebird driver will not throw any exception if it does not by default.
    if ($path === false)
    {
      throw new InvalidArgumentException("Database does not exist.");
    }

    $host = $config['host'];
    if ( empty($host))
    {
      throw new InvalidArgumentException("Host not given, required.");
    }

    return $this->createConnection("firebird:dbname={$host}:{$path}", $config, $options);
  }

}