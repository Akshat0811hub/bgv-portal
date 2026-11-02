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
        Schema::create('case_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->string('check_type');
            $table->enum('status', [
                'Pending', 'In Progress', 'Insufficiency', 'Amber',
                'Discrepancy Found', 'Clear', 'Failed', 'Completed'
            ])->default('Pending');
            $table->json('form_data')->nullable();
            $table->json('verified_data')->nullable();
            $table->text('comments')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['case_id', 'check_type']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_checks');
    }
};
