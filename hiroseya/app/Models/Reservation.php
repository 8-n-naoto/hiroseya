<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * 予約（リクエスト承認制）。
 *
 * お客様の送信は仮予約であり、店舗が承認して初めて確定になる。
 * 変更・キャンセルは電話のみのため、reservation_no を発行してメールに記載し、
 * 電話口での照合に使う。
 */
#[Fillable([
    'reservation_no', 'name', 'name_kana', 'tel', 'email',
    'reserved_date', 'reserved_time', 'party_size', 'request',
    'status', 'status_changed_at', 'handled_by', 'admin_memo',
    'ip_address', 'user_agent',
])]
class Reservation extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => ReservationStatus::class,
            'reserved_date' => 'date',
            'status_changed_at' => 'datetime',
            'party_size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $reservation): void {
            $reservation->reservation_no ??= self::generateNumber();
        });
    }

    /**
     * 予約番号。「260901-7K3Q」のように 日付 + 英数4文字 で発行する。
     * 電話口で読み上げるため、紛らわしい 0/O と 1/I は使わない。
     */
    public static function generateNumber(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

        do {
            $suffix = '';
            for ($i = 0; $i < 4; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $number = now()->format('ymd').'-'.$suffix;
        } while (static::withTrashed()->where('reservation_no', $number)->exists());

        return $number;
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /*
    |--------------------------------------------------------------------------
    | スコープ
    |--------------------------------------------------------------------------
    */

    /** 未確認。ダッシュボードの「未確認の予約 n件」に使う。 */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ReservationStatus::Pending->value);
    }

    /** 枠の受付人数を消費する予約（未確認 + 確定）。 */
    public function scopeOccupying(Builder $query): Builder
    {
        return $query->whereIn('status', ReservationStatus::occupyingValues());
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('reserved_date', $date);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('reserved_date', '>=', today())
            ->orderBy('reserved_date')
            ->orderBy('reserved_time');
    }

    /** 電話を受けながら探すため、電話番号は数字だけで照合できるようにする。 */
    public function scopeSearchTel(Builder $query, string $tel): Builder
    {
        $digits = preg_replace('/[^0-9]/', '', $tel);

        return $query->where('tel', 'like', '%'.$digits.'%');
    }

    /*
    |--------------------------------------------------------------------------
    | 状態変更
    |--------------------------------------------------------------------------
    */

    public function changeStatus(ReservationStatus $status, ?int $userId = null): void
    {
        $this->forceFill([
            'status' => $status,
            'status_changed_at' => now(),
            'handled_by' => $userId ?? auth()->id() ?? $this->handled_by,
        ])->save();
    }

    public function isPending(): bool
    {
        return $this->status === ReservationStatus::Pending;
    }

    public function isConfirmed(): bool
    {
        return $this->status === ReservationStatus::Confirmed;
    }

    /** "2026年9月1日(火) 18:00 / 4名" の形。 */
    public function summaryLabel(): string
    {
        $day = BusinessHour::DAY_LABELS[(int) $this->reserved_date->dayOfWeek] ?? '';

        return sprintf(
            '%s(%s) %s / %d名',
            $this->reserved_date->format('Y年n月j日'),
            $day,
            substr((string) $this->reserved_time, 0, 5),
            $this->party_size,
        );
    }

    public function timeLabel(): string
    {
        return substr((string) $this->reserved_time, 0, 5);
    }

    public function maskedEmail(): string
    {
        return Str::mask($this->email, '*', 2, max(1, strpos($this->email, '@') - 3));
    }
}
