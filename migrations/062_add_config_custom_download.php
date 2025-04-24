<?php

class AddConfigCustomDownload extends Migration
{
    public function description()
    {
        return 'Add config for editable download options';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_playlist`
            ADD COLUMN `allow_download` boolean DEFAULT NULL");
        $db->exec("ALTER TABLE `oc_video`
            ADD COLUMN `allow_download` boolean DEFAULT NULL");

        // Get the current value of the config and map to new config value.
        $current_media_download_config = (bool) Config::get()->OPENCAST_ALLOW_MEDIADOWNLOAD ?? null;
        $new_config_value = $current_media_download_config === true ? 'allow' : 'never';

        $db->exec("DELETE FROM `config` WHERE `field`='OPENCAST_ALLOW_MEDIADOWNLOAD'");

        $stmt = $db->prepare('INSERT IGNORE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
                              VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');
        $stmt->execute([
            'name'        => 'OPENCAST_MEDIADOWNLOAD',
            'section'     => 'opencast',
            'description' => 'Erlaubnis für Mediendownloads verwalten.',
            'range'       => 'global',
            'type'        => 'string',
            'value'       => $new_config_value
        ]);

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        // Revert the config value with mapping to the old config.
        $new_media_download_config = (string) Config::get()->OPENCAST_MEDIADOWNLOAD ?? 'never';
        $old_config_value = $new_media_download_config !== 'allow' ? true : false;

        $db->exec("DELETE FROM config WHERE field = 'OPENCAST_MEDIADOWNLOAD'");
        $db->exec("ALTER TABLE `oc_playlist` DROP COLUMN `allow_download`");
        $db->exec("ALTER TABLE `oc_video` DROP COLUMN `allow_download`");

        $stmt = $db->prepare('INSERT IGNORE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
                             VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');
        $stmt->execute([
            'name'        => 'OPENCAST_ALLOW_MEDIADOWNLOAD',
            'section'     => 'opencast',
            'description' => 'Wird Nutzern angeboten, Aufzeichnungen herunterzuladen?',
            'range'       => 'global',
            'type'        => 'boolean',
            'value'       => $old_config_value
        ]);

        SimpleOrMap::expireTableScheme();
    }
}
