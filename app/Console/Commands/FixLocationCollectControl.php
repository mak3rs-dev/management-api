<?php

namespace App\Console\Commands;

use App\Models\CollectControl;
use App\Models\User;
use Illuminate\Console\Command;

class FixLocationCollectControl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mak3r:fixLocation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("Starting import location...");

        $collects = CollectControl::where('location', null)->get();

        foreach ($collects as $collect) {
            $user = User::where('id', $collect->InCommunity->user_id)->first();

            if ($user != null) {
                $this->info("Update location user $user->alias");
                $collect->location = $user->location;

                if ($collect->save()) {
                    $this->info("Update location user $user->alias complete");

                } else {
                    $this->error("Update location user $user->alias failed");
                }
            }
        }
    }
}
