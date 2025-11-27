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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('height', 5, 2)->nullable()->after('password'); // cm
            $table->decimal('weight', 5, 2)->nullable()->after('height'); // kg
            $table->integer('age')->nullable()->after('weight');
            $table->string('gender')->nullable()->after('age'); // male, female
            $table->string('activity_level')->default('medium')->after('gender'); // low, medium, high
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['height', 'weight', 'age', 'gender', 'activity_level']);
        });
    }
};
