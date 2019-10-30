<?php

require('config.php');
require('helpers/SlackApiHelper.php');

$slack = new SlackApiHelper();
$slack->setChannelId(SLACK_CHANNEL_ID);

$postResult = $slack->postMessage('Test');
$postResult = $slack->updateMessage($postResult->ts, 'Test, updated');
$postResult = $slack->updateMessage($postResult->ts, 'Test, updated 2');
