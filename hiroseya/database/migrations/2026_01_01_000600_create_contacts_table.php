<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * お問い合わせ。単なるフォームではなく「問い合わせ管理 + 管理画面からのメール返信」。
 *
 * 個人情報を保持するため、保持期間・バックアップ・削除方針を公開前に決めること。
 * プライバシーポリシーの掲示も必要。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('name_kana', 100)->nullable();
            $table->string('email');
            $table->string('tel', 20)->nullable();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->string('status', 20)->default('pending')->comment('pending|in_progress|done');
            $table->timestamp('status_changed_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_memo')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
