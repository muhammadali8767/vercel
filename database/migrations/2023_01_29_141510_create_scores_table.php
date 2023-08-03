<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('theme_id')->constrained('themes');
            $table->foreignId('level_id')->constrained('levels')->default(0);
            $table->timestamp('start_time')->nullable();
            $table->timestamp('expire_time')->nullable();
            $table->bigInteger('duration_in_seconds')->default(0);
            $table->integer('correct_answers')->default(0);
            $table->integer('keys_count')->default(0);
            $table->integer('used_keys')->default(0);
            $table->integer('not_used_keys')->default(0);
            $table->enum('status', ['active', 'completed'])->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scores');
    }
};
