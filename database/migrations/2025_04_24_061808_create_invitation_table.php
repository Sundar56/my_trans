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
        Schema::create('invitation', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('project_id')->index()->unsigned();
            $table->foreign('project_id')->references('id')->on('projects')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->bigInteger('invitefrom')->index()->unsigned();
            $table->foreign('invitefrom')->references('id')->on('users')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->bigInteger('inviteto')->index()->unsigned()->nullable();
            $table->foreign('inviteto')->references('id')->on('users')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('invitemail')->index()->nullable();
            $table->enum('invitestatus', ['0', '1','2'])
            ->default('0')
            ->comment('0 - invitesent, 1 - accept, 2 - decline'); 
            $table->tinyInteger('reinvite')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitation');
    }
};
