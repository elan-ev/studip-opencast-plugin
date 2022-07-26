<?php

namespace Opencast\Models;

use Opencast\Models\SeminarSeries;

class VideosUserPerms extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_video_user_perms';

        $config['belongs_to']['user'] = [
            'class_name' => 'User',
            'foreign_key' => 'user_id',
        ];

        parent::configure($config);
    }

    public static function setPermissions($eventType, $episode, $video)
    {
        // check, if there are any permissions for this video yet. Initial setting of permissions is only done once for each new video.
        if (!empty(self::findByVideo_id($video->id))) {
            return;
        }

        // check if a series is assigned to this event
        if ($episode->is_part_of) {
            // get the courses this series belongs to
            $series = SeminarSeries::findBySeries_id($episode->is_part_of);

            foreach ($series as $s) {
                $course = \Course::find($s['course_id']);
                foreach ($course->getMembers('dozent') as $member) {
                    $perm = new self();
                    $perm->user_id  = $member->id;
                    $perm->video_id = $video->id;
                    $perm->perm     = 'owner';
                    $perm->store();
                }
            }
        } else {
            // if no series is assigned, try other mappings
            // TODO: This field is not safe and could have been manipulated by the uploader! For now, we rely on it to detect the correct user, but this needs to be changed before production!

            $user_id = \get_userid($episode->presenter[0]);

            if ($user_id) {
                $perm = new self();
                $perm->user_id  = $user_id;
                $perm->video_id = $video->id;
                $perm->perm     = 'owner';
                $perm->store();
            }
        }
    }
}
