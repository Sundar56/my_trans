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
        Schema::create('webhookhistory', function (Blueprint $table) {
            $table->id();
            $table->string('customer_email')->nullable();
            $table->string('contractor_email')->nullable();
            $table->integer('transactionid')->nullable();
            $table->integer('eventid')->nullable();
            $table->string('amount')->nullable();
            $table->tinyInteger('istest')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhookhistory');
    }
};
