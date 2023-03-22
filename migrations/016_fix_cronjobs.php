<?php

class FixCronjobs extends Migration
{
    /*
    const FILENAMES = [
        'public/plugins_packages/elan-ev/OpenCast/cronjobs/refresh_scheduled_events.php',
        'public/plugins_packages/elan-ev/OpenCast/cronjobs/refresh_series.php'
    ];
    */

    public function description()
    {
        return 'adds a cronjob for reuploading filed media uploads and fixes all cronjob registrations';
    }

    public function up()
    {
        return;
    }

    function down() {}
}
