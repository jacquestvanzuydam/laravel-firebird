<?php namespace Firebird;

use Illuminate\Database\DatabaseManager as BaseDatabaseManager;
use Firebird\ConnectionFactory as FirebirdConnectionFactory;

class DatabaseManager extends BaseDatabaseManager
{

    /**
     * Create a new database manager instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @param  \Illuminate\Database\Connectors\ConnectionFactory  $factory
     * @return void
     */
    public function __construct($app, FirebirdConnectionFactory $factory)
    {
        $this->app = $app;
        $this->factory = $factory;
    }

}
