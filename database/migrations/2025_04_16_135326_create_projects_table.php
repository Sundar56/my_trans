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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('contractor_id')->nullable()->index()->unsigned();
            $table->foreign('contractor_id')->references('id')->on('users')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->bigInteger('customer_id')->nullable()->index()->unsigned();
            $table->foreign('customer_id')->references('id')->on('users')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('projectname')->index();
            $table->string('projectamount')->nullable();
            $table->string('customer_email')->index()->nullable();
            $table->integer('project_type')->nullable();
            $table->integer('currency')->nullable();
            $table->string('escrowfund')->nullable();
            $table->string('balancefund')->nullable();
            $table->string('projectlocation')->nullable();
            $table->string('startdate')->nullable();
            $table->string('completiondate')->nullable();
            $table->longText('conditions')->nullable();
            $table->string('agreement')->nullable();
            $table->string('admincommission')->nullable();
            $table->string('customer_sign')->nullable();
            $table->string('contractor_sign')->nullable();
            $table->string('customer_name')->nullable();
            $table->enum('projectstatus', ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16','17'])
                ->default('0')
                ->comment('0 - invitationsent, 1 - accepted, 2 - ongoing, 3 - funds_deposited, 4 - Completed, 5 - Dispute, 6- Void,7-All,8-Decline,9-Reinvite, 10-Verified, 11- Verify, 12 - Partial Payment, 13 - Full Payment, 14 - Funds Released, 15 - Transaction live, 16 - Draft, 17 - Contractor Acceptance');
            $table->enum('status', ['0', '1', '2', '3', '4', '5'])
                ->default('0')
                ->comment('0 - invitationsent, 1 - accepted, 2 - ongoing, 3 - funds_deposited, 4 - Completed, 5 - Dispute');
            $table->tinyInteger('is_create')->default(0);
            $table->tinyInteger('contractor_acceptance')->default(0);
            $table->string('projectchannel')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
