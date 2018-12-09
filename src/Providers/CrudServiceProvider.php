<?php

namespace HC\Crud\Providers;

use HC\Crud\Controllers\CrudController;
use HC\Crud\CrudService;
use Illuminate\Contracts\Routing\Registrar as RegistrarContract;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

/**
 *
 */
class CrudServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot()
    {
        // laravel
        if (! function_exists('config_path')) {
            return;
        }

        // Laravel or... config[view] not set
        if ($this->app['config']->has('view')) {
            $this->loadViewsFrom(
                __DIR__ . '/../../resources/views', 'crud'
            );
        } else {
            $this->app['view']->addNamespace(
                'crud', __DIR__ . '/../../resources/views'
            );
        }

        $this->publishes([
            __DIR__ . '/../../config/crud.php' => config_path('crud.php'),
        ]);
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->app->singleton(CrudService::class, function () {
            $settings = $this->app['config']->get('crud', []);

            return new CrudService(
                $this->app,
                $settings['entities'] ?? [],
                $settings['uri'] ?? '',
                $settings['menu'] ?? [],
                $settings['dashboard'] ?? false
            );
        });

        $this->app->afterResolving(RegistrarContract::class, function (Router $router) {
            $settings = $this->app['config']->get('crud');

            $dashboard = $settings['dashboard'] ?? false;

            $router->group([
                'as'         => 'crud.',
                'prefix'     => $settings['uri'] ?? '',
                'middleware' => $settings['middleware'] ?? [],
            ], function (Router $router) use ($dashboard) {
                $this->registerRoutes($router, $dashboard);
            });

            // dd($router->getRoutes());
        });

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/crud.php', 'crud'
        );
    }

    /**
     * [registerRoutes description].
     *
     * @param Router      $rtr
     * @param bool|string $dashboard
     *
     * @todo custom action:
     *  post('{entity}/{action?})') : store
     *  post('{entity}/{id}/{action}') : action
     *  put('{entity}/{id}/{action?}') : update
     */
    public function registerRoutes(Router $rtr, $dashboard)
    {
        $rtr->get('/', [
            'uses' => CrudController::class . '@dashboard',
            'as'   => 'dashboard',
        ]);

        $rtr->group(['prefix' => '{entity}', 'middleware' => 'api'], function (Router $rtr) {
            $rtr->get('_schema', [
                'uses' => CrudController::class . '@schema',
                'as'   => 'schema',
            ]);

            $rtr->any('action/{action}', [
                'uses' => CrudController::class . '@executeCustomAction',
                'as'   => 'action',
            ]);

            $rtr->any('{id}/{action}', [
                'uses' => CrudController::class . '@executeCustomAction',
                'as'   => 'action',
            ]);

            $rtr->resource('/', CrudController::class, [
                'parameters' => ['' => 'id'],
                'only'       => ['index', 'store', 'show', 'update', 'destroy'],
            ]);
        });
    }
}
