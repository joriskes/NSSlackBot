<?php

require('CacheHelper.php');

class NSApiHelper extends CacheHelper
{
    // https://apiportal.ns.nl/docs/services/public-reisinformatie-api/operations/ApiV2StationsGet
    private $api_url = 'https://gateway.apiportal.ns.nl/public-reisinformatie/api/v2/';
    private $api_key = NS_API_KEY;
    private $stationList = [];

    public function __construct()
    {
        parent::__construct();
        $this->setCachePath(dirname(__FILE__) . '/../cache');
        if (substr($this->api_url, -1) != '/') $this->api_url .= '/';
    }

    private function createUrl($postfix)
    {
        $url = $this->api_url;
        if (substr($postfix, 0, 1) == '/') $postfix = substr(1, $postfix);
        return $url . $postfix;
    }

    private function get($url, $maximum_age = '10 years')
    {
        if ($this->hasCache($url, $maximum_age)) {
            return $this->fromCache($url);
        }
        $uri = $this->createUrl($url);

        $reqPrefs = ["http" => [
                "method" => "GET",
                "header" =>
                    "Ocp-Apim-Subscription-Key: $this->api_key\r\n",
                "user_agent" => "Train delay notifications bot"
            ],
        ];

        $stream_context = stream_context_create($reqPrefs);
        $response = file_get_contents($uri, false, $stream_context);
        $resObject = @json_decode($response);
        if (!empty($resObject)) {
            $this->toCache($url, $resObject);
        }
        return $resObject;
    }

    public function getStations()
    {
        $res = $this->get('stations', '7 days');
        if($res && $res->payload) return $res->payload;
        return $res;
    }

    public function getDisruptions() {
        return $this->get('disruptions?type=storing&actual=true', '15 minutes');
    }

    public function getDepartures($uicCode) {
        $res = $this->get('departures?uicCode='.$uicCode, '9 minutes');
        if($res && $res->payload && $res->payload->departures) return $res->payload->departures;
        return $res;
    }

    public function stationNameToUICCode($name) {
        if(count($this->stationList) < 1) $this->stationList = $this->getStations();

        foreach($this->stationList as $station) {
            foreach($station->namen as $naam) {
                if($naam === $name) {
                    return $station->UICCode;
                }
            }
        }
        return false;
    }

    public function UICCodeToStationName($uicCode) {
        if(count($this->stationList) < 1) $this->stationList = $this->getStations();

        foreach($this->stationList as $station) {
            if($station->UICCode == $uicCode) {
                return $station->namen->middel;
            }
        }
        return false;
    }

}
