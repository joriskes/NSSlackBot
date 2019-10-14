<?php

class CacheHelper
{
    private $cachePath;

    public function __construct()
    {
    }

    protected function setCachePath($directory = '')
    {
        if(empty($directory)) throw new Error('Invalid cache path');

        if (!file_exists($directory)) {
            mkdir($directory);
            chmod($directory, 0777);
        }
        $this->cachePath = $directory;
    }

    private function endpointToCacheFile($endpoint)
    {
        return $this->cachePath . '/' . md5($endpoint) . '.json';
    }

    protected function hasCache($endpoint, $maximum_age = '10 years')
    {
        $fileobj = $this->fromCache($endpoint);
        if ($fileobj) {
            if (intval($fileobj->_cache_age) < strtotime('-' . $maximum_age)) {
                return false;
            }
            return true;
        }
        return false;
    }

    protected function fromCache($endpoint)
    {
        $filename = $this->endpointToCacheFile($endpoint);
        if (file_exists($filename)) {
            $resJson = file_get_contents($filename);
            if (!empty($resJson)) return @json_decode($resJson);
        }
        return null;
    }

    protected function toCache($endpoint, $object)
    {
        $object->_cache_age = time();
        file_put_contents($this->endpointToCacheFile($endpoint), json_encode($object));
    }
}
