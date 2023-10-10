<?php

namespace Opencast\Models;
use \Course;

class VideoCoursewareBlocks extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_video_cw_blocks';

        parent::configure($config);
    }
    /**
     * Adds or updates a video courseware block record
     *
     * @param string $seminar_id    id of course
     * @param int    $token         token of video
     * @param string $block_id      id of block
     *
     * @return bool
     * @throws Exception
     */
    public static function setRecord($seminar_id, $token, $block_id)
    {
        // Preventing record entry except for a course!
        if (empty($seminar_id) || !Course::find($seminar_id)) {
            return false;
        }

        if (!empty($seminar_id) && !empty($token) && !empty($block_id)) {
            if (!$video_cw = self::findOneBySQL('block_id = ? AND seminar_id = ?', [$block_id, $seminar_id])) {
                $video_cw = new self();
            }

            $video_cw->setData(compact('seminar_id', 'token', 'block_id'));
            return $video_cw->store();
        } else {
            throw new \Exception(_('Falsche Informationen zum Speichern von Daten.'));
        }
    }

    public static function findByBlock_id($block_id)
    {
        return self::findOneBySQL('block_id = ?', [$block_id]);
    }
}
