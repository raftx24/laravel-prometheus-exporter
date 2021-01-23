<?php

namespace Raftx24\LaravelPrometheusExporter;

use Illuminate\Support\Facades\Facade;

class PrometheusFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'prometheus';
    }
}
