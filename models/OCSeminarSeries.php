<?php

namespace Opencast\Models;

class OCSeminarSeries extends \SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_seminar_series';
        parent::configure($config);
    }

    public static function getMissingSeries($course_id)
    {
        $series_client = \SeriesClient::create($course_id);

        $return = [];
        foreach(self::findBySeminar_id($course_id) as $series) {
            if (!$series_client->getSeries($series['series_id'])) {
                $return[] = $series;
            }
        }

        return $return;
    }

    public static function getSeries($course_id)
    {
        $series_client = \SeriesClient::create($course_id);

        $return = [];
        foreach(self::findBySeminar_id($course_id) as $series) {
            if ($series_client->getSeries($series['series_id'])) {
                $return[] = $series;
            }
        }

        return $return;
    }
}
