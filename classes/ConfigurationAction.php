<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (13:45)
 */

abstract class ConfigurationAction
{
    public abstract function trigger($event, $object, $data);

    public static function determine_change_type($old_value, $new_value){
        //no change
        if ($old_value == $new_value){
            return 'no_change';
        }
        //init of value
        if ($old_value == '' && $new_value != ''){
            return 'just_init';
        }
        return 'change';
    }
}