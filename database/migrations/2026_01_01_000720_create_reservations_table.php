<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 予約（リクエスト承認制）。
 *
 * 変更・キャンセルは電話のみのため、キャンセル用トークンは持たない。
 * かわりに reservation_no を発行してメールに記載し、電話口での照合に使う。
 * 「お名前と日付」だけで探すと同姓や聞き間違いで事故るため、番号は必須。
 * 電話を受けながら探せるよう tel にも索引を張っている。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_no', 20)->unique()->comment('電話口での照合用');
            $table->string('name', 100);
            $table->string('name_kana', 100)->nullable();
            $table->string('tel', 20);
            $table->string('email');
            $table->date('reserved_date');
            $table->time('reserved_time');
            $table->unsignedSmallInteger('party_size');
            $table->text('request')->nullable()->comment('座敷希望・アレルギーなどのご要望');
            $table->string('status', 20)->default('pending')
                ->comment('pending|confirmed|rejected|cancelled|completed|no_show');
            $table->timestamp('status_changed_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_memo')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tel');
            $table->index(['reserved_date', 'reserved_time']);
            $table->index(['status', 'reserved_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
