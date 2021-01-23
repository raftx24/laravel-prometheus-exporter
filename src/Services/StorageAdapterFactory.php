<?php

namespace Raftx24\LaravelPrometheusExporter\Services;

use InvalidArgumentException;
use Prometheus\Storage\Adapter;
use Prometheus\Storage\APC;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;

class StorageAdapterFactory
{
    public function make($driver, array $config = [])
    {
        switch ($driver) {
            case 'memory':
                return new InMemory();
            case 'redis':
                return $this->makeRedisAdapter($config);
            case 'apc':
                return new APC();
        }

        throw new InvalidArgumentException(sprintf('The driver [%s] is not supported.', $driver));
    }

    protected function makeRedisAdapter(array $config)
    {
        if (isset($config['prefix'])) {
            Redis::setPrefix($config['prefix']);
        }

        return new Redis($config);
    }
}
