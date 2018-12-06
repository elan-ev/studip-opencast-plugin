<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (14:00)
 */

class AddSchedulePuffer extends Migration
{

    function up()
    {
        DBManager::get()->query("create table oc_config_precise( id int auto_increment, name varchar(100) null, description text null, value varchar(255) null, for_config int null, constraint oc_config_precise_id_uindex unique (id)); alter table oc_config_precise add primary key (id); ");
        Configuration::instance(OC_GLOBAL_CONFIG_ID)->set('upload_chunk_size', 10000000, 'Größe der Chunks für den Upload in Byte');
        Configuration::instance(OC_GLOBAL_CONFIG_ID)->set('number_of_configs', 1, 'Maximale Anzahl von sichtbaren Konfigurationen');
        Configuration::instance(OC_GLOBAL_CONFIG_ID)->set('time_buffer_overlap', 60, 'Zeitpuffer (in Sekunden) um Überlappungen zu verhindern');
        Configuration::instance(OC_GLOBAL_CONFIG_ID)->set('upload_encoding', 'UTF-8', 'Encoding für den Upload');
        Configuration::instance(OC_GLOBAL_CONFIG_ID)->set('ssl_verify_peer', 'false', 'SSL Zertifikat des Peers prüfen');
        Configuration::instance(OC_GLOBAL_CONFIG_ID)->set('ssl_verify_host', 'false', 'SSL Zertifikat des Hosts prüfen');
        Configuration::instance(OC_GLOBAL_CONFIG_ID)->set('ssl_cipher_list', 'none', 'Zu benutzende SSL Chiffren');

        DBManager::get()->query("ALTER TABLE `oc_config` DROP COLUMN `schedule_time_puffer_seconds`;");
    }

    function down()
    {
        DBManager::get()->query("ALTER TABLE `oc_config` ADD `schedule_time_puffer_seconds` int DEFAULT 300 NOT NULL;");
        DBManager::get()->query("truncate table oc_config_precise;");
    }

}
