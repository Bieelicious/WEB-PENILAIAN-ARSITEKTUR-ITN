<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('assessments', 'lecturer_id')) {
                $table->foreignId('lecturer_id')
                      ->nullable()
                      ->constrained('users')
                      ->after('student_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lecturer_id');
        });
    }
};

