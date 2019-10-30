<?php

class SlackApiHelper extends CacheHelper
{
    private $channelId;

    public function __construct()
    {
        parent::__construct();
        $this->channelId = '';
    }

    public function getChannelList()
    {
        $res = $this->send('conversations.list');
        return $res;
    }

    private function send($method, $postArray = null)
    {
        $post = ($postArray !== null);
        $url = 'https://slack.com/api/' . $method;
        if (!$post) {
            $url = $url . '?token=' . urlencode(SLACK_CHAT_TOKEN);
            if ($this->channelId !== '') {
                $url .= '&channel=' . urlencode($this->channelId);
            }
        }

        $c = curl_init($url);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false); // This is bad
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        $headers = [];
        $headers[] = 'Content-Type: application/json; charset=utf-8';


        if ($post) {
            $headers[] = 'Authorization: Bearer ' . SLACK_CHAT_TOKEN;
            curl_setopt($c, CURLOPT_POST, true);
            curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($postArray));
        }
        curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($c);
        curl_close($c);

        if ($result) {
            $resultJson = json_decode($result);
            if (($resultJson) && ($resultJson->ok)) {
                return $resultJson;
            }
        }
        return $result;
    }

    public function setChannelId($id)
    {
        $this->channelId = $id;
    }

    public function reportTrainMessage($trainMessage)
    {
        $routeMsg = $trainMessage->from . ' - ' . $trainMessage->to;
        $timeMsg = '_' . date('H:i', $trainMessage->plannedTimestamp) . 'u_';

        $slackMsg = '';
        if ($trainMessage->cancelled) {
            $slackMsg = $routeMsg . ' ' . $timeMsg . ' *TREIN VERVALT*';
        } else {
            // Don't report trains with a delay smaller than 5 minutes, only when already reporting or in debug mode
            if (($trainMessage->delayedMinutes > 4) || (!empty($trainMessage->slackTS)) || DEBUG) {
                if ($trainMessage->delayedMinutes > 15) {
                    $slackMsg = $routeMsg . ' ' . $timeMsg . ' *+' . $trainMessage->delayedMinutes . ' minuten*';
                } else {
                    if ($trainMessage->delayedMinutes < 1) {
                        $slackMsg = $routeMsg . ' ' . $timeMsg . ' op tijd';
                    } else {
                        $slackMsg = $routeMsg . ' ' . $timeMsg . ' +' . $trainMessage->delayedMinutes . ' minuten';
                    }
                }
            }
        }

        $postResult = null;
        if (!empty($slackMsg)) {
            if (empty($trainMessage->slackTS)) {
                if (DEBUG) {
                    echo 'DEBUG, skip NEW Slack message: ' . $slackMsg . PHP_EOL;
                } else {
                    $postResult = $this->postMessage($slackMsg);
                }
            } else {
                $slackMsg = $slackMsg . ' (update: ' . date('H:i') . ')';
                if (DEBUG) {
                    echo 'DEBUG, skip UPDATE Slack message ' . $trainMessage->slackTS . ': ' . $slackMsg . PHP_EOL;
                } else {
                    $postResult = $this->updateMessage($trainMessage->slackTS, $slackMsg);
                }
            }
        }

        if (($postResult) && (isset($postResult->ts))) {
            return $postResult->ts;
        }
        return 0;
    }

    public function postMessage($message)
    {
        if (empty($this->channelId)) {
            echo 'Set ChannelId first' . PHP_EOL;
            return false;
        }
        $messageObject = [
            'channel' => $this->channelId,
            'text' => $message
        ];
        $res = $this->send('chat.postMessage', $messageObject);
        return $res;
    }

    public function updateMessage($ts, $message)
    {
        if (empty($this->channelId)) {
            echo 'Set ChannelId first' . PHP_EOL;
            return false;
        }
        $messageObject = [
            'ts' => $ts,
            'channel' => $this->channelId,
            'text' => $message
        ];
        $res = $this->send('chat.update', $messageObject);
        return $res;
    }
}
