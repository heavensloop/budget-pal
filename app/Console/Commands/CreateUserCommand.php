<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:create-user {email?} {password?} {name?}')]
#[Description('Command description')]
class CreateUserCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::factory()->create([
            'email' => $this->getEmail(),
            'password' => bcrypt($this->getPassword()),
            'name' => $this->getUserName(),
        ]);
    }

    private function getEmail(): string
    {
        if ($this->argument('email')) {
            return $this->argument('email');
        }
        return $this->ask('Enter email');
    }

    private function getPassword(): string
    {
        if ($this->argument('password')) {
            return $this->argument('password');
        }
        return $this->secret('Enter password');
    }

    private function getUserName(): string
    {
        if ($this->argument('name')) {
            return $this->argument('name');
        }

        return $this->ask('Enter name');
    }
}
