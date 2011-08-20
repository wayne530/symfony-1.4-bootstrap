<?php

class Redis_Stat {

    /** redis stat data point key prefix */
    const KEY_PREFIX = 's';

    /** default duration sizes */
    const DURATION_10MIN = 600;
    const DURATION_1HR = 3600;
    const DURATION_1DAY = 86400;

    /** @var Redis_Stat  singleton instance */
    protected static $_instance = null;

    /** @var array  default rollup periods for stats */
    protected static $_defaultPeriods = array(
        array('duration' => 600, 'maxSamples' => 288),    // 10 min, 288 samples
        array('duration' => 3600, 'maxSamples' => 240),   // 1 hour, 240 samples
        array('duration' => 86400, 'maxSamples' => 90),   // 1 day, 90 samples
    );

    /** prevent direct instantiation */
    final private function __construct() { }

    /**
     * get the Redis_Stat singleton instance, creating it if necessary
     *
     * @static
     * @return Redis_Stat
     */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new Redis_Stat();
        }
        return self::$_instance;
    }

    /**
     * increment a particular statistic by the provided value and for the specified rollup periods
     *
     * @param string $statKey  statistic's base key name
     * @param int $value  amount to increment the current periods' value by the provided value; default 1
     * @param array|null $periods  rollup periods; @see self::$_defaultPeriods
     *
     * @return bool  whether the operation was successful
     */
    public function increment($statKey, $value = 1, $periods = null) {
        if (is_null($periods)) { $periods = self::$_defaultPeriods; }
        try {
            $client = Redis_Client::create();
            foreach ($this->_analyticsKeys($statKey, $periods) as $key) {
                // @todo: need to implement TTL mechanism to limit data points
                $client->incrBy($key, $value);
            }
            return true;
        } catch (Exception $e) {
            // @todo: log exception
        }
        return false;
    }

    /**
     * generate a single analytics data point key given the base key name, duration and period start timestamp
     *
     * @param string $statKey  analytics base key name
     * @param int $duration  duration size
     * @param int $periodStart  period start timestamp
     *
     * @return string
     */
    protected function _analyticsKey($statKey, $duration, $periodStart) {
        return implode(':', array(self::KEY_PREFIX, $statKey, $duration, $periodStart));
    }

    /**
     * maps stat key and periods to redis keys
     *
     * @param string $statKey  base key name
     * @param array $periods  stats rollup periods
     *
     * @return array  redis keys
     */
    protected function _analyticsKeys($statKey, $periods) {
        $keys = array();
        $currentTime = time();
        foreach ($periods as $period) {
            $duration = $period['duration'];
            $periodStart = intval($currentTime / $duration) * $duration;
            $keys[] = $this->_analyticsKey($statKey, $duration, $periodStart);
        }
        return $keys;
    }

    /**
     * fetch the keys to obtain stats data points for the specified key, duration size and start/end range
     *
     * @param string $statKey  statistic's base key name
     * @param int $duration  duration size, see Redis_Stat::DURATION_* constants
     * @param int $start  starting timestamp
     * @param int $end  end timestamp
     *
     * @return array  redis keys
     */
    protected function _timeseriesKeys($statKey, $duration, $start, $end) {
        $keys = array();
        $periodStart = intval($start / $duration) * $duration;
        $periodEnd = intval($end / $duration) * $duration;
        $c = $periodStart;
        while ($c <= $periodEnd) {
            $keys[] = $this->_analyticsKey($statKey, $duration, $c);
            $c += $duration;
        }
        return $keys;
    }

    /**
     * zero fill timeseries data for missing keys in the desired range
     *
     * @param array $data  input data, keyed by timeseries key
     * @param string $statKey  statistic's base key name
     * @param int $duration  duration size, see Redis_Stat::DURATION_* constants
     * @param int $start  starting timestamp
     * @param int $end  end timestamp
     *
     * @return array  data, with missing keys zero-filled
     */
    protected function _zeroFillTimeseries($data, $statKey, $duration, $start, $end) {
        $keys = $this->_timeseriesKeys($statKey, $duration, $start, $end);
        $zeroFilledData = array();
        foreach ($keys as $key) {
            $zeroFilledData[$key] = isset($data[$key]) ? $data[$key] : 0;
        }
        return $zeroFilledData;
    }

    /**
     * fetch data points for the specified stat key, duration size and start/end range
     *
     * @param string $statKey  statistic's base key name
     * @param int $duration  duration size, see Redis_Stat::DURATION_* constants
     * @param int $start  starting timestamp
     * @param int|null $end  end timestamp; null for current timestamp
     * @param bool $zeroFill  whether to zero-fill for missing data points; default true
     *
     * @return array|null  desired data points; null if an error occurred
     */
    public function getTimeseries($statKey, $duration, $start, $end = null, $zeroFill = true) {
        if (is_null($end)) { $end = time(); }

        // fetch and return the data
        try {
            $client = Redis_Client::create();
            $data = $client->getMultiple($this->_timeseriesKeys($statKey, $duration, $start, $end));
            if ($zeroFill) {
                $data = $this->_zeroFillTimeseries($data, $statKey, $duration, $start, $end);
            }
            return $data;
        } catch (Exception $e) {
            // @todo: log exception
        }
        return null;
    }

}