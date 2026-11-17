<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('case_checks', 'custom_schema')) {
            Schema::table('case_checks', function (Blueprint $table) {
                $table->json('custom_schema')->nullable()->after('comments');
            });
        }

        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('case_checks', 'custom_schema')) {
            Schema::table('case_checks', function (Blueprint $table) {
                $table->dropColumn('custom_schema');
            });
        }

        Schema::dropIfExists('settings');
    }
};
