<?php
namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class DemoDataSeeder extends Seeder
{
    private $faker;
    private $admin;
    private $employee;
    private $startDate;
    private $endDate;

    public function __construct()
    {
        $this->faker = Faker::create('tr_TR');
        $this->startDate = Carbon::create(2023, 1, 1);
        $this->endDate = Carbon::now();
    }

    public function run(): void
    {
        $this->admin = User::where('email', 'admin@admin.com')->first();
        if (!$this->admin) {
            throw new \RuntimeException('لم يتم العثور على مستخدم المشرف. يجب تشغيل UserSeeder أولاً.');
        }

        $this->employee = User::where('email', 'test@test.com')->first();
        if (!$this->employee) {
            throw new \RuntimeException('لم يتم العثور على مستخدم الاختبار. يجب تشغيل UserSeeder أولاً.');
        }

        // For Admin, create all data
        $this->createCustomerGroups($this->admin);
        $this->createCategories($this->admin);
        $this->createCustomersAndLeads($this->admin);
        $this->createCustomerNotes($this->admin);
        $this->createCustomerAgreements($this->admin);
        $this->createAccounts($this->admin);
        $this->createLoans($this->admin);
        $this->createSavingsAndInvestments($this->admin);
        $this->createProjects($this->admin);
        $this->createTransactions($this->admin);

        // For Employee, only create customer management and transactions
        // Use admin's created accounts, categories, and groups
        $this->createCustomersAndLeads($this->employee); // Employee creates own customers
        $this->createCustomerNotes($this->employee);   // Employee creates own notes
        $this->createTransactions($this->employee);    // Employee creates transactions for own customers (using admin's accounts/categories)
        $this->createCommissionPayouts($this->employee); // Create commission payouts for employee
    }

    private function createCategories(User $user): void
    {
        // Simplified category structure (5-6 Income, 5-6 Expense)
        $incomeCategories = [
            ['name' => 'دخل الخدمة', 'type' => 'income'],
            ['name' => 'دخل المبيعات', 'type' => 'income'],
            ['name' => 'دخل الاشتراك', 'type' => 'income'],
            ['name' => 'دخل العمولة', 'type' => 'income'],
            ['name' => 'دخل الفوائد', 'type' => 'income'],
            ['name' => 'دخل آخر', 'type' => 'income'],
        ];

        $expenseCategories = [
            ['name' => 'مصاريف المكتب', 'type' => 'expense'], // Rent, Bills, Materials, etc.
            ['name' => 'مصاريف الموظفين', 'type' => 'expense'],
            ['name' => 'البرمجيات والاشتراكات', 'type' => 'expense'],
            ['name' => 'التسويق والإعلان', 'type' => 'expense'],
            ['name' => 'البنك والتمويل', 'type' => 'expense'],
            ['name' => 'مصاريف أخرى', 'type' => 'expense'],
        ];

        foreach (array_merge($incomeCategories, $expenseCategories) as $category) {
            Category::create([
                'name' => $category['name'],
                'type' => $category['type'],
                'user_id' => $user->id,
            ]);
        }
    }

    private function createCustomerGroups(User $user): void
    {
        $groups = [
            [
                'name' => 'عملاء الشركات والمؤسسات',
                'description' => 'الشركات والمؤسسات التجارية',
            ],
            [
                'name' => 'عملاء الأفراد',
                'description' => 'الأفراد والعملاء الشخصيون',
            ],
            [
                'name' => 'عملاء التجارة الإلكترونية',
                'description' => 'العملاء القادمون من منصات البيع عبر الإنترنت',
            ],
            [
                'name' => 'العملاء الدوليون',
                'description' => 'العملاء من خارج البلاد',
            ],
        ];

        foreach ($groups as $group) {
            CustomerGroup::create([
                'name' => $group['name'],
                'description' => $group['description'],
                'user_id' => $user->id,
            ]);
        }
    }

    private function createCustomersAndLeads(User $user): void
    {
        // First make sure groups exist
        // Always use admin user's customer groups
        $groups = CustomerGroup::where('user_id', $this->admin->id)->get();
        if ($groups->isEmpty()) {
            throw new \RuntimeException('لم يتم العثور على مجموعات العملاء. يجب تشغيل createCustomerGroups أولاً.');
        }

        // Create customers for last 1 year
        $startDate = Carbon::now()->subYear();
        $currentDate = $startDate->copy();
        while ($currentDate <= Carbon::now()) {
            // Create customers for last 1 year
            $monthlyCustomerCount = $this->faker->numberBetween(5, 10);
            for ($i = 0; $i < $monthlyCustomerCount; $i++) {
                $group = $groups->random(); // Random group selection
                $createdAt = $currentDate->copy()->addDays($this->faker->numberBetween(1, 28));
                $isCompany = $this->faker->boolean(70);
                Customer::create([
                    'name' => $isCompany ? $this->faker->company : $this->faker->name,
                    'type' => $isCompany ? 'corporate' : 'individual',
                    'tax_number' => $this->faker->numerify('##########'),
                    'tax_office' => $this->faker->city,
                    'email' => $this->faker->companyEmail,
                    'phone' => $this->faker->phoneNumber,
                    'address' => $this->faker->address,
                    'city' => $this->faker->city,
                    'district' => $this->faker->city,
                    'description' => $this->faker->sentence,
                    'status' => true, // All customers are active
                    'customer_group_id' => $group->id,
                    'user_id' => $user->id,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }

            // Leads only for admin
            if ($user->hasRole('admin')) {
                $monthlyLeadCount = $this->faker->numberBetween(2, 4);
                for ($i = 0; $i < $monthlyLeadCount; $i++) {
                    $createdAt = $currentDate->copy()->addDays($this->faker->numberBetween(1, 28));
                    $nextContactDate = $createdAt->copy()->addDays($this->faker->numberBetween(1, 30));
                    Lead::create([
                        'name' => $this->faker->company,
                        'type' => 'corporate',
                        'email' => $this->faker->companyEmail,
                        'phone' => $this->faker->phoneNumber,
                        'address' => $this->faker->address,
                        'city' => $this->faker->city,
                        'district' => $this->faker->city,
                        'source' => $this->faker->randomElement(['website', 'referral', 'social_media', 'other']),
                        'status' => $this->faker->randomElement(['new', 'contacted', 'negotiating', 'converted', 'lost']),
                        'last_contact_date' => $createdAt,
                        'next_contact_date' => $nextContactDate,
                        'notes' => $this->faker->paragraph,
                        'user_id' => $user->id,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }
            }

            $currentDate->addMonth();
        }
    }

    private function createAccounts(User $user): void
    {
        $accounts = [
            [
                'name' => 'الحساب المصرفي الرئيسي',
                'type' => Account::TYPE_BANK_ACCOUNT,
                'currency' => 'YER',
                'balance' => 50000,
                'details' => [
                    'bank_name' => 'بنك',
                    'branch' => 'الفرع الرئيسي',
                    'account_no' => '1234567890',
                    'iban' => 'TR33 0006 4000 0011 2345 6789 01'
                ]
            ],
            [
                'name' => 'حساب الدولار الأمريكي',
                'type' => Account::TYPE_BANK_ACCOUNT,
                'currency' => 'USD',
                'balance' => 5000,
                'details' => [
                    'bank_name' => 'بنك',
                    'branch' => 'الفرع الرئيسي',
                    'account_no' => '1234567891',
                    'iban' => 'TR33 0006 4000 0011 2345 6789 02'
                ]
            ],
            [
                'name' => 'حساب اليورو',
                'type' => Account::TYPE_BANK_ACCOUNT,
                'currency' => 'EUR',
                'balance' => 3000,
                'details' => [
                    'bank_name' => 'بنك ',
                    'branch' => 'الفرع الرئيسي',
                    'account_no' => '1234567892',
                    'iban' => 'TR33 0006 4000 0011 2345 6789 03'
                ]
            ],
            [
                'name' => 'بطاقة',
                'type' => Account::TYPE_CREDIT_CARD,
                'currency' => 'YER',
                'balance' => 15000,
                'details' => [
                    'bank_name' => 'بنك',
                    'credit_limit' => 20000,
                    'statement_day' => 15,
                    'current_debt' => 15000
                ]
            ],
            [
                'name' => 'محفظة بيتكوين على بينانس',
                'type' => Account::TYPE_CRYPTO_WALLET,
                'currency' => 'USD',
                'balance' => 1000,
                'details' => [
                    'platform' => 'Binance',
                    'wallet_address' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
                ]
            ],
            [
                'name' => 'نقاط بيع الافتراضية',
                'type' => Account::TYPE_VIRTUAL_POS,
                'currency' => 'YER',
                'balance' => 2575,
                'details' => [
                    'provider' => 'بي بال',
                ]
            ],
            [
                'name' => 'نقدًا',
                'type' => Account::TYPE_CASH,
                'currency' => 'YER',
                'balance' => 5000,
            ],
        ];

        foreach ($accounts as $account) {
            Account::create([
                'name' => $account['name'],
                'type' => $account['type'],
                'currency' => $account['currency'],
                'balance' => $account['balance'],
                'details' => $account['details'] ?? null,
                'user_id' => $user->id,
            ]);
        }
    }

    private function createProjects(User $user): void
    {
        // Create single project
        $project = Project::create([
            'name' => 'نظام إدارة العملاء',
            'description' => 'مشروع برمجي لإدارة علاقات العملاء وعمليات المبيعات',
            'status' => 'active',
            'view_type' => 'list',
            'created_by' => $user->id,
        ]);

        // Create task lists
        $lists = [
            ['name' => 'قيد الانتظار', 'order' => 1],
            ['name' => 'قيد التنفيذ', 'order' => 2],
            ['name' => 'قيد الاختبار', 'order' => 3],
            ['name' => 'مكتمل', 'order' => 4]
        ];

        $taskLists = [];
        foreach ($lists as $list) {
            $taskLists[$list['name']] = \App\Models\TaskList::create([
                'board_id' => 1,
                'name' => $list['name'],
                'order' => $list['order']
            ]);
        }

        // Tasks by list
        $tasks = [
            [
                'list' => 'قيد التنفيذ',
                'tasks' => [
                    [
                        'title' => 'تطوير نظام تقارير العملاء',
                        'content' => 'إنشاء تقارير دخل وأنشطة مخصصة لكل عميل',
                        'priority' => 'high',
                        'due_date' => now()->addDays(5),
                    ],
                    [
                        'title' => 'دمج نظام إشعارات البريد الإلكتروني',
                        'content' => 'إرسال إشعارات بريد إلكتروني تلقائية للأنشطة المهمة المتعلقة بالعملاء',
                        'priority' => 'medium',
                        'due_date' => now()->addDays(7),
                    ]
                ]
            ],
            [
                'list' => 'قيد الاختبار',
                'tasks' => [
                    [
                        'title' => 'تحديث واجهة بوابة العملاء',
                        'content' => 'تحسينات التصميم الجديد وتجربة المستخدم',
                        'priority' => 'medium',
                        'due_date' => now()->addDays(3),
                    ],
                    [
                        'title' => 'وحدة تتبع التحصيلات',
                        'content' => 'تتبع ودفعات العملاء وإعداد التقارير',
                        'priority' => 'high',
                        'due_date' => now()->addDays(2),
                    ]
                ]
            ],
            [
                'list' => 'مكتمل',
                'tasks' => [
                    [
                        'title' => 'تحسين قاعدة بيانات العملاء',
                        'content' => 'تحسينات الأداء وفهرسة البيانات',
                        'priority' => 'high',
                        'due_date' => now()->subDays(2),
                    ],
                    [
                        'title' => 'تحديث نظام الصلاحيات',
                        'content' => 'التحكم في الوصول حسب الأدوار وتحديثات الأمان',
                        'priority' => 'high',
                        'due_date' => now()->subDays(1),
                    ]
                ]
            ]
        ];

        // Create tasks
        foreach ($tasks as $listTasks) {
            $list = $taskLists[$listTasks['list']];
            foreach ($listTasks['tasks'] as $index => $task) {
                \App\Models\Task::create([
                    'task_list_id' => $list->id,
                    'title' => $task['title'],
                    'content' => $task['content'],
                    'priority' => $task['priority'],
                    'due_date' => $task['due_date'],
                    'order' => $index + 1,
                    'assigned_to' => $user->id
                ]);
            }
        }
    }

    private function createSavingsAndInvestments(User $user): void
    {
        // Only 2 savings plans
        $savingsPlans = [
            [
                'goal_name' => 'صندوق الطوارئ',
                'target_amount' => 50000,
                'saved_amount' => 35000,
                'target_date' => Carbon::now()->addMonths(6),
                'status' => 'active'
            ],
            [
                'goal_name' => 'صندوق نقل المكتب',
                'target_amount' => 100000,
                'saved_amount' => 25000,
                'target_date' => Carbon::now()->addYear(),
                'status' => 'active'
            ]
        ];

        foreach ($savingsPlans as $plan) {
            \App\Models\SavingsPlan::create([
                'user_id' => $user->id,
                'goal_name' => $plan['goal_name'],
                'target_amount' => $plan['target_amount'],
                'saved_amount' => $plan['saved_amount'],
                'target_date' => $plan['target_date'],
                'status' => $plan['status']
            ]);
        }

        // Only 2 investment plans
        $investmentPlans = [
            [
                'investment_name' => 'استثمار البيتكوين',
                'invested_amount' => 20000,
                'current_value' => 25000,
                'investment_type' => 'crypto',
                'investment_date' => Carbon::now()->subMonths(3)
            ],
            [
                'investment_name' => 'محفظة الأسهم',
                'invested_amount' => 50000,
                'current_value' => 55000,
                'investment_type' => 'stocks',
                'investment_date' => Carbon::now()->subMonths(6)
            ]
        ];

        foreach ($investmentPlans as $plan) {
            \App\Models\InvestmentPlan::create([
                'user_id' => $user->id,
                'investment_name' => $plan['investment_name'],
                'invested_amount' => $plan['invested_amount'],
                'current_value' => $plan['current_value'],
                'investment_type' => $plan['investment_type'],
                'investment_date' => $plan['investment_date']
            ]);
        }
    }

    private function createLoans(User $user): void
    {
        // Loans only for admin
        if (!$user->hasRole('admin')) {
            return;
        }

        $loans = [
            [
                'bank_name' => 'بنك İş',
                'loan_type' => 'business',
                'amount' => 100000,
                'installments' => 24,
                'monthly_payment' => 5000,
                'remaining_installments' => 18,
                'start_date' => Carbon::now()->subMonths(6),
            ]
        ];

        foreach ($loans as $loan) {
            $startDate = $loan['start_date'];
            $monthlyPayment = $loan['monthly_payment'];
            $remainingInstallments = $loan['remaining_installments'];

            \App\Models\Loan::create([
                'user_id' => $user->id,
                'bank_name' => $loan['bank_name'],
                'loan_type' => $loan['loan_type'],
                'amount' => $loan['amount'],
                'monthly_payment' => $monthlyPayment,
                'installments' => $loan['installments'],
                'remaining_installments' => $remainingInstallments,
                'start_date' => $startDate,
                'next_payment_date' => Carbon::now()->addMonth()->startOfMonth(),
                'due_date' => $startDate->copy()->addMonths($loan['installments']),
                'remaining_amount' => $monthlyPayment * $remainingInstallments,
                'status' => 'pending',
                'notes' => 'قرض يستخدم لتمويل مصاريف العمل',
            ]);
        }
    }

    private function createTransactions(User $user): void
    {
        // Always use admin user's accounts and categories
        // --- Data Preparation (Before loop) ---
        // 1. Get required admin accounts and check
        $mainAccount = Account::where('user_id', $this->admin->id)->where('name', 'الحساب المصرفي الرئيسي')->first();
        $creditCard = Account::where('user_id', $this->admin->id)->where('type', Account::TYPE_CREDIT_CARD)->first();
        $cryptoWallet = Account::where('user_id', $this->admin->id)->where('type', Account::TYPE_CRYPTO_WALLET)->first();
        $virtualPos = Account::where('user_id', $this->admin->id)->where('type', Account::TYPE_VIRTUAL_POS)->first();
        $cashAccount = Account::where('user_id', $this->admin->id)->where('type', Account::TYPE_CASH)->first();

        if (!$mainAccount) {
            \Log::error('DemoDataSeeder: لم يتم العثور على الحساب المصرفي الرئيسي للمشرف!');
            return;
        }

        // 2. Get admin category IDs and check
        $adminIncomeCategoryIds = Category::where('user_id', $this->admin->id)->where('type', 'income')->pluck('id')->toArray();
        $adminExpenseCategoryIds = Category::where('user_id', $this->admin->id)->where('type', 'expense')->pluck('id')->toArray();

        if (empty($adminIncomeCategoryIds) || empty($adminExpenseCategoryIds)) {
            \Log::error('DemoDataSeeder: لم يتم العثور على فئات الدخل أو المصروف للمشرف!');
            return;
        }

        // 3. Get current user's customer IDs and check
        $customerIds = Customer::where('user_id', $user->id)->pluck('id')->toArray();
        if (empty($customerIds) && $user->id !== $this->admin->id) {
            \Log::warning("DemoDataSeeder: لم يتم العثور على عملاء للمستخدم {$user->id}، لن يتم إنشاء معاملات ايرادات.");
        }

        // --- Transaction Creation Loops ---
        $startDate = $this->startDate->copy();
        $endDate = Carbon::now();
        $oneMonthAgo = Carbon::now()->subMonth();
        $createdIncomeTransactionIdsLastMonth = [];

        while ($startDate <= $endDate) {
            // A. Credit Card Expense and Payment
            if ($creditCard && $mainAccount) {
                $expenseCatId = !empty($adminExpenseCategoryIds) ? $adminExpenseCategoryIds[array_rand($adminExpenseCategoryIds)] : null;
                if ($expenseCatId) {
                    $ccAmount = $this->faker->numberBetween(1000, 9999);
                    Transaction::create([
                        'user_id' => $user->id,
                        'category_id' => $expenseCatId,
                        'source_account_id' => $creditCard->id,
                        'destination_account_id' => null,
                        'type' => 'expense',
                        'amount' => $ccAmount,
                        'currency' => 'YER',
                        'exchange_rate' => 1,
                        'try_equivalent' => $ccAmount,
                        'date' => min($startDate->copy()->addDays($this->faker->numberBetween(1, 15)), $endDate),
                        'payment_method' => 'credit_card',
                        'description' => 'مصروف بطاقة ائتمان',
                        'status' => 'completed',
                    ]);

                    // Payment (End of the month)
                    $paymentDate = $startDate->copy()->endOfMonth()->subDays(rand(0, 5));
                    if ($paymentDate <= $endDate) {
                        Transaction::create([
                            'user_id' => $user->id,
                            'source_account_id' => $mainAccount->id,
                            'destination_account_id' => $creditCard->id,
                            'type' => 'payment',
                            'category_id' => null,
                            'amount' => $ccAmount,
                            'currency' => 'YER',
                            'exchange_rate' => 1,
                            'try_equivalent' => $ccAmount,
                            'date' => min($paymentDate, $endDate),
                            'payment_method' => 'bank',
                            'description' => 'دفع بطاقة ائتمان',
                            'status' => 'completed',
                        ]);
                    }
                }
            }

            // B. Customer Incomes (Only if customer exists)
            if (!empty($customerIds)) {
                $incomeCount = $this->faker->numberBetween(3, 8);
                for ($i = 0; $i < $incomeCount; $i++) {
                    $customerId = $customerIds[array_rand($customerIds)];
                    $incomeCatId = !empty($adminIncomeCategoryIds) ? $adminIncomeCategoryIds[array_rand($adminIncomeCategoryIds)] : null;

                    if ($customerId && $incomeCatId) {
                        $amount = $this->faker->numberBetween(500, 6000);
                        $paymentMethodType = $this->faker->randomElement(['bank', 'virtual_pos', 'cash']);
                        $transactionDate = min($startDate->copy()->addDays($this->faker->numberBetween(1, 28)), $endDate);

                        $sourceAccount = null;
                        $destAccount = null;
                        $paymentMethod = null;
                        $description = null;

                        if ($paymentMethodType === 'virtual_pos') {
                            if ($virtualPos && $mainAccount) {
                                $sourceAccount = $virtualPos;
                                $destAccount = $mainAccount;
                                $paymentMethod = 'virtual_pos';
                                $description = 'تحصيل عبر نقاط البيع الافتراضية';
                            } else {
                                continue;
                            }
                        } elseif ($paymentMethodType === 'cash') {
                            if ($cashAccount) {
                                $sourceAccount = $cashAccount;
                                $destAccount = null;
                                $paymentMethod = 'cash';
                                $description = 'تحصيل نقدًا';
                            } else {
                                continue;
                            }
                        } elseif ($paymentMethodType === 'bank') {
                            if ($mainAccount) {
                                $sourceAccount = $mainAccount;
                                $destAccount = null;
                                $paymentMethod = 'bank';
                                $description = 'تحصيل عبر حوالة/حوالة إلكترونية';
                            } else {
                                continue;
                            }
                        } else {
                            \Log::warning("DemoDataSeeder: نوع طريقة دفع غير متوقع: {$paymentMethodType}");
                            continue;
                        }

                        if ($sourceAccount) {
                            $transactionData = [
                                'user_id' => $user->id,
                                'category_id' => $incomeCatId,
                                'customer_id' => $customerId,
                                'source_account_id' => $sourceAccount->id,
                                'destination_account_id' => $destAccount ? $destAccount->id : null,
                                'type' => 'income',
                                'amount' => $amount,
                                'currency' => $sourceAccount->currency === 'USD' ? 'USD' : 'YER',
                                'exchange_rate' => $sourceAccount->currency === 'USD' ? 32 : 1,
                                'try_equivalent' => $sourceAccount->currency === 'USD' ? $amount * 32 : $amount,
                                'date' => $transactionDate,
                                'payment_method' => $paymentMethod,
                                'description' => $description,
                                'status' => 'completed',
                                'is_subscription' => false,
                            ];

                            if ($transactionDate >= $oneMonthAgo) {
                                $createdIncomeTransactionIdsLastMonth[] = Transaction::create($transactionData)->id;
                                continue;
                            }

                            Transaction::create($transactionData);
                        }
                    }
                }
            }

            // C. Fixed Expenses (Only for admin)
            if ($user->hasRole('admin') && $mainAccount) {
                $expenseCatId = !empty($adminExpenseCategoryIds) ? $adminExpenseCategoryIds[array_rand($adminExpenseCategoryIds)] : null;
                if ($expenseCatId) {
                    $expenses = [
                        ['name' => 'إيجار المكتب', 'amount' => 15000],
                        ['name' => 'فاتورة الكهرباء', 'amount' => [400, 800]],
                        ['name' => 'فاتورة المياه', 'amount' => [200, 400]],
                        ['name' => 'فاتورة الإنترنت', 'amount' => 500],
                        ['name' => 'فاتورة الهاتف', 'amount' => [300, 600]],
                        ['name' => 'خدمة التنظيف', 'amount' => 2000],
                    ];

                    foreach ($expenses as $expense) {
                        $amount = is_array($expense['amount'])
                            ? $this->faker->numberBetween($expense['amount'][0], $expense['amount'][1])
                            : $expense['amount'];

                        Transaction::create([
                            'user_id' => $user->id,
                            'category_id' => $expenseCatId,
                            'source_account_id' => $mainAccount->id,
                            'destination_account_id' => null,
                            'type' => 'expense',
                            'amount' => $amount,
                            'currency' => 'YER',
                            'exchange_rate' => 1,
                            'try_equivalent' => $amount,
                            'date' => min($startDate->copy()->addDays($this->faker->numberBetween(1, 28)), $endDate),
                            'payment_method' => 'bank',
                            'description' => $expense['name'],
                            'status' => 'completed'
                        ]);
                    }
                }
            }

            $startDate->addMonth();
        }

        // --- Set Subscriptions (After loop) ---
        if (!empty($createdIncomeTransactionIdsLastMonth)) {
            $subscriptionCount = $this->faker->numberBetween(5, 10);
            shuffle($createdIncomeTransactionIdsLastMonth);
            $subscriptionIds = array_slice($createdIncomeTransactionIdsLastMonth, 0, $subscriptionCount);

            if (!empty($subscriptionIds)) {
                $subscriptionsToUpdate = Transaction::whereIn('id', $subscriptionIds)->get();
                foreach ($subscriptionsToUpdate as $sub) {
                    $sub->update([
                        'is_subscription' => true,
                        'subscription_period' => 'monthly',
                        'auto_renew' => true,
                        'next_payment_date' => Carbon::parse($sub->date)->addMonth(),
                        'description' => $sub->description . ' (اشتراك شهري)'
                    ]);
                }
            }
        }

        // D. Crypto Transactions (Only for admin and if accounts exist)
        if ($user->hasRole('admin') && $cryptoWallet && $mainAccount) {
            $cryptoTransactions = [
                ['type' => 'expense', 'description' => 'شراء بيتكوين', 'amount' => 10000],
                ['type' => 'income', 'description' => 'بيع بيتكوين', 'amount' => 12000],
                ['type' => 'expense', 'description' => 'شراء إيثريوم', 'amount' => 5000],
                ['type' => 'income', 'description' => 'بيع إيثريوم', 'amount' => 6000],
            ];

            foreach ($cryptoTransactions as $index => $tx) {
                $date = Carbon::now()->subMonths($index + 1);
                $exchangeRate = $this->faker->randomFloat(2, 28, 32);
                $categoryId = null;

                if ($tx['type'] === 'income' && !empty($adminIncomeCategoryIds)) {
                    $categoryId = $adminIncomeCategoryIds[array_rand($adminIncomeCategoryIds)];
                } elseif ($tx['type'] === 'expense' && !empty($adminExpenseCategoryIds)) {
                    $categoryId = $adminExpenseCategoryIds[array_rand($adminExpenseCategoryIds)];
                }

                if ($categoryId) {
                    $transactionData = [
                        'user_id' => $user->id,
                        'category_id' => $categoryId,
                        'type' => $tx['type'],
                        'amount' => $tx['amount'],
                        'currency' => 'USD',
                        'exchange_rate' => $exchangeRate,
                        'try_equivalent' => $tx['amount'] * $exchangeRate,
                        'date' => $date,
                        'payment_method' => 'crypto',
                        'description' => $tx['description'],
                        'status' => 'completed'
                    ];

                    if ($tx['type'] === 'income') {
                        $transactionData['source_account_id'] = $cryptoWallet->id;
                        $transactionData['destination_account_id'] = $mainAccount->id;
                    } else {
                        $transactionData['source_account_id'] = $mainAccount->id;
                        $transactionData['destination_account_id'] = $cryptoWallet->id;
                    }

                    Transaction::create($transactionData);
                }
            }
        }
    }

    private function getPaymentMethod(string $accountType): string
    {
        return match ($accountType) {
            Account::TYPE_BANK_ACCOUNT => 'bank',
            Account::TYPE_CREDIT_CARD => 'credit_card',
            Account::TYPE_CRYPTO_WALLET => 'crypto',
            Account::TYPE_VIRTUAL_POS => 'virtual_pos',
            Account::TYPE_CASH => 'cash',
            default => 'bank',
        };
    }

    private function createCustomerNotes(User $user): void
    {
        $customers = \App\Models\Customer::where('user_id', $user->id)->get();
        $noteTypes = ['note', 'call', 'meeting', 'email', 'other'];

        $noteContents = [
            'note' => [
                'تم تقييم الحالة العامة مع العميل.',
                'تم إبلاغ العميل بعرض جديد.',
                'يجب إرسال تذكير بالدفع.',
                'تم إرسال استبيان رضا العميل.',
                'تم تدوين ملاحظات حول تحليل المنافسين.',
            ],
            'call' => [
                'تم إجراء مكالمة هاتفية مع العميل لمناقشة تفاصيل العرض.',
                'تم الاتصال لطلب دعم، وتم حل المشكلة.',
                'تم الاتصال لإبلاغه بحملة جديدة.',
                'تم الاتصال لتأكيد الموعد.',
                'تم مناقشة حالة التحصيل.',
            ],
            'meeting' => [
                'تم عقد اجتماع في مكتب العميل وتقديم عرض المشروع.',
                'تم عرض تجريبي عبر اجتماع افتراضي.',
                'تم عقد اجتماع استراتيجي وتحديد الخطوات التالية.',
                'تم تنفيذ اجتماع تقييم سنوي.',
                'تم عقد اجتماع حول فرص التعاون الجديدة.',
            ],
            'email' => [
                'تم إرسال العرض عبر البريد الإلكتروني.',
                'تم مشاركة ملخص الاجتماع عبر البريد الإلكتروني.',
                'تم إرسال مسودة العقد عبر البريد الإلكتروني.',
                'تم إرسال بريد إلكتروني إعلامي.',
                'تم إرسال رد طلب الدعم عبر البريد الإلكتروني.',
            ],
            'other' => [
                'تم الحضور في فعالية العميل.',
                'تم التفاعل عبر وسائل التواصل الاجتماعي.',
                'تم التحقق من المراجع.',
                'تم اللقاء أثناء زيارة المعرض.',
                'ملاحظات بحث عامة.',
            ],
        ];

        foreach ($customers as $customer) {
            $noteCount = $this->faker->numberBetween(3, 5);
            for ($i = 0; $i < $noteCount; $i++) {
                $type = $this->faker->randomElement($noteTypes);
                $activityDate = $this->faker->boolean(70)
                    ? $this->faker->dateTimeBetween('-6 months', 'now')
                    : $this->faker->dateTimeBetween('now', '+1 month');

                $content = isset($noteContents[$type]) && !empty($noteContents[$type])
                    ? $this->faker->randomElement($noteContents[$type])
                    : $this->faker->sentence;

                \App\Models\CustomerNote::create([
                    'customer_id' => $customer->id,
                    'user_id' => $user->id,
                    'assigned_user_id' => $this->faker->boolean(30) ? $user->id : null,
                    'content' => $content,
                    'type' => $type,
                    'activity_date' => $activityDate,
                ]);
            }
        }
    }

    private function createCustomerAgreements(User $user): void
    {
        $customers = \App\Models\Customer::where('user_id', $user->id)->get();
        foreach ($customers as $customer) {
            if ($this->faker->boolean(70)) {
                $agreementCount = $this->faker->numberBetween(1, 3);
                for ($i = 0; $i < $agreementCount; $i++) {
                    $startDate = $this->faker->dateTimeBetween('-1 year', 'now');
                    $amount = $this->faker->numberBetween(5000, 50000);
                    \App\Models\CustomerAgreement::create([
                        'user_id' => $user->id,
                        'customer_id' => $customer->id,
                        'name' => $this->faker->randomElement([
                            'اتفاقية صيانة شهرية',
                            'مشروع تطوير برمجي',
                            'خدمة استشارية',
                            'حزمة دعم فني',
                            'خدمة تحسين محركات البحث (SEO)'
                        ]),
                        'description' => $this->faker->sentence(10),
                        'amount' => $amount,
                        'start_date' => $startDate,
                        'next_payment_date' => Carbon::parse($startDate)->addMonth(),
                        'status' => $this->faker->randomElement(['active', 'completed', 'cancelled'])
                    ]);
                }
            }
        }
    }

    private function createCommissionPayouts(User $employee): void
    {
        // Get required admin category and account for payment
        $adminExpenseCategory = Category::where('user_id', $this->admin->id)
            ->where('type', 'expense')
            ->where('name', 'مصاريف الموظفين')
            ->first();

        $adminMainAccount = Account::where('user_id', $this->admin->id)
            ->where('name', 'الحساب المصرفي الرئيسي')
            ->first();

        if (!$adminExpenseCategory || !$adminMainAccount) {
            \Log::warning('DemoDataSeeder: لم يتم العثور على فئة المصروف أو الحساب المطلوب لدفع العمولة.');
            return;
        }

        // Target last two months
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();
        $previousMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $previousMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // Get employee's commission rate (default %5)
        $commissionRate = $employee->commission_rate ? ($employee->commission_rate / 100) : 0.05;

        // 1. Previous Month Commission (Full Payment)
        $previousMonthCommission = Transaction::where('user_id', $employee->id)
            ->where('type', 'income')
            ->whereBetween('date', [$previousMonthStart, $previousMonthEnd])
            ->sum('amount') * $commissionRate;

        if ($previousMonthCommission > 0) {
            $paymentDate = $currentMonthStart->copy()->addDays(5);

            \App\Models\CommissionPayout::create([
                'user_id' => $employee->id,
                'amount' => $previousMonthCommission,
                'payment_date' => $paymentDate,
            ]);

            Transaction::create([
                'user_id' => $employee->id,
                'type' => 'expense',
                'category_id' => $adminExpenseCategory->id,
                'source_account_id' => $adminMainAccount->id,
                'destination_account_id' => null,
                'amount' => $previousMonthCommission,
                'currency' => 'YER',
                'exchange_rate' => 1,
                'try_equivalent' => $previousMonthCommission,
                'date' => $paymentDate,
                'payment_method' => 'bank',
                'description' => $previousMonthStart->format('F Y') . ' دفع عمولة الفترة (كاملة)',
                'status' => 'completed',
            ]);
        }

        // 2. Current Month Commission (Half Payment)
        $currentMonthCommission = Transaction::where('user_id', $employee->id)
            ->where('type', 'income')
            ->whereBetween('date', [$currentMonthStart, $currentMonthEnd])
            ->sum('amount') * $commissionRate;

        if ($currentMonthCommission > 0) {
            $amountToPay = round($currentMonthCommission / 2, 2);
            $paymentDate = $currentMonthEnd->copy()->addDays(5);

            \App\Models\CommissionPayout::create([
                'user_id' => $employee->id,
                'amount' => $amountToPay,
                'payment_date' => $paymentDate,
            ]);

            Transaction::create([
                'user_id' => $employee->id,
                'type' => 'expense',
                'category_id' => $adminExpenseCategory->id,
                'source_account_id' => $adminMainAccount->id,
                'destination_account_id' => null,
                'amount' => $amountToPay,
                'currency' => 'YER',
                'exchange_rate' => 1,
                'try_equivalent' => $amountToPay,
                'date' => $paymentDate,
                'payment_method' => 'bank',
                'description' => $currentMonthStart->format('F Y') . ' دفع عمولة الفترة (نصف المبلغ)',
                'status' => 'completed',
            ]);
        }
    }
}