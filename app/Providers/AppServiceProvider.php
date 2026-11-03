<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Blade::directive('naira', fn (string $expression) => "<?php echo e(\\App\\Support\\Money::format((int) ($expression))); ?>");
    }
}
