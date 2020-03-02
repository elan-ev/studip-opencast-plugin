<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (14:00)
 */

class UpdatePreciseConfig extends Migration
{

    function up()
    {
        DBManager::get()->query("REPLACE INTO `oc_config_precise`(`id`, `name`, `description`, `value`, `for_config`) VALUES
            (8, 'lti_consumerkey', 'LTI Consumerkey', 'CONSUMERKEY', -1),
            (9, 'lti_consumersecret', 'LTI Consumersecret', 'CONSUMERSECRET', -1)
        ");
    }

    function down()
    {
    }

}
