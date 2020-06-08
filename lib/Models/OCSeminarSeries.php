<?php

namespace Opencast\Models;

class OCSeminarSeries extends \SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_seminar_series';
        parent::configure($config);
    }

    private static function checkSeries($course_id, $series_id)
    {
        static $series = [];

        $series_client = \SeriesClient::create($course_id);

        if (!isset($series[$series_id])) {

            $series[$series_id] =
                $series_client->getSeries($series_id)
                ? true : false;
        }

        return $series[$series_id];
    }

    public static function getMissingSeries($course_id)
    {
        $return = [];

        foreach(self::findBySeminar_id($course_id) as $series) {
            if (!self::checkSeries($course_id, $series['series_id'])) {
                $return[] = $series;
            }
        }

        return $return;
    }

    public static function getSeries($course_id)
    {
        $return = [];

        foreach(self::findBySeminar_id($course_id) as $series) {
            if (self::checkSeries($course_id, $series['series_id'])) {
                $return[] = $series;
            }
        }

        return $return;
    }
}
