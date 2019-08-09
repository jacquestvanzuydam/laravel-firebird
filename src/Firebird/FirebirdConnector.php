<?php

namespace Firebird;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use Illuminate\Database\Connectors\Connector;
use Illuminate\Database\Connectors\ConnectorInterface;
use PDO;

class FirebirdConnector extends Connector implements ConnectorInterface
{

    /**
     * Establish a database connection.
     *
     * @param array $config
     * @return PDO
     */
    public function connect(array $config)
    {
        $dsn = $this->getDsn($config);

        $options = $this->getOptions($config);

        // We need to grab the PDO options that should be used while making the brand
        // new connection instance. The PDO options control various aspects of the
        // connection's behavior, and some might be specified by the developers.
        $connection = $this->createConnection($dsn, $config, $options);

        return $connection;
    }

    /**
     * Create a DSN string from a configuration.
     *
     * @param array $config
     * @return string
     */
    protected function getDsn(array $config)
    {
        $dsn = '';
        if (isset($config['host'])) {
            $dsn .= $config['host'];
        }
        if (isset($config['port'])) {
            $dsn .= "/" . $config['port'];
        }
        if (!isset($config['database'])) {
            throw new InvalidArgumentException("Database not given, required.");
        }
        if ($dsn) {
            $dsn .= ':';
        }
        $dsn .= $config['database'] . ';';
        if (isset($config['charset'])) {
            $dsn .= "charset=" . $config['charset'];
        }
        if (isset($config['role'])) {
            $dsn .= ";role=" . $config['role'];
        }
        $dsn = 'firebird:dbname=' . $dsn;

        return $dsn;
    }

}
