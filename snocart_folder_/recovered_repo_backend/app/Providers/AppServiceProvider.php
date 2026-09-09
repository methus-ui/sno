<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Traits\AddonHelper;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Config;
use App\CentralLogics\Helpers;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    use AddonHelper;
    
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Relation::morphMap([
            'Item' => 'App\Models\Item',
            'ItemCampaign' => 'App\Models\ItemCampaign',
        ]);

        // Register Store Observer to clear cache when store status changes
        \App\Models\Store::observe(\App\Observers\StoreObserver::class);

        try {

            Config::set('addon_admin_routes', $this->get_addon_admin_routes());
            Config::set('get_payment_publish_status', $this->get_payment_publish_status());
            Paginator::useBootstrap();

            $viewKeys = \Cache::rememberForever('view_keys', function () {
                return Helpers::get_view_keys();
            });

            foreach($viewKeys as $key => $value) {
                view()->share($key, $value);
            }

        } catch(\Exception $e) {
            // silent
        }
    }

}
