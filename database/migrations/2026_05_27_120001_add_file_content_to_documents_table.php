<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('documents', 'file_content')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->longText('file_content')->nullable()->after('uploaded_by_role');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('documents', 'file_content')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropColumn('file_content');
            });
        }
    }
};
