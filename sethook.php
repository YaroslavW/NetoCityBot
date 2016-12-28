<?php

require_once "vendor/autoload.php";

try {
    $bot = new \TelegramBot\Api\Client(getenv('BOT_TOKEN'));
    $result = $bot->setWebhook(getenv('BOT_HOOK'));
    
    if ($result) {
        echo 'Webhook установлен';
    }
} catch (\TelegramBot\Api\Exception $e) {
    $e->getMessage();
}
