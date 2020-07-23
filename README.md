# NSSlackBot
A simple slack bot that connects with the open NS api and checks delays for selected stations.
You can set op trajectories for morning and afternoon

You'll need a Slack app and an NS apiportal account with the Ns-App enabled

## Installation
- Create a webserver writable cache folder in the root of the project 
- Update config_example.php and rename it to config.php
- Use the stationlist.php to find your stations UICCode
- Use the channellist.php to find the Slack Channel Id 
- Run this script in a cron of 10 minutes or use the docker image

## Future ideas
- Consolidate departure times (e.g .39+28 = the 18.09 train, probably just group everything that departs within say 10 mins with same from/to)
- Read and parse disruptions endpoint (see NSApiHelper->getDisruptions)
