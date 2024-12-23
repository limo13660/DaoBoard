<?php

namespace App\Plugins\Telegram\Commands;

use App\Models\User;
use App\Plugins\Telegram\Telegram;

class GetLatestUrl extends Telegram {
    public $command = '/getlatesturl';
    public $description = '将Telegram账号绑定到网站';

    public function handle($message, $match = []) {
        $telegramService = $this->telegramService;
        $text = sprintf(
            "%s的最新网址是：%s",
            config('daoboard.app_name', 'DaoBoard'),
            config('daoboard.app_url')
        );
        $telegramService->sendMessage($message->chat_id, $text, 'markdown');
    }
}
