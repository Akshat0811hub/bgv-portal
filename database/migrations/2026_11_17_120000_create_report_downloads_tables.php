<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_downloads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->string('filename');
            $table->unsignedInteger('parts');
            $table->unsignedBigInteger('byte_size');
            $table->string('sha256', 64);
            $table->timestamp('expires_at')->index();
        });

        Schema::create('report_download_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('report_download_id')->constrained('report_downloads')->cascadeOnDelete();
            $table->unsignedInteger('part');
            $table->longText('content');
            $table->unique(['report_download_id', 'part']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_download_parts');
        Schema::dropIfExists('report_downloads');
    }
};
