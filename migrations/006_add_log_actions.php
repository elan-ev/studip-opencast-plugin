<?php

class AddLogActions extends Migration {

    static $log_actions = array(
        array(
            'name'        => 'OC_CHANGE_EPISODE_VISIBILITY',
            'description' => 'Sichtbarkeit einer Episoden geändert',
            'template'    => '%user ändert Sichtbarkeit der Aufzeichnung %affected in %sem(%coaffected)',
            'active'      => 1
        ), array(
            'name'        => 'OC_CHANGE_TAB_VISIBILITY',
            'description' => 'Sichtbarkeit des Kursreiters geändert',
            'template'    => '%user ändert Sichtbarkeit des Kursreiters in %sem(%affected)',
            'active'      => 1
        ), array(
            'name'        => 'OC_SCHEDULE_EVENT',
            'description' => 'Planung einer Opencast Aufzeichnung',
            'template'    => '%user plant Aufzeichnung %affected in %sem(%coaffected)',
            'active'      => 1
        ), array(
            'name'        => 'OC_REFRESH_SCHEDULED_EVENT',
            'description' => 'Aktualisierung einer Opencast Aufzeichnung',
            'template'    => '%user aktualisiert Aufzeichnung %affected in %sem(%coaffected)',
            'active'      => 1
        ), array(
            'name'        => 'OC_CANCEL_SCHEDULED_EVENT',
            'description' => 'Stornierung einer Opencast Aufzeichnung',
            'template'    => '%user storniert Aufzeichnung %affected in %sem(%coaffected)',
            'active'      => 1
        ), array(
            'name'        => 'OC_CREATE_SERIES',
            'description' => 'Anlegen einer Opencast Aufzeichnungsserie',
            'template'    => '%user legt neue Aufzeichnungsserie in %sem(%affected) an',
            'active'      => 1
        ), array(
            'name'        => 'OC_CONNECT_SERIES',
            'description' => 'Verknüpfung einer Opencast Aufzeichnungsserie',
            'template'    => '%user verknüpft vorhandene Aufzeichnungsserie %affected in %sem(%coaffected) an',
            'active'      => 1
        ), array(
            'name'        => 'OC_REMOVE_CONNECTED_SERIES',
            'description' => 'Aufheben einer Opencast Aufzeichnungsserienverknüpfung',
            'template'    => '%user löscht die Verbindung zur Aufzeichnungsserie %affected in %sem(%coaffected) an',
            'active'      => 1
        ), array(
            'name'        => 'OC_UPLOAD_MEDIA',
            'description' => 'Upload einer Datei in einer Opencast Aufzeichnungsserie',
            'template'    => '%user lädt eine Datei mit der WorkflowID %affected in %sem(%coaffected) hoch',
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