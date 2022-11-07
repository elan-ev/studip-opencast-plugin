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
        $db->exec("DELETE FROM `config` WHERE `field`='OPENCAST_ALLOW_MEDIADOWNLOAD'");
        
        $stmt = $db->prepare('INSERT IGNORE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
                              VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');
        $stmt->execute([
            'name'        => 'OPENCAST_MEDIADOWNLOAD_NEVER',
            'section'     => 'opencast',
            'description' => 'Mediendownloads werden niemals angeboten',
            'range'       => 'global',
            'type'        => 'boolean',
            'value'       => true
        ]);
        $stmt->execute([
            'name'        => 'OPENCAST_MEDIADOWNLOAD_ALLOWED',
            'section'     => 'opencast',
            'description' => 'Mediendownloads sind erlaubt - für einzelne Wiedergabelisten und Videos abschaltbar',
            'range'       => 'global',
            'type'        => 'boolean',
            'value'       => false
        ]);
        $stmt->execute([
            'name'        => 'OPENCAST_MEDIADOWNLOAD_DISALLOWED',
            'section'     => 'opencast',
            'description' => 'Mediendownloads sind verboten - für einzelne Wiedergabelisten und Videos einschaltbar',
            'type'        => 'boolean',
            'value'       => false
        ]);

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec("DELETE FROM config WHERE field = 'OPENCAST_MEDIADOWNLOAD_NEVER'");
        $db->exec("DELETE FROM config WHERE field = 'OPENCAST_MEDIADOWNLOAD_ALLOWED'");
        $db->exec("DELETE FROM config WHERE field = 'OPENCAST_MEDIADOWNLOAD_DISALLOWED'");
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
            'value'       => true
        ]);

        SimpleOrMap::expireTableScheme();
    }
}
