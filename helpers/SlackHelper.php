<?php

class SlackHelper extends CacheHelper
{
    public function __construct() {
    }

    public function postMessage($messageText) {
        if(DEBUG) {
            echo 'DEBUG OUTPUT: '.$messageText.'<br/>';
        } else {
            $message = array('payload' => json_encode(array('text' => $messageText)));
            $c = curl_init(SLACK_WEBHOOK);
            curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($c, CURLOPT_POST, true);
            curl_setopt($c, CURLOPT_POSTFIELDS, $message);
            curl_exec($c);
            curl_close($c);
        }
    }
}
