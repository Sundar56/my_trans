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
        Schema::create('disputefiles', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('dispute_id')->index()->unsigned();
            $table->foreign('dispute_id')->references('id')->on('dispute')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('support_material')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disputefiles');
    }
};
