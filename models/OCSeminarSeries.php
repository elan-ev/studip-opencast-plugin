<?php

namespace Opencast\Models;

class OCSeminarSeries extends \SimpleORMap
{
    protected static function configure($config = [])
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

    public static function findAll()
    {
        return self::findBySQL("1 ORDER BY mkdate");
    }

    public static function getSeriesByUserMemberStatus($user_id, $status = 'dozent') {
        $user_series = [];
        foreach (self::findAll() as $series) {
            if (empty($series->seminar_id) || in_array($series->series_id, $user_series)) {
                continue;
            }
            $course = \Course::find($series->seminar_id);
            if (!empty($course) && in_array($user_id, array_column($course->getMembersWithStatus($status), 'user_id'))) {
                if (self::checkSeries($series->seminar_id, $series->series_id)) {
                    $user_series[] = $series->series_id;
                }
            }
        }
        return $user_series;
    }
}
