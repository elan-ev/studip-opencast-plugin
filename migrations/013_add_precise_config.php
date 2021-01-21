<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (14:00)
 */

class AddPreciseConfig extends Migration
{

    function up()
    {
        //setup precise config
        DBManager::get()->query('DROP TABLE IF EXISTS `oc_config_precise`');

        DBManager::get()->query('CREATE TABLE IF NOT EXISTS `oc_config_precise` (
            `id` int(11) NOT NULL,
            `name` varchar(100) COLLATE latin1_german1_ci DEFAULT NULL,
            `description` text COLLATE latin1_german1_ci,
            `value` varchar(255) COLLATE latin1_german1_ci DEFAULT NULL,
            `for_config` int(11) DEFAULT NULL)
        ');

        DBManager::get()->query("INSERT INTO `oc_config_precise`(`id`, `name`, `description`, `value`, `for_config`) VALUES
            (1, 'upload_chunk_size', 'Größe der Chunks für das Hochladen in Byte', '10000000', -1),
            (2, 'number_of_configs', 'Maximale Anzahl von sichtbaren Konfigurationen', '1', -1),
            (3, 'time_buffer_overlap', 'Zeitpuffer (in Sekunden) um Überlappungen zu verhindern', '60', -1),
            (4, 'ssl_verify_peer', 'SSL Zertifikat des Peers prüfen', 'false', -1),
            (5, 'ssl_verify_host', 'SSL Zertifikat des Hosts prüfen', 'false', -1),
            (6, 'ssl_cipher_list', 'Zu benutzende SSL Chiffren', 'none', -1),
            (7, 'capture_agent_attribute', 'Namen der Capture Agents als Ressourcen-Objekte', 'Opencast Capture Agent', -1),
            (8, 'lti_consumerkey', 'LTI Consumerkey', 'CONSUMERKEY', -1),
            (9, 'lti_consumersecret', 'LTI Consumersecret', 'CONSUMERSECRET', -1)
        ");

        DBManager::get()->query('ALTER TABLE `oc_config_precise` ADD UNIQUE KEY `oc_config_precise_id_uindex`(`id`)');
        DBManager::get()->query('ALTER TABLE `oc_config_precise` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9');

        //remove column from old config
        DBManager::get()->query("ALTER TABLE `oc_config` DROP COLUMN `schedule_time_puffer_seconds`;");
    }

    function down()
    {
        //remove precise config
        DBManager::get()->query('DROP TABLE oc_config_precise');

        //readd
        DBManager::get()->query("ALTER TABLE `oc_config` ADD `schedule_time_puffer_seconds` int DEFAULT 300 NOT NULL;");
    }

}
