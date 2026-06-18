<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MakeSuperAdmin extends Command
{
    protected $signature = 'wakeel:make-super-admin
                            {--name= : Name of the super-admin}
                            {--email= : Email address}
                            {--password= : Password (prompted if omitted)}';

    protected $description = 'Create a super-admin user for the Filamen admin panel';

    public function handle(): int
    {
        $name = $this->option('name') ?? $this->ask('Name');
        $email = $this->option('email') ?? $this->ask('Email address');
        $password = $this->option('password') ?? $this->secret('Password (leave blank to generate)');

        if (empty($password)) {
            $password = Str::password(16);
            $this->info("Generated password: {$password}");
        }

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return Command::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_super_admin' => true,
            'company_id' => null,
        ]);

        $this->info("Super-admin created: {$user->email}");

        return Command::SUCCESS;
    }
}
