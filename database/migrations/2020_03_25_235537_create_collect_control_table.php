<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectControlTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('collect_control', function (Blueprint $table) {
            $table->id();
            $table->foreignId('in_community_id')->references('id')->on('in_community');
            $table->foreignId('community_id')->references('id')->on('community');
            $table->foreignId('status_id')->references('id')->on('status');
            $table->string('address')->nullable();
            $table->string('location', 60)->nullable();
            $table->string('province', 60)->nullable();
            $table->string('state', 60)->nullable();
            $table->string('country', 60)->nullable();
            $table->string('cp', 8)->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('collect_control');
    }
}
