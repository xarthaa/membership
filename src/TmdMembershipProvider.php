<?php

namespace Timedoor\TmdMembership;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;
use Timedoor\TmdMembership\macros\AuthAttemptMacro;

class TmdMembershipProvider extends ServiceProvider 
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/Config/tmd-membership.php', 'tmd-membership'
        );
    }

    public function boot()
    {
        Builder::mixin(new AuthAttemptMacro);

        // $this->loadRoutesFrom(__DIR__ . '/routes/admin.php');
        // $this->loadRoutesFrom(__DIR__ . '/routes/membership.php');
        // $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        $this->publishes([
            __DIR__ . '/Config/tmd-membership.php' => config_path('tmd-membership.php'),
            __DIR__ . '/database/migrations/2021_10_06_000001_create_fcm_tokens_table.php' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_fcm_tokens_table.php'),
            __DIR__ . '/database/migrations/2021_10_06_000002_create_otps_table.php' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_otps_table.php'),
        ], 'tmd-membership');

        $this->app['router']->pushMiddlewareToGroup('api', \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class);
        
        $this->initFcm();
        $this->app['router']->aliasMiddleware('is-reached-max', \Timedoor\TmdMembership\Middleware\OtpIsReachedMaxHit::class);

        $this->commands([
            Console\InstallCommand::class,
        ]);
    }

    protected function initFcm()
    {
        $auth = auth('sanctum');

        if ($auth->check()) {
            $user            = $auth->user();
            $currentToken    = $user->currentAccessToken()->token;
            $currentFcmToken = $user->fcmToken()->where('token_id', $currentToken)->first();
    
            $user->withFcmToken($currentFcmToken);
        }
    }
}