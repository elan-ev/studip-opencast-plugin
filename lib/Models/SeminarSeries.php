<?php

namespace Opencast\Models;

use Opencast\Models\REST\SeriesClient;
use \Course;

class SeminarSeries extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_seminar_series';

        $config['has_many']['courses'] = [
            'class_name'        => Course::class,
            'foreign_key'       => 'seminar_id',
            'assoc_foreign_key' => 'Seminar_id'
        ];

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

        // make sure all ACLs are correctly set
        $series = self::findBySeries_id($series_id);

        $courses = \SimpleCollection::createFromArray($series)->pluck('seminar_id');

        $acl = Helpers::createACLsForCourses($courses);
        $oc_acl = Helpers::filterACLs($series_client->getACL($series_id));

        if ($acl <> $oc_acl) {
            $series_client->setACL($series_id, $acl);
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
