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
        Schema::create('businessprofile', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->index()->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('businessname')->index();
            $table->string('businessemail')->index()->nullable();
            $table->string('company_registernum')->nullable();
            $table->string('address')->nullable();
            $table->string('businessphone')->nullable();
            $table->string('businessimage')->nullable();
            $table->enum('businesstype', ['0', '1'])
            ->default('0')
            ->comment('0 - sole_trader, 1 - ltd_company');   
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businessprofile');
    }
};
