<?php

namespace Firebird;

use Firebird\Connection as FirebirdConnection;
use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;

class FirebirdServiceProvider extends ServiceProvider
{

    /**
     * Register the application services.
     * This is where the connection gets registered
     *
     * @return void
     */
    public function register()
    {
        Connection::resolverFor('firebird', function ($connection, $database, $tablePrefix, $config) {
            return new FirebirdConnection($connection, $database, $tablePrefix, $config);
        });
        $this->app->bind('db.connector.firebird', FirebirdConnector::class);
    }

}
