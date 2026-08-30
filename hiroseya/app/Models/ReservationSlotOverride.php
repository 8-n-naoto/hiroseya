<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * 特定日の予約枠の上書き（貸切・満席・臨時休業・時間変更）。
 * starts_at が null の行はその日全体への指定。
 */
#[Fillable(['date', 'starts_at', 'capacity', 'is_closed', 'note'])]
class ReservationSlotOverride extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'capacity' => 'integer',
            'is_closed' => 'boolean',
        ];
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    /**
     * その日全体への指定。
     *
     * UNIQUE 制約は NULL を重複として扱わないため、
     * 「その日全体」の行が複数できないことはここで担保する。
     */
    public static function forWholeDay(string $date): ?self
    {
        return static::whereDate('date', $date)->whereNull('starts_at')->first();
    }

    public static function upsertWholeDay(string $date, bool $isClosed, ?string $note = null): self
    {
        $row = static::forWholeDay($date) ?? new self(['date' => $date, 'starts_at' => null]);

        $row->fill(['is_closed' => $isClosed, 'note' => $note])->save();

        return $row;
    }
}
