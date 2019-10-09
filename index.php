<?php
require('config.php');
require('helpers/NSApiHelper.php');
require('helpers/SlackHelper.php');

// No reporting for weekends
$day = date('N');
if($day > 5) exit();

$ns = new NSApiHelper();
$slack = new SlackHelper();

$trajecten = [];
$time = date('G');
// Select the right trajectories by time
if($time > 5 && $time < 13) $trajecten = TRAJECTEN_OCHTEND;
if($time > 12 && $time < 22) $trajecten = TRAJECTEN_MIDDAG;

$reportedTrains = [];

if(count($trajecten)) {
    foreach($trajecten as $traject) {
        $depUIC = $traject[0];
        $destUIC = $traject[1];

        $departures = $ns->getDepartures($depUIC);

        if(count($departures)) {
            // Filter out all departures that do not stop at the wanted destination
            $departures = array_filter($departures,
                function($dep) use ($destUIC, $ns)
                {
                    // The route stations do have a UICCode in the resultset but the destination, oddly, has not
                    $trajectDestUIC = $ns->stationNameToUICCode($dep->direction);
                    if($destUIC == $trajectDestUIC) return true;

                    // Check if the destination is in the route stations
                    if($dep->routeStations && count($dep->routeStations) > 0) {
                        foreach($dep->routeStations as $routeStation) {
                            if($routeStation->uicCode == $destUIC) {
                                return true;
                            }
                        }
                    }

                    // Destination not found, filter out
                    return false;
                });
        }

        if(count($departures)) {
            $mergedKeys = []; // holds keys that are merged, since the delay is so long that it's actually the next train on the schedule (e.g. 25-35 minute delay on a 30 minute schedule)

            foreach ($departures as $departure) {
                // For each remaining departure: build up a slack message reporting about it (if delayed)
                $slackMsg = '';
                $cancelled = $departure->cancelled;
                $plannedTime = strtotime($departure->plannedDateTime);
                $actualTime = strtotime($departure->actualDateTime);

                $routeMsg = $ns->UICCodeToStationName($depUIC) . ' - ' . $ns->UICCodeToStationName($destUIC);
                $timeMsg = '_' . date('H:i', $plannedTime) . 'u_';

                if ($cancelled) {
                    $slackMsg = $routeMsg . ' ' . $timeMsg . ' *TREIN VERVALT*';
                } else {
                    if ($plannedTime != $actualTime) {
                        $delayMinutes = round(($actualTime - $plannedTime) / 60);

                        $tsBucketBefore = ($actualTime - ($actualTime % (60 * 5)));
                        $tsBucketAfter = ($actualTime + ($actualTime % (60 * 5)));
                        // var_dump($tsBucket, date('Y-m-d H:i:s', $tsBucket));
                        $mergeKeys = [];
                        $mergeKeys[] = $routeMsg . $tsBucketBefore;
                        $mergeKeys[] = $routeMsg . $tsBucketAfter;
                        foreach ($mergeKeys as $mergeKey) {
                            if (isset($mergedKeys[$mergeKey])) {
                                // skip merged
                                if (DEBUG) {
                                    $slackMsg = $routeMsg . ' ' . $timeMsg . ' skipped, duplicate of ' . $mergedKeys[$mergeKey];
                                    $slack->postMessage($slackMsg);
                                }
                                continue 2;
                            }
                        }
                        $mergedKeys[$mergeKey] = $routeMsg . ' ' . $timeMsg . ' delay ' . round(($actualTime - $plannedTime) / 60);
                        // var_dump($routeMsg, $actualTime, $mergeKey);  // @todo cleanup
                        // echo "\n\n"; // @todo cleanup

                        if ($delayMinutes > 5) {
                            if ($delayMinutes > 15) {
                                $slackMsg = $routeMsg . ' ' . $timeMsg . ' *+' . round(($actualTime - $plannedTime) / 60) . ' minuten*';
                            } else {
                                $slackMsg = $routeMsg . ' ' . $timeMsg . ' +' . round(($actualTime - $plannedTime) / 60) . ' minuten';
                            }
                        }
                    } else {
                        if (DEBUG) {
                            // In debug mode we also report on times
                            $slackMsg = $routeMsg . ' ' . $timeMsg . ' on time';
                        }
                    }
                }
                if (!empty($slackMsg)) {
                    if (!in_array($departure->name, $reportedTrains)) {
                        $reportedTrains[] = $departure->name;
                        $slack->postMessage($slackMsg);
                    } else {
                        if (DEBUG) {
                            $slack->postMessage($slackMsg . ', already reported');
                        }
                    }
                }
            }
        }
    }
}


