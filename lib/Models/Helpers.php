<?php

namespace Opencast\Models;

use \DBManager;
use \PDO;

use Opencast\VersionHelper;
use Opencast\Providers\Perm;
use Opencast\Models\Videos;
use Opencast\Models\REST\ApiEventsClient;
use Opencast\Models\REST\ApiWorkflowsClient;

class Helpers
{
    const RECORDED_SERIES_ID_CACHE_ID = 'OpencastV3/series/recorded_series_ids';

    static function getConfigurationstate()
    {
        $stmt = DBManager::get()->prepare("SELECT COUNT(*) AS c FROM oc_config WHERE active = 1");
        $stmt->execute();

        if ($stmt->fetchColumn() > 0) {
            return true;
        }

        return false;
    }

    static function getMyCourses($user_id)
    {
        global $perm;

        $p_sql = VersionHelper::get()->getPluginActivatedSQL();

        // get id of opencast plugin to check if it is activated for selected courses
        $plugin_id = \DBManager::get()->query("SELECT pluginid
            FROM plugins WHERE pluginname = 'OpencastV3'")->fetchColumn();

        if (!$perm->have_perm('admin')) {
            // get all courses, user is lecturer or tutor in
            $stmt = \DBManager::get()->prepare($q = "SELECT DISTINCT seminar_user.seminar_id  FROM seminar_user
                $p_sql
                WHERE user_id = :user_id
                    AND (seminar_user.status = 'dozent' OR seminar_user.status = 'tutor')
            ");

            $stmt->execute([
                ':user_id'   => $user_id,
                ':plugin_id' => $plugin_id,
            ]);

            $courses = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            // add courses where user is media admin

            if (\Config::get()->OPENCAST_MEDIA_ROLES) {
                // get administrated institutes
                $institute_ids = Perm::getRoleInstitutes('Medienadmin', $user_id);

                if (!empty($institute_ids)) {
                    $stmt = \DBManager::get()->prepare("SELECT DISTINCT seminar_user.seminar_id  FROM seminar_user
                        $p_sql
                        INNER JOIN seminar_inst ON (seminar_inst.seminar_id = seminar_user.seminar_id)
                        WHERE seminar_inst.institut_id IN (:inst_ids)
                    ");

                    $stmt->execute([
                        ':plugin_id' => $plugin_id,
                        ':inst_ids'  => $institute_ids
                    ]);

                    $courses = array_merge($courses, $stmt->fetchAll(\PDO::FETCH_COLUMN));
                }
            }
        } else if (!$perm->have_perm('root')) {
            $institute_ids = [];
            $institutes = new \SimpleCollection(\Institute::getMyInstitutes($user_id));
            $institutes->filter(function ($a) use (&$institute_ids) {
                $institute_ids[] = $a->Institut_id;
            });

            // get courses for admins
            $stmt = \DBManager::get()->prepare("SELECT DISTINCT seminare.Seminar_id FROM seminare
                $p_sql
                INNER JOIN seminar_inst ON (seminare.Seminar_id = seminar_inst.seminar_id)
                INNER JOIN Institute ON (seminar_inst.institut_id = Institute.Institut_id)
                LEFT JOIN sem_types ON (sem_types.id = seminare.status)
                LEFT JOIN sem_classes ON (sem_classes.id = sem_types.class)
                INNER JOIN seminar_user AS dozenten ON (dozenten.Seminar_id = seminare.Seminar_id AND dozenten.status = 'dozent')
                INNER JOIN auth_user_md5 AS dozentendata ON (dozenten.user_id = dozentendata.user_id)
                WHERE sem_classes.studygroup_mode = '0'
                    AND (
                        CONCAT_WS(' ', seminare.VeranstaltungsNummer, seminare.name, seminare.Untertitel, dozentendata.Nachname) LIKE :search
                        OR CONCAT(dozentendata.Nachname, ', ', dozentendata.Vorname) LIKE :search
                        OR CONCAT_WS(' ', dozentendata.Vorname, dozentendata.Nachname) LIKE :search
                        OR dozentendata.Vorname LIKE :search OR dozentendata.Nachname LIKE :search
                    )
                    AND seminar_inst.institut_id IN (:institute_ids)
            ");

            $stmt->execute([
                ':search'        => '%%%',
                ':institute_ids' => $institute_ids,
                ':plugin_id'     => $plugin_id
            ]);

            $courses = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } else {
            $stmt = \DBManager::get()->prepare("SELECT DISTINCT seminar_id FROM seminar_user
                $p_sql WHERE 1
            ");

            $stmt->execute([
                ':plugin_id' => $plugin_id
            ]);

            $courses = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }

        return $courses;
    }

    /**
     * Check and make sure, that a valid server is set.
     * If no server is detected, -1 will be stored.
     *
     * @return
     */
    static function validateDefaultServer() {
        // Only check active servers
        $config = new \SimpleCollection(Config::findBySql('active = 1'));
        $config_ids = $config->pluck('id');

        $value = \Config::get()->OPENCAST_DEFAULT_SERVER;
        $valid_value = $value;

        // Check if list is empty
        if (empty($config_ids)) {
            $valid_value = -1;
        }
        // Check if the set value is valid (id exists)
        elseif (in_array($valid_value, $config_ids) === false) {
            $valid_value = reset($config_ids);
        }
        // Only update value if necessary
        if ($valid_value != $value) {
            \Config::get()->store('OPENCAST_DEFAULT_SERVER', $valid_value);
        }
    }

    /**
     * Check if the default course playlists exists and create if necessary
     *
     * @param string $course_id
     *
     * @return object Default playlist of the course with the passed id
     */
    static public function checkCoursePlaylist($course_id)
    {
        $playlists = PlaylistSeminars::findBySQL('seminar_id = ? AND is_default = 1', [$course_id]);

        $course = \Course::find($course_id);

        if (!empty($playlists)) {
            $playlist = $playlists[0]->playlist;
        } else {
            // create new playlist
            $playlist = Playlists::createPlaylist([
                'config_id'      => \Config::get()->OPENCAST_DEFAULT_SERVER,
                'title'          => $course->getFullname('number-name-semester'),
                'description'    => '',
                'creator'        => '',
            ]);

            // connect playlist to course
            $pcourse = new PlaylistSeminars();
            $pcourse->playlist_id = $playlist->id;
            $pcourse->seminar_id  = $course_id;
            $pcourse->visibility = 'visible';
            $pcourse->is_default = 1;

            // Set the default playlist to contain livestreams and scheduled recordings as well.
            $pcourse->contains_scheduled = 1;
            $pcourse->contains_livestreams = 1;

            $pcourse->store();
        }

        // add all current seminar lectures to have rights on this playlist
        foreach ($course->getMembersWithStatus('dozent') as $user) {

            // check, if relation exists
            $perm = PlaylistsUserPerms::findOneBySQL('playlist_id = ? AND user_id = ?', [$playlist->id, $user->user_id]);

            if (empty($perm)) {
                $perm = new PlaylistsUserPerms();
                $perm->playlist_id = $playlist->id;
                $perm->user_id     = $user->user_id;
                $perm->perm        = 'owner';

                $perm->store();
            }
        }

        return $playlist;
    }

    /**
     * Check if the default course playlist exists
     *
     * @param string $course_id
     *
     * @return bool whether the default course playlist exists
     */
    public static function checkCourseDefaultPlaylist($course_id)
    {
        $default_playlist = PlaylistSeminars::getDefaultPlaylistSeminar($course_id);

        if (empty($default_playlist)) {
            // set the first found playlist as default, to prevent breaking the course
            $stmt = \DBManager::get()->prepare("UPDATE `oc_playlist_seminar` SET is_default = 1 WHERE seminar_id = ? LIMIT 1");
            $stmt->execute([$course_id]);

            $default_playlist = PlaylistSeminars::getDefaultPlaylistSeminar($course_id);
        }

        return !empty($default_playlist);
    }

    /**
     * Makes sure that there is only one default playlist in a given course at a time.
     *
     * @param string $course_id Course id
     * @param string $default_playlist_id default playlist id to keep
     *
     * @return int then number of affected rows
     */
    public static function ensureCourseHasOneDefaultPlaylist($course_id, $default_playlist_id)
    {
        $stmt = \DBManager::get()->prepare("UPDATE `oc_playlist_seminar` SET is_default = 0 WHERE seminar_id = ? AND playlist_id != ? AND is_default = 1");

        $stmt->execute([$course_id, $default_playlist_id]);
        return $stmt->rowCount();
    }

    /**
     * Create list of LTI ACLs for the passed courses. Returns an array with the read
     * and write ACLs for Instructor and Learner
     *
     * @param array $course_ids
     *
     * @return array
     */
    public static function createACLsForCourses($course_ids)
    {
        $acl = [];

        foreach ($course_ids as $course_id) {
            $acl[] = [
                'allow'  => true,
                'role'   => $course_id . '_Instructor',
                'action' => 'read'
            ];

            $acl[] = [
                'allow'  => true,
                'role'   => $course_id . '_Instructor',
                'action' => 'write'
            ];

            $acl[] = [
                'allow'  => true,
                'role'   => $course_id . '_Learner',
                'action' => 'read'
            ];
        }

        return $acl;
    }

    /**
     * Get all ACL entries Stud.IP is responsible for
     *
     * @param array $acls
     *
     * @return array
     */
    public static function filterACLs($acls, $studip_acls)
    {
        if (!is_array($acls)) {
            return [
                'studip' => [],
                'other'  => []
            ];
        }

        // prevent duplicate ACLs
        $temp_acls = [];
        foreach ($acls as $acl) {
            $temp_acls[$acl['allow'] .'#'. $acl['role'] . $acl['action']] = $acl;
        }

        $acls = array_values($temp_acls);

        $possible_roles = array_column($studip_acls, 'role');

        sort($acls);

        $result = [];
        foreach ($acls as $entry) {
            // Add if existing role exists in new acls or is a legacy course role
            if ((in_array($entry['role'], $possible_roles) !== false) ||
                preg_match('/[0-9a-f]{32}_(?:Instructor|Learner)/', $entry['role'])
            ){
                $result[$entry['role'] .'_'. $entry['action']] = $entry;
            }
        }

        $result = array_values($result);
        sort($result);


        $diff = array_udiff($acls, $result, ['Opencast\Models\Helpers', 'compareACLs']);

        return [
            'studip' => $result,
            'other'  => $diff
        ];
    }

    public static function compareACLs($a, $b)
    {
        $comp_a = implode('#', $a);
        $comp_b = implode('#', $b);

        return $comp_a <=> $comp_b;

    }

    /**
     * Notify users about the video event
     *
     * @Notification OpencastNotifyUsers
     *
     * @param string $eventType
     * @param object $event
     * @param  Opencast\Models\Videos $video
     */
    public static function notifyUsers($eventType, $event, $video)
    {
        // get the first course the video is assigned to
        if (!empty($video->playlists) && !empty($video->playlists[0]->courses)) {
            $course_id = $video->playlists[0]->courses[0]->id;
        }

        // Notify user:
        // Here we need to notify the users with owner permission about the video event.
        $targeted_perm = 'owner';
        $video_owners = VideosUserPerms::findBySQL('video_id = ? AND perm = ?', [$video->id, $targeted_perm]);
        if (empty($video_owners)) {
            return;
        }
        foreach($video_owners as $vuser) {
            $url = \URLHelper::getURL('plugins.php/opencastv3/contents/index', [], true);

            if (!empty($course_id)) {
                $url = \URLHelper::getURL('plugins.php/opencastv3/course/index', ['cid' => $course_id], true);
            }

            $title = sprintf(_('Das Video mit dem Titel "%s" wurde fertig verarbeitet.'), $video->title);

            if ($video->state == 'cutting') {
                $title = sprintf(_('Das Video mit dem Titel "%s" wartet auf den Schnitt.'), $video->title);
            }

            if ($video->state == 'failed') {
                $title = sprintf(_('Das Video mit dem Titel "%s" hat einen Verarbeitungsfehler!'), $video->title);
            }

            \PersonalNotifications::add(
                $vuser->user_id, $url, $title,
                "opencast_" . $event->identifier,
                \Icon::create('video'),
                false
            );
        }
    }

    /**
     * Determines if an event can run a workflow.
     *
     * This function checks if the given event meets the criteria for running a republish workflow.
     * It ensures that:
     * 1. The event is not empty
     * 2. The event has an 'engage-player' publication status
     * 3. The associated video is not in a 'running' or 'failed' state
     *
     * @param object $event The Opencast event object to check
     * @param object $video The associated video object from the Stud.IP system
     *
     * @return boolean Returns true if the event can run a workflow, false otherwise
     */
    public static function canEventRunWorkflow($event, Videos $video)
    {
        if (empty($event)
            || in_array('engage-player', (array)$event->publication_status) === false
            || $video->state == 'running' || $video->state == 'failed')
        {
            return false;
        }

        return true;
    }


    public static function isWorldReadable($oc_acls)
    {
        // check if ACL contains ROLE_ANONYMOUS
        $has_anonymous_role = false;
        foreach ($oc_acls as $acl_entry) {
            if ($acl_entry['role'] === 'ROLE_ANONYMOUS'
                && $acl_entry['action'] === 'read'
                && $acl_entry['allow'] === true
            ) {
                $has_anonymous_role = true;
                break;
            }
        }

        return $has_anonymous_role;
    }

    /**
     * Retrieves all known recorded Opencast series IDs from the cache or database.
     *
     * This method returns an array of all series IDs that are known to the system,
     * combining both user-specific and seminar-specific series. It first attempts
     * to read the list from the cache. If the cache is empty or if the $force
     * parameter is set to true, it queries the database for all user and seminar
     * series IDs, merges and deduplicates them, and then updates the cache.
     *
     * @param bool $force If true, forces a refresh from the database instead of using the cache.
     * @return array List of unique recorded series IDs.
     */
    public static function getAllRecordedSeriesIds(bool $force = false)
    {
        $cache = \StudipCacheFactory::getCache();
        $all_known_seriesids = $cache->read(self::RECORDED_SERIES_ID_CACHE_ID);
        if ($force || empty($all_known_seriesids)) {
            $combined_records = [];
            $user_series_ids =\SimpleCollection::createFromArray(
                UserSeries::findBySql('1')
            )->toArray();
            $seminar_series_ids =\SimpleCollection::createFromArray(
                SeminarSeries::findBySql('1')
            )->toArray();
            $combined_records = array_merge($user_series_ids, $seminar_series_ids);
            $all_known_seriesids = array_column($combined_records, 'series_id');
            $all_known_seriesids = array_unique($all_known_seriesids);
            $cache->write(self::RECORDED_SERIES_ID_CACHE_ID, $all_known_seriesids);
        }
        return $all_known_seriesids;
    }

    /**
     * Determines whether a given Opencast event belongs to any know series in this Stud.IP instance.
     *
     * This method checks if the provided Opencast event's series ID (`is_part_of`)
     * is known to the current Stud.IP system. If the event does not have a series ID,
     * it is considered valid for this Stud.IP instance. Otherwise, the method checks
     * if the series ID exists in the list of all recorded series IDs known to Stud.IP.
     *
     * @param object $oc_event The Opencast event object to check.
     * @return bool True if the event belongs to this Stud.IP instance, false otherwise.
     */
    public static function isEventInAnyKnownSeries($oc_event)
    {
        if (empty($oc_event->is_part_of)) {
            // No series id, so we consider it as a valid event for this studip to be processed!
            return true;
        }

        $all_known_seriesids = self::getAllRecordedSeriesIds();

        if (in_array($oc_event->is_part_of, $all_known_seriesids)) {
            return true;
        }

        return false;
    }

    /**
     * Invalidates the cache for recorded series IDs.
     *
     * This method clears the cache entry that stores all known recorded series IDs.
     * It should be called whenever a series is created or deleted to ensure
     * that the cache remains consistent with the database.
     * @see \Opencast\Models\UserSeries
     * @see \Opencast\Models\SeminarSeries
     */
    public static function invalidateRecordedSeriesIdsCache()
    {
        $cache = \StudipCacheFactory::getCache();
        $cache->expire(self::RECORDED_SERIES_ID_CACHE_ID);
    }

    /**
     * Gives the events without series id a chance of getting one by mapping user perms and user series.
     *
     * @Notification OpencastVideoSync
     *
     * @param string $eventType
     * @param object $event
     * @param  Opencast\Models\Videos $video
     */
    public static function mapEventUserSeriesUserPerms($eventType, $event, $video)
    {
        if (!empty($event->is_part_of)) {
            // Already has a series id, then we are done here!
            return;
        }

        // Get the (a) video owner.
        $video_owner = VideosUserPerms::findOneBySQL('video_id = ? AND perm = ?', [$video->id, 'owner']);
        if (empty($video_owner)) {
            // No owner, then we have nothing to do here!
            return;
        }

        // Make sure the owner has a user series!
        $user_series = null;

        $all_user_series = UserSeries::getSeries($video_owner->user_id);
        // Enforce user series creation!
        if (empty($all_user_series)) {
            $user_series = UserSeries::createSeries($video_owner->user_id);
        } else {
            $user_series = $all_user_series[0];
        }

        // Update the event with the new series id.
        $api_event_client = ApiEventsClient::getInstance($video->config_id);

        $metadata[] = [
            'id' => 'isPartOf',
            'value' => $user_series['series_id']
        ];
        $response = $api_event_client->updateMetadata($video->episode, $metadata);
        $republish = in_array($response['code'], [200, 204]) === true;

        if ($republish) {
            $api_wf_client = ApiWorkflowsClient::getInstance($video->config_id);

            if ($api_wf_client->republish($video->episode)) {
                echo 'Event metadata has been updated by the owner specific series id: ' . $video->episode . "\n";
            }
        }
    }
}
