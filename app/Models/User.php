<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * 管理画面の利用者。会員機能は無いため、users は管理者専用。
 */
#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner;
    }

    /** SEO・SNS・予約・メール・サイト基本設定・ユーザー管理に入れるか。 */
    public function canManageSettings(): bool
    {
        return $this->role->canManageSettings();
    }

    public function roleLabel(): string
    {
        return $this->role->label();
    }
}
