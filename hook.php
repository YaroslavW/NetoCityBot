<?php

require_once "vendor/autoload.php";

$cities = array_map('trim', file('./cities.txt'));

function getRandom($list) {
    $index = rand(0, count($list) - 1);
    return $list[$index];
}

function getLast($text) {
    $badLetters = ['ы', 'ь', 'й'];
    $size = 1;
    $i = -$size;
    
    do {
        $letter = mb_substr($text, $i, ($i + $size === 0) ? null : $i + $size);
        $i -= $size;
    } while (check($letter, $badLetters));
    return $letter;
}

function getFirst($text) {
    return mb_substr(mb_strtolower($text, 'UTF-8'), 0, 1);
}

function check($item, $list) {
    return array_search($item, $list) !== false;
}

try {
    $bot = new \TelegramBot\Api\Client(getenv('BOT_TOKEN'));
    
    $bot->on(function ($update) use ($bot) {
        $message = $update->getMessage();
        if (!$message) {
           return true;
        }
        $chatId = $message->getChat()->getId();
        
        session_id($chatId);
        session_name('chat');
        session_start();
    }, function () {return true;});
    
    $bot->command('play', function ($message) use ($bot, $cities) {
        $city = getRandom($cities);
        $letter = getLast($city);
        $_SESSION['isPlaing'] = true;
        $_SESSION['city'] = $city;
        $_SESSION['used'] = [ $city ];
        $_SESSION['letter'] = $letter;
        
        $bot->sendMessage($message->getChat()->getId(), 'Начинаем игру');
        $bot->sendMessage($message->getChat()->getId(), $city);
        $bot->sendMessage($message->getChat()->getId(), 'Тебе на ' . $letter);
    });
    
    $bot->command('start', function ($message) use ($bot) {
        $bot->sendMessage(
            $message->getChat()->getId(), 
            'Привет, я люблю играть в города. Чтобы начать игру набери команду /play'
        );
    });
    
    $bot->on(function ($update) use ($bot, $cities) {
       $message = $update->getMessage();
       if (!$message) {
           return true;
       }
       if (!isset($_SESSION['isPlaing']) || !$_SESSION['isPlaing'])  {
           $bot->sendMessage($chatId, 'Хотите сыграть в города? Наберите /play');
       }
       $city = $message->getText();
       $letter = getFirst($city);
       $chatId = $message->getChat()->getId();
       
       if (!check($city, $cities)) {
           $bot->sendMessage($chatId, 'Такого города нет');
           return;
       }
        if ($letter !== $_SESSION['letter']) {
            $bot->sendMessage($chatId, 'Нужен город на ' . $_SESSION['letter']);
            return;
        }
        if (check($city, $_SESSION['used'])) {
            $bot->sendMessage($chatId, 'Такой город уже был');
            return;
        }
        
        $_SESSION['used'][] = $city;
        $letter = getLast($city);
        $available = array_filter($cities, function ($city) use ($letter) {
            return !check($city, $_SESSION['used']) && getFirst($city) === $letter;
        });
        
        if (!$available) {
            $bot->sendMessage($chatId, 'Сдаюсь!');
            return;
        }
        
        $city = getRandom(array_values($available));
        $letter = getLast($city);
        $_SESSION['city'] = $city;
        $_SESSION['used'][] = $city;
        $_SESSION['letter'] = $letter;

        $bot->sendMessage($message->getChat()->getId(), $city);
        $bot->sendMessage($message->getChat()->getId(), 'Тебе на ' . $letter);

    }, function () {return true;});
    
    $bot->run();

} catch (\TelegramBot\Api\Exception $e) {
    $e->getMessage();
} catch (Exception $e) {
    var_export($e);
}