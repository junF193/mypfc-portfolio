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
        // テーブル名を favorite_foods から favorites に変更
        Schema::rename('favorite_foods', 'favorites');

        // カラムの追加と変更
        Schema::table('favorites', function (Blueprint $table) {
            // food_log_id を source_food_log_id にリネームし、NULLを許可
            $table->renameColumn('food_log_id', 'source_food_log_id');
            $table->foreignId('source_food_log_id')->nullable()->change();

            // 編集可能なデータを保存するカラムを追加（命名規則を統一）
            $table->string('food_name')->after('user_id');
            $table->decimal('energy_kcal_100g', 8, 2)->nullable()->after('food_name');
            $table->decimal('proteins_100g', 8, 2)->nullable()->after('energy_kcal_100g');
            $table->decimal('fat_100g', 8, 2)->nullable()->after('proteins_100g');
            $table->decimal('carbohydrates_100g', 8, 2)->nullable()->after('fat_100g');
            $table->text('memo')->nullable()->after('carbohydrates_100g');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->dropColumn([
                'food_name',
                'energy_kcal_100g',
                'proteins_100g',
                'fat_100g',
                'carbohydrates_100g',
                'memo',
            ]);

            $table->renameColumn('source_food_log_id', 'food_log_id');
            $table->foreignId('food_log_id')->nullable(false)->change();
        });

        Schema::rename('favorites', 'favorite_foods');
    }
};