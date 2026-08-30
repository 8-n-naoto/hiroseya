<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * 臨時休業・時間変更。business_hours より優先される。
 */
#[Fillable(['date', 'is_closed', 'opens_at', 'closes_at', 'note'])]
class SpecialDay extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_closed' => 'boolean',
        ];
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('date', '>=', today())->orderBy('date');
    }
}
