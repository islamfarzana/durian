<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        $this->app['router']->matched(function (\Illuminate\Routing\Events\RouteMatched $event) {
            $route = $event->route;
            if (!Arr::has($route->getAction(), 'guard')) {
                return;
            }
            Auth::setDefaultDriver($route->getAction()['guard']);
        });

        Gate::define('manage', function ($admin, $modelAction) {
            // If the user is a superadmin, they have complete access
            if ($admin->type === 'superadmin') {
                return true;
            }

            $permission = config('permission');

            if (!isset($permission[$admin->type])) {
                return false;
            }

            list($model, $action) = explode('-', $modelAction);

            // Check permission for all modules
            if (in_array($action, $permission[$admin->type]['*'])) {
                return true;
            }

            // Special handling for add-edit action
            if ($action === 'add-edit') {
                $addPermission = in_array('add', $permission[$admin->type][$model] ?? []);
                $editPermission = in_array('edit', $permission[$admin->type][$model] ?? []);
                return $addPermission && $editPermission;
            }

            // Check permission for the specific module
            if (isset($permission[$admin->type][$model]) && in_array($action, $permission[$admin->type][$model])) {
                return true;
            }

            return false;
        });
    }
}
