<?php

class REST {

    /** connection parameters */
    const AUTH_USER = 'user';
    const AUTH_PASSWORD = 'password';
    const POST_PAYLOAD = 'payload';
    const FOLLOW_REDIRECTS = 'follow-redirects';
    const HEADERS = 'headers';
    const CONN_TIMEOUT = 'conn-timeout';
    const TIMEOUT = 'timeout';
    const STREAMING = 'streaming';

    /** default connection parameters */
    const DEFAULT_CONN_TIMEOUT = 10;
    const DEFAULT_TIMEOUT = 60;

    /** @var array curl handles, keyed by request parameter hash */
    protected static $_chs = array();

    /** @var array param keys that are used to generate the connection key */
    protected static $_keyParams = array(
        self::AUTH_USER => true,
        self::AUTH_PASSWORD => true,
        self::FOLLOW_REDIRECTS => true,
        self::HEADERS => true,
        self::CONN_TIMEOUT => true,
        self::TIMEOUT => true,
        self::STREAMING => true,
    );

    /** @var array param keys that result in a new connection every request */
    protected static $_nocacheParams = array(
        self::POST_PAYLOAD => true,
    );

    /** @var int last request's http response code */
    protected static $_httpResponseCode = null;

    /** @var array last request's http response headers */
    protected static $_httpResponseHeaders = array();

    protected static $_httpResponseHeader = null;

    /**
     * for any given request, determine whether to create or reuse connections
     *
     * @static
     * @param array $params  request parameters
     *
     * @return cURL handle
     */
    protected static function _getCurl($params) {
        $keyData = array();
        ksort($params);
        foreach ($params as $key => $val) {
            if (isset(self::$_nocacheParams[$key])) {
                return self::_initCurl($params);
            }
            if (isset(self::$_keyParams[$key])) {
                $keyData[] = $val;
            }
        }
        $connectionKey = md5(json_encode($keyData));
        if (! isset(self::$_chs[$connectionKey])) {
            self::$_chs[$connectionKey] = self::_initCurl($params);
        }
        return self::$_chs[$connectionKey];
    }

    /**
     * curl response header callback
     *
     * @static
     * @param cURL $ch
     * @param string $string  contents of response header as a string
     *
     * @return int  header length
     */
    public static function _processResponseHeader($ch, $string) {
        self::$_httpResponseHeader .= $string;
        return strlen($string);
    }

    /**
     * returns the response code of the last http request
     *
     * @static
     *
     * @return int  response code
     */
    public static function getLastResponseCode() {
        return self::$_httpResponseCode;
    }

    /**
     * get the last http request's response header value or headers as key-value pair
     *
     * @static
     * @param string|null $key  return the value for the specified key; null for all headers
     *
     * @return string|array  value for the specified header, null if it does not exist; key-value pairs for all headers
     */
    public static function getLastResponseHeaders($key = null) {
        return ! is_null($key) ? (isset(self::$_httpResponseHeaders[$key]) ? self::$_httpResponseHeaders[$key] : null) : self::$_httpResponseHeaders;
    }

    /**
     * init the static curl object
     *
     * @static
     * @param array $params  key-value pairs for connection settings, such as username/password
     *
     * @return cURL
     */
    protected static function _initCurl($params = array()) {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_CONNECTTIMEOUT => isset($params[self::CONN_TIMEOUT]) ? $params[self::CONN_TIMEOUT] : self::DEFAULT_CONN_TIMEOUT,
            CURLOPT_TIMEOUT => isset($params[self::TIMEOUT]) ? $params[self::TIMEOUT] : self::DEFAULT_TIMEOUT,
            CURLOPT_USERAGENT => 'REST client v0.1',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HEADERFUNCTION => array(__CLASS__, '_processResponseHeader'),
        ));
        if (isset($params[self::AUTH_USER]) && isset($params[self::AUTH_PASSWORD])) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $params[self::AUTH_USER] . ':' . $params[self::AUTH_PASSWORD]);
        }
        if (isset($params[self::FOLLOW_REDIRECTS])) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $params[self::FOLLOW_REDIRECTS]);
        }
        if (isset($params[self::HEADERS])) {
            $headers = is_array($params[self::HEADERS]) ? $params[self::HEADERS] : array($params[self::HEADERS]);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if (isset($params[self::STREAMING]) && is_callable($params[self::STREAMING])) {
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, $params[self::STREAMING]);
        }
        return $ch;
    }

    /**
     * parse response and populate some static vars
     *
     * @static
     *
     * @return void
     */
    protected static function _parseResponse($ch) {
        self::$_httpResponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        self::$_httpResponseHeaders = array();
        $headers = explode("\r\n", self::$_httpResponseHeader);
        foreach ($headers as $header) {
            if (preg_match('/^([^:]+):\s*(.+)$/', $header, $matches)) {
                self::$_httpResponseHeaders[$matches[1]] = $matches[2];
            }
        }
    }

    /**
     * make the actual request
     *
     * @static
     * @param string $method  request method type, e.g. 'GET', 'POST'
     * @param string $url  url to fetch
     * @param array $params  key-value pairs for additional connection/request settings
     *
     * @return mixed  request response; null if an error occurred
     */
    protected static function _doRequest($method, $url, $params = array()) {
        $ch = self::_getCurl($params);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_URL, $url);
        if (isset($params[self::POST_PAYLOAD]) && ($method === 'POST' || $method === 'PUT')) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params[self::POST_PAYLOAD]);
        }

        self::$_httpResponseHeader = '';
        $return = curl_exec($ch);
        self::_parseResponse($ch);
        if (self::$_httpResponseCode < 200 || self::$_httpResponseCode > 299) {
            throw new Exception('Request returned ' . self::$_httpResponseCode . ' HTTP response code: '.$url);
        }
        return $return;
    }

    /**
     * make an http get request for the provided url and parameters
     *
     * @static
     * @param string $url  url to fetch
     * @param array $params  key-value pairs
     *
     * @return mixed
     */
    public static function get($url, $params = array()) {
        return self::_doRequest('GET', $url, $params);
    }

    /**
     * make an http post request for the provided url and parameters
     *
     * @static
     * @param string $url  url to fetch
     * @param array $params  key-value pairs
     *
     * @return mixed
     */
    public static function post($url, $params = array()) {
        return self::_doRequest('POST', $url, $params);
    }

    /**
     * make an http put request for the provided url and parameters
     *
     * @static
     * @param string $url  url to fetch
     * @param array $params  key-value pairs
     *
     * @return mixed
     */
    public static function put($url, $params = array()) {
        return self::_doRequest('PUT', $url, $params);
    }

    /**
     * make an http delete request for the provided url and parameters
     *
     * @static
     * @param string $url  url to fetch
     * @param array $params  key-value pairs
     *
     * @return mixed
     */
    public static function delete($url, $params = array()) {
        return self::_doRequest('DELETE', $url, $params);
    }

}
