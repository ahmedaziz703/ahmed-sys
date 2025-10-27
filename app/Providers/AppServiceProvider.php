<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Facades\Blade;
use App\Services\Customer\Contracts\CustomerServiceInterface;
use App\Services\Customer\Implementations\CustomerService;
use App\Services\Lead\Contracts\LeadServiceInterface;
use App\Services\Lead\Implementations\LeadService;
use App\Services\CustomerGroup\Contracts\CustomerGroupServiceInterface;
use App\Services\CustomerGroup\Implementations\CustomerGroupService;
use App\Services\Transaction\Contracts\TransactionServiceInterface;
use App\Services\Transaction\Implementations\TransactionService;
use App\Services\BankAccount\Contracts\BankAccountServiceInterface;
use App\Services\BankAccount\Implementations\BankAccountService;
use App\Services\Project\Contracts\ProjectServiceInterface;
use App\Services\Project\Implementations\ProjectService;
use App\Services\Payment\Contracts\PaymentServiceInterface;
use App\Services\Payment\Implementations\PaymentService;
use App\Services\Account\Contracts\AccountServiceInterface;
use App\Services\Account\Implementations\AccountService;
use App\Services\Debt\Contracts\DebtServiceInterface;
use App\Services\Debt\Implementations\DebtService;
use App\Services\Loan\Contracts\LoanServiceInterface;
use App\Services\Loan\Implementations\LoanService;
use App\Services\Role\Contracts\RoleServiceInterface;
use App\Services\Role\Implementations\RoleService;
use App\Services\Supplier\Contracts\SupplierServiceInterface;
use App\Services\Supplier\Implementations\SupplierService;
use App\Services\User\Contracts\UserServiceInterface;
use App\Services\User\Implementations\UserService;

/** Transaction Services */
use App\Services\Transaction\Contracts\AccountBalanceServiceInterface;
use App\Services\Transaction\Contracts\ExpenseTransactionServiceInterface;
use App\Services\Transaction\Contracts\IncomeTransactionServiceInterface;
use App\Services\Transaction\Contracts\InstallmentTransactionServiceInterface;
use App\Services\Transaction\Contracts\SubscriptionTransactionServiceInterface;
use App\Services\Transaction\Contracts\TransferTransactionServiceInterface;
use App\Services\Transaction\Implementations\AccountBalanceService;
use App\Services\Transaction\Implementations\ExpenseTransactionService;
use App\Services\Transaction\Implementations\IncomeTransactionService;
use App\Services\Transaction\Implementations\InstallmentTransactionService;
use App\Services\Transaction\Implementations\SubscriptionTransactionService;
use App\Services\Transaction\Implementations\TransferTransactionService;

use App\Services\Planning\Contracts\PlanningServiceInterface;
use App\Services\Planning\Implementations\PlanningService;
use App\Services\Analytics\TransactionAnalyticsService;
use App\Services\CreditCard\Contracts\CreditCardServiceInterface;
use App\Services\CreditCard\Implementations\CreditCardService;

use App\Services\AI\Contracts\AIAssistantInterface;
use App\Services\AI\Implementations\OpenAIAssistant;
use App\Services\AI\Implementations\GeminiAssistant;
use OpenAI\Client;
use OpenAI\Laravel\Facades\OpenAI;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


