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
        print_r([$event,$object,$data]);
    }
}