<?php

use Opencast\Models\Videos;
use Opencast\Models\Filter;
use Opencast\Models\PlaylistSeminars;

class OCCourseModel
{
    private
        $context_id;

    public function __construct($context_id)
    {
        $this->context_id = $context_id;

    }

    public function getEpisodesForRest()
    {
        // fake filter request, use course id
        $request = [
            'limit'  => -1,
            'order'  => 'created_desc',
            'cid'    => $this->context_id
        ];

        // get available playlists for this course
        $seminar_playlists = new \SimpleCollection(PlaylistSeminars::findBySeminar_id($this->context_id));

        $playlist_ids = $seminar_playlists->pluck('playlist_id');

        if (!empty($playlist_ids)) {
            $videos = Videos::findBySQL('LEFT JOIN oc_playlist_video AS opv
                    ON (opv.video_id = id AND opv.playlist_id IN ('. implode(', ', $playlist_ids) .'))
                WHERE opv.playlist_id IS NOT NULL
                GROUP BY oc_video.id ORDER BY oc_video.created desc',
                [
                    ':seminar_id' => $this->context_id
                ]
            );
        } else {
            $videos = [];
        }

        $ret = [];
        foreach ($videos as $video) {
            $vid = $video->toSanitizedArray();
            $vid['id'] = $vid['episode'];
            $ret[] = $vid;
        }

        return $ret;
    }
}