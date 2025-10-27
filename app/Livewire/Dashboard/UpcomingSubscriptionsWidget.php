<?php

namespace App\Livewire\Dashboard;

use App\Models\Transaction;
use Livewire\Component;

/**
 * Upcoming Subscriptions Widget Component
 * 
 * This component provides functionality to display upcoming subscriptions on the dashboard.
 * Features:
 * - Display upcoming subscriptions
 */
class UpcomingSubscriptionsWidget extends Component
{
    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        $upcomingSubscriptions = Transaction::where('is_subscription', true)
            ->where('next_payment_date', '<=', now()->addDays(30))
            ->orderBy('next_payment_date')
            ->take(5)
            ->get();
        
        return view('livewire.dashboard.upcoming-subscriptions-widget', [
            'subscriptions' => $upcomingSubscriptions
        ]);
    }
} 