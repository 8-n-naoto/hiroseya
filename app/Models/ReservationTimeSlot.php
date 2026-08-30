<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * 予約枠の基本パターン（曜日別）。
 * capacity は在庫ではなく「Webで受け付ける上限」。
 */
#[Fillable(['day_of_week', 'starts_at', 'capacity', 'is_active', 'sort_order'])]
class ReservationTimeSlot extends Model
{
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'capacity' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForDay(Builder $query, int $dayOfWeek): Builder
    {
        return $query->where('day_of_week', $dayOfWeek)->orderBy('starts_at');
    }

    public function dayLabel(): string
    {
        return BusinessHour::DAY_LABELS[$this->day_of_week] ?? '';
    }

    /** "11:00" の形。 */
    public function timeLabel(): string
    {
        return substr((string) $this->starts_at, 0, 5);
    }
}
