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
        Schema::create('direct_alert_history', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key for the history record
            $table->string('account_number')->index()->nullable(); // Indexed but not unique
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
            $table->timestamps(); // Includes created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direct_alert_history');
    }
};
