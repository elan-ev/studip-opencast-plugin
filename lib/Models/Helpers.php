<?php

namespace Opencast\Models;

use \DBManager;
use \PDO;
use \Configuration as StudipConfiguration;

use Opencast\LTI\OpencastLTI;
use Opencast\VersionHelper;
use Opencast\Providers\Perm;

class Helpers
{
    static function getMetadata($metadata, $type = 'title')
    {
        foreach($metadata->metadata as $data) {
            if($data->key == $type) {
                $return = $data->value;
            }
        }
        return $return;
    }

    static function getDates($seminar_id)
    {
       $stmt = DBManager::get()->prepare("SELECT * FROM `termine` WHERE `range_id` = ?");

       $stmt->execute(array($seminar_id));
       $dates =  $stmt->fetchAll(PDO::FETCH_ASSOC);
       return $dates;
    }

    static function retrieveRESTservices($components, $match_protocol)
    {
        $services = array();
        foreach ($components as $service) {
            if (!preg_match('/remote/', $service->type)
                && !preg_match('#https?://localhost.*#', $service->host)
                && mb_strpos($service->host, $match_protocol) === 0
            ) {
                $services[preg_replace(array("/\/docs/"), array(''), $service->host.$service->path)]
                         = preg_replace("/\//", '', $service->path);
            }
        }

        return $services;
    }

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
            FROM plugins WHERE pluginname = 'OpenCast'")->fetchColumn();

        if (!$perm->have_perm('admin')) {

            // get administrated institutes (faceted by active media roles, if enabled)
            if (\Config::get()->OPENCAST_MEDIA_ROLES) {
                $institute_ids = Perm::getRoleInstitutes('Medienadmin', $user_id);
            } else {
                $institute_ids = [];
                $institutes = new \SimpleCollection(\Institute::getMyInstitutes($user_id));
                $institutes->filter(function ($a) use (&$institute_ids) {
                    $institute_ids[] = $a->Institut_id;
                });
            }

            $stmt = \DBManager::get()->prepare("SELECT DISTINCT seminar_user.seminar_id  FROM seminar_user
                $p_sql
                INNER JOIN seminar_inst ON (seminar_inst.seminar_id = seminar_user.seminar_id
                    AND seminar_inst.institut_id IN (:inst_ids))
                WHERE user_id = :user_id
                    AND (seminar_user.status = 'dozent' OR seminar_user.status = 'tutor')
            ");

            $stmt->execute([
                ':user_id'   => $user_id,
                ':plugin_id' => $plugin_id,
                ':inst_ids'  => $institute_ids
            ]);

            $courses = $stmt->fetchAll(\PDO::FETCH_COLUMN);
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
            $playlist = new Playlists();
            $playlist->title = $course->getFullname('number-name-semester');
            $playlist->store();

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
            if (in_array($entry['role'], $possible_roles) !== false) {
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
}
