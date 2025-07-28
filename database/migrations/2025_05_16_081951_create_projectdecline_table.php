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
        Schema::create('projectdecline', function (Blueprint $table) {
            $table->id(); $table->bigInteger('project_id')->index()->unsigned();
            $table->foreign('project_id')->references('id')->on('projects')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->bigInteger('customer_id')->index()->unsigned();
            $table->foreign('customer_id')->references('id')->on('users')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->bigInteger('contractor_id')->index()->unsigned()->nullable();
            $table->foreign('contractor_id')->references('id')->on('users')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('reason')->index()->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projectdecline');
    }
};
