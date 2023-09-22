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
        Schema::create('investment_naira1s', function (Blueprint $table) {
            $table->id();
            $table->string('amount');
            $table->foreignId('user_id')
            ->constrained('users')
            ->cascadeOnDelete();
            // $table->timestamp('created_at');
            $table->unique(['user_id', 'created_at']);
            $table->string('cummulative_interest')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_naira1s');
    }
};
