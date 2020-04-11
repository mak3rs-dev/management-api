<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();
            $table->string('name', 60);
            $table->string('alias', 60)->unique()->nullable();
            $table->string('email', 120)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('hash_email_verified')->nullable();
            $table->string('hash_password_verified')->nullable();
            $table->string('password');
            $table->string('phone', 20);
            $table->foreignId('role_id')->references('id')->on('roles');
            $table->longText('picture')->nullable();
            $table->string('address')->nullable();
            $table->string('location', 60)->nullable();
            $table->string('province', 60)->nullable();
            $table->string('state', 60)->nullable();
            $table->string('country', 60)->nullable();
            $table->string('cp', 8)->nullable();
            $table->longText('address_description')->nullable();
            $table->longText('telegram_data')->nullable();
            $table->timestamp('privacy_policy_accepted_at')->default('1970-01-01');
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
