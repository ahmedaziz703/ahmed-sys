<?php

declare(strict_types=1);

use Illuminate\Support\Facades\{Route, Auth};

// Auth Components
use App\Livewire\Auth\{
    Login,
    Register
};

// Post Components
use App\Livewire\{
    Dashboard
};

// Settings Components
use App\Livewire\Settings\{
    SettingsIndex,
    SiteSettings,
    NotificationSettings,
    TelegramSettings
};

// Role Components
use App\Livewire\Role\RoleManager;
use App\Livewire\Role\RoleForm;

// Income & Expense Components
use App\Livewire\Categories\{
    CategoryManager
};

// Transaction Components
use App\Livewire\Transaction\{
    TransactionManager,
    TransactionForm
};

// Deposit Management
use App\Livewire\BankAccount;

// Customer Group Components
use App\Livewire\CustomerGroup\CustomerGroupManager;

// Customer Components
use App\Livewire\Customer\CustomerManager;

// Supplier Components
use App\Livewire\Supplier\SupplierManager;

// Lead Components
use App\Livewire\Lead\LeadManager;

// Customer Detail Components
use App\Livewire\Customer\CustomerDetail;

// Proposal Management
use App\Http\Controllers\ProposalController;
use App\Livewire\Proposal\ProposalTemplateManager;
use App\Livewire\Proposal\ProposalTemplateForm;

// Project Management
use App\Livewire\Project\ProjectManager;
use App\Livewire\Project\Board\BoardManager;

// Financial Management Routes
use App\Livewire\Account\AccountManager;
use App\Livewire\Account\BankAccountManager;
use App\Livewire\Account\CryptoWalletManager;
use App\Livewire\Account\VirtualPosManager;
use App\Livewire\Account\CreditCardManager;
use App\Livewire\Account\CreditCardTransactions;
use App\Livewire\Account\AccountHistory;

// Debt Components
use App\Livewire\Debt\DebtManager;
use App\Livewire\Debt\DebtPayments;

// Loan Components
use App\Livewire\Loan\LoanManager;

// Analysis & Tracking Components
use App\Livewire\Analysis\CashFlowAnalysis;
use App\Livewire\Analysis\ProfitLossAnalysis;
use App\Livewire\Analysis\ExpenseCategoryAnalysis;
use App\Livewire\Analysis\IncomeSourceAnalysis;
use App\Livewire\Analysis\BudgetPerformanceAnalysis;
use App\Livewire\Analysis\CustomerProfitabilityAnalysis;
use App\Livewire\Analysis\ProjectProfitabilityAnalysis;
use App\Livewire\Analysis\CategoryAnalysis;

// User Components
use App\Livewire\User\UserManager;
use App\Livewire\User\UserForm;

use App\Livewire\Commission\CommissionManager;
use App\Livewire\Commission\UserCommissionHistory;

use  \App\Livewire\Transaction\RecurringTransactionList;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Auth Routes
Route::get('/', Login::class)->name('login');
//Route::get('/register', Register::class)->name('register');

