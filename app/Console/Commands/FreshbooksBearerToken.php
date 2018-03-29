<?php

namespace App\Console\Commands;

use App\Providers\Freshbooks\Authentication;

class FreshbooksBearerToken extends \Illuminate\Console\Command
{
    protected $signature = "freshbooks:bearer-token {code?}";
    protected $description = "Initialize the Freshbooks bearer token storage using the given oAuth code";

    public function handle(): void
    {
        $code = $this->argument("code");
        $auth = app(Authentication::class);
        if ($code) {
            try {
                $auth->initBearerToken($code);
                $this->info("Updated bearer token");
            } catch (\Exception $e) {
                $this->error($e);
            }
        } else {
            $url = $auth->getAuthorizationUrl();
            $this->info("Please visit this URL to retrieve a code:\n{$url}");
        }
    }
}
