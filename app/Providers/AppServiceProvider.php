<?php

namespace App\Providers;

use App\Integrations\IntegrationManager;
use App\Services\NotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(IntegrationManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->shareNotificationCount();
    }

    /**
     * The top bar shows how many operational alerts are outstanding. It is
     * derived from real records and cached briefly to avoid running the
     * aggregate queries on every single page render.
     */
    protected function shareNotificationCount(): void
    {
        View::composer('partials.topbar', function ($view): void {
            $user = auth()->user();

            $view->with('notificationCount', $user === null ? 0 : Cache::remember(
                'notification-count:'.$user->getKey(),
                60,
                fn (): int => app(NotificationService::class)->count($user),
            ));
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(8)
                ->letters()
                ->numbers()
            : null,
        );
    }
}
