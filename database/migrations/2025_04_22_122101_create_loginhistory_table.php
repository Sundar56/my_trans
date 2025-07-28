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
        Schema::create('loginhistory', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->index()->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->datetime('logintime')->nullable();
            $table->datetime('logouttime')->nullable();
            $table->integer('duration')->nullable();
            $table->string('ipaddress')->nullable();
            $table->string('useragent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loginhistory');
    }
};
