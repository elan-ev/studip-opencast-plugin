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
            'order'  => 'mkdate_desc',
            'cid'    => $this->context_id
        ];

        // get available playlists for this course
        $seminar_playlists = new \SimpleCollection(PlaylistSeminars::findBySeminar_id($this->context_id));

        $playlist_ids = $seminar_playlists->pluck('playlist_id');

        $videos = Videos::findBySQL($sql = 'LEFT JOIN oc_video_seminar AS vs ON (vs.seminar_id = :seminar_id AND vs.video_id = id) '
            . (!empty($playlist_ids) ? ' LEFT JOIN oc_playlist_video AS opv ON (opv.video_id = id AND opv.playlist_id IN ('. implode(', ', $playlist_ids) .')) ' : '')
            . ' WHERE vs.video_id IS NOT NULL '
            .  (!empty($playlist_ids) ? ' OR opv.playlist_id IS NOT NULL ' : '')
            . ' GROUP BY oc_video.id ORDER BY oc_video.mkdate desc',
            [
                ':seminar_id' => $this->context_id
            ]
        );

        $ret = [];
        foreach ($videos as $video) {
            $vid = $video->toSanitizedArray();
            $vid['id'] = $vid['episode'];
            $ret[] = $vid;
        }

        return $ret;
    }
}