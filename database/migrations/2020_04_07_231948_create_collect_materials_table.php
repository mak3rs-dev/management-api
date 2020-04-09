<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectMaterialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('collect_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_requests_id')->references('id')->on('material_requests');
            $table->foreignId('collect_control_id')->references('id')->on('collect_control');
            $table->integer('units_delivered');
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
        Schema::dropIfExists('collect_materials');
    }
}
