<?php

class Mongo_Client {

    /** default host */
    const DEFAULT_HOST = '127.0.0.1';

    /** default connection port */
    const DEFAULT_PORT = 27017;

    /** default mongodb database */
    const DEFAULT_DB = 'test';

    /** @var Mongo[]  mongo connection instance by key */
    protected static $_instances = null;

    /** @var array  cache of collections by key */
    protected static $_listCollectionsCache = array();

    /**
     * create/return a mongo client connection
     *
     * @static
     * @param null|string $host  hostname to connect to; null to use config
     * @param int $port  port to connect to; default is 6379
     * @param string $db  database name to connect to; default 'test'
     *
     * @return MongoDB  a mongo db connection instance
     */
    public static function create($host = null, $port = self::DEFAULT_PORT, $db = self::DEFAULT_DB) {
        if (is_null($host)) {
            // use config details
            $mongo_ip_port = sfConfig::get('sf_mongo_ip_port', self::DEFAULT_HOST);
            $db = sfConfig::get('sf_mongo_db', self::DEFAULT_DB);
            list ($host, $port) = explode(':', $mongo_ip_port, 2);
            if (is_null($port)) { $port = self::DEFAULT_PORT; }
        }
        $key = self::_createConnectionKey($host, $port, $db);
        if (! isset(self::$_instances[$key])) {
            $mongo = new Mongo('mongodb://' . $host . ':' . $port, array('persist' => $key));
            self::$_instances[$key] = $mongo->selectDB($db);
        }
        return self::$_instances[$key];
    }

    /**
     * return a unique connection key based on connection parameters
     *
     * @static
     * @param string $host  hostname to connect to
     * @param int $port  connection port
     * @param string $db  database to connect to
     *
     * @return string
     */
    protected static function _createConnectionKey($host, $port, $db) {
        return implode(':', array($host, $port, $db));
    }

    /**
     * return a collection object for use in querying
     *
     * @static
     * @param string $collectionName  name of the collection to access
     * @param null|string $host  hostname to connect to
     * @param int $port  port to connect to
     * @param string $db  database to connect to
     *
     * @return MongoCollection
     */
    public static function collection($collectionName, $host = null, $port = self::DEFAULT_PORT, $db = self::DEFAULT_DB) {
        return self::create($host, $port, $db)->selectCollection($collectionName);
    }

    /**
     * determine whether a collection exists
     *
     * @static
     * @param string $collectionName  collection to test
     * @param bool $useStaticCache  whether to statically cache the results of listCollections()
     * @param null|string $host  hostname to connect to
     * @param int $port  port to connect to
     * @param string $db  database to connect to
     *
     * @return bool
     */
    public static function collectionExists($collectionName, $useStaticCache = true, $host = null, $port = self::DEFAULT_PORT, $db = self::DEFAULT_DB) {
        $key = self::_createConnectionKey($host, $port, $db);
        if ($useStaticCache && isset(self::$_listCollectionsCache[$key])) {
            return isset(self::$_listCollectionsCache[$key][$collectionName]);
        }
        $db = self::create($host, $port, $db);
        $collections = $db->listCollections();
        $byCollectionName = array();
        foreach ($collections as $collection) {
            $byCollectionName[$collection->getName()] = true;
        }
        if ($useStaticCache) {
            self::$_listCollectionsCache[$key] = $byCollectionName;
        }
        return isset($byCollectionName[$collectionName]);
    }

    /**
     * checks the status of the default mongodb connection
     *
     * @static
     * @return bool  does mongodb appear up or down?
     */
    public static function status() {
        try {
            return ! is_null(self::create()->getProfilingLevel());
        } catch (Exception $e) {
            return false;
        }
    }

}