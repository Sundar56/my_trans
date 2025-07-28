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
        Schema::create('escrow_updatehistory', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('updated_by')->index()->unsigned();
            $table->foreign('updated_by')->references('id')->on('users')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');        
            $table->json('previous_record')->nullable();
            $table->json('updated_record')->nullable();
            $table->string('updated_time')->nullable();
            $table->string('ipaddress')->nullable();
            $table->string('useragent')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escrow_updatehistory');
    }
};
