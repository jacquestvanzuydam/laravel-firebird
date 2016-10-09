<?php namespace Firebird;

use PDO;
use Firebird\Query\Grammars\FirebirdGrammar as QueryGrammar;
use Firebird\Query\Grammars\FirebirdGrammar30 as QueryGrammar30;
use Firebird\Query\Builder as QueryBuilder;
use Firebird\Schema\Grammars\FirebirdGrammar as SchemaGrammar;
use Firebird\Schema\Builder as SchemaBuilder;
use Firebird\Query\Processors\FirebirdProcessor as Processor;

class Connection extends \Illuminate\Database\Connection
{

    /**
     * Firebird Engine version
     * 
     * @var string 
     */
    private $engine_version = null;

    /**
     * Get engine version
     * 
     * @return string
     */
    protected function getEngineVersion()
    {
        if (!$this->engine_version) {
            $this->engine_version = isset($this->config['engine_version']) ? $this->config['engine_version'] : null;
        }
        if (!$this->engine_version) {
            $sql = "SELECT RDB\$GET_CONTEXT(?, ?) FROM RDB\$DATABASE";
            $sth = $this->getPdo()->prepare($sql);
            $sth->execute(['SYSTEM', 'ENGINE_VERSION']);
            $this->engine_version = $sth->fetchColumn();
            $sth->closeCursor();
        }
        return $this->engine_version;
    }

    /**
     * Get major engine version
     * It allows you to determine the features of the engine.
     * 
     * @return int
     */
    protected function getMajorEngineVersion()
    {
        $version = $this->getEngineVersion();
        $parts = explode('.', $version);
        return (int) $parts[0];
    }

    /**
     * Get the default query grammar instance
     *
     * @return Query\Grammars\FirebirdGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        if ($this->getMajorEngineVersion() >= 3) {
            return new QueryGrammar30;
        }
        return new QueryGrammar;
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Firebird\Query\Processors\FirebirdProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new Processor;
    }

    /**
     * Get a schema builder instance for this connection.
     * @return \Firebird\Schema\Builder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new SchemaBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Firebird\Schema\Grammars\FirebirdGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SchemaGrammar);
    }

    /**
     * Get query builder
     * 
     * @return \Firebird\Query\Builder
     */
    protected function getQueryBuilder()
    {
        $processor = $this->getPostProcessor();
        $grammar = $this->getQueryGrammar();

        return new QueryBuilder($this, $grammar, $processor);
    }

    /**
     * Begin a fluent query against a database table.
     *
     * @param  string  $table
     * @return \Firebird\Query\Builder
     */
    public function table($table)
    {
        $query = $this->getQueryBuilder();

        return $query->from($table);
    }
    
    /**
     * Execute stored function
     * 
     * @param string $function
     * @param array $values
     * @return mixed
     */
    public function executeFunction($function, array $values = null) {
        $query = $this->getQueryBuilder();

        return $query->executeFunction($function, $values);       
    }
    
    /**
     * Execute stored procedure
     * 
     * @param string $procedure
     * @param array $values
     */
    public function executeProcedure($procedure, array $values = null) {
        $query = $this->getQueryBuilder();

        $query->executeProcedure($procedure, $values);        
    }

    /**
     * Start a new database transaction.
     *
     * @return void
     * @throws Exception
     */
    public function beginTransaction()
    {
        if ($this->transactions == 0 && $this->pdo->getAttribute(PDO::ATTR_AUTOCOMMIT) == 1) {
            $this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
        }
        parent::beginTransaction();
    }

    /**
     * Commit the active database transaction.
     *
     * @return void
     */
    public function commit()
    {
        parent::commit();
        if ($this->transactions == 0 && $this->pdo->getAttribute(PDO::ATTR_AUTOCOMMIT) == 0) {
            $this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
        }
    }

    /**
     * Rollback the active database transaction.
     *
     * @return void
     */
    public function rollBack()
    {
        parent::rollBack();
        if ($this->transactions == 0 && $this->pdo->getAttribute(PDO::ATTR_AUTOCOMMIT) == 0) {
            $this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
        }
    }

}
