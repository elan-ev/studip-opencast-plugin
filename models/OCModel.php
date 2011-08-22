<?PHP
    
class OCModel
{

    static function getUnconnectedSeries() {
        $stmt = DBManager::get()->prepare("SELECT *
            FROM oc_series
            WHERE 1");
        $stmt->execute();
        $series = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $series;
    }
    
    static function getConnectedSeries($course_id) {
        $stmt = DBManager::get()->prepare("SELECT series_id 
            FROM oc_seminar_series
            WHERE seminar_id = ?");
        $stmt->execute(array($course_id));
        $series = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $series;
    }
    
    static function setSeriesforCourse($course_id, $series_id, $visibility = 'visible') {
        $stmt = DBManager::get()->prepare("UPDATE oc_series 
                SET seminars = seminars+1
                WHERE series_id = ?");
        $stmt->execute(array($series_id));

        $stmt = DBManager::get()->prepare("REPLACE INTO
                oc_seminar_series (series_id, seminar_id, visibility)
                VALUES (?, ?, ?)");
        return $stmt->execute(array($series_id, $course_id, $visibility));
    }
    
    static function removeSeriesforCourse($course_id, $series_id) {
        $stmt = DBManager::get()->prepare("UPDATE 
                oc_series SET seminars = seminars-1
                WHERE series_id =?");
       $stmt->execute(array($course_id));
       $stmt = DBManager::get()->prepare("DELETE FROM
                oc_seminar_series
                WHERE series_id =? AND seminar_id = ?");
        return $stmt->execute(array($series_id, $course_id));
    }

    static function getOCRessources() {
       $stmt = DBManager::get()->prepare("SELECT * FROM resources_objects ro
                LEFT JOIN resources_objects_properties rop ON (ro.resource_id = rop.resource_id)
                WHERE rop.property_id = (SELECT property_id FROM resources_properties WHERE name = 'Opencast Capture Agent' )
                AND rop.state = 'on'");

       $stmt->execute();
       $resources =  $stmt->fetchAll(PDO::FETCH_ASSOC);
       return $resources;
    }

    static function getAssignedOCRessources() {
       $stmt = DBManager::get()->prepare("SELECT * FROM resources_objects ro
                LEFT JOIN resources_objects_properties rop ON (ro.resource_id = rop.resource_id)
                LEFT JOIN oc_resources or rop ON (ro.resource_id = or.resource_id)
                WHERE rop.property_id = (SELECT property_id FROM resources_properties WHERE name = 'Opencast Capture Agent' )
                AND rop.state = 'on'");

       $stmt->execute();
       $resources =  $stmt->fetchAll(PDO::FETCH_ASSOC);
       return $resources;
    }

    static function setCAforResource($resource_id, $capture_agent) {
        $stmt = DBManager::get()->prepare("REPLACE INTO
                oc_resources (resource_id, capture_agent)
                VALUES (?, ?)");
        return $stmt->execute(array($resource_id, $capture_agent));
    }



    static function getMetadata($metadata, $type = 'title') {
        foreach($metadata->metadata as $data) {
            if($data->key == $type) {
                $return = $data->value;
            }
        }
       
        return $return;
    }

    static function getDates($seminar_id) {
        $stmt = DBManager::get()->prepare("SELECT * FROM `termine` WHERE `range_id` = ?");

       $stmt->execute(array($seminar_id));
       $dates =  $stmt->fetchAll(PDO::FETCH_ASSOC);
       return $dates;
    }

    
}
?>
