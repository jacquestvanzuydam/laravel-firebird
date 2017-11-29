<?php namespace Firebird;

use Illuminate\Support\ServiceProvider;
use Firebird\Connection as FirebirdConnection;
use Firebird\FirebirdConnector;

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
        $this->app->bind('db.connection.firebird', FirebirdConnection::class);
        $this->app->bind('db.connector.firebird', FirebirdConnector::class);
    }

}
