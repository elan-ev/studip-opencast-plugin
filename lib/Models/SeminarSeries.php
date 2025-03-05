<?php

namespace Opencast\Models;

use Opencast\Models\REST\SeriesClient;
use \Course;

class SeminarSeries extends UPMap
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

        // make sure all ACLs are correctly set
        $series = self::findBySeries_id($series_id);

        $courses = \SimpleCollection::createFromArray($series)->pluck('seminar_id');

        $acl = Helpers::createACLsForCourses($courses);
        $oc_acls = Helpers::filterACLs($series_client->getACL($series_id), $acl);

        if ($acl <> $oc_acls['studip']) {
            $new_acl = array_merge($oc_acls['other'], $acl);
            $series_client->setACL($series_id, $new_acl);
        }

        return (bool) $series;
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

    public static function getSeries($course_id, $offline = false)
    {
        $return = [];

        foreach (self::findBySeminar_id($course_id) as $series) {
            if ($offline) {
                $return[] = $series;
            } else if (self::checkSeries($course_id, $series['series_id'])) {
                $return[] = $series;
            }
        }

        return $return;
    }
}
