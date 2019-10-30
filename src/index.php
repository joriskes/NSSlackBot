<?php
require('config.php');
require('helpers/CacheHelper.php');
require('helpers/NSApiHelper.php');
require('helpers/SlackApiHelper.php');
require('helpers/TrainSlackTSCache.php');

function run()
{
    // No reporting for weekends
    $day = date('N');
    if ($day > 5) {
        exit();
    }

    $ns = new NSApiHelper();
    $slack = new SlackApiHelper();
    $cache = new TrainSlackTSCache();

    $trajecten = [];
    $handled_trains = [];
    $time = date('G');
    // Select the right trajectories by time
    if ($time > 5 && $time < 13) {
        $trajecten = TRAJECTEN_OCHTEND;
    }
    if ($time > 12 && $time < 22) {
        $trajecten = TRAJECTEN_MIDDAG;
    }

    if (count($trajecten)) {
        foreach ($trajecten as $traject) {
            $depUIC = $traject[0];
            $destUIC = $traject[1];

            $departures = $ns->getDepartures($depUIC);

            if (count($departures)) {
                // Filter out all departures that do not stop at the wanted destination
                $departures = array_filter($departures,
                    function ($dep) use ($destUIC, $ns) {
                        // The route stations do have a UICCode in the resultset but the destination, oddly, has not
                        $trajectDestUIC = $ns->stationNameToUICCode($dep->direction);
                        if ($destUIC == $trajectDestUIC) {
                            return true;
                        }

                        // Check if the destination is in the route stations
                        if ($dep->routeStations && count($dep->routeStations) > 0) {
                            foreach ($dep->routeStations as $routeStation) {
                                if ($routeStation->uicCode == $destUIC) {
                                    return true;
                                }
                            }
                        }

                        // Destination not found, filter out
                        return false;
                    });
            }

            if (count($departures)) {
                foreach ($departures as $departure) {
                    $trainId = $departure->name;
                    // Don't double report about the same train twice (eg. Eindhoven - Breda is sometimes also Tilburg - Breda)
                    if (!in_array($trainId, $handled_trains)) {
                        $handled_trains[] = $trainId;
                        $trainMessage = $cache->findTrain($trainId);

                        // For each remaining departure: build up a slack message reporting about it (if delayed)
                        $plannedTime = strtotime($departure->plannedDateTime);
                        $actualTime = strtotime($departure->actualDateTime);

                        $trainMessage->from = $ns->UICCodeToStationName($depUIC);
                        $trainMessage->to = $ns->UICCodeToStationName($destUIC);
                        $trainMessage->plannedTimestamp = $plannedTime;
                        $trainMessage->cancelled = $departure->cancelled;

                        if (!$trainMessage->cancelled) {
                            $delayMinutes = round(($actualTime - $plannedTime) / 60);
                            $trainMessage->delayedMinutes = $delayMinutes;
                        }

                        $trainMessage->slackTS = $slack->reportTrainMessage($trainMessage);
                        $cache->saveTrain($trainMessage);
                    }
                }
            }
        }
        $cache->save();
    }
}

// In LOOP_MODE keep running with delay
do {
    if (LOOP_MODE) {
        echo 'Running' . PHP_EOL;
    }
    run();
    if (LOOP_MODE) {
        sleep(300);
    }
} while (LOOP_MODE);