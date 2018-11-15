<?php

namespace Tumobi\QQMapRegion;


class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(Region::class, function(){
            return new Region(config('services.region.key'));
        });

        $this->app->alias(Region::class, 'region');
    }

    public function provides()
    {
        return [Region::class, 'region'];
    }
}
