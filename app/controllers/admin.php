<?php

use Opencast\Models\OCConfig;
use Opencast\Models\OCEndpoints;
use Opencast\Models\OCSeminarSeries;
use Opencast\LTI\OpencastLTI;
use Opencast\Constants;
use Opencast\Configuration;

class AdminController extends Opencast\Controller
{
    /**
     * Constructs the controller and provide translations methods.
     *
     * @param object $dispatcher
     * @see https://stackoverflow.com/a/12583603/982902 if you need to overwrite
     *      the constructor of the controller
     */
    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);

        //$this->config = OCConfig::getBaseServerConf();
    }

    function before_filter(&$action, &$args)
    {
        if (!$GLOBALS['perm']->have_perm('root')) {
            throw new AccessDeniedException();
        }

        // notify on trails action
        $klass = substr(get_called_class(), 0, -10);
        $name = sprintf('oc_admin.performed.%s_%s', $klass, $action);
        NotificationCenter::postNotification($name, $this);

        parent::before_filter($action, $args);
    }

    public function index_action()
    {
        Navigation::activateItem('/admin/config/oc-config');

        /*
        Helpbar::get()->addPlainText('', $this->_('Hier wird die Anbindung zum Opencast System verwaltet.
        Geben Sie die Daten ihres Opencast-Systems ein, um Aufzeichnungen in Ihren Veranstaltungen bereitstellen zu können.
        Optional haben Sie die Möglichkeit, ein zweites Opencast-System im Nur-Lesen-Modus anzubinden.')
        );
        */

        // starting point for vue app
    }

    public function isUploadStudygroupActivatable()
    {
        foreach ($GLOBALS['SEM_CLASS'] as $sem_class) {
            if ($sem_class['name'] == 'Studiengruppen') {
                if (!$sem_class['modules']['OpenCast']['activated'] && $sem_class['modules']['OpenCast']['sticky']) {
                    return false;
                }
            }
        }
        return true;
    }
}
