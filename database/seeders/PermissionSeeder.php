<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing permissions
        Schema::disableForeignKeyConstraints();
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('permissions')->truncate();
        Schema::enableForeignKeyConstraints();

        // 1. Customer Management Permissions
        // 1.1 Customer CRUD
        Permission::create(['name' => 'customers.view', 'guard_name' => 'web', 'display_name' => 'عرض العملاء']);
        Permission::create(['name' => 'customers.create', 'guard_name' => 'web', 'display_name' => 'أنشاء عميل']);
        Permission::create(['name' => 'customers.edit', 'guard_name' => 'web', 'display_name' => 'تعديل العملاء']);
        Permission::create(['name' => 'customers.delete', 'guard_name' => 'web', 'display_name' => 'حذف العملاء']);
        Permission::create(['name' => 'customers.detail', 'guard_name' => 'web', 'display_name' => 'تفاصيل العميل']);
        Permission::create(['name' => 'customers.credentials', 'guard_name' => 'web', 'display_name' => 'معلومات العملاء الحساسة']);
        Permission::create(['name' => 'customers.agreements', 'guard_name' => 'web', 'display_name' => 'اتفاقيات العملاء']);

        // 1.2 Customer Group CRUD
        Permission::create(['name' => 'customer_groups.view', 'guard_name' => 'web', 'display_name' => 'مجموعات العملاء']);
        Permission::create(['name' => 'customer_groups.create', 'guard_name' => 'web', 'display_name' => 'أنشاء مجموعات العملاء']);
        Permission::create(['name' => 'customer_groups.edit', 'guard_name' => 'web', 'display_name' => 'تعديل مجموعات العملاء']);
        Permission::create(['name' => 'customer_groups.delete', 'guard_name' => 'web', 'display_name' => 'حذف مجموعات العملاء']);

        // 1.3 Lead CRUD
        Permission::create(['name' => 'leads.view', 'guard_name' => 'web', 'display_name' => 'عرض العملاء المحتملين']);
        Permission::create(['name' => 'leads.create', 'guard_name' => 'web', 'display_name' => 'أنشاء عرض العملاء المحتملين']);
        Permission::create(['name' => 'leads.edit', 'guard_name' => 'web', 'display_name' => 'تعديل عرض العملاء المحتملين']);
        Permission::create(['name' => 'leads.delete', 'guard_name' => 'web', 'display_name' => 'حذف عرض العملاء المحتملين']);
        Permission::create(['name' => 'leads.convert_customer', 'guard_name' => 'web', 'display_name' => 'تحويل إلى مخصصr']);

        // 2. Project CRUD
        Permission::create(['name' => 'projects.view', 'guard_name' => 'web', 'display_name' => 'عرض المشاريع']);
        Permission::create(['name' => 'projects.details', 'guard_name' => 'web', 'display_name' => 'تفاصيل المشروع']);
        Permission::create(['name' => 'projects.create', 'guard_name' => 'web', 'display_name' => 'إنشاء المشروع']);
        Permission::create(['name' => 'projects.edit', 'guard_name' => 'web', 'display_name' => 'تعديل المشروع']);
        Permission::create(['name' => 'projects.delete', 'guard_name' => 'web', 'display_name' => 'حذف المشروع']);

        // 3. Account Management Permissions
        // 3.1 Bank Account CRUD
        Permission::create(['name' => 'bank_accounts.view', 'guard_name' => 'web', 'display_name' => 'عرض الحسابات البنكية']);
        Permission::create(['name' => 'bank_accounts.create', 'guard_name' => 'web', 'display_name' => 'أنشاء الحسابات البنكية']);
        Permission::create(['name' => 'bank_accounts.edit', 'guard_name' => 'web', 'display_name' => 'تعديل الحسابات البنكية']);
        Permission::create(['name' => 'bank_accounts.delete', 'guard_name' => 'web', 'display_name' => 'حذف الحسابات البنكية']);
        Permission::create(['name' => 'bank_accounts.history', 'guard_name' => 'web', 'display_name' => 'تاريخ حساب البنك']);
        Permission::create(['name' => 'bank_accounts.transactions', 'guard_name' => 'web', 'display_name' => 'عمليات حسابات البنك']);
        Permission::create(['name' => 'bank_accounts.transfers', 'guard_name' => 'web', 'display_name' => 'التحويلات المصرفية']);

        // 3.2 Credi Card CRUD
        Permission::create(['name' => 'credit_cards.view', 'guard_name' => 'web', 'display_name' => 'عرض بطاقات الائتمان']);
        Permission::create(['name' => 'credit_cards.create', 'guard_name' => 'web', 'display_name' => 'إنشاء بطاقة الائتمان']);
        Permission::create(['name' => 'credit_cards.edit', 'guard_name' => 'web', 'display_name' => 'تعديل بطاقة الائتمان']);
        Permission::create(['name' => 'credit_cards.delete', 'guard_name' => 'web', 'display_name' => 'حذف بطاقة الائتمان']);
        Permission::create(['name' => 'credit_cards.history', 'guard_name' => 'web', 'display_name' => 'تاريخ بطاقة الائتمان']);
        Permission::create(['name' => 'credit_cards.payments', 'guard_name' => 'web', 'display_name' => 'مدفوعات بطاقات الائتمان']);

        // 3.3 Crypto Wallet CRUD
        Permission::create(['name' => 'crypto_wallets.view', 'guard_name' => 'web', 'display_name' => 'عرض محافظ العملات المشفرة']);
        Permission::create(['name' => 'crypto_wallets.create', 'guard_name' => 'web', 'display_name' => 'أنشاء محافظ العملات المشفرة']);
        Permission::create(['name' => 'crypto_wallets.edit', 'guard_name' => 'web', 'display_name' => 'تعديل محافظ العملات المشفرة']);
        Permission::create(['name' => 'crypto_wallets.delete', 'guard_name' => 'web', 'display_name' => 'حذف محافظ العملات المشفرة']);
        Permission::create(['name' => 'crypto_wallets.transfer', 'guard_name' => 'web', 'display_name' => 'تحويل محفظة العملات المشفرة']);

        // 3.4 Virtual POS CRUD
        Permission::create(['name' => 'virtual_pos.view', 'guard_name' => 'web', 'display_name' => 'عرض نقاط البيع الافتراضية']);
        Permission::create(['name' => 'virtual_pos.create', 'guard_name' => 'web', 'display_name' => 'أنشاء نقاط البيع الافتراضية']);
        Permission::create(['name' => 'virtual_pos.edit', 'guard_name' => 'web', 'display_name' => 'يحرر نقاط البيع الافتراضية']);
        Permission::create(['name' => 'virtual_pos.delete', 'guard_name' => 'web', 'display_name' => 'يمسح نقاط البيع الافتراضية']);
        Permission::create(['name' => 'virtual_pos.transfer', 'guard_name' => 'web', 'display_name' => 'التحويلات لنقاط البيع الافتراضية']);

        // 4. Financial Transaction Permissions
        // 4.1 Loan CRUD
        Permission::create(['name' => 'loans.view', 'guard_name' => 'web', 'display_name' => 'عرض الاعتمادات']);
        Permission::create(['name' => 'loans.create', 'guard_name' => 'web', 'display_name' => 'أنشاء ائتمان']);
        Permission::create(['name' => 'loans.edit', 'guard_name' => 'web', 'display_name' => 'تعديل الائتمان']);
        Permission::create(['name' => 'loans.delete', 'guard_name' => 'web', 'display_name' => 'حذف الائتمان']);
        Permission::create(['name' => 'loans.payments', 'guard_name' => 'web', 'display_name' => 'مدفوعات القروض']);

        // 4.2 Borç/Alacak CRUD
        Permission::create(['name' => 'debts.view', 'guard_name' => 'web', 'display_name' => 'عرض المدينين/الائتمانات']);
        Permission::create(['name' => 'debts.create', 'guard_name' => 'web', 'display_name' => 'إنشاء الديون/المستحقات']);
        Permission::create(['name' => 'debts.edit', 'guard_name' => 'web', 'display_name' => 'تحرير الديون/المستحقات']);
        Permission::create(['name' => 'debts.delete', 'guard_name' => 'web', 'display_name' => 'حذف الديون/المستحقات']);

        // 4.3 Transaction CRUD
        Permission::create(['name' => 'transactions.view', 'guard_name' => 'web', 'display_name' => 'عرض المعاملات']);
        Permission::create(['name' => 'transactions.create', 'guard_name' => 'web', 'display_name' => 'إنشاء معاملة']);
        Permission::create(['name' => 'transactions.edit', 'guard_name' => 'web', 'display_name' => 'تعديل معاملة']);
        Permission::create(['name' => 'transactions.delete', 'guard_name' => 'web', 'display_name' => 'حذف معاملة']);

        // 4.3.2 Recurring Transactions
        Permission::create(['name' => 'recurring_transactions.view', 'guard_name' => 'web', 'display_name' => 'عرض العملية']);
        Permission::create(['name' => 'recurring_transactions.copy', 'guard_name' => 'web', 'display_name' => 'نسخ العملية']);
        Permission::create(['name' => 'recurring_transactions.complete', 'guard_name' => 'web', 'display_name' => 'استمرار العمليه']);

        // 5. Analysis and Reporting Permissions
        Permission::create(['name' => 'reports.cash_flow', 'guard_name' => 'web', 'display_name' => 'تقرير الدفق نقدا']);
        Permission::create(['name' => 'reports.category_analysis', 'guard_name' => 'web', 'display_name' => 'فئة تحليل']);
        
        // 5.1.1 Savings Plan View
        Permission::create(['name' => 'savings.view', 'guard_name' => 'web', 'display_name' => 'عرض خطة الادخار']);
        Permission::create(['name' => 'savings.create', 'guard_name' => 'web', 'display_name' => 'أنشاء خطة الادخار']);
        Permission::create(['name' => 'savings.edit', 'guard_name' => 'web', 'display_name' => 'تعديل خطة الادخار']);
        Permission::create(['name' => 'savings.delete', 'guard_name' => 'web', 'display_name' => 'حذف خطة الادخار']);

        // 5.1.2 Investment Plan View
        Permission::create(['name' => 'investments.view', 'guard_name' => 'web', 'display_name' => 'عرض خطة الاستثمار']);
        Permission::create(['name' => 'investments.create', 'guard_name' => 'web', 'display_name' => 'أنشاء خطة الاستثمار']);
        Permission::create(['name' => 'investments.edit', 'guard_name' => 'web', 'display_name' => 'تحرير خطة الاستثمار']);
        Permission::create(['name' => 'investments.delete', 'guard_name' => 'web', 'display_name' => 'حذف خطة الاستثمار']);

        // 6. Category CRUD
        Permission::create(['name' => 'categories.view', 'guard_name' => 'web', 'display_name' => 'عرض الفئات']);
        Permission::create(['name' => 'categories.create', 'guard_name' => 'web', 'display_name' => 'أنشاء الفئات ']);
        Permission::create(['name' => 'categories.edit', 'guard_name' => 'web', 'display_name' => 'تعديل الفئات ']);
        Permission::create(['name' => 'categories.delete', 'guard_name' => 'web', 'display_name' => 'حذف الفئات ']);

        // 6.1 Suppliers CRUD
        Permission::create(['name' => 'suppliers.view', 'guard_name' => 'web', 'display_name' => 'عرض الموردين']);
        Permission::create(['name' => 'suppliers.create', 'guard_name' => 'web', 'display_name' => 'أنشاء موردين']);
        Permission::create(['name' => 'suppliers.edit', 'guard_name' => 'web', 'display_name' => 'تعديل الموردين']);
        Permission::create(['name' => 'suppliers.delete', 'guard_name' => 'web', 'display_name' => 'حذف  الموردين']);

        // 7. System Management CRUD
        Permission::create(['name' => 'settings.view', 'guard_name' => 'web', 'display_name' => 'عرض الإعدادات']);
        Permission::create(['name' => 'settings.site', 'guard_name' => 'web', 'display_name' => 'إعدادات الموقع']);
        Permission::create(['name' => 'settings.notification', 'guard_name' => 'web', 'display_name' => 'إعدادات الإخطار']);
        Permission::create(['name' => 'settings.telegram', 'guard_name' => 'web', 'display_name' => 'إعدادات التليجرام']);
        
        Permission::create(['name' => 'roles.view', 'guard_name' => 'web', 'display_name' => 'عرض الأدوار']);
        Permission::create(['name' => 'roles.create', 'guard_name' => 'web', 'display_name' => 'أنشاء الأدوار']);
        Permission::create(['name' => 'roles.edit', 'guard_name' => 'web', 'display_name' => 'تعديل الأدوار']);
        Permission::create(['name' => 'roles.delete', 'guard_name' => 'web', 'display_name' => 'حذف الأدوار']);

        Permission::create(['name' => 'users.view', 'guard_name' => 'web', 'display_name' => 'عرض المستخدمين']);
        Permission::create(['name' => 'users.create', 'guard_name' => 'web', 'display_name' => 'أنشاء المستخدمين']);
        Permission::create(['name' => 'users.edit', 'guard_name' => 'web', 'display_name' => 'تعديل المستخدمين']);
        Permission::create(['name' => 'users.delete', 'guard_name' => 'web', 'display_name' => 'حذف المستخدمين']);

        Permission::create(['name' => 'users.commissions', 'guard_name' => 'web', 'display_name' => 'عرض اللجان']);
        Permission::create(['name' => 'users.commission.payment', 'guard_name' => 'web', 'display_name' => 'دفع العمولة']);
        Permission::create(['name' => 'users.change_password', 'guard_name' => 'web', 'display_name' => 'تغيير كلمة المرور']);
    }
} 