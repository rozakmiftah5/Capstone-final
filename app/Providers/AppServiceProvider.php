<?php

namespace App\Providers;

use App\Models\MaterialRequest;
use App\Models\User;
use App\Notifications\MaterialRequestCompleted;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('admin', fn (User $user): bool => $user->role === 'Admin');
        Gate::define('stock-operations', fn (User $user): bool => in_array($user->role, ['Admin', 'Warehouse Staff'], true));
        Gate::define('stock-receipts', fn (User $user): bool => in_array($user->role, ['Admin', 'Warehouse Staff'], true));
        Gate::define('material-receipts', fn (User $user): bool => in_array($user->role, ['Admin', 'Warehouse Staff'], true));

        View::composer('layouts.navigation', function (ViewInstance $view): void {
            $unreadRequests = collect();
            $unreadRequestCount = 0;
            $requestCompletionNotifications = collect();

            if (Auth::user()?->role === 'Admin') {
                $unreadRequestCount = MaterialRequest::query()
                    ->whereNull('read_at')
                    ->count();
                $unreadRequests = MaterialRequest::query()
                    ->with([
                        'requester:user_id,username,email',
                        'items:id,material_request_id,material_description',
                    ])
                    ->whereNull('read_at')
                    ->latest()
                    ->limit(5)
                    ->get(['id', 'request_number', 'user_id', 'requesting_division', 'requesting_department', 'created_at']);
            }

            $hasNotificationsTable = Schema::hasTable('notifications');

            if (Auth::check() && $hasNotificationsTable) {
                $requestCompletionNotifications = Auth::user()
                    ->unreadNotifications()
                    ->where('type', MaterialRequestCompleted::class)
                    ->latest()
                    ->limit(5)
                    ->get();
            }

            $requestCompletionNotificationCount = Auth::check() && $hasNotificationsTable
                ? Auth::user()->unreadNotifications()->where('type', MaterialRequestCompleted::class)->count()
                : 0;

            $view->with(compact(
                'unreadRequestCount',
                'unreadRequests',
                'requestCompletionNotifications',
                'requestCompletionNotificationCount'
            ));
        });
    }
}
