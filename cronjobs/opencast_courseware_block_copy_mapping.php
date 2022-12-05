<?php

require_once __DIR__.'/../bootstrap.php';
require_once __DIR__.'/../vendor/autoload.php';

use Opencast\Models\CoursewareBlockMappings;
use Opencast\Models\VideoCoursewareBlocks;
use Courseware\Block;

class OpencastCoursewareBlockCopyMapping extends CronJob
{

    /**
     * Return the name of the cronjob.
     */
    public static function getName()
    {
        return _('Opencast - Courseware Block Kopie-Mapping');
    }

    /**
     * Return the description of the cronjob.
     */
    public static function getDescription()
    {
        return _('Opencast: Fügt die Datensätze der kopierten Opencast-Courseware-Blöcke in die Tabelle oc_video_cw_blocks ein');
    }

    public function execute($last_result, $parameters = array())
    {
        $db = DBManager::get();
        $new_block_query = $db->prepare("SELECT id FROM cw_blocks WHERE payload LIKE CONCAT('%', :token, '%')");
        foreach (CoursewareBlockMappings::findBySql('1') as $mapping) {
            try {
                echo 'Initiate mapping ID: ' . $mapping->id . "\n";
                $token = $mapping->token;
                $new_block_query->execute([':token' => $token]);
                $new_block_record = $new_block_query->fetchOne(PDO::FETCH_ASSOC);
                if (!empty($new_block_record)) {
                    echo 'Peform mapping for block: ' . $new_block_record['id'] . "\n";

                    // Add record into the VideoCoursewareBlocks
                    $msg = 'Record is added into oc_video_cw_blocks';
                    $added = VideoCoursewareBlocks::setRecord($mapping->new_seminar_id, $mapping->video_id, $new_block_record['id']);
                    if (!$added) {
                        $msg = 'No record is added into oc_video_cw_blocks!';
                    }
                    echo $msg;

                    // Get the actual block object.
                    $new_block = Block::find($new_block_record['id']);
                    if (!empty($new_block)) {
                        // Remove extra param from block's payload.
                        $palyload = json_decode($new_block->payload, true);
                        unset($palyload['copied_from']);
                        $new_block->payload = json_encode($palyload);
                        $new_block->store();
                        echo "Block's Playload cleared \n";
                    }

                    // Delete the mapping record!
                    $mapping->delete();
                    echo "Mapping completed\n";
                }
            } catch (\Throwable $th) {}
        }

        return true;
    }
}
