<?php

namespace App\Livewire\Auth;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Register')]
class Register extends Component
{
    public string $company_name = '';

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register()
    {
        $this->validate([
            'company_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        DB::transaction(function () {
            $company = Company::create([
                'name' => $this->company_name,
                'slug' => Str::slug($this->company_name),
                'email' => $this->email,
                'phone' => '',
                'plan' => 'starter',
                'is_active' => true,
            ]);

            $user = User::create([
                'company_id' => $company->id,
                'name' => $this->name,
                'email' => $this->email,
                'password' => bcrypt($this->password),
                'role' => UserRole::Owner,
            ]);

            Auth::login($user);
        });

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('pages::auth.register')
            ->layout('layouts.auth', ['title' => __('Register')]);
    }
}