// Protected Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/logout', function () {
        Auth::logout();
        return redirect()->route('login');
    })->name('logout');

    // Admin Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', Dashboard::class)->name('dashboard');

        // Role Management
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', RoleManager::class)->name('index')->middleware('permission:roles.view');
            Route::get('/create', RoleForm::class)->name('create')->middleware('permission:roles.create');
            Route::get('/{role}/edit', RoleForm::class)->name('edit')->middleware('permission:roles.edit');
        });

        // Settings Management
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', SettingsIndex::class)->name('index')->middleware('permission:settings.view');
            Route::get('/site', SiteSettings::class)->name('site')->middleware('permission:settings.site');
            Route::get('/notification', NotificationSettings::class)->name('notification')->middleware('permission:settings.notification');
            Route::get('/telegram', TelegramSettings::class)->name('telegram')->middleware('permission:settings.telegram'); 
        });

        // User Management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', UserManager::class)->name('index')->middleware('permission:users.view');
            Route::get('/create', UserForm::class)->name('create')->middleware('permission:users.create');
            Route::get('/{user}/edit', UserForm::class)->name('edit')->middleware('permission:users.edit');
            Route::get('/{user}/commissions', UserCommissionHistory::class)->name('commissions')->middleware('permission:users.commissions');
        });

        // Income & Expense Management
        Route::prefix('transactions')->group(function () {
            Route::get('/', TransactionManager::class)->name('transactions.index')->middleware('permission:transactions.view');
            Route::get('/create', TransactionForm::class)->name('transactions.create')->middleware('permission:transactions.create');
            Route::get('/{transaction}/edit', TransactionForm::class)->name('transactions.edit')->middleware('permission:transactions.edit');
        });
        
        Route::get('/recurring', RecurringTransactionList::class)->name('recurring')->middleware('permission:recurring_transactions.view');

        // Category Management
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', CategoryManager::class)->name('index')->middleware('permission:categories.view');
        });

        // Proposal Routes
        Route::prefix('proposals')->name('proposals.')->group(function () {
            Route::get('/templates', ProposalTemplateManager::class)->name('templates');
            Route::get('/templates/create', ProposalTemplateForm::class)->name('create');
            Route::get('/templates/{template}/edit', ProposalTemplateForm::class)->name('edit');
            Route::get('/{proposal}/pdf', [ProposalController::class, 'downloadPdf'])->name('pdf');
        });

        // Supplier Routes
        Route::prefix('suppliers')->name('suppliers.')->group(function () {
            Route::get('/', SupplierManager::class)->name('index')->middleware('permission:suppliers.view');
        });

        // Customer Routes
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('/', CustomerManager::class)->name('index')->middleware('permission:customers.view');
            Route::get('/groups', CustomerGroupManager::class)->name('groups')->middleware('permission:customer_groups.view');
            Route::get('/potential', LeadManager::class)->name('potential')->middleware('permission:leads.view');
            Route::get('/{customer}', CustomerDetail::class)->name('show')->middleware('permission:customers.detail');
        });

        // Project Management
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::get('/', ProjectManager::class)->name('index')->middleware('permission:projects.view');
            Route::get('/{project}/boards', BoardManager::class)->name('boards')->middleware('permission:projects.details');
        });

        // Loan Management
        Route::prefix('loans')->name('loans.')->group(function () {
            Route::get('/', LoanManager::class)->name('index')->middleware(['permission:loans.view']);
        });

        // Debt & Receivable Tracking
        Route::prefix('debts')->name('debts.')->group(function () {
            Route::get('/', DebtManager::class)->name('index')->middleware(['permission:debts.view']);
            // Not active - Route::get('/payments/{debt}', DebtPayments::class)->name('payments');
        });

        // Financial Management Routes
        Route::prefix('accounts')->name('accounts.')->group(function () {
            Route::get('/bank', BankAccountManager::class)->name('bank')->middleware(['permission:bank_accounts.view']);
            Route::get('/credit-cards', CreditCardManager::class)->name('credit-cards')->middleware(['permission:credit_cards.view']);
            Route::get('/crypto', CryptoWalletManager::class)->name('crypto')->middleware(['permission:crypto_wallets.view']);
            Route::get('/virtual-pos', VirtualPosManager::class)->name('virtual-pos')->middleware(['permission:virtual_pos.view']);
            Route::get('/{account}/history', AccountHistory::class)->name('history')->middleware(['permission:bank_accounts.history']);
        });
        
        // Analysis & Tracking Routes
        Route::prefix('analysis')->name('analysis.')->group(function () {
            // Financial Analysis
            Route::get('/cash-flow', CashFlowAnalysis::class)->name('cash-flow')->middleware(['permission:reports.cash_flow']);
            Route::get('/categories', CategoryAnalysis::class)->name('categories')->middleware(['permission:reports.category_analysis']);
        });

        // Planning Module
        Route::prefix('planning')->name('planning.')->group(function () {
            Route::get('/savings', \App\Livewire\Planning\SavingsPlanner::class)->name('savings')->middleware(['permission:savings.view']);
            Route::get('/investments', \App\Livewire\Planning\InvestmentPlanner::class)->name('investments')->middleware(['permission:investments.view']);
        });

    });
});