<?php

class AddNewLogActionOcWarning extends Migration
{
    const OC_WARNING_LOG_ACTON_NAME = 'OC_WARNINGS';
    const PLUGINCLASSNAME = 'OpencastV3';
    public function description()
    {
        return 'Registering new log action "OC_WARNING" for Opencast Plugin, in order to log the warnings throughout the app.';
    }

    public function up()
    {
        $description = 'Opencast: Warnungen / Meldungen';
        $info_template = '[Opencast Warnung]: %info - (Wo: %affected, Wer: %user)';
        StudipLog::registerActionPlugin(
            self::OC_WARNING_LOG_ACTON_NAME,
            $description,
            $info_template,
            self::PLUGINCLASSNAME
        );
    }

    public function down()
    {
        StudipLog::unregisterAction(self::OC_WARNING_LOG_ACTON_NAME);
    }
}
