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
        Schema::create('direct_alert', function (Blueprint $table) {
            $table->id();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('cell_phone')->nullable();
            $table->string('home_phone')->nullable();
            $table->string('work_phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('optin_cell_sms')->nullable();
            $table->timestamp('optin_cell_call')->nullable();
            $table->timestamp('optin_home_call')->nullable();
            $table->timestamp('optin_work_call')->nullable();
            $table->timestamp('optin_emergency_email')->nullable();
            $table->timestamp('optin_email')->nullable();
            $table->string('alternate_phone')->nullable();
            $table->timestamps();

            // Add a unique index on account_number and account_name
            $table->unique(['account_number', 'account_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direct_alert');
    }
};
