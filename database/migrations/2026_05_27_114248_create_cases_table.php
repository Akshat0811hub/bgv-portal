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
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->unique();
            $table->foreignId('client_organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', [
                'Pending', 'In Progress', 'Insufficiency', 'Amber',
                'Discrepancy Found', 'On Hold', 'Completed - Green (Clear)', 'Completed - Discrepancy Found'
            ])->default('Pending');
            // Candidate info
            $table->string('candidate_name');
            $table->string('candidate_email');
            $table->string('candidate_phone')->nullable();
            $table->string('candidate_father_name')->nullable();
            $table->string('candidate_designation')->nullable();
            $table->date('case_start_date')->nullable();
            $table->date('report_closed_date')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('client_organization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
