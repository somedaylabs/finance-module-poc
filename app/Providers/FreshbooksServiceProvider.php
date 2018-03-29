<?php

namespace App\Providers;

use App\Providers\Freshbooks\ApiClient;
use App\Providers\Freshbooks\Authentication;

class FreshbooksServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        $this->app->singleton(Authentication::class, function () {
            return new Authentication(new ApiClient(null));
        });
    }
}
