<?php

namespace Superbalist\LaravelPrometheusExporter;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use LaravelEnso\ControlPanelApi\Commands\Monitor;
use LaravelEnso\ControlPanelApi\Http\Middleware\RequestMonitor;
use LaravelEnso\ControlPanelApi\Services\Actions;
use LaravelEnso\ControlPanelApi\Services\Statistics;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Adapter;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->middleware()
            ->command()
            ->publish()
            ->load();

        $exporter = $this->app->make(PrometheusExporter::class);
        /* @var PrometheusExporter $exporter */
        foreach (config('prometheus.collectors') as $class) {
            $collector = $this->app->make($class);
            $exporter->registerCollector($collector);
        }
    }

    private function command(): self
    {
        $this->commands(Monitor::class);

        $this->app->booted(fn () => $this->app->make(Schedule::class)
            ->command('enso:control-panel-api:monitor')->everyFiveMinutes());

        return $this;
    }

    private function middleware(): self
    {
//        $this->app['router']
//            ->aliasMiddleware('request-monitor', RequestMonitor::class);

        return $this;
    }

    private function publish(): self
    {
        $this->publishes([
            __DIR__.'/../config/prometheus.php' => config_path('prometheus.php'),
        ], ['prometheus', 'enso-configs']);

        return $this;
    }

    private function load()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->mergeConfigFrom(__DIR__.'/../config/prometheus.php', 'enso.prometheus');
    }


    /**
     * Register bindings in the container.
     */
    public function register()
    {

        $this->app->singleton(PrometheusExporter::class, function ($app) {
            $adapter = $app['prometheus.storage_adapter'];
            $prometheus = new CollectorRegistry($adapter);
            return new PrometheusExporter(config('prometheus.namespace'), $prometheus);
        });
        $this->app->alias(PrometheusExporter::class, 'prometheus');

        $this->app->bind('prometheus.storage_adapter_factory', function () {
            return new StorageAdapterFactory();
        });

        $this->app->bind(Adapter::class, function ($app) {
            $factory = $app['prometheus.storage_adapter_factory'];
            /** @var StorageAdapterFactory $factory */
            $driver = config('prometheus.storage_adapter');
            $configs = config('prometheus.storage_adapters');
            $config = Arr::get($configs, $driver, []);
            return $factory->make($driver, $config);
        });

        $this->app->alias(Adapter::class, 'prometheus.storage_adapter');
    }

    public function provides()
    {
        return [
            'prometheus',
            'prometheus.storage_adapter_factory',
            'prometheus.storage_adapter',
        ];
    }
}
