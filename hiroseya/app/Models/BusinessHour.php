<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['day_of_week', 'opens_at', 'closes_at', 'is_closed', 'label', 'sort_order'])]
class BusinessHour extends Model
{
    /** 0=日曜。JSON-LD の openingHoursSpecification に使う略号と対で持つ。 */
    public const DAY_LABELS = ['日', '月', '火', '水', '木', '金', '土'];

    public const SCHEMA_DAYS = [
        'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_closed' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function dayLabel(): string
    {
        return self::DAY_LABELS[$this->day_of_week] ?? '';
    }

    public function schemaDay(): string
    {
        return self::SCHEMA_DAYS[$this->day_of_week] ?? '';
    }

    /** "11:00〜14:00" の形。定休日なら「定休日」。 */
    public function rangeLabel(): string
    {
        if ($this->is_closed) {
            return '定休日';
        }

        return substr((string) $this->opens_at, 0, 5).'〜'.substr((string) $this->closes_at, 0, 5);
    }
}
