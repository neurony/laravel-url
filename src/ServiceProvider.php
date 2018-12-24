<?php

namespace Zbiller\Url;

use Zbiller\Url\Models\Url;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Route as Router;
use Zbiller\Url\Contracts\UrlModelContract;
use Illuminate\Routing\ControllerDispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Create a new service provider instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishMigrations();
        $this->registerRoutes();
        $this->registerRouteBindings();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * @return void
     */
    protected function publishMigrations()
    {
        if (empty(File::glob(database_path('migrations/*_create_urls_table.php')))) {
            $timestamp = date('Y_m_d_His', time());
            $migration = database_path("migrations/{$timestamp}_create_urls_table.php");

            $this->publishes([
                __DIR__.'/../database/migrations/create_urls_table.php.stub' => $migration,
            ], 'migrations');
        }
    }

    /**
     * @return void
     */
    protected function registerRoutes()
    {
        Route::macro('customUrl', function () {
            Route::get('{all}', function ($url = '/') {
                $url = Url::whereUrl($url)->first();

                if (!$url) {
                    abort(404);
                }

                $model = $url->urlable;

                if (!$model) {
                    abort(404);
                }

                $controller = $model->getUrlOptions()->routeController;
                $action = $model->getUrlOptions()->routeAction;

                return (new ControllerDispatcher(app()))->dispatch(
                    app(Router::class)->setAction([
                        'uses' => $controller.'@'.$action,
                        'model' => $model,
                    ]), app($controller), $action
                );
            })->where('all', '(.*)');
        });
    }

    /**
     * @return void
     */
    protected function registerRouteBindings()
    {
        Route::model('url', UrlModelContract::class);
    }
}
