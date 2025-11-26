<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('food_logs', function (Blueprint $table) {
            $table->date('consumed_at')->nullable()->after('multiplier')->index();

            
    });
    
    DB::table('food_logs')->update(['consumed_at' => DB::raw('DATE(created_at)')]);
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food_logs', function (Blueprint $table) {
            $table->dropColumn('consumed_at');
            $table->dropIndex('consumed_at');


        });
    }
};
