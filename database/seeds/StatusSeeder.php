<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('status')->insert(
            [
                'uuid' => \Illuminate\Support\Str::uuid(),
                'code' => 'COLLECT:REQUESTED',
                'name' => 'Solicitada',
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ]
        );

        DB::table('status')->insert(
            [
                'uuid' => \Illuminate\Support\Str::uuid(),
                'code' => 'COLLECT:DELIVERED',
                'name' => 'Entregada',
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ]
        );

        DB::table('status')->insert(
            [
                'uuid' => \Illuminate\Support\Str::uuid(),
                'code' => 'COLLECT:RECEIVED',
                'name' => 'Recibida',
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now(),
            ]
        );
    }
}
