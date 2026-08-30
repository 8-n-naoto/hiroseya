<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 管理画面から送った返信の履歴。
 *
 * 送信結果（成功・失敗とエラー内容）まで保存する。
 * 「返信したつもりで届いていない」は飲食店の問い合わせ運用で最も起きやすい事故のため、
 * 送りっぱなしにせず必ず結果を残す。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->string('to_email');
            $table->timestamp('sent_at')->nullable()->comment('null のままなら未送信または送信失敗');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['contact_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_replies');
    }
};
