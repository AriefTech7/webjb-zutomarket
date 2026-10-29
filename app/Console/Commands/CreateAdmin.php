<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Buat akun administrator toko';

    public function handle(): int
    {
        $data = ['name' => $this->ask('Nama admin'), 'username' => $this->ask('Username'), 'email' => $this->ask('Email'), 'password' => $this->secret('Password (minimal 12 karakter)')];
        $validator = Validator::make($data, ['name' => 'required|max:100', 'username' => 'required|alpha_dash|max:50|unique:users', 'email' => 'required|email|unique:users', 'password' => 'required|min:12']);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }
        User::create($data);
        $this->info('Admin berhasil dibuat. Login melalui /admin/login.');

        return self::SUCCESS;
    }
}
