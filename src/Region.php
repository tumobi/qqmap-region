<?php

namespace Tumobi\QQMapRegion;

use GuzzleHttp\Client;
use Tumobi\QQMapRegion\Exceptions\HttpException;
use Tumobi\QQMapRegion\Exceptions\InvalidArgumentException;

class Region
{
    protected $key = '';

    protected $guzzleOptions = [];

    private $caches = [];

    public function __construct($key)
    {
        $this->key = $key;
    }

    public function getAllDistrict()
    {
        if (isset($this->caches['allDistrict']) && !empty($this->caches['allDistrict'])) {
            return $this->caches['allDistrict'];
        }

        $url = 'https://apis.map.qq.com/ws/district/v1/list';

        $query = array_filter([
            'key' => $this->key,
        ]);

        try {
            $result = $this->getHttpClient()->get($url, [
                'query' => $query,
            ])->getBody()->getContents();

            $result = json_decode($result, true);
            $this->caches['allDistrict'] = $result;
            return $result;
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getChildrenDistrict($id)
    {
        $url = 'https://apis.map.qq.com/ws/district/v1/getchildren';

        if (empty($id) || !is_int($id)) {
            throw new InvalidArgumentException('id 不得为空');
        }

        $query = array_filter([
            'key' => $this->key,
            'id' => $id,
        ]);

        try {
            $result = $this->getHttpClient()->get($url, [
                'query' => $query,
            ])->getBody()->getContents();

            return json_decode($result, true);
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function searchDistrict($keyword)
    {
        $url = 'https://apis.map.qq.com/ws/district/v1/search?&keyword=香格里拉&key=OB4BZ-D4W3U-B7VVO-4PJWW-6TKDJ-WPB77';

        if (empty($keyword)) {
            throw new InvalidArgumentException('keyword 不得为空');
        }

        $query = array_filter([
            'key' => $this->key,
            'keyword' => $keyword,
        ]);

        try {
            $result = $this->getHttpClient()->get($url, [
                'query' => $query,
            ])->getBody()->getContents();

            return json_decode($result, true);
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getHttpClient()
    {
        return new Client($this->guzzleOptions);
    }

    public function setGuzzleOptions(array $options)
    {
        $this->guzzleOptions = $options;
    }
}
