<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (14:07)
 */



class ResourceObjectAttributeChangeAction extends ConfigurationAction
{

    public function trigger($event, $object, $data)
    {
        $prefix = 'OCCA#';
        $stmt = DBManager::get()->prepare("UPDATE `resources_properties` SET `name`=? WHERE `name`=?");
        $result = $stmt->execute([$prefix.$data['new'],$prefix.$data['old']]);
    }
}