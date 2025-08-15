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
        Schema::table('students', function (Blueprint $table) {
            $table->string('title_of_the_final_project_proposal')->after('nim')->nullable();
            $table->string('design_theme')->after('title_of_the_final_project_proposal')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('title_of_the_final_project_proposal');
            $table->dropColumn('design_theme');
        });
    }
};