/**
 * App Service Provider
 *
 * Manages service registration and bootstrapping for the application.
 * Registers all service interfaces and their implementations.
 * Singleton and bind registrations are configured.
 *
 * @return void
*/
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register services for the application.
     *
     * @return void
     */
    public function register(): void
    {
        try {
            $settings = Cache::get('site_settings', []);

            if (!empty($settings['site_title'])) {
                config(['filament.brand' => $settings['site_title']]);
            }

            $logoUrl = null;
            if (!empty($settings['site_logo'])) {
                 $logoPath = $settings['site_logo'];
            }

            $faviconUrl = null;
            if (!empty($settings['site_favicon'])) {
            }

        } catch (\Exception $e) {
            Log::error('Filament ayarları register metodunda yüklenirken hata oluştu: ' . $e->getMessage());
        }

        // Register core service interfaces and their implementations
        $this->app->singleton(CustomerServiceInterface::class, CustomerService::class);
        $this->app->singleton(LeadServiceInterface::class, LeadService::class);
        $this->app->singleton(CustomerGroupServiceInterface::class, CustomerGroupService::class);
        $this->app->singleton(TransactionServiceInterface::class, TransactionService::class);
        $this->app->bind(BankAccountServiceInterface::class, BankAccountService::class);
        $this->app->singleton(ProjectServiceInterface::class, ProjectService::class);
        $this->app->singleton(UserServiceInterface::class, UserService::class);

        // Credit card service
        $this->app->bind(CreditCardServiceInterface::class, CreditCardService::class);

        // Other core services
        $this->app->bind(DebtServiceInterface::class, DebtService::class);
        $this->app->bind(RoleServiceInterface::class, RoleService::class);
        $this->app->bind(SupplierServiceInterface::class, SupplierService::class);
        $this->app->bind(LoanServiceInterface::class, LoanService::class);
        $this->app->bind(AccountServiceInterface::class, AccountService::class);

        // Register helper services as singletons
        $this->app->singleton(PaymentServiceInterface::class, PaymentService::class);
        $this->app->singleton(TransactionAnalyticsService::class);

        // Transaction services
        $this->app->bind(AccountBalanceServiceInterface::class, AccountBalanceService::class);
        $this->app->bind(IncomeTransactionServiceInterface::class, IncomeTransactionService::class);
        $this->app->bind(ExpenseTransactionServiceInterface::class, ExpenseTransactionService::class);
        $this->app->bind(TransferTransactionServiceInterface::class, TransferTransactionService::class);
        $this->app->bind(InstallmentTransactionServiceInterface::class, InstallmentTransactionService::class);
        $this->app->bind(SubscriptionTransactionServiceInterface::class, SubscriptionTransactionService::class);

        // Planning service
        $this->app->bind(PlanningServiceInterface::class, PlanningService::class);


        $this->app->bind(AIAssistantInterface::class, function ($app) {
            $dateAnalyzer = $app->make(\App\Services\AI\DateRangeAnalyzer::class);
            return match(config('ai.default')) {
                'gemini' => new GeminiAssistant($dateAnalyzer),
                'openai' => new OpenAIAssistant($app->make(Client::class), $dateAnalyzer),
                default => new GeminiAssistant($dateAnalyzer),
            };
        });

        if (config('ai.default') === 'openai') {
            $this->app->bind(Client::class, function ($app) {
                return OpenAI::client(config('ai.openai.api_key'), [
                    'organization' => config('ai.openai.organization'),
                ]);
            });
        }

        // AI and SQL services
        $this->app->singleton(\App\Services\AI\DatabaseSchemaService::class);
        $this->app->singleton(\App\Services\AI\SqlQueryService::class);
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        if($this->app->environment('production')) {
            \URL::forceScheme('https');
        }

        // Register blade components
        Blade::component('auth', \App\View\Auth::class);

        // Register filament color schemes
        FilamentColor::register([
            'danger' => Color::Red,
            'gray' => Color::Gray,
            'primary' => Color::Blue,
            'success' => Color::Green,
            'warning' => Color::Yellow,
        ]);

        // Configure system settings
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        // Configure logo and favicon URLs
        try {
             if ($this->app->runningInConsole() || !Schema::hasTable('settings')) {
                 return;
             }
             $settings = Cache::get('site_settings', []);

             // Configure site logo URL
             if (!empty($settings['site_logo']) && Storage::disk('public')->exists($settings['site_logo'])) {
                 config(['filament.logo' => Storage::url($settings['site_logo'])]);
             } else {
                 config(['filament.logo' => null]);
             }

             // Configure favicon URL
             if (!empty($settings['site_favicon']) && Storage::disk('public')->exists($settings['site_favicon'])) {
                 config(['filament.favicon' => Storage::url($settings['site_favicon'])]);
             } else {
                 config(['filament.favicon' => null]);
             }
        } catch (\Exception $e) {
             Log::error('Filament logo/favicon URL ayarlanırken hata oluştu (boot): ' . $e->getMessage());
        }

        if (config('app.app_demo_mode')) {
            $this->app->bind(
                \Illuminate\Foundation\MaintenanceMode::class,
                \Illuminate\Foundation\MaintenanceModeBypassCookie::class
            );
        }
    }
}
