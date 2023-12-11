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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('username')->unique();
            // $table->string('account_name')->nullable();
            $table->enum('role', ['client', 'admin'])->default('client');
            $table->string('transfer_recipient')->nullable();
            $table->string('transfer_code')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('ref_code')->unique();
            $table->string('user_bank_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('referer_code')->nullable();
            $table->boolean('has_invested')->default(false);
            $table->string('usdt_balance')->nullable();
            $table->string('team_earning')->nullable();
            // $table->string('usdt_investment')->nullable();
            $table->string('naira_balance')->nullable();
            // $table->string('naira_investment')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('asset_password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
