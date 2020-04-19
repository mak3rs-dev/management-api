<?php

namespace App\Console\Commands;

use App\Models\Community;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Laravel\Facades\Telegram;

class KickCommunityNonUsers extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mak3rs:KickCommunityNonUsers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kick Community Non Users';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $communities = Community::whereRaw("telegram_data REGEXP '.*\"autokicknonuser\":.*true.*'")->get();

        foreach ($communities as $community) {
            $this->info('Running for '.$community->name);

            $telData = json_decode($community->telegram_data);
            $kickExcludedIds = [];
            foreach ($telData->kickExcludedAlias as $item) $kickExcludedIds[] = $item;

            $users = User::select('telegram_data')->where('telegram_data', "LIKE", "%chatid%")->whereIn('id', $community->InCommunities->pluck('user_id')->toArray())->get();
            foreach ($users as $user) {
                $userTelData = json_encode($user->telegram_data);
                $kickExcludedIds[] = $userTelData->chatid;
            }

            $deletedItems = [];
            foreach ($telData->pendingCheckUsers as $toKickUser) {
                if (!in_array($toKickUser, $kickExcludedIds)) {
                    try {
                        Telegram::kickChatMember([
                            'chat_id' => $telData->chatid,
                            'user_id' => $toKickUser,
                            'until_date' => Carbon::now()->addSecond(60)->timestamp
                        ]);
                        $deletedItems[] = $toKickUser;
                    } catch (TelegramSDKException $e) {
                        ob_start();var_dump($e);$textException=ob_get_clean();
                        Log::error($textException);
                    }
                }
            }

            foreach ($deletedItems as $item) array_splice($telData->pendingCheckUsers, array_search($item, $telData->pendingCheckUsers), 1);

            $community->telegram_data = json_encode($telData);
            $community->save();
        }

    }

}
