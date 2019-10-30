<?php

require('config.php');
require('helpers/SlackApiHelper.php');

$slack = new SlackApiHelper();

$channels = $slack->getChannelList();
foreach ($channels as $channel) {
    echo $channel->id . ' ' . $channel->name_normalized . PHP_EOL;
}
