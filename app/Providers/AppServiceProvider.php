<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Like;
use App\Observers\CommentObserver;
use App\Observers\LikeObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        Comment::observe(CommentObserver::class);
        Like::observe(LikeObserver::class);

        // Belt-and-suspenders alongside trustProxies() in bootstrap/app.php:
        // force https:// in every generated URL in production, regardless
        // of how a given host's proxy reports its forwarded scheme.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
