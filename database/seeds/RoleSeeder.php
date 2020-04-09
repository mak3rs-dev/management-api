<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('roles')->insert(
            [
            'uuid' => \Illuminate\Support\Str::uuid(),
            'name' => 'USER:ADMIN',
            'created_at' => \Carbon\Carbon::now(),
            'updated_at' => \Carbon\Carbon::now(),
            ]
        );

        DB::table('roles')->insert(
            [
                'uuid' => \Illuminate\Support\Str::uuid(),
                'name' => 'USER:COMMON',
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ]
        );

        DB::table('roles')->insert(
            [
                'uuid' => \Illuminate\Support\Str::uuid(),
                'name' => 'MAKER:ADMIN',
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ]
        );

        DB::table('roles')->insert(
            [
                'uuid' => \Illuminate\Support\Str::uuid(),
                'name' => 'MAKER:USER',
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ]
        );
    }
}
