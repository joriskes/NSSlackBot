# NSSlackBot
A simple slack bot that connects with the open NS api and checks delays for selected stations.
You can set op trajectories for morning and afternoon

You'll need a Slack webhook url and an NS apiportal account with the Public-Travel-Information enabled

## Installation
- Create a webserver writable cache folder in the root of the project 
- Use the stationlist.php to find your stations UICCode
- Update config_example.php and rename it to config.php 
- Run this script in a cron of 10 minutes

## Future ideas
- Try to post all notifications in one message
- Consolidate departure times (e.g .39+28 = the 18.09 train, probably just group everything that departs within say 10 mins with same from/to)
- Read and parse disruptions endpoint (see NSApiHelper->getDisruptions)