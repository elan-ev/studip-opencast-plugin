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
        print_r([$event,$object,$data,ConfigurationAction::determine_change_type($data[1],$data[2])]);
        $name = $data[0];
        $old_value = $data[1];
        $new_value = $data[2];
        if($name == 'capture_agent_attribute' && ConfigurationAction::determine_change_type()=='change'){
            $stmt = DBManager::get()->prepare("UPDATE `resources_properties` SET `name`=? WHERE `name`=?");
            $result = $stmt->execute([$old_value, $new_value]);
        }
    }
}