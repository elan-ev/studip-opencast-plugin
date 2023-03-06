<?php

namespace Opencast\Models;

use Opencast\Models\REST\SeriesClient;

class SeminarSeries extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_seminar_series';
        parent::configure($config);
    }

    private static function checkSeries($course_id, $series_id)
    {
        static $series = [];

        $config_id = \Config::get()->OPENCAST_DEFAULT_SERVER;
        $series_client = new SeriesClient($config_id);

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

        foreach (self::findBySeminar_id($course_id) as $series) {
            if (!self::checkSeries($course_id, $series['series_id'])) {
                $return[] = $series;
            }
        }

        return $return;
    }

    public static function getSeries($course_id)
    {
        $return = [];

        foreach (self::findBySeminar_id($course_id) as $series) {
            if (self::checkSeries($course_id, $series['series_id'])) {
                $return[] = $series;
            }
        }

        return $return;
    }

    public static function getVisibilityForCourse($course_id)
    {
        $visibility = 'visible';
        $series     = self::getSeries($course_id);
        if ($series) {
            $visibility = $series[0]['visibility'];
        }

        return $visibility;
    }
}
