<?php
class AddConfigOptionToggleUnavailableVideos extends Migration
{


    public function description()
    {
        return 'Create new config option to toggle unavailable videos listing.';
    }

    public function up()
    {
        Config::get()->create('OPENCAST_LIST_UNAVAILABLE_VIDEOS', [
            'value' => false,
            'type' => 'boolean',
            'range' => 'global',
            'section' => 'opencast',
            'description' => 'Nicht verfügbare Videos in Videolisten anzeigen?'
        ]);
    }

    public function down()
    {
        Config::get()->delete('OPENCAST_LIST_UNAVAILABLE_VIDEOS');
    }
}
