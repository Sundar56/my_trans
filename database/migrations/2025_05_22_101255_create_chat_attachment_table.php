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
        Schema::create('chat_attachment', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('disputechat_id')->index()->unsigned();
            $table->foreign('disputechat_id')->references('id')->on('disputechat')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('filename')->nullable();
            $table->string('filetype')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_attachment');
    }
};
