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

    public function getSelectorRegions()
    {
        $districts = null;
        try {
            $districts = $this->getAllDistrict();
            $districts = $districts['result'];
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }

        // 省
        $regions = [];
        foreach ($districts[0] as $key => $province) {
            $province_region = [
                'id' => $province['id'],
                'parent_id' => 0,
                'name' => isset($province['name']) ? $province['name'] : $province['fullname'],
                'fullname' => $province['fullname'],
                'level' => 0,
                'children' => [],
            ];

            // 获取市级数据
            $cities = $this->getChildrenRegion($province, $districts[1]);
            foreach ($cities as $index => $city) {
                // 保存没有三级区域的数据
                if (!isset($city['cidx']) || !is_array($city['cidx'])) {
                    $new_city = $province_region;
                    $new_city['id'] = $province['id'] + 9900;
                    // 判断是否添加过
                    $max_index = count($province_region['children']) - 1;
                    if ($max_index >= 0) {
                        $max_children = $province_region['children'][$max_index];
                        if ($max_children && $max_children['id'] === $new_city['id']) {
                            continue;
                        }
                    }

                    $new_cities = $cities;
                    if (mb_strpos($province['fullname'], '省', 0, 'utf-8')) {
                        $new_city['name'] = '省直辖县级行政区划';
                        $new_city['fullname'] = '省直辖县级行政区划';
                        $new_cities = $this->getNoChildrenCities($cities);
                    } else if (mb_strpos($province['fullname'], '自治区', 0, 'utf-8')) {
                        $new_city['name'] = '自治区直辖县级行政区划';
                        $new_city['fullname'] = '自治区直辖县级行政区划';
                        $new_cities = $this->getNoChildrenCities($cities);
                    }
                    $new_cities = array_map(function ($v) use ($new_city) {
                        return [
                            'id' => $v['id'],
                            'parent_id' => $new_city['id'],
                            'name' => $v['name'],
                            'fullname' => $v['fullname'],
                            'level' => 2,
                            'children' => [],
                        ];
                    }, $new_cities);
                    $city_region = [
                        'id' => $new_city['id'],
                        'parent_id' => $province['id'],
                        'name' => $new_city['name'],
                        'fullname' => $new_city['fullname'],
                        'level' => 1,
                        'children' => $new_cities,
                    ];

                } else {
                    $city_region = [
                        'id' => $city['id'],
                        'parent_id' => $province['id'],
                        'name' => isset($city['name']) ? $city['name'] : $city['fullname'],
                        'fullname' => $city['fullname'],
                        'level' => 1,
                        'children' => [],
                    ];

                    // 获取县、区
                    $counties = $this->getChildrenRegion($city, $districts[2]);
                    foreach ($counties as $i => $county) {
                        $county_region = [
                            'id' => $county['id'],
                            'parent_id' => $city['id'],
                            'name' => isset($county['name']) ? $county['name'] : $county['fullname'],
                            'fullname' => $county['fullname'],
                            'level' => 2,
                            'children' => [],
                        ];
                        $city_region['children'][] = $county_region;
                    }
                }

                $province_region['children'][] = $city_region;
            }

            $regions[] = $province_region;
        }

        return $regions;
    }

    private function getChildrenRegion($region, $regions)
    {
        $length = $region['cidx'][1] - $region['cidx'][0] + 1;
        return array_slice($regions, $region['cidx'][0], $length);
    }

    private function getNoChildrenCities($cities)
    {
        $arr = [];
        foreach ($cities as $city) {
            if (!isset($city['cidx']) || !is_array($city['cidx'])) {
                $arr[] = $city;
            }
        }

        return $arr;
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
