<?php

class Redis_Client {

    /** default host */
    const DEFAULT_HOST = '127.0.0.1';

    /** default connection port */
    const DEFAULT_PORT = 6379;

    /** default timeout in seconds */
    const DEFAULT_TIMEOUT = 3.0;

    /** @var Redis[]  redis connection instance by key */
    protected static $_instances = null;

    /**
     * create/return a redis client connection
     *
     * @static
     * @param null|string $host  hostname to connect to; null to use config
     * @param int $port  port to connect to; default is 6379
     * @param float $timeout  timeout in seconds; default is 3.0
     *
     * @return Redis  a redis connection object
     */
    public static function create($host = null, $port = self::DEFAULT_PORT, $timeout = self::DEFAULT_TIMEOUT) {
        if (is_null($host)) {
            // use config details
            $redis_ip_port = sfConfig::get('sf_redis_ip_port', self::DEFAULT_HOST);
            $timeout = sfConfig::get('sf_redis_timeout', self::DEFAULT_TIMEOUT);
            list ($host, $port) = explode(':', $redis_ip_port, 2);
            if (is_null($port)) { $port = self::DEFAULT_PORT; }
        }
        $key = self::_createConnectionKey($host, $port, $timeout);
        if (! isset(self::$_instances[$key])) {
            self::$_instances[$key] = new Redis();
            self::$_instances[$key]->pconnect($host, $port, $timeout);
        }
        return self::$_instances[$key];
    }

    /**
     * return a unique connection key based on connection parameters
     *
     * @static
     * @param string $host  hostname to connect to
     * @param int $port  connection port
     * @param float $timeout  timeout in seconds
     *
     * @return string
     */
    protected static function _createConnectionKey($host, $port, $timeout) {
        return implode(':', array($host, $port, $timeout));
    }

    /**
     * returns whether the default redis connection appears to be up
     *
     * @static
     * @return bool
     */
    public static function status() {
        try {
            return self::create()->ping() === '+PONG';
        } catch (Exception $e) {
            return false;
        }
    }

}