<?php

class AddLogActions extends Migration {

    static $log_actions = array(
        array(
            'name'        => 'OC_CHANGE_EPISODE_VISIBILITY',
            'description' => 'Opencast: Sichtbarkeit einer Episode geandert',
            'template'    => '%user aendert Sichtbarkeit der Aufzeichnung %affected in %sem(%coaffected)',
            'active'      => 1
        ), array(
            'name'        => 'OC_CHANGE_TAB_VISIBILITY',
            'description' => 'Opencast:  Sichtbarkeit des Kursreiters geaendert',
            'template'    => '%user aendert Sichtbarkeit des Kursreiters in %sem(%affected)',
            'active'      => 1
        ), array(
            'name'        => 'OC_SCHEDULE_EVENT',
            'description' => 'Opencast: Planung einer Aufzeichnung',
            'template'    => '%user plant Aufzeichnung %affected in %sem(%coaffected)',
            'active'      => 1
        ), array(
            'name'        => 'OC_REFRESH_SCHEDULED_EVENT',
            'description' => 'Opencast: Aktualisierung einer Aufzeichnung',
            'template'    => '%user aktualisiert Aufzeichnung %affected in %sem(%coaffected)',
            'active'      => 1
        ), array(
            'name'        => 'OC_CANCEL_SCHEDULED_EVENT',
            'description' => 'Opencast: Stornierung einer Aufzeichnung',
            'template'    => '%user storniert Aufzeichnung %affected in %sem(%coaffected)',
            'active'      => 1
        ), array(
            'name'        => 'OC_CREATE_SERIES',
            'description' => 'Opencast: Anlegen einer Aufzeichnungsserie',
            'template'    => '%user legt neue Aufzeichnungsserie in %sem(%affected) an',
            'active'      => 1
        ), array(
            'name'        => 'OC_CONNECT_SERIES',
            'description' => 'Opencast: Verknuepfung einer Aufzeichnungsserie',
            'template'    => '%user verknuepft vorhandene Aufzeichnungsserie %affected in %sem(%coaffected) an',
            'active'      => 1
        ), array(
            'name'        => 'OC_REMOVE_CONNECTED_SERIES',
            'description' => 'Opencast: Aufheben einer Aufzeichnungsserienverknuepfung',
            'template'    => '%user loescht die Verbindung zur Aufzeichnungsserie %affected in %sem(%coaffected) an',
            'active'      => 1
        ), array(
            'name'        => 'OC_UPLOAD_MEDIA',
            'description' => 'Opencast: Upload einer Datei in einer Aufzeichnungsserie',
            'template'    => '%user laedt eine Datei mit der WorkflowID %affected in %sem(%coaffected) hoch',
            'active'      => 1
        )
    );



    function up()
    {
        $db = DBManager::get();
        $query = $db->prepare("INSERT INTO log_actions (action_id, name, description, info_template, active) VALUES (?, ?, ?, ?, ?)");

        foreach (self::$log_actions as $action) {
            $query->execute(array(md5($action['name']), $action['name'], $action['description'], $action['template'], $action['active']));
        }

    }

    function down()
    {
        $db = DBManager::get();
        $query = $db->prepare("DELETE FROM log_actions WHERE action_id = ?");
        $query2 = $db->prepare("DELETE FROM log_events WHERE action_id = ?");

        foreach (self::$log_actions as $action) {
            $query->execute(array(md5($action['name'])));
            $query2->execute(array(md5($action['name'])));
        }

    }
}

?>
