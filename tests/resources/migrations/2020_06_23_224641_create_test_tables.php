<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title')->nullable();
            $table->string('body')->nullable();
            $table->integer('author_id')->default(0);

            $table->timestamp('deleted_at')->nullable();

            $table->timestamps();
        });

        Schema::create('posts_trash', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title')->nullable();
            $table->string('body')->nullable();
            $table->integer('author_id')->default(0);

            $table->timestamp('deleted_at')->nullable();

            $table->timestamps();
        });

        Schema::create('authors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();

            $table->timestamp('deleted_at')->nullable();

            $table->timestamps();
        });

        Schema::create('authors_trash', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();

            $table->timestamp('deleted_at')->nullable();

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
        Schema::dropIfExists('posts');
        Schema::dropIfExists('posts_trash');
        Schema::dropIfExists('authors');
        Schema::dropIfExists('authors_trash');
    }
}
