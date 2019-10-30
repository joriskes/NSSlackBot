<?php
date_default_timezone_set('Europe/Amsterdam');

// If debug is set to true the script will output instead of posting to slack
// Also, debug mode will report on time trains, non-debug mode only reports delays / cancellations
define('DEBUG',true);

// Request a free api key here: https://apiportal.ns.nl/
// After that, activiate the Public-Travel-Information product and fill in the api key here
define('NS_API_KEY', '');

// Find your stations uiccodes by visiting stationlist.php in your browser
define('UIC_BREDA',8400131);
define('UIC_EINDHOVEN',8400206);
define("UIC_BOXTEL", 8400129);
define("UIC_TILBURG", 8400597);
define("UIC_WEERT", 8400684);

// Slack OAuth Access Token
// Add the following oauth scopes to the slack application: chat:write:bot & channels:read
define('SLACK_CHAT_TOKEN','');
// Channel id where the bot lives, use channellist.php to find the id
define('SLACK_CHANNEL_ID','');

define('TRAJECTEN_OCHTEND', [
    [UIC_EINDHOVEN, UIC_BREDA],
    [UIC_WEERT, UIC_EINDHOVEN],
    [UIC_BOXTEL, UIC_TILBURG],
    [UIC_TILBURG, UIC_BREDA],
]);

define('TRAJECTEN_MIDDAG', [
    [UIC_BREDA, UIC_EINDHOVEN],
    [UIC_EINDHOVEN, UIC_WEERT],
    [UIC_TILBURG, UIC_BOXTEL],
    [UIC_BREDA, UIC_TILBURG],
]);

// Slack webhook where to post your notifications
define('SLACK_WEBHOOK','https://hooks.slack.com/services/111111/222222222/3333333333');