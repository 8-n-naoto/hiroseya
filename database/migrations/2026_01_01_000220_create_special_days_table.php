<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 臨時休業・年末年始・時間変更。business_hours より優先される。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_days', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->boolean('is_closed')->default(true);
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_days');
    }
};
