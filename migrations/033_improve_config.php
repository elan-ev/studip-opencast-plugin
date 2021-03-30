<?php

class ImproveConfig extends Migration
{
    public function description()
    {
        return 'Update configuration to new storage type';
    }

    public function up()
    {
        $db = DBManager::get();

        // drop old seminar_id
        try {
            $db->exec("ALTER TABLE `oc_config`
                ADD COLUMN `settings` TEXT NOT NULL AFTER `tos`");

            $db->exec("ALTER TABLE `oc_config`
                CHANGE `config_id` `id` int(11) NOT NULL AUTO_INCREMENT FIRST;");
        } catch (PDOException $e) {}

        // set seminar_id for all entries, update only the first connected course
        $defaults = $db->query("SELECT name, value
            FROM oc_config_precise
            WHERE for_config = -1")->fetchAll(PDO::FETCH_KEY_PAIR);

        $results = $db->query("SELECT * FROM oc_config_precise");

        $configs = [];

        while ($data = $results->fetch(PDO::FETCH_ASSOC)) {
            $confid = $data['for_config'];

            if ($confid != -1) {
                if (!isset($configs[$confid][$data['name']])) {
                    $configs[$confid][$data['name']] = $data['value'];
                }
            }
        }

        $update_stmt = $db->prepare('UPDATE oc_config
            SET settings = ?
            WHERE id = ?');

        foreach ($configs as $id => $config) {
            $config = array_merge($defaults, $config);
            $update_stmt->execute([json_encode($config), $id]);
        }

        try {
            $db->exec("DROP TABLE oc_config_precise");
        } catch (PDOException $e) {}

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
        $db = DBManager::get();

        try {
            $db->exec('CREATE TABLE IF NOT EXISTS `oc_config_precise` (
                `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `name` varchar(100) COLLATE latin1_german1_ci DEFAULT NULL,
                `description` text COLLATE latin1_german1_ci DEFAULT NULL,
                `value` varchar(255) COLLATE latin1_german1_ci DEFAULT NULL,
                `for_config` int(11) DEFAULT NULL)
            ');
        
            $db->query("INSERT INTO `oc_config_precise`(`name`, `description`, `value`, `for_config`) VALUES
                ('upload_chunk_size', 'Größe der Chunks für das Hochladen in Byte', '10000000', -1),
                ('number_of_configs', 'Maximale Anzahl von sichtbaren Konfigurationen', '1', -1),
                ('time_buffer_overlap', 'Zeitpuffer (in Sekunden) um Überlappungen zu verhindern', '60', -1),
                ('ssl_verify_peer', 'SSL Zertifikat des Peers prüfen', 'false', -1),
                ('ssl_verify_host', 'SSL Zertifikat des Hosts prüfen', 'false', -1),
                ('ssl_cipher_list', 'Zu benutzende SSL Chiffren', 'none', -1),
                ('capture_agent_attribute', 'Namen der Capture Agents als Ressourcen-Objekte', 'Opencast Capture Agent', -1),
                ('lti_consumerkey', 'LTI Consumerkey', 'CONSUMERKEY', -1),
                ('lti_consumersecret', 'LTI Consumersecret', 'CONSUMERSECRET', -1)
            ");

            $stmt = $db->prepare("INSERT IGNORE INTO oc_config_precise (name, value, for_config)
                VALUES (:name, :value, :for_config)");
            $data = $db->query("SELECT id, settings FROM `oc_config`")->fetchAll(PDO::FETCH_KEY_PAIR);
            foreach($data as $id => $settings) {
                $configs = json_decode($settings, True);
                foreach($configs as $name => $value) {
                    $stmt->execute([
                        'name'       => $name,
                        'value'      => $value,
                        'for_config' => $id
                    ]);
                }
            }

            $db->exec("ALTER TABLE `oc_config`
                CHANGE `id` `config_id` int(11) NOT NULL AUTO_INCREMENT FIRST;");
            $db->exec("ALTER TABLE `oc_config`
                DROP COLUMN `settings`");
        } catch (PDOException $e) {}

        SimpleOrMap::expireTableScheme();
    }
}
