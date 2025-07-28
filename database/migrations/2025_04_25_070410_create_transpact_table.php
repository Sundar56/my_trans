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
        Schema::create('transpact', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('customer_id')->index()->unsigned();
            $table->foreign('customer_id')->references('id')->on('users')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->bigInteger('project_id')->index()->unsigned();
            $table->foreign('project_id')->references('id')->on('projects')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('transpactnumber')->index()->nullable();
            $table->enum('status', ['0', '1'])
            ->default('0')
            ->comment('0 - pending, 1 - accepted');  
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transpact');
    }
};
