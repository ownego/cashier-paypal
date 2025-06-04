<?php

namespace Ownego\Cashier;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CashierServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->bootRoutes();
        $this->bootCommands();
        $this->bootResources();
        $this->bootDirectives();
        $this->bootMigrations();
        $this->bootPublishing();
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/cashier.php', 'cashier');

        // Register the service the package provides.
        $this->app->singleton('cashier', function ($app) {
            return new Cashier;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['cashier'];
    }

    protected function bootResources()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'cashier');
    }

    protected function bootDirectives()
    {
        Blade::directive('paypalJS', function () {
            return "<?php echo view('cashier::js'); ?>";
        });
    }

    protected function bootRoutes()
    {
        if (Cashier::$registerRoutes) {
            Route::group([
                'prefix' => config('cashier.path'),
                'as' => 'cashier.',
            ], function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
        }
    }

    protected function bootMigrations()
    {
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function bootPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/cashier.php' => $this->app->configPath('cashier.php'),
            ], 'cashier-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => $this->app->databasePath('migrations'),
            ], 'cashier-migrations');
        }
    }

    protected function bootCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\CreateProductCommand::class,
                Console\ListProductCommand::class,
                Console\ListPlanCommand::class,
                Console\ShowPlanCommand::class,
            ]);
        }
    }
}
