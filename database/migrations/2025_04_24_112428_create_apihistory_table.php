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
        Schema::create('apihistory', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('customer_id')->index()->unsigned();
            $table->foreign('customer_id')->references('id')->on('users')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('apiurl')->index()->nullable();
            $table->string('apimethod')->index()->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('apimode', 20)->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apihistory');
    }
};
