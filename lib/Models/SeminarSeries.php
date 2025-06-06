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

    /**
     * Ensures that a course series exists for the given course and configuration.
     *
     * If the series does not exist in the database or is not found in Opencast,
     * this method will attempt to re-create a new series for the seminar and store it.
     *
     * @param int $config_id   The Opencast server configuration ID.
     * @param string $course_id The Stud.IP course ID.
     * @param string $series_id The Opencast series ID to check or create.
     * @return bool True if a new series was created, false otherwise.
     */
    public static function ensureSeriesExists($config_id, $course_id, $series_id)
    {
        // Set a flag to force re-creationg of the series in Opencast.
        $recreate_series_needed = false;
        $series_client = new SeriesClient($config_id);
        $seminar_series = self::findOneBySQL('seminar_id = ? AND series_id = ?', [$course_id, $series_id]);

        if (empty($seminar_series)) {
            // If the series is not found, we need to create one.
            $recreate_series_needed = true;
        } else {
            // If the series is found, we need to check if it is still valid.
            $series_data = $series_client->getSeries($series_id, true);
            if ($series_data == 404) {
                $recreate_series_needed = true;
            }
        }

        if ($recreate_series_needed) {

            $new_series_id = $series_client->createSeriesForSeminar($course_id);

            if ($new_series_id) {
                // Make sure the old/lost series record is removed from Stud.IP as well.
                if (!empty($seminar_series)) {
                    $seminar_series->delete();
                }
                $new_seminar_series = SeminarSeries::create([
                    'config_id'  => $config_id,
                    'seminar_id' => $course_id,
                    'series_id'  => $new_series_id,
                ]);
                $new_seminar_series->store();

                $log_info = "Die Kurs-Serie (ID: {$series_id}) ist verloren gegangen und eine neue wurde erstellt (ID: {$new_series_id}).";
                \StudipLog::log('OC_WARNINGS', $course_id, null, $log_info);
                return true;
            }
        }

        return false;
    }
}
