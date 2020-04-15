<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCollectControlTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('collect_control', function (Blueprint $table) {
            $table->uuid('uuid')->default('')->after('id');
        });

        foreach (\App\Models\CollectControl::all() as $collect) {
            $collect->uuid = \Illuminate\Support\Str::uuid();
            $collect->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
