<?php

namespace App\Classes;

/**
 * Class ZoomHttpRequest
 *
 * Zoom HTTP Request builder
 *
 * @package App\Classes
 */
class ZoomHttpRequest
{
    private $headers = [];
    private $params = [];
    private $body = null;
    private $url;
    private $method = 'GET';
    private $miscOptions = [];


    public function getHeaders() {
        return $this->headers;
    }

    public function setHeaders($headers) {
        $this->headers = $headers;
        return $this;
    }

    public function getParams() {
        return $this->params;
    }

    public function setParams($params) {
        $this->params = $params;
        return $this;
    }

    public function getBody() {
        return $this->body;
    }

    public function setBody($body) {
        $this->body = $body;
        return $this;
    }

    public function getUrl() {
        return $this->url;
    }

    public function setUrl($url) {
        $this->url = $url;
        return $this;
    }

    public function getMethod()  {
        return $this->method;
    }

    public function setMethod($method) {
        $this->method = $method;
        return $this;
    }

    /**
     * @return array
     */
    public function getMiscOptions() {
        return $this->miscOptions;
    }

    /**
     * @param array $miscOptions
     * @return self
     */
    public function setMiscOptions($miscOptions)
    {
        $this->miscOptions = $miscOptions;
        return $this;
    }

}