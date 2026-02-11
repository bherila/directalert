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
        Schema::create('admin_audit_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('auth_user_id')->nullable();
            $table->string('action'); // login, import, export
            $table->boolean('was_successful');
            $table->integer('records_affected')->default(0);
            $table->integer('records_skipped')->default(0);
            $table->integer('records_failed')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->foreign('auth_user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_audit_log');
    }
};
