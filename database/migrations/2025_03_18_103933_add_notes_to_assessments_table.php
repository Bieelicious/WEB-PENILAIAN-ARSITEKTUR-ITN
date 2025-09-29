<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('assessments', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('assessments', 'type')) {
                $table->string('type')->default('supervisor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            if (Schema::hasColumn('assessments', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('assessments', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};

