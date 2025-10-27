<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;

final class Login extends Component
{
    public string $email = '';
    public string $password = '';

    // validate
    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|min:8',
    ];

    protected $messages = [
        'email.required' => 'حقل البريد الإلكتروني مطلوب.',
        'email.email' => 'يرجى إدخال عنوان بريد إلكتروني صحيح.',
        'password.required' => 'حقل كلمة المرور مطلوب.',
        'password.min' => 'يجب أن تكون كلمة المرور 8 أحرف على الأقل.',
    ];

    public function mount(): void
    {
        if (Auth::check()) {
            $this->redirect(route('admin.dashboard'));
        }
    }

    public function submit()
    {
        $credentials = $this->validate();

        if (Auth::attempt($credentials)) {
            session()->regenerate();
            
            $this->dispatch('loginSuccess');
            
            Notification::make()
                ->title(__('تم تسجيل الدخول بنجاح'))
                ->success()
                ->send();
                
            return redirect()->intended(route('admin.dashboard'));
        }
        
        Notification::make()
            ->title(__('فشل في تسجيل الدخول'))
            ->danger()
            ->send();
            
        $this->resetPasswordField();
    }

    public function resetPasswordField(): void
    {
        $this->password = '';
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
} 