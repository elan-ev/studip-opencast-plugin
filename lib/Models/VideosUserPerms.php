<?php

namespace Opencast\Models;

use Opencast\Models\SeminarSeries;
use Opencast\Models\UserSeries;

class VideosUserPerms extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_video_user_perms';

        $config['belongs_to']['user'] = [
            'class_name' => 'User',
            'foreign_key' => 'user_id',
        ];

        $config['belongs_to']['video'] = [
            'class_name' => 'Opencast\\Models\\Videos',
            'foreign_key' => 'video_id',
        ];

        parent::configure($config);
    }

    public function toSanitizedArray()
    {
        $data = $this->toArray();

        $data['fullname'] = get_fullname($data['user_id']);

        return $data;

    }

    /**
     * Set permissions for users who have rights on the passed video. Extracts
     * lecturers if this Video belongs to a series in a course, otherwise tries to map the presenter
     * to a Stud.IP username
     *
     * @Notification OpencastVideoSync
     *
     * @param string                $eventType
     * @param object                $episode
     * @param Opencast\Models\Video $video
     *
     * @return void
     */
    public static function setPermissions($eventType, $episode, $video)
    {
        // check, if there are any permissions for this video yet. Initial setting of permissions is only done once for each new video.
        if (!empty(self::findByVideo_id($video->id)) || empty($episode)) {
            return;
        }

        // check if a series is assigned to this event
        if ($episode->is_part_of) {
            // get the courses this series belongs to
            $series = SeminarSeries::findBySeries_id($episode->is_part_of);

            foreach ($series as $s) {
                $course = \Course::find($s['seminar_id']);
                foreach ($course->getMembersWithStatus('dozent') as $member) {
                    // check, if there is already an entry for this user-video combination
                    $perm = self::findOneBySQL('video_id = :video_id AND user_id = :user_id', [
                        ':video_id' => $video->id,
                        ':user_id'  => $member->user_id
                    ]);

                    if (empty($perm)) {
                        $perm = new self();
                        $perm->user_id  = $member->user_id;
                        $perm->video_id = $video->id;
                        $perm->perm     = 'owner';
                        $perm->store();
                    }
                }
            }

            // Get the user this series belongs to
            $series = UserSeries::findBySeries_id($episode->is_part_of);

            foreach ($series as $s) {
                // check, if there is already an entry for this user-video combination
                $perm = self::findOneBySQL('video_id = :video_id AND user_id = :user_id', [
                    ':video_id' => $video->id,
                    ':user_id'  => $s['user_id']
                ]);

                if (empty($perm)) {
                    $perm = new self();
                    $perm->user_id  = $s['user_id'];
                    $perm->video_id = $video->id;
                    $perm->perm     = 'owner';
                    $perm->store();
                }
            }
        }

        // always make sure, that the uploader is in the list of the owners!
        // TODO: This field is not safe and could have been manipulated by the uploader!

        if ($episode->presenter[0]) {
            $user_id = \get_userid($episode->presenter[0]);

            if ($user_id) {
                // check, if this perm has already been assigned
                $perm = self::findOneBySQL('user_id = :user_id AND video_id = :video_id', [
                    ':user_id'  => $user_id,
                    ':video_id' => $video->id
                ]);

                if (empty($perm)) {
                    $perm = new self();
                    $perm->user_id  = $user_id;
                    $perm->video_id = $video->id;
                    $perm->perm     = 'owner';
                    $perm->store();

                    // notify user, that one of his videos is now available
                    \PersonalNotifications::add(
                        $user_id,
                        \URLHelper::getURL('plugins.php/opencast/contents/index', [], true),
                        sprintf(_('Das Video mit dem Titel "%s" wurde fertig verarbeitet.'), $episode->title),
                        "opencast_" . $episode->identifier,
                        \Icon::create('video'),
                        false
                    );
                }
            }
        }

    }
}
