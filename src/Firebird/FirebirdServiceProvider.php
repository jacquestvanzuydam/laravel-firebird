<?php namespace Firebird;

use Illuminate\Support\ServiceProvider;
use Firebird\Eloquent\Model as FirebirdModel;
use Firebird\ConnectionFactory as FirebirdConnectionFactory;
use Firebird\DatabaseManager as FirebirdDatabaseManager;

class FirebirdServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        FirebirdModel::setConnectionResolver($this->app['db']);

        FirebirdModel::setEventDispatcher($this->app['events']);
    }

    /**
     * Register the application services.
     * This is where the connection gets registered
     *
     * @return void
     */
    public function register()
    {
        $this->registerQueueableEntityResolver();


        // The connection factory is used to create the actual connection instances on
        // the database. We will inject the factory into the manager so that it may
        // make the connections while they are actually needed and not of before.
        $this->app->singleton('db.factory', function($app) {
            return new FirebirdConnectionFactory($app);
        });
        // The database manager is used to resolve various connections, since multiple
        // connections might be managed. It also implements the connection resolver
        // interface which may be used by other components requiring connections.
        $this->app->singleton('db', function($app) {
            return new FirebirdDatabaseManager($app, $app['db.factory']);
        });
    }

    /**
     * Register the queueable entity resolver implementation.
     *
     * @return void
     */
    protected function registerQueueableEntityResolver()
    {
        $this->app->singleton('Illuminate\Contracts\Queue\EntityResolver', function() {
            return new Eloquent\QueueEntityResolver;
        });
    }

}
