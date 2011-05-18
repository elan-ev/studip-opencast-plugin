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
    
}
?>
