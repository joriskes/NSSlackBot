<?php

require('CacheHelper.php');

class SlackApiHelper extends CacheHelper
{
    private $channelId;

    public function __construct() {
        $this->channelId = '';
    }

    private function send($method, $postArray = null) {
        $post = ($postArray !== null);
        $url = 'https://slack.com/api/'.$method;
        if(!$post) {
            $url = $url.'?token='.urlencode(SLACK_CHAT_TOKEN);
            if($this->channelId !== '') $url.='&channel='.urlencode($this->channelId);
        }

        $c = curl_init($url);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false); // This is bad
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        $headers = [];
        $headers[] = 'Content-Type: application/json; charset=utf-8';


        if($post) {
            $headers[] = 'Authorization: Bearer '.SLACK_CHAT_TOKEN;
            curl_setopt($c, CURLOPT_POST, true);
            curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($postArray));
        }
        curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($c);
        curl_close($c);

        if($result) {
            $resultJson = json_decode($result);
            if(($resultJson) && ($resultJson->ok)) {
                return $resultJson;
            }
        }
        return $result;
    }

    public function getChannelList() {
        $res = $this->send('conversations.list');
        return $res;
    }

    public function setChannelId($id) {
        $this->channelId = $id;
    }

    public function postMessage($message) {
        if(empty($this->channelId)) throw new Exception('Set channel id first');
        $messageObject = [
            'channel' => $this->channelId,
            'text' => $message
        ];
        $res = $this->send('chat.postMessage',$messageObject);
        return $res;
    }

    public function updateMessage($ts, $message) {
        if(empty($this->channelId)) throw new Exception('Set channel id first');
        $messageObject = [
            'ts' => $ts,
            'channel' => $this->channelId,
            'text' => $message
        ];
        $res = $this->send('chat.update',$messageObject);
        return $res;
    }
}
