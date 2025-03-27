<?php

namespace Ownego\Cashier;

use Illuminate\Support\Facades\Blade;
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
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'ownego');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'cashier');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');
        Blade::directive('paypalJS', function () {
            return "<?php echo view('cashier::js'); ?>";
        });

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
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

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__ . '/../config/cashier.php' => config_path('cashier.php'),
        ], 'cashier-config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/ownego'),
        ], 'cashier-paypal.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/ownego'),
        ], 'cashier-paypal.assets');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/ownego'),
        ], 'cashier-paypal.lang');*/

        // Registering package commands.
        $this->commands([
            Console\CreateProductCommand::class,
            Console\ListProductCommand::class,
            Console\ListPlanCommand::class,
            Console\ShowPlanCommand::class,
        ]);
    }
}
