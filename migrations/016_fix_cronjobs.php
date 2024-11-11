<?php

class FixCronjobs extends Migration
{

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
