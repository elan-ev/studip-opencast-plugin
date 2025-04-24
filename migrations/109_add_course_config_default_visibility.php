<?php
class AddCourseConfigDefaultVisibility extends Migration
{


    public function description()
    {
        return 'Create new course config for default visibility of the episodes';
    }

    public function up()
    {
        Config::get()->create('OPENCAST_COURSE_DEFAULT_EPISODES_VISIBILITY', [
            'value' => 'default',
            'type' => 'string',
            'range' => 'course',
            'section' => 'opencast',
            'description' => 'Legt den Sichtbarkeitsstatus für Episoden fest, die den Standardwerten im Kurs folgen.'
        ]);
    }

    public function down()
    {
        Config::get()->delete('OPENCAST_COURSE_DEFAULT_EPISODES_VISIBILITY');
    }
}
