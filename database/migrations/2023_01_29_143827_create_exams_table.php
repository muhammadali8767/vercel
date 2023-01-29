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
            $table->foreignId('user_id');
            $table->foreignId('theme_id');
            $table->foreignId('level_id');
            $table->timestamp('start_time');
            $table->timestamp('expire_time');
            $table->integer('total')->default(10);
            $table->integer('correct_answers')->default(0);
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
