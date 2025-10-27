<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">مدفوعات الاشتراكات القادمة</h3>
        
        @if($subscriptions->isEmpty())
            <p class="text-gray-500">لا توجد مدفوعات اشتراك قادمة.</p>
        @else
            <div class="space-y-4">
                @foreach($subscriptions as $subscription)
                    <div class="flex justify-between items-center border-b pb-3">
                        <div>
                            <p class="font-medium">{{ $subscription->description }}</p>
                            <p class="text-sm text-gray-500">
                                {{ $subscription->type === 'income' ? 'إيرادات' : 'مصروفات' }} | 
                                {{ match($subscription->subscription_period) {
                                    'weekly' => 'أسبوعي',
                                    'monthly' => 'شهري',
                                    'yearly' => 'سنوي',
                                    default => $subscription->subscription_period,
                                } }}
                            </p>
                            <p class="text-sm text-gray-500">
                                الدفع التالي: {{ $subscription->next_payment_date->format('d.m.Y') }}
                            </p>
                        </div>
                        <div>
                            <span class="text-lg font-bold">
                                {{ $subscription->currency === 'YER' ? '$' : $subscription->currency }}
                                {{ number_format($subscription->amount, 2) }}
                            </span>
                            <a href="{{ route('admin.transactions.create', [
                                'type' => $subscription->type,
                                'amount' => $subscription->amount,
                                'category_id' => $subscription->category_id,
                                'description' => $subscription->description,
                                'currency' => $subscription->currency,
                            ]) }}" class="ml-2 text-primary-600 hover:text-primary-800">
                                <x-heroicon-o-plus-circle class="w-5 h-5 inline" />
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div> 