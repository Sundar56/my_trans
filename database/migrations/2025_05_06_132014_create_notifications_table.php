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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('from_id')->index()->unsigned()->nullable();
            $table->foreign('from_id')->references('id')->on('users')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->bigInteger('to_id')->index()->unsigned()->nullable();
            $table->foreign('to_id')->references('id')->on('users')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->bigInteger('project_id')->index()->unsigned();
            $table->foreign('project_id')->references('id')->on('projects')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('message')->nullable();
            $table->tinyInteger('isread')->default(0);
            $table->tinyInteger('status')->default(0);
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
