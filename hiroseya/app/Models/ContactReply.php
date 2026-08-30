<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['contact_id', 'user_id', 'body', 'to_email', 'sent_at', 'error_message'])]
class ContactReply extends Model
{
    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wasSent(): bool
    {
        return $this->sent_at !== null;
    }

    /** 送信に失敗した返信。管理画面で必ず目立たせること。 */
    public function hasFailed(): bool
    {
        return $this->sent_at === null && filled($this->error_message);
    }
}
