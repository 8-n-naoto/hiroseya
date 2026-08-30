<?php

namespace App\Models;

use App\Enums\ContactStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'name_kana', 'email', 'tel', 'subject', 'body',
    'status', 'status_changed_at', 'assigned_to', 'admin_memo',
    'ip_address', 'user_agent',
])]
class Contact extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => ContactStatus::class,
            'status_changed_at' => 'datetime',
        ];
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ContactReply::class)->orderBy('created_at');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ContactStatus::Pending->value);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ContactStatus::Pending->value,
            ContactStatus::InProgress->value,
        ]);
    }

    public function changeStatus(ContactStatus $status): void
    {
        $this->forceFill([
            'status' => $status,
            'status_changed_at' => now(),
        ])->save();
    }

    /** 送信済みの返信が 1 件でもあるか。 */
    public function hasSentReply(): bool
    {
        return $this->replies()->whereNotNull('sent_at')->exists();
    }
}
