<?php

class Redis_Id {

    /** key for review ids */
    const KEY_REVIEW = 'ID:REVIEW';

    /** key for hotel ids */
    const KEY_HOTEL = 'ID:HOTEL';

    /** key for review site ids */
    const KEY_REVIEW_SITE = 'ID:REVIEW_SITE';

    /** key for provider batch ids */
    const KEY_PROVIDER_BATCH = 'ID:PROVIDER_BATCH';

    /**
     * gets an id for the specified key
     *
     * @static
     * @param string $idKey  id key
     *
     * @return int
     */
    public static function get($idKey) {
        return (int) Redis_Client::create()->incr($idKey);
    }

    /**
     * sets an id for the specified key - protected to prevent common use; extend this class to use
     *
     * @static
     * @param string $idKey  id key
     * @param int $value  new id value
     *
     * @return bool  whether the set was successful
     */
    protected static function set($idKey, $value) {
        return Redis_Client::create()->set($idKey, (int) $value);
    }

}
