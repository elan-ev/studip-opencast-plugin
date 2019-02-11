<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (13:45)
 */

abstract class ConfigurationAction
{
    public abstract function trigger($event, $object, $data);

    /**
     * @param        $special_event
     */
    public function add_as_observer($special_event){
        $event = "opencast.configuration.$special_event";
        NotificationCenter::addObserver($this,'trigger',$event);
    }
}