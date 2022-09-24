<?php

use Opencast\Models\Videos;
use Opencast\Models\Filter;

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
            'offset' => 0,
            'limit'  => 500,
            'order'  => 'mkdate_desc',
            'cid'    => $this->context_id
        ];

        // select all videos the current user has perms on
        $videos = Videos::findByFilter(new Filter($request));

        $ret = [];
        foreach ($videos['videos'] as $video) {
            $ret[] = $video->toSanitizedArray();
        }

        return $ret;
    }
}