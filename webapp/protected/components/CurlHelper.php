<?php

class CurlHelper {
    private $_options = array();
    private $_curlInit;
    private $_response;

    public function __construct($url, $options = array()) {
        if (!is_array($options) || !$url) {
            throw new InvalidArgumentException("Cannot create CurlHelper. Parameters are not valid.");
        }

        $this->_options = $options;
        $this->_curlInit = curl_init($url);
    }

    public function execute() {
        curl_setopt($this->_curlInit, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_curlInit, CURLOPT_POST, true);
        curl_setopt($this->_curlInit, CURLOPT_POSTFIELDS, $this->_options);

        $this->_response = curl_exec($this->_curlInit);

        curl_close($this->_curlInit);
    }

    public function getResponse() {
        return $this->_response;
    }

    public function getResponseXML() {
        if (!$this->_response) {
            throw new Exception("Response is null");
        }

        return new SimpleXMLElement($this->_response);
    }
}