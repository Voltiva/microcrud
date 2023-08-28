<?php

namespace Microcrud\Services\Curl\Services;

use Microcrud\Services\Curl\Exceptions\CurlException;

class CurlService
{
    private $type;
    private $url;
    private $headers = [];
    private $params = [];

    private $response = [];
    private $request = [];

    const GET = 'GET';
    const POST = 'POST';

    public function get($url)
    {
        return $this->invokeCurlRequest(self::GET, $url);
    }

    public function post($url)
    {
        return $this->invokeCurlRequest(self::POST, $url);
    }

    protected function invokeCurlRequest($type, $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if (!empty($this->headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($type == self::POST && !empty($this->params)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->params));
        }
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err) {
            throw new CurlException("Error: {$err}", 403);
        }
        $this->response = json_decode($response, true);
        return $this;
    }

    public function setHeader(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    public function setParams($params)
    {
        $this->params = $params;
        return $this;
    }

    protected function getParams()
    {
        return $this->params;
    }

    public function getResponse($key = null)
    {
        if ($key) {
            if (array_key_exists($key, $this->response)) {
                return $this->response[$key];
            } else {
                throw new CurlException('Invalid response key: ' . $key);
            }
        }
        return $this->response;
    }

    public function getUrl($key)
    {
        $base_user_url = env('BASE_USER_URL', '');
        $base_notification_url = env('BASE_NOTIFICATION_URL', '');
        $base_car_url = env('BASE_CAR_URL', '');
        $base_payment_url = env('BASE_PAYMENT_URL', '');

        $urls = [

            'user'=>$base_user_url.'',
            'notification'=>$base_notification_url.'create',
            'car'=>$base_car_url.'create',
            'payment'=>$base_payment_url.'create',
        ];
        return $urls[$key];
    }
}
