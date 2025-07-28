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
        Schema::create('projecttasks', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('project_id')->index()->unsigned();
            $table->foreign('project_id')->references('id')->on('projects')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('taskname')->index();
            $table->string('taskamount')->nullable();
            $table->longText('tasknotes')->nullable();
            $table->string('taskdocuments')->nullable();
            $table->enum('taskstatus', ['0', '1', '2', '3', '4'])
                ->default('0')
                ->comment('0 - pending, 1 - ongoing, 2 - completed');
            $table->enum('is_verified', ['0', '1', '2'])
                ->default('0')
                ->comment('0 - pending, 1 - completed, 2 - other');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projecttasks');
    }
};
