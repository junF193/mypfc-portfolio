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
        // 1. users テーブル
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // 2. food_compositions テーブル (マスター食品DB)
        Schema::create('food_compositions', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('food_group')->nullable();
            $table->string('food_number')->unique();
            $table->string('index_number')->nullable();
            $table->string('food_name');
            $table->integer('refuse_rate')->nullable();
            $table->integer('energy_kj')->nullable();
            $table->integer('energy_kcal')->nullable();
            $table->decimal('water', 8, 1)->nullable();
            $table->decimal('protein', 8, 2)->nullable();
            $table->string('protein_amino_acid')->nullable();
            $table->decimal('fat', 8, 2)->nullable();
            $table->decimal('triglyceride', 8, 2)->nullable();
            $table->integer('cholesterol')->nullable();
            $table->decimal('carbohydrate', 8, 2)->nullable();
            $table->decimal('available_carb_monosaccharide', 8, 2)->nullable();
            $table->decimal('available_carb_mass', 8, 2)->nullable();
            $table->decimal('available_carb_subtraction', 8, 2)->nullable();
            $table->decimal('dietary_fiber', 8, 2)->nullable();
            $table->decimal('sugar_alcohol', 8, 2)->nullable();
            $table->decimal('organic_acid', 8, 2)->nullable();
            $table->decimal('ash', 8, 2)->nullable();
            $table->integer('sodium')->nullable();
            $table->integer('potassium')->nullable();
            $table->integer('calcium')->nullable();
            $table->integer('magnesium')->nullable();
            $table->integer('phosphorus')->nullable();
            $table->decimal('iron', 8, 2)->nullable();
            $table->decimal('zinc', 8, 2)->nullable();
            $table->decimal('copper', 8, 2)->nullable();
            $table->decimal('manganese', 8, 3)->nullable();
            $table->integer('iodine')->nullable();
            $table->integer('selenium')->nullable();
            $table->integer('chromium')->nullable();
            $table->integer('molybdenum')->nullable();
            $table->integer('retinol_activity_equivalent')->nullable();
            $table->integer('retinol')->nullable();
            $table->integer('alpha_carotene')->nullable();
            $table->integer('beta_carotene')->nullable();
            $table->integer('beta_cryptoxanthin')->nullable();
            $table->integer('beta_carotene_equivalent')->nullable();
            $table->decimal('vitamin_d', 8, 2)->nullable();
            $table->decimal('alpha_tocopherol', 8, 2)->nullable();
            $table->decimal('beta_tocopherol', 8, 2)->nullable();
            $table->decimal('gamma_tocopherol', 8, 2)->nullable();
            $table->decimal('delta_tocopherol', 8, 2)->nullable();
            $table->integer('vitamin_k')->nullable();
            $table->decimal('vitamin_b1', 8, 2)->nullable();
            $table->decimal('vitamin_b2', 8, 2)->nullable();
            $table->decimal('niacin', 8, 2)->nullable();
            $table->decimal('niacin_equivalent', 8, 2)->nullable();
            $table->decimal('vitamin_b6', 8, 2)->nullable();
            $table->decimal('vitamin_b12', 8, 2)->nullable();
            $table->integer('folate')->nullable();
            $table->decimal('pantothenic_acid', 8, 2)->nullable();
            $table->integer('biotin')->nullable();
            $table->integer('vitamin_c')->nullable();
            $table->decimal('alcohol', 8, 1)->nullable();
            $table->decimal('salt_equivalent', 8, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        // 3. food_logs テーブル (食事記録)
        Schema::create('food_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->string('food_name');
            $table->decimal('energy_kcal_100g', 8, 2)->nullable();
            $table->decimal('proteins_100g', 8, 2)->nullable();
            $table->decimal('fat_100g', 8, 2)->nullable();
            $table->decimal('carbohydrates_100g', 8, 2)->nullable();

            $table->string('meal_type');
            $table->string('source_type');
            $table->string('source_food_number');

            $table->timestamps();
        });

        // 4. favorite_foods テーブル (お気に入り)
        Schema::create('favorite_foods', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('food_log_id');
            $table->foreign('food_log_id')->references('id')->on('food_logs')->onDelete('cascade');

            $table->timestamps();
            $table->unique(['user_id', 'food_log_id']);
        });

        // 5. Laravel標準テーブル
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // downメソッドも、すべてのテーブルを、正しい順番で、削除するように、修正
        Schema::dropIfExists('favorite_foods');
        Schema::dropIfExists('food_logs');
        Schema::dropIfExists('food_compositions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
