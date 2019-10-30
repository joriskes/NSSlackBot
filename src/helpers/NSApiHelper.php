<?php

class NSApiHelper extends CacheHelper
{
    private $api_url = 'https://gateway.apiportal.ns.nl/public-reisinformatie/api/v2/';
    private $api_key = NS_API_KEY;
    private $stationList = [];

    public function __construct()
    {
        parent::__construct();
        $this->setCachePath(dirname(__FILE__) . '/../cache');
        if (substr($this->api_url, -1) != '/') {
            $this->api_url .= '/';
        }
    }

    private function createUrl($postfix)
    {
        $url = $this->api_url;
        if (substr($postfix, 0, 1) == '/') {
            $postfix = substr(1, $postfix);
        }
        return $url . $postfix;
    }

    private function get($url, $maximum_age = '10 years')
    {
        if ($this->hasCache($url, $maximum_age)) {
            return $this->fromCache($url);
        }
        $uri = $this->createUrl($url);

        $c = curl_init($uri);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false); // This is bad
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        $headers = [];
        $headers[] = 'Content-Type: application/json; charset=utf-8';
        $headers[] = 'Ocp-Apim-Subscription-Key: ' . $this->api_key;
        $headers[] = 'user_agent: NSSlackBot';
        curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($c);
        curl_close($c);

        if ($result) {
            $resultJson = @json_decode($result);
            if ($resultJson) {
                return $resultJson;
            }
        }
        return $result;
    }

    public function getStations()
    {
        $res = $this->get('stations', '7 days');
        if ($res && $res->payload) {
            return $res->payload;
        }
        return $res;
    }

    public function getDisruptions()
    {
        return $this->get('disruptions?type=storing&actual=true', '15 minutes');
    }

    public function getDepartures($uicCode)
    {
        $res = $this->get('departures?uicCode=' . $uicCode, '5 minutes');
        if ($res && $res->payload && $res->payload->departures) {
            return $res->payload->departures;
        }
        return $res;
    }

    public function stationNameToUICCode($name)
    {
        if (count($this->stationList) < 1) {
            $this->stationList = $this->getStations();
        }

        foreach ($this->stationList as $station) {
            foreach ($station->namen as $naam) {
                if ($naam === $name) {
                    return $station->UICCode;
                }
            }
        }
        return false;
    }

    public function UICCodeToStationName($uicCode)
    {
        if (count($this->stationList) < 1) {
            $this->stationList = $this->getStations();
        }

        foreach ($this->stationList as $station) {
            if ($station->UICCode == $uicCode) {
                return $station->namen->middel;
            }
        }
        return false;
    }

}
