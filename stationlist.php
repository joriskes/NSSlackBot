<?php

require('helpers/NSApiHelper.php');

$ns = new NSApiHelper();

$stations = $ns->getStations();
foreach($stations as $station) {
    echo $station->namen->lang.' '.$station->UICCode.'<br/>';
}
