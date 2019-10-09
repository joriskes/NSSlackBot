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
if($time > 5 && $time < 13) $trajecten = TRAJECTEN_OCHTEND;
if($time > 12 && $time < 24) $trajecten = TRAJECTEN_MIDDAG;

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
                    $trajectDestUIC = $ns->stationNameToUICCode($dep->direction);
                    if($destUIC == $trajectDestUIC) return true;

                    if($dep->routeStations && count($dep->routeStations) > 0) {
                        foreach($dep->routeStations as $routeStation) {
                            if($routeStation->uicCode == $destUIC) {
                                return true;
                            }
                        }
                    }
                    return false;
                });
        }
        foreach ($departures as $departure) {
            $slackMsg = '';
            $cancelled = $departure->cancelled;
            $plannedTime = strtotime($departure->plannedDateTime);
            $actualTime = strtotime($departure->actualDateTime);

            if($cancelled) {
                $slackMsg = $ns->UICCodeToStationName($depUIC).' - '.$ns->UICCodeToStationName($destUIC).': '.date('H:i',$plannedTime).'u TREIN VERVALT';
            } else {
                if($plannedTime != $actualTime) {
                    $slackMsg = $ns->UICCodeToStationName($depUIC).' - '.$ns->UICCodeToStationName($destUIC).': '.date('H:i',$plannedTime).'u '.round(($actualTime - $plannedTime)/60).' minuten vertraging';
                } else {
                    if(DEBUG) {
                        // In debug mode we also report on times
                        $slackMsg = $ns->UICCodeToStationName($depUIC).' - '.$ns->UICCodeToStationName($destUIC).': '.date('H:i',$plannedTime).'u on time';
                    }
                }
            }
            if(!empty($slackMsg)) {
                if(!in_array($departure->name, $reportedTrains)) {
                    $reportedTrains[] = $departure->name;
                    $slack->postMessage($slackMsg);
                } else {
                    if (DEBUG) {
                        $slack->postMessage($slackMsg.', already reported');
                    }
                }
            }
        }
    }
}


