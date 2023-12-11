<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usdt_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
            $table->boolean('is_verified')->default(false);
            $table->string('amount');
            $table->string('date');
            $table->boolean('is_sent')->default(false);
            $table->string('status')->nullable();
            $table->string('address');
            $table->string('payout_id')->nullable();
            $table->string('batch_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usdt_withdrawals');
    }
};
