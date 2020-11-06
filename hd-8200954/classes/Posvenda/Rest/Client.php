<?php

namespace Posvenda\Rest;

class Client
{
    /**
     * @var resource $curl
     */
    private $curl;

    /**
     * @var string $url
     */
    private $url;

    /**
     * @var string $api
     */
    private $api;

    /**
     * @var string $resource
     */
    private $resource;

    /**
     * @var string $params
     */
    private $params;

    /**
     * @var array $headers
     */
    private $headers = array();

    /**
     * @var string $method
     */
    private $method;

    /**
     * @var string $body
     */
    private $body = '';

    /**
     * @var boolean $json
     */
    private $json = false;

    /**
     * @param string $api
     * @param array $headers
     */
    public function __construct($api, $headers = array())
    {
        $this->api = $api;

        if (!empty($headers)) {
            foreach ($headers as $k => $v) {
                if (is_int($k)) {
                    $this->headers[] = $v;
                } else {
                    $this->headers[] = $k . ': ' . $v;
                }
            }
        }
    }

    /**
     * @var mixed $json
     *
     * @return Posvenda\Rest\Client
     */
    public function setJson($json)
    {
        /**
         * XXX - faz cast para boolean, logo qualquer valor informado que não
         *  seja boolean false fará que o valor seja true.
         */
        $this->json = (boolean) $json;

        return $this;

    }

    /**
     * @var string $resource
     * @var string|array $param
     *
     * @return array
     */
    public function get($resource, $params = null)
    {
        $this->method = "GET";

        return $this->request($resource, $params);
    }

    /**
     * @var string $resource
     * @var string|array $data
     *
     * @return array
     */
    public function post($resource, $data)
    {
        $this->method = "POST";

        return $this->request($resource, null, $data);
    }

    /**
     * @var string $resource
     * @var string|array $data
     *
     * @return array
     */
    public function put($resource, $data)
    {
        $this->method = "PUT";

        return $this->request($resource, null, $data);
    }

    /**
     * @var string $resource
     *
     * @return array
     */
    public function delete($resource)
    {
        $this->method = "DELETE";

        return $this->request($resource);
    }

    /**
     * @var string $resource
     * @var string|array $params
     * @var string|array $data
     *
     * @return array
     */
    private function request($resource, $params = null, $data = null)
    {
        $this->resource = $resource;

        if ($this->method == "GET" and !empty($params)) {
            $p = $params;

            if (is_array($params)) {
                $p = '';

                foreach ($params as $k => $v) {
                    $p .= '/' . $k . '/' . $v;
                }
            }

            $this->params = $p;
        } elseif (in_array($this->method, array("POST", "PUT")) and !empty($data)) {
            $this->buildRequestData($data);
        }

        $this->buildUrl();

        return $this->curlExec();
    }

    /**
     * @return Posvenda\Rest\Client
     */
    private function buildUrl()
    {
        $this->url = $this->api;

        if (!empty($this->resource)) {
            $this->url .= $this->resource;
        }

        if (!empty($this->params)) {
            $this->url .= $this->params;
        }

        return $this;
    }

    /**
     * @param array $data
     *
     * @return Posvenda\Rest\Client
     */
    private function buildRequestData($data)
    {
        if (true === $this->json) {
            $this->headers[] = 'Content-Type: application/json';

            $str = json_encode($data);

            if (null === $str) {
                $str = '';
            }

            $this->body = $str;

            return $this;
        }

        $str = '';

        foreach ($data as $k => $v) {
            $str .= $k . '=' . $v . '&';
        }

        $this->body = rtrim($str, '&');

        return $this;
    }

    /**
     * @return array
     */
    private function curlExec()
    {
        $this->curl = curl_init();

        curl_setopt($this->curl, CURLOPT_URL, $this->url);

        switch ($this->method) {
            case "POST":
                curl_setopt($this->curl, CURLOPT_POST, true);
                break;
            case "PUT":
            case "DELETE":
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $this->method);
                break;
        }

        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);

        if (!empty($this->headers)) {
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
        }

        if (!empty($this->body)) {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->body);
        }

        $response = curl_exec($this->curl);
        $httpCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        curl_close($this->curl);

        return array(
            "status_code" => $httpCode,
            "response" => $response
        );
    }
}
