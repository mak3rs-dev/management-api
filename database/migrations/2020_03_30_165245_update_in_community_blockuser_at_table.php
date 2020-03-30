<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateInCommunityBlockuserAtTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('in_community', function (Blueprint $table) {
            $table->timestamp('blockuser_at')->nullable()->after('disabled_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('community_blockuser_at', function (Blueprint $table) {
            //
        });
    }
}
