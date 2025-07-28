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
        Schema::create('disputechat', function (Blueprint $table) {

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->bigInteger('from_id')->index()->unsigned();
            $table->foreign('from_id')->references('id')->on('users')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->bigInteger('to_id')->index()->unsigned();
            $table->foreign('to_id')->references('id')->on('users')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->bigInteger('project_id')->index()->unsigned();
            $table->foreign('project_id')->references('id')->on('projects')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->bigInteger('dispute_id')->index()->unsigned();
            $table->foreign('dispute_id')->references('id')->on('dispute')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->longText('body')->nullable()->charset('utf8mb4')->collation('utf8mb4_unicode_ci');
            $table->tinyInteger('seen')->default(0);
            $table->tinyInteger('is_edited')->default(0);
            $table->tinyInteger('is_replied')->default(0);
            $table->bigInteger('replied_id')->nullable()->index()->unsigned();
            $table->foreign('replied_id')->references('id')->on('disputechat')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->tinyInteger('is_forwarded')->default(0);
            $table->tinyInteger('is_saved')->default(0);
            $table->integer('no_of_attachment')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disputechat');
    }
};
