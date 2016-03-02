<?php namespace Firebird;

use PDO;
use Firebird\Schema\Grammars\FirebirdGrammar as SchemaGrammar;

class Connection extends \Illuminate\Database\Connection {

  /**
   * The Firebird database handler.
   *
   * @var Firebird
   */
  protected $db;

  /**
   * The Firebird connection handler.
   *
   * @var PDO
   */
  protected $connection;

  /**
   * Create a new database connection instance.
   *
   * @param  array $config
   */
  public function __construct(PDO $pdo, $database = '', $tablePrefix = '', array $config = array())
  {
    $this->pdo = $pdo;

    $this->config = $config;

    // First we will setup the default properties. We keep track of the DB
    // name we are connected to since it is needed when some reflective
    // type commands are run such as checking whether a table exists.
    $this->database = $database;

    $this->tablePrefix = $tablePrefix;

    $this->config = $config;

    // The connection string
    $dsn = $this->getDsn($config);

    // Create the connection
    $this->connection = $this->createConnection($dsn, $config);

    // Set the database
    $this->db = $this->connection;

    // We need to initialize a query grammar and the query post processors
    // which are both very important parts of the database abstractions
    // so we initialize these to their default values while starting.
    $this->useDefaultQueryGrammar();

    $this->useDefaultPostProcessor();
  }
  /**
   * Return the DSN string from configuration
   *
   * @param  array   $config
   * @return string
   */
  protected function getDsn(array $config)
  {
    // Check that the host and database are not empty
    if( ! empty($config['host']) && ! empty ($config['database']) )
    {
      return 'firebird:dbname='.$config['host'].':'.$config['database'].';charset='.$config['charset'];
    }
    else
    {
      trigger_error( 'Cannot connect to Firebird Database, no host or path supplied' );
    }
  }

  /**
   * Create the Firebird Connection
   *
   * @param  string  $dsn
   * @param  array   $config
   * @return PDO
   */
  public function createConnection($dsn, array $config)
  {
    //Check the username and password
    if (!empty($config['username']) && !empty($config['password']))
    {
      try {
        return new PDO($dsn, $config['username'], $config['password']);
      } catch (PDOException $e) {
        trigger_error($e->getMessage());
      }
    }
    else
    {
      trigger_error('Cannot connect to Firebird Database, no username or password supplied');
    }
    return null;
  }

  /**
   * Get the default query grammar instance
   *
   * @return Query\Grammars\FirebirdGrammar
   */
  protected function getDefaultQueryGrammar()
  {
      return new Query\Grammars\FirebirdGrammar;
  }

  /**
   * Get the default post processor instance.
   *
   * @return Query\Processors\FirebirdProcessor
   */
  protected function getDefaultPostProcessor()
  {
    return new Query\Processors\FirebirdProcessor;
  }

  /**
   * Get a schema builder instance for this connection.
   * @return Schema\Builder
   */
  public function getSchemaBuilder()
  {
    if (is_null($this->schemaGrammar)) { $this->useDefaultSchemaGrammar(); }

    return new Schema\Builder($this);
  }

  /**
   * Get the default schema grammar instance.
   *
   * @return SchemaGrammar;
   */
  protected function getDefaultSchemaGrammar() {
    return $this->withTablePrefix(new SchemaGrammar);
  }

  /**
   * Begin a fluent query against a database table.
   *
   * @param  string  $table
   * @return Firebird\Query\Builder
   */
  public function table($table)
  {
    $processor = $this->getPostProcessor();

    $query = new Query\Builder($this, $this->getQueryGrammar(), $processor);

    return $query->from($table);
  }

  public function beginTransaction()
  {
    $this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
    parent::beginTransaction();
  }

  public function commit()
  {
    parent::commit();
    $this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
  }

  public function rollBack()
  {
    parent::rollBack();
    $this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
  }
}
