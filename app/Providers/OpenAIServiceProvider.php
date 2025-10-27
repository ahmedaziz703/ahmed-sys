<?php

namespace App\Providers;

use App\Services\AI\Contracts\AIAssistantInterface;
use App\Services\AI\Implementations\OpenAIAssistant;
use Illuminate\Support\ServiceProvider;
use OpenAI;

/**
 * OpenAI Service Provider
 *
 * Registers the OpenAI client and AI assistant implementation.
 *
 * @return void
*/
class OpenAIServiceProvider extends ServiceProvider
{
    /**
     * Register the OpenAI client and AI assistant implementation.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(OpenAI\Client::class, function () {
            return OpenAI::client(config('ai.openai.api_key'));
        });

        $this->app->bind(AIAssistantInterface::class, OpenAIAssistant::class);
    }
} 