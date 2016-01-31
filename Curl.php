<?php

namespace amirasran\yii2curl;

use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\helpers\Json;
use yii\web\HttpException;
/**
 * Yii2 Curl Library
 *
 * @author Amir Mohsen Asaran <admin@mihanmail.com>
 */
class Curl extends Component
{
    /**
     * @var float timeout to use for http request.
     * This value will be used to configure the curl `CURLOPT_CONNECTTIMEOUT` option.
     * If not set, no explicit timeout will be set for curl.
     */
    public $connectionTimeout = null;

    /**
     * @var float timeout to use when reading the http response.
     * This value will be used to configure the curl `CURLOPT_TIMEOUT` option.
     * If not set, no explicit timeout will be set for curl.
     */
    public $dataTimeout = null;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }

    /**
     * Performs GET HTTP request
     *
     * @param string $url URL
     * @param array $options URL options
     * @param boolean $raw if response body contains JSON and should be decoded
     * @return mixed response
     */
    public function get($url, $options = [], $raw = true)
    {
        return $this->httpRequest('GET', $this->createUrl($url, $options), null, $raw);
    }

    /**
     * Performs HEAD HTTP request
     *
     * @param string $url URL
     * @param array $options URL options
     * @param array $body request body
     * @return mixed response
     */
    public function head($url, $options = [], $body = null)
    {
        return $this->httpRequest('HEAD', $this->createUrl($url, $options), $body);
    }

    /**
     * Performs POST HTTP request
     *
     * @param string $url URL
     * @param array $options URL options
     * @param array $body request body
     * @param boolean $raw if response body contains JSON and should be decoded
     * @return mixed response
     */
    public function post($url, $options = [], $body = null, $raw = true)
    {
        return $this->httpRequest('POST', $this->createUrl($url, $options), $body, $raw);
    }

    /**
     * Performs PUT HTTP request
     *
     * @param string $url URL
     * @param array $options URL options
     * @param array $body request body
     * @param boolean $raw if response body contains JSON and should be decoded
     * @return mixed response
     */
    public function put($url, $options = [], $body = null, $raw = true)
    {
        return $this->httpRequest('PUT', $this->createUrl($url, $options), $body, $raw);
    }

    /**
     * Performs DELETE HTTP request
     *
     * @param string $url URL
     * @param array $options URL options
     * @param array $body request body
     * @param boolean $raw if response body contains JSON and should be decoded
     * @return mixed response
     */
    public function delete($url, $options = [], $body = null, $raw = true)
    {
        return $this->httpRequest('DELETE', $this->createUrl($url, $options), $body, $raw);
    }

    /**
     * Creates URL
     *
     * @param mixed $path path
     * @param array $options URL options
     * @return string
     */
    private function createUrl($path, $options = [])
    {
        if (!is_string($path)) {
            $url = implode('/', array_map(function ($a) {
                return urlencode(is_array($a) ? implode(',', $a) : $a);
            }, $path));
            if (!empty($options)) {
                $url .= '?' . http_build_query($options);
            }
        } else {
            $url = $path;
            if (!empty($options)) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($options);
            }
        }
        return $url;
    }

    /**
     * Performs HTTP request
     *
     * @param string $method method name
     * @param string $url URL
     * @param array $requestBody request body
     * @param boolean $raw if response body contains JSON and should be decoded
     * @throws Exception if request failed
     * @throws HttpException
     * @return mixed if response http code is 404, return false; if http code >= 200 and < 300, return response body;
     * throws HttpException for other http code.
     */
    protected function httpRequest($method, $url, $requestBody = null, $raw = false)
    {
        $method = strtoupper($method);
        // response body
        $body = '';
        $options = [
            CURLOPT_USERAGENT      => 'Yii Framework 2 ' . __CLASS__,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER         => false,
            // http://www.php.net/manual/en/function.curl-setopt.php#82418
            CURLOPT_HTTPHEADER     => ['Expect:'],
            CURLOPT_WRITEFUNCTION  => function ($curl, $data) use (&$body) {
                $body .= $data;
                return mb_strlen($data, '8bit');
            },
            CURLOPT_CUSTOMREQUEST  => $method,
        ];
        if ($this->connectionTimeout !== null) {
            $options[CURLOPT_CONNECTTIMEOUT] = $this->connectionTimeout;
        }
        if ($this->dataTimeout !== null) {
            $options[CURLOPT_TIMEOUT] = $this->dataTimeout;
        }
        if ($requestBody !== null) {
            if(is_array($requestBody))
                $options[CURLOPT_POSTFIELDS] = http_build_query($requestBody);
            else
                $options[CURLOPT_POSTFIELDS] = $requestBody;

        }
        if ($method == 'HEAD') {
            $options[CURLOPT_NOBODY] = true;
            unset($options[CURLOPT_WRITEFUNCTION]);
        }
        $profile = $method . ' ' . $url . '#' . md5(serialize($requestBody));
        Yii::trace("Sending request: $url\n" . Json::encode($requestBody), __METHOD__);
        Yii::beginProfile($profile, __METHOD__);
        $curl = curl_init($url);
        curl_setopt_array($curl, $options);
        if (curl_exec($curl) === false) {
            throw new Exception('curl request failed: ' . curl_error($curl) , curl_errno($curl));
        }
        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        Yii::endProfile($profile, __METHOD__);
        if ($responseCode >= 200 && $responseCode < 300) {
            if ($method == 'HEAD') {
                return true;
            } else {
                return $raw ? $body : Json::decode($body);
            }
        } elseif ($responseCode == 404) {
            return false;
        } else {
            throw new HttpException($responseCode, $body);
        }
    }
}