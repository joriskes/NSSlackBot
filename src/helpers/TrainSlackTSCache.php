<?php

class TrainMessage {
    public $trainId;
    public $slackTS;
    public $from;
    public $to;
    public $plannedTimestamp;
    public $cancelled;
    public $delayedMinutes;
}

class TrainSlackTSCache extends CacheHelper
{
    private $endPoint = 'trainslacktscache';
    private $trainCache = null;

    public function __construct()
    {
        parent::__construct();
        $this->setCachePath(dirname(__FILE__) . '/../cache');
        if(!$this->hasCache($this->endPoint)) {
            $obj = new \stdClass();
            $obj->trains = Array();
            $this->toCache($this->endPoint, $obj);
        }
        $this->trainCache = $this->fromCache($this->endPoint);
        // Json decode returns all objects, convert the trains back to array
        $this->trainCache->trains = (array) $this->trainCache->trains;
    }

    public function findTrain($trainId) {
        if(isset($this->trainCache->trains[$trainId])) {
            return $this->trainCache->trains[$trainId];
        } else {
            $trainMessage = new TrainMessage();
            $trainMessage->trainId = $trainId;
            $trainMessage->slackTS = '';
            return $trainMessage;
        }
    }

    public function saveTrain($trainMessage) {
        $this->trainCache->trains[$trainMessage->trainId] = $trainMessage;
    }

    public function save() {
        $clearTime = strtotime('-4 hours');

        // Filter out all trains with planned departure older than 4 hours
        $this->trainCache->trains = array_filter($this->trainCache->trains,
            function($train) use ($clearTime)
            {
                return $train->plannedTimestamp > $clearTime;
            });
        $this->toCache($this->endPoint, $this->trainCache);
    }
}
