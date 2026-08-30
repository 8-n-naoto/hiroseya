<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['dish_id', 'sort_order'])]
class HomeRecommendedDish extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }
}
