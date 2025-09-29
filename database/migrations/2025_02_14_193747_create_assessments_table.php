<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // assessments (header)
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained('students');
            $table->foreignId('lecturer_id')->nullable()->constrained('users');
            $table->string('type')->default('supervisor'); // supervisor/examiner
            $table->string('assessment_stage')->nullable();
            $table->timestamps();
        });

        // assessment_items (detail per kriteria)
        Schema::create('assessment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')
                  ->constrained('assessments')
                  ->onDelete('cascade');
            $table->string('label');       // contoh: ZONING, LANDSCAPE, dll.
            $table->text('criteria');      // indikator penilaian
            $table->integer('score')->default(0);
            $table->text('description')->nullable(); // catatan dosen
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_items');
        Schema::dropIfExists('assessments');
    }
};
