<?php

use Opencast\Models\OCSeminarWorkflowConfiguration;
use Opencast\Models\OCSeminarSeries;

class OCCourseModel
{

    /**
     * This is the maximum number of seconds that unread entries are
     * marked as new.
     */
    const LAST_VISIT_MAX = 7776000; // 90 days

    /**
     * @param $course_id
     */
    public function __construct($course_id)
    {
        if (!$course_id) {
            throw new Exception('Missing course-id!');
        }

        $this->setCourseID($course_id);
        // take care of connected series
        $cseries = OCSeminarSeries::getSeries($this->getCourseID());

        if (!empty($cseries)) {
            $current_seriesdata = array_pop($cseries);
            $this->setSeriesMetadata($current_seriesdata);
            $this->setSeriesID($current_seriesdata['series_id']);
        } else {
            $this->setSeriesID(false);
        }

    }

    public function getCourseID()
    {
        return $this->course_id;
    }

    public function getSeriesID()
    {
        return $this->series_id;
    }

    public function getSeriesMetadata()
    {
        return $this->seriesMetadata;
    }

    public function setSeriesID($series_id)
    {
        $this->series_id = $series_id;
    }

    public function setCourseID($course_id)
    {
        $this->course_id = $course_id;
    }

    public function setSeriesMetadata($seriesMetadata)
    {
        $this->seriesMetadata = $seriesMetadata;
    }


    public function getEpisodes()
    {
        static $ordered_episodes;

        if (empty($ordered_episodes)) {
            if ($this->getSeriesID()) {
                $search_client = SearchClient::create($this->getCourseID());

                $course = Course::find($this->getCourseID());
                $role   = '';

                if (OCPerm::editAllowed($course->id)) {
                    $role = 'Instructor';
                } else if ($GLOBALS['perm']->have_studip_perm('autor', $course->id)) {
                    $role = 'Learner';
                }

                $api_events = ApiEventsClient::create($this->getCourseID());
                $series     = $api_events->getBySeries($this->getSeriesID());

                $stored_episodes  = OCModel::getCoursePositions($this->getCourseID());
                $ordered_episodes = [];

                //check if series' episodes is already stored in studip
                if (!empty($series)) {
                    // add additional episode metadata from opencast
                    $ordered_episodes = $this->episodeComparison($stored_episodes, $series);
                }

                /*
                if ($unset_live) {
                    $oc_events = ApiEventsClient::create($this->getCourseID());
                    $oc_series = OCSeminarSeries::getSeries($this->getCourseID());
                    $events = $oc_events->getBySeries($oc_series[0]['series_id']);

                    foreach ($ordered_episodes as $episode) {
                        if ($events[$episode['id']]->publication_status[0] == 'engage-live')
                        {
                            unset($ordered_episodes[$episode['id']]);
                        }
                    }
                }
                */

                return $ordered_episodes;
            } else {
                return false;
            }
        }

        return $ordered_episodes;

    }

    /**
     * This function sorts an array 'deep'. This means the content is first sorted
     * by the first key in the array. If there are more than one entry for this key
     * the episodes in this group are sorted by the second key and so on.
     *
     * @param $keys
     * @param $sort_flags
     * @param $reversed
     * @param $episodes
     *
     * @return array
     */
    private function order_episodes_by($keys, $sort_flags, $reversed, $episodes)
    {
        $ordered = [];

        //Get the current settings for this episode group
        $key                = array_shift($keys);
        $current_reversed   = array_shift($reversed);
        $current_sort_flags = array_shift($sort_flags);

        //Regroup the current episodes bv the key field
        foreach ($episodes as $episode) {
            $ordered[$episode[$key]][] = $episode;
        }

        //Sort, reverse if needed
        if ($current_reversed) {
            krsort($ordered, $current_sort_flags);
        } else {
            ksort($ordered, $current_sort_flags);
        }

        //Now remove the grouping but contain the order within
        $episodes = [];

        foreach ($ordered as $entries) {
            if (count($keys) > 0 && count($entries) > 1) {
                $entries = $this->order_episodes_by($keys, $sort_flags, $reversed, $entries);
            }
            foreach ($entries as $entry) {
                $episodes[] = $entry;
            }
        }

        //Return really ordered list of episodes
        return $episodes;
    }

    private function episodeComparison($stored_episodes, $oc_episodes)
    {
        $episodes    = [];

        $local_episodes = [];
        foreach ($stored_episodes as $episode) {
            $local_episodes[$episode['episode_id']] = $episode;
        }

        $vis_conf = !is_null(CourseConfig::get($this->course_id)->COURSE_HIDE_EPISODES)
            ? boolval(CourseConfig::get($this->course_id)->COURSE_HIDE_EPISODES)
            : \Config::get()->OPENCAST_HIDE_EPISODES;
        $vis = $vis_conf
            ? 'invisible'
            : 'visible';

        foreach ($oc_episodes as $oc_episode) {
            $l_episode = $local_episodes[$oc_episode['id']];

            if (!$l_episode) {
                // add new episode to Stud.IP

                $oc_episode['visibility']    = $vis;
                $oc_episode['is_retracting'] = false;
                $oc_episode['mkdate']        = $timestamp;

                // invalidate acl cache for this course
                $cache = \StudipCacheFactory::getCache();
                $cache_key = 'sop/visiblity/'. \Context::getId();
                $cache->expire($cache_key);

                NotificationCenter::postNotification('NewEpisodeForCourse', [
                    'episode_id'    => $oc_episode['id'],
                    'course_id'     => $this->getCourseID(),
                    'episode_title' => $oc_episode->title
                ]);
            } else {
                $oc_episode['visibility']    = $l_episode['visible'];
                $oc_episode['is_retracting'] = $l_episode['is_retracting'];
                $oc_episode['mkdate']        = $l_episode['mkdate'];
            }

            OCModel::setEpisode(
                $oc_episode['id'],
                $oc_episode['series_id'],
                $this->getCourseID(),
                $oc_episode['visibility'],
                $oc_episode['is_retracting']
            );

            $episodes[] = $oc_episode;
        }

        return $episodes;
    }

    /**
     * return number of new episodes since last visit up to 3 month ago
     *
     * @param string $visitdate count all entries newer than this timestamp
     *
     * @return int the number of entries
     */
    public static function getCount($course_id, $visitdate)
    {
        if ($visitdate < time() - OCCourseModel::LAST_VISIT_MAX) {
            $visitdate = time() - OCCourseModel::LAST_VISIT_MAX;
        }

        $stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM oc_seminar_episodes
            WHERE seminar_id = :seminar_id AND oc_seminar_episodes.mkdate > :lastvisit");

        $stmt->bindParam(':seminar_id', $course_id);
        $stmt->bindParam(':lastvisit', $visitdate);

        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function getEpisodesforREST()
    {
        $rest_episodes = [];
        $is_dozent     = $GLOBALS['perm']->have_studip_perm('autor', $this->course_id);
        $episodes      = $this->getEpisodes();

        foreach ($episodes as $episode) {
            if ($episode['visibility'] == 'true') {
                $rest_episodes[] = $episode;
            } else {
                if ($is_dozent) {
                    $rest_episodes[] = $episode;
                }
            }
        }

        return $rest_episodes;
    }

    public function toggleSeriesVisibility()
    {
        if ($this->getSeriesVisibility() == 'visible') {
            $visibility = 'invisible';
        } else {
            $visibility = 'visible';
        }

        return OCSeriesModel::updateVisibility($this->course_id, $visibility);
    }

    /**
     * Toggle schedule flag for current series
     *
     * @return bool true on success, false on failure
     */
    public function toggleSeriesSchedule()
    {
        $series = $this->getSeriesMetadata();

        return OCSeriesModel::updateSchedule(
            $this->course_id, $series['schedule'] ? 0 : 1
        );
    }

    public function getSeriesVisibility()
    {
        $visibility = OCSeriesModel::getVisibility($this->course_id);

        return $visibility['visibility'];
    }

    /**
     * refine the list of episodes wrt. the visibility of an episode
     *
     * @param array $ordered_episodes list of all episodes for the given course
     *
     * @return array episodes refined list of episodes - only visible episodes are considered
     */
    public function refineEpisodesForStudents($ordered_episodes)
    {
        $episodes = [];
        foreach ($ordered_episodes as $episode) {
            if ($episode['visibility'] != 'invisible') {
                $episodes[] = $episode;
            }
        }

        return $episodes;
    }

    public function getWorkflow($target)
    {
        $workflow = static::getWorkflowWithCustomCourseID($this->getCourseID(), $target);
        if (!$workflow) {
            $workflow = static::getWorkflowWithCustomCourseID('default_workflow', $target);
        }

        return $workflow;
    }

    public static function getWorkflowWithCustomCourseID($course_id, $target)
    {
        return OCSeminarWorkflowConfiguration::findOneBySql(
            'seminar_id = ? AND target = ?',
            [$course_id, $target]
        );
    }

    public function setWorkflow($workflow_id, $target)
    {
        return static::setWorkflowWithCustomCourseID(
            $this->getCourseID(), $workflow_id, $target
        );
    }

    public static function setWorkflowWithCustomCourseID($course_id, $workflow_id, $target)
    {
        if (!$ocw = OCSeminarWorkflowConfiguration::findOneBySql(
            'seminar_id = ? AND target = ?',
            [$course_id, $target]
        )) {
            $ocw = new OCSeminarWorkflowConfiguration();
        }

        $ocw->setData([
            'seminar_id'  => $course_id,
            'workflow_id' => $workflow_id,
            'target'      => $target
        ]);

        return $ocw->store();
    }

    public static function removeWorkflowsWithoutCustomCourseID($course_id, $target)
    {
        return OCSeminarWorkflowConfiguration::deleteBySql(
            'seminar_id = ? AND target = ?',
            [$course_id, $target]
        );
    }

    public function setWorkflowForDate($termin_id, $workflow_id)
    {
        $stmt = DBManager::get()->prepare("UPDATE
                oc_scheduled_recordings SET workflow_id = ?
                WHERE seminar_id = ? AND date_id = ?");

        return $stmt->execute([$workflow_id, $this->getCourseID(), $termin_id]);
    }
}
