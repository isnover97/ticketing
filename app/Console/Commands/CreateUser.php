<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateUser extends Command
{
    protected $signature = 'helpdesk:create-user';
    protected $description = 'Create a helpdesk account with a securely hashed password';

    public function handle(): int
    {
        $data = [
            'name' => $this->ask('Full name'),
            'email' => strtolower(trim((string) $this->ask('Email address'))),
            'role' => ['Client' => 'user', 'System Developer' => 'agent', 'Administrator' => 'admin'][
                $this->choice('Account role', ['Client', 'System Developer', 'Administrator'], 0)
            ],
            'password' => $this->secret('Password (at least 12 characters)'),
            'password_confirmation' => $this->secret('Confirm password'),
        ];
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => 'required|in:user,agent,admin',
            'password' => 'required|string|min:12|confirmed',
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) $this->error($error);
            return self::FAILURE;
        }
        unset($data['password_confirmation']);
        User::create($data);
        $this->info('Account created. Sign in at /login.');
        return self::SUCCESS;
    }
}
