<?php

use Opencast\LTI\OpencastLTI;
use Opencast\Models\OCSeminarWorkflowConfiguration;
use Opencast\Models\OCSeminarSeries;
use Opencast\Models\OCAclQueue;

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


    public function getEpisodes($filter = true, $top = true, $episode_id = null)
    {
        $ordered_episodes = [];

        if ($this->getSeriesID()) {
            $api_events = ApiEventsClient::create($this->getCourseID());
            $series     = $api_events->getBySeries($this->getSeriesID(), $this->getCourseID(), $filter, $episode_id);

            $stored_episodes  = OCModel::getCoursePositions($this->getCourseID());

            //check if series' episodes is already stored in studip
            if (!empty($series)) {
                // add additional episode metadata from opencast
                $ordered_episodes = $this->episodeComparison($stored_episodes, $series, $top);
            }
        }
        return $ordered_episodes;
    }

    public function episodeComparison($stored_episodes, $oc_episodes, $top)
    {
        $episodes    = [];

        $local_episodes = [];
        foreach ($stored_episodes as $episode) {
            $local_episodes[$episode['episode_id']] = $episode;
        }

        $oc_acl_queue = [];
        foreach (OCAclQueue::findBySQL(1) as $entry) {
            $oc_acl_queue[$entry->episode_id] = $entry;
        }

        $vis_conf = !is_null(CourseConfig::get($this->course_id)->COURSE_HIDE_EPISODES)
            ? boolval(CourseConfig::get($this->course_id)->COURSE_HIDE_EPISODES)
            : \Config::get()->OPENCAST_HIDE_EPISODES;
        $vis = $vis_conf
            ? 'invisible'
            : 'visible';

        $client = \ApiEventsClient::create($this->course_id);

        foreach ($oc_episodes as $oc_episode) {
            if ($oc_episode === NULL) {
                continue;
            }

            $l_episode = $local_episodes[$oc_episode['id']];
            $setAcls = false;

            if (!$l_episode) {
                // add new episode to Stud.IP

                $oc_episode['visibility']    = $vis;
                $oc_episode['is_retracting'] = false;
                $oc_episode['mkdate']        = time();

                if ($top) {
                    NotificationCenter::postNotification('NewEpisodeForCourse', [
                        'episode_id'    => $oc_episode['id'],
                        'course_id'     => $this->getCourseID(),
                        'episode_title' => $oc_episode->title
                    ]);
                }

                $setAcls = true;
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

            if ((isset($oc_acl_queue[$oc_episode['id']]) || $setAcls) && $top) {
                $continue = true;
                if (isset($oc_acl_queue[$oc_episode['id']])) {
                    if ($oc_acl_queue[$oc_episode['id']]->chdate > (time() - 120)) {
                        $continue = false;
                    }
                }
                if ($continue) {
                    $workflow = $client->getEpisode($oc_episode['id'])[1]->processing_state;
                    if (!in_array($workflow, ['SUCCEEDED', 'FAILED', 'STOPPED', ''])) {
                        $continue = false;
                    }
                }
                if ($continue) {
                    $set = OpencastLTI::setAcls($this->getCourseID(), $oc_episode['id']);
                    if ($set === false) {
                        $item = OCAclQueue::find($oc_episode['id']);
                        if (!$item) {
                            $item = OCAclQueue::create(['episode_id' => $oc_episode['id']]);
                            $item->store();
                        }
                        $item->trys += 1;
                        $item->store();
                        if ($item->trys > 15) {
                            $item->delete();
                        } elseif ($item->trys > 10) {
                            $item->chdate = time() + 3600;
                            $item->store();
                        }
                    } elseif ($set === NULL && isset($oc_acl_queue[$oc_episode['id']])) {
                        $oc_acl_queue[$oc_episode['id']]->delete();
                    }
                }
            }

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

    //CW 4.6
    public function getEpisodesforREST()
    {
        $episodes = [];
        if ($this->getSeriesID()) {
            $api_events = ApiEventsClient::create($this->getCourseID());
            $episodes     = $api_events->getBySeries($this->getSeriesID(), $this->getCourseID(), false);
        }

        return $episodes;
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
            $this->course_id,
            $series['schedule'] ? 0 : 1
        );
    }

    public function getSeriesVisibility()
    {
        $visibility = OCSeriesModel::getVisibility($this->course_id);

        return $visibility['visibility'];
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
            $this->getCourseID(),
            $workflow_id,
            $target
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
