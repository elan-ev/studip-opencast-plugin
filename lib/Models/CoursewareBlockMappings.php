<?php

namespace Opencast\Models;

class CoursewareBlockMappings extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_cw_block_copy_mapping';

        parent::configure($config);
    }

    /**
     * Adds a new mapping record
     *
     * @param string $seminar_id id of course
     * @param int $video_id id of video
     * @param string $block_id id of block
     *
     * @return bool
     * @throws Exception
     */
    public static function setRecord($token, $video_id, $new_seminar_id)
    {
        if (!empty($token) && !empty($video_id) && !empty($new_seminar_id)) {
            if (!$video_cw_mapping = self::findOneBySQL('video_id = ? AND new_seminar_id = ? AND token = ?', [$video_id, $new_seminar_id, $token])) {
                $video_cw_mapping = new self();
            }

            $video_cw_mapping->setData(compact('token', 'video_id', 'new_seminar_id'));
            return $video_cw_mapping->store();
        } else {
            throw new \Exception('Could not store course mappings, missing data.');
        }
    }

}
