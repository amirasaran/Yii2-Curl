Yii2 Curl
=========
Create Rest Request (POST,GET,PUT,DELETE,...)

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require amirasaran/yii2-curl:dev-master
```

or add

```
"amirasaran/yii2-curl": "dev-master"
```

to the require section of your `composer.json` file.


Configure
---------

Add the following code to your `common/config/main.php` `components`

```php
'components' => [
    ...
    'curl' => [
        'class' => 'amirasaran\yii2curl\Curl',
        'connectionTimeout' => 100,
        'dataTimeout' => 100,
    ],
    ...
]
```


Usage
-----

```php
        $url = 'http://jsonplaceholder.typicode.com/posts';
        /** @var \amirasaran\yii2curl\Curl $curl */
        $curl = Yii::$app->curl;
        $res = $curl->get($url,[],false);
        echo '<pre>';
        print_r($res);exit;
```
