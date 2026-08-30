<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * 管理画面の初期ユーザー。
 *
 * パスワードは開発用の仮の値。
 * 本番では必ず変更し、この Seeder は本番で実行しないこと。
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'owner@example.com'],
            [
                'name' => '管理者',
                'password' => Hash::make('password'),
                'role' => UserRole::Owner,
                'is_active' => true,
            ],
        );

        User::firstOrCreate(
            ['email' => 'editor@example.com'],
            [
                'name' => '編集者',
                'password' => Hash::make('password'),
                'role' => UserRole::Editor,
                'is_active' => true,
            ],
        );
    }
}
