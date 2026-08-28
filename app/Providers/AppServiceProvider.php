<?php

namespace App\Providers;

use App\Ai\AiProvider;
use App\Ai\DeepSeekProvider;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Adapter pattern: thêm provider mới = thêm 1 class implement AiProvider + 1 nhánh match ở đây
        $this->app->bind(AiProvider::class, fn (): AiProvider => match (config('services.ai.provider')) {
            'deepseek' => new DeepSeekProvider,
            default => throw new RuntimeException('AI provider không hợp lệ: '.config('services.ai.provider')),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DevCommands::except('vite'); // giao diện là Blade thuần, không dùng Vite
    }
}
