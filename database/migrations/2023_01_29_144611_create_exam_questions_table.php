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
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id');
            $table->foreignId('question_id');
            $table->foreignId('key_usage')->default(0);
            $table->enum('answer', ['a', 'b', 'c', 'd', null])->nullable();
            $table->tinyInteger('is_correct')->default(0);
            $table->timestamp('start_time')->nullable();
            $table->timestamp('expire_time')->nullable();
            $table->timestamp('answer_time')->nullable();
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
        Schema::dropIfExists('exam_questions');
    }
};
