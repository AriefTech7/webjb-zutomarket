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
        // is_admin is not fillable, so it is set explicitly. The first admin of a fresh store is its super admin.
        $isFirst = ! User::where('is_super_admin', true)->exists();
        User::create($data)->forceFill(['is_admin' => true, 'is_super_admin' => $isFirst])->save();
        $this->info(($isFirst ? 'Super admin' : 'Admin').' berhasil dibuat. Login melalui /admin/login.');

        return self::SUCCESS;
    }
}
