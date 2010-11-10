<?PHP
    
class OCModel
{

    static function getUnconnectedSeries() {
        $stmt = DBManager::get()->prepare("SELECT series_id 
            FROM oc_series
            WHERE seminar_id IS NULL");
        $stmt->execute();
        $series = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $series;
    }
    
    static function getConnectedSeries($course_id) {
        $stmt = DBManager::get()->prepare("SELECT series_id 
            FROM oc_series
            WHERE seminar_id = ?");
        $stmt->execute(array($course_id));
        $series = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $series;
    }
    
    static function setSeriesforCourse($course_id, $series_id) {
        $stmt = DBManager::get()->prepare("REPLACE INTO 
                oc_series (series_id, seminar_id)
                VALUES (?, ?)");
        //var_dump($stmt, $course_id, $series_id);die();
        return $stmt->execute(array($series_id, $course_id));
    }
    
    static function removeSeriesforCourse($course_id, $series_id) {
        $stmt = DBManager::get()->prepare("UPDATE 
                oc_series SET seminar_id = NULL
                WHERE series_id =? AND seminar_id = ?");
        return $stmt->execute(array($series_id, $course_id));
    }
    
}
?>