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
        Schema::create('dispute', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('project_id')->index()->unsigned();
            $table->foreign('project_id')->references('id')->on('projects')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->bigInteger('created_by')->index()->unsigned();
            $table->foreign('created_by')->references('id')->on('users')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->bigInteger('sent_to')->index()->unsigned();
            $table->foreign('sent_to')->references('id')->on('users')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('reason')->index()->nullable();
            $table->tinyInteger('status')->default(0);
            $table->enum('disputestatus', ['0', '1'])
                ->default('0')
                ->comment('0 - ongoing, 1 - resolved');
            $table->string('roomname')->index()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispute');
    }
};
