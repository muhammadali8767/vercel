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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_id')->constrained('levels');
            $table->foreignId('score_id')->constrained('scores');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('expire_time')->nullable();
            $table->bigInteger('duration_in_seconds')->default(0);
            $table->integer('correct_answers')->default(0);
            $table->integer('keys_count')->default(0);
            $table->integer('used_keys')->default(0);
            $table->timestamps();
            $table->enum('status', ['active', 'completed'])->default('active');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exams');
    }
};
