<h1 align="center"> qqmap-region </h1>

<p align="center"> 腾讯位置服务中国标准行政区划数据 SDK.</p>

<p align="center">
    <a href="https://github.styleci.io/repos/156715216"><img src="https://github.styleci.io/repos/156715216/shield?branch=master" alt="StyleCI"></a>
</p>

## 安装

```shell
$ composer require tumobi/qqmap-region -vvv
```

## 配置
### 创建 key
在使用本扩展之前，你需要去 [腾讯位置服务](https://lbs.qq.com/index.html) 注册账号，在 **key管理** 中创建新密钥。
### 开启 WebServiceAPI
找到刚新创建的 key ，点击 **设置** 按钮进入 KEY 设置页面，勾选 WebServiceAPI 后保存。

## 使用
```php
use Tumobi\QQMapRegion\Region;

$key = '你创建的 key';
$region = new Region($key);
```

### 获取全部行政区划数据
```php
$result = $region->getAllDistrict();
print_r($result);
```
### 获取子级行政区划
```php
// 北京市
$region_id = 110000;
$result = $region->getChildrenDistrict($region_id);
print_r($result);
```

### 搜索指定关键词的行政区划
```php
$keyword = '香格里拉';
$result = $region->searchDistrict($keyword);
print_r($result);
```

### 获取省市区三级选择器行政区划数据
```php
$result = $region->getSelectorRegions();
print_r($result);
```

## 在 Laravel 中使用
安装方式同上，需要添加两处配置，在 config/services.php 加入如下配置
```php

'region' => [
    'key' => env('REGION_KEY'),
],
```

在 .env 文件中加入如下配置
```
REGION_KEY=在腾讯位置服务创建的key

```

### 使用方法
```php
public function edit(Region $region) 
{
    $districts = $region->getAllDistrict();
}
```

或
```php
public function edit() 
{
    $districts = app('region')->getAllDistrict();
}
```

## 参考
+ [行政区划 | 腾讯位置服务](https://lbs.qq.com/webservice_v1/guide-region.html)
+ [PHP 扩展包实战教程 - 从入门到发布](https://laravel-china.org/courses/creating-package?rf=23775)

## License

MIT
