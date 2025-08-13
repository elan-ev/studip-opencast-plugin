<?php
class RemoveLogActions extends Migration
{
    const PLUGINCLASSNAME = 'OpencastV3';

    private $log_actions = [
        [
            'name'        => 'OC_CHANGE_EPISODE_VISIBILITY',
            'description' => 'Opencast: Sichtbarkeit einer Episode geandert',
            'template'    => '%user aendert Sichtbarkeit der Aufzeichnung %affected in %sem(%coaffected)',
        ],
        [
            'name'        => 'OC_CHANGE_TAB_VISIBILITY',
            'description' => 'Opencast:  Sichtbarkeit des Kursreiters geaendert',
            'template'    => '%user aendert Sichtbarkeit des Kursreiters in %sem(%affected)',
        ],
        [
            'name'        => 'OC_CREATE_SERIES',
            'description' => 'Opencast: Anlegen einer Aufzeichnungsserie',
            'template'    => '%user legt neue Aufzeichnungsserie in %sem(%affected) an',
        ],
        [
            'name'        => 'OC_CONNECT_SERIES',
            'description' => 'Opencast: Verknuepfung einer Aufzeichnungsserie',
            'template'    => '%user verknuepft vorhandene Aufzeichnungsserie %affected in %sem(%coaffected) an',
        ],
        [
            'name'        => 'OC_REMOVE_CONNECTED_SERIES',
            'description' => 'Opencast: Aufheben einer Aufzeichnungsserienverknuepfung',
            'template'    => '%user loescht die Verbindung zur Aufzeichnungsserie %affected in %sem(%coaffected) an',
        ],
        [
            'name'        => 'OC_UPLOAD_MEDIA',
            'description' => 'Opencast: Upload einer Datei in einer Aufzeichnungsserie',
            'template'    => '%user laedt eine Datei mit der WorkflowID %affected in %sem(%coaffected) hoch',
        ],
        [
            'name'        => 'OC_REMOVE_MEDIA',
            'description' => 'Opencast: Episode geloescht',
            'template'    => '%user loeschte Episode %info in %sem(%affected)',
        ],
        [
            'name'        => 'OC_TOS',
            'description' => 'Opencast: TOS akzeptiert/abgelehnt',
            'template'    => '%user hat TOS %info in %sem(%affected)',
        ],
    ];

    public function description()
    {
        return 'Remove unwanted log actions';
    }

    public function up()
    {
        foreach ($this->log_actions as $log_action) {
            StudipLog::unregisterAction($log_action['name']);
        }
    }

    public function down()
    {
        foreach ($this->log_actions as $log_action) {
            StudipLog::registerActionPlugin(
                $log_action['name'],
                $log_action['description'],
                $log_action['template'],
                self::PLUGINCLASSNAME
            );
        }
    }
}
