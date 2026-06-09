<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('idempotency_key')->unique();
            $table->string('subscriber_id')->index();
//            $table->enum('channel', ['sms', 'email']);
//            $table->enum('type', ['transactional', 'bulk'])->default('bulk');
            $table->string('channel');
            $table->string('type')->default('bulk');

            $table->text('message');
            $table->string('recipient');
//            $table->enum('status', ['queued', 'sent', 'delivered', 'dropped'])
//                  ->default('queued');
            $table->string('status')->default('queued');
            $table->text('failure_reason')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
