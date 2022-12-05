<?php

namespace Opencast\Models;
use \DBManager;
use \PDO;

use Opencast\Models\I18N as _;

class Resources extends \SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_resources';

        parent::configure($config);
    }

    /**
     * Finds the resource object based on the resource id
     *
     * @param string $resource_id
     * @return Resources|null the object or null
     *
     */
    public static function findByResource_id($resource_id)
    {
        return self::findOneBySQL('resource_id = ?', [$resource_id]);
    }

    /**
     * Adds or updates a resource
     * 
     * @param string $resource_id id of resource
     * @param string $config_id id of server config
     * @param string $capture_agent name of capture agent
     * @param string $workflow_id name of workflow
     * 
     * @return bool
     * @throws Exception
     */
    public static function setResource($resource_id, $config_id, $capture_agent, $workflow_id)
    {
        if (!empty($config_id) && !empty($capture_agent) && !empty($resource_id)) {
            if (!$resource = self::findByResource_id($resource_id)) {
                $resource = new self();
            }

            $resource->setData(compact('config_id', 'resource_id', 'capture_agent', 'workflow_id'));
            return $resource->store();
        } else {
            throw new \Exception(_('Die Resourcen wurden nicht korrekt angegeben.'));
        }
    }

    /**
     * Removes a resource
     * 
     * @param string $resource_id id of resource
     * 
     * @return bool
     */
    public static function removeResource($resource_id)
    {
        return self::deleteBySql(
            'resource_id = ?',
            [$resource_id]
        );
    }

    /**
     * Retreives StudIP resources based on OPENCAST_RESOURCE_PROPERTY_ID
     * 
     * @return array the list of StudIP resources
     */
    public static function getStudipResources()
    {
       $stmt = DBManager::get()->prepare("SELECT r.*
            FROM resource_properties AS rp
            JOIN resources AS r ON (r.id = rp.resource_id)
            WHERE property_id = ?
            AND state = 1");

       $stmt->execute([\Config::get()->OPENCAST_RESOURCE_PROPERTY_ID]);
       $resources =  $stmt->fetchAll(PDO::FETCH_ASSOC);
       return $resources;
    }
}
