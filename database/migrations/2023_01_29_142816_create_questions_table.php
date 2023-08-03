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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->text('hint_for_the_question');
            $table->text('a');
            $table->text('b');
            $table->text('c');
            $table->text('d');
            $table->enum('correct', ['a', 'b', 'c', 'd']);
            $table->boolean('has_image')->default(false);
            $table->string('image')->nullable();
            $table->foreignId('theme_id')->constrained('themes');
            $table->foreignId('level_id')->constrained('levels');
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
        Schema::dropIfExists('questions');
    }
};
