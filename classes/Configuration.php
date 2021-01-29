<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (13:27)
 */

namespace Opencast;

use Opencast\Models\OCConfig;

class Configuration implements \ArrayAccess
{
    private static $instances = [];
    private
        $values,
        $descriptions,
        $database_ids,
        $config_id;

    /**
     * @param int $config_id
     *
     * @return Configuration
     */
    public static function instance($config_id)
    {
        $name = 'c_(' . $config_id . ')';
        if (!static::$instances[$name]) {
            static::$instances[$name] = new Configuration($config_id);
        }

        return static::$instances[$name];
    }


    private function __construct($config_id)
    {
        $this->config_id = $config_id;
        $this->values = [];

        // set default config as standard
        foreach (Constants::$DEFAULT_CONFIG as $option) {
            $this->values[$option['name']] = $option['value'];
        }

        $this->load();
    }

    public function has($name)
    {
        return isset($this->values[$name]);
    }

    public function set($name, $new_value)
    {
        $option = null;

        foreach (Constants::$DEFAULT_CONFIG as $cfgoption) {
            if ($cfgoption['name'] == $name) {
                $option = $cfgoption;
            }
        }

        if (!$option) {
            throw new \InvalidArgumentException('could not find option ' . $name);
        }

        if ($option['type'] == 'boolean') {
            $new_value = $new_value ? true : false;
        }

        $this->values[$name] = $new_value;
    }

    public function remove($name)
    {
        unset($this->values[$name]);
    }

    public function get($name)
    {
        if ($this->has($name)) {
            return $this->values[$name];
        }

        return false;
    }

    public function load()
    {
        $config = OCConfig::find($this->config_id);
        if (!empty($config)) {
            $this->values = json_decode($config->settings->__toString(), true);
        }

        $this->values['livestream'] = false;
    }

    public function store()
    {
        $config = OCConfig::find($this->config_id);

        $config->settings = $this->values;
        $config->store();

        /*
        $stmt = \DBManager::get()->prepare("UPDATE oc_config
            SET settings = ?");

        return $stmt->execute([json_encode($this->values)]);
        */
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        return $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        return $this->remove($offset);
    }

    public function toArray()
    {
        return $this->values;
    }

    public static function overall_used_config_ids()
    {
        $tables_to_look_at = [
            //Tabellenname            //Spaltenname
            'oc_endpoints'         => 'config_id',
            'oc_resources'         => 'config_id',
            'oc_seminar_series'    => 'config_id',
            'oc_seminar_workflows' => 'config_id'
        ];
        $found_ids = [];

        foreach ($tables_to_look_at as $table => $column) {
            $stmt = \DBManager::get()->prepare('SELECT ' . $column . ' FROM ' . $table);
            if ($stmt->execute()) {
                foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $entry) {
                    $id = $entry[$column];

                    if (!$found_ids[$id]) {
                        $found_ids[$id] = [];
                    }
                    if (!in_array($id, $found_ids)) {
                        if (!in_array($table, $found_ids[$id])) {
                            $found_ids[$id][] = $table;
                        }
                    }
                }
            }
        }

        return $found_ids;
    }

    public static function getGlobalConfig()
    {
        $config = [];

        foreach (Constants::$GLOBAL_CONFIG_OPTIONS as $option) {
            $data = \Config::get()->getMetadata($option);
            $config[] = [
                'name'        => $option,
                'description' => $data['description'],
                'value'       => \Config::get()->$option,
                'type'        => $data['type']
            ];
        }

        return $config;
    }

    public static function setGlobalConfig($option, $value)
    {
        \Config::get()->store($option, $value);
    }
}
