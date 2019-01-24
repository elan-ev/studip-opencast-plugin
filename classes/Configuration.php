<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (13:27)
 */

define('OC_GLOBAL_CONFIG_ID', -1);

class Configuration implements ArrayAccess
{
    private static $instances = [];

    /**
     * @param int $config_id
     *
     * @return Configuration
     */
    public static function instance($config_id = OC_GLOBAL_CONFIG_ID)
    {
        $name = 'c_(' . $config_id . ')';
        if (!static::$instances[$name]) {
            static::$instances[$name] = new Configuration($config_id);
        }

        return static::$instances[$name];
    }

    /**
     * Alias for instance()
     *
     * @param int $config_id
     *
     * @return Configuration
     */
    public static function i($config_id = OC_GLOBAL_CONFIG_ID)
    {
        return static::instance($config_id);
    }

    private $values;
    private $descriptions;
    private $database_ids;
    private $config_id;

    private function __construct($config_id)
    {
        $this->config_id = $config_id;
        $this->values = [];
        $this->descriptions = [];
        $this->database_ids = [];
        $this->load();
    }

    public function has($name)
    {
        return isset($this->values[$name]);
    }

    public function set($name, $new_value, $description = '', $database_id = '')
    {
        $old_value = $this->values[$name];
        $this->values[$name] = $new_value;
        if (!empty($description) && $description != '') {
            $this->descriptions[$name] = $description;
        }
        if (!empty($database_id) && $database_id != '') {
            $this->database_ids[$name] = $database_id;
        }
        if ($this->has_id($name)) {
            $stmt = DBManager::get()->prepare("UPDATE `oc_config_precise` SET `name`=?,`value`=?,`description`=? WHERE id=?");
            $result = $stmt->execute([$name, $new_value, $this->descriptions[$name], $this->database_ids[$name]]);
        } else {
            $stmt = DBManager::get()->prepare("INSERT INTO `oc_config_precise` (`name`,`value`,`description`,`for_config`)VALUES(?,?,?,?)");
            $result = $stmt->execute([$name, $new_value, $this->descriptions[$name], $this->config_id]);
            $new_id = $stmt->insert_id;
            $this->database_ids[$name] = $new_id;
        }

        $change_type = $this->determine_change_type($old_value, $new_value);
        $event = "opencast.configuration.$change_type.$name";

        try {
            NotificationCenter::postNotification($event, $this, [$old_value, $new_value]);
        } catch (NotificationVetoException $e) {
            error_log("Vetoed Notification: $event");
        }

        return $result;
    }

    private function has_id($name)
    {
        return $this->database_ids[$name];
    }

    public function remove($name)
    {
        $result = 'NO SQL NEEDED';
        if ($this->has_id($name)) {
            $stmt = DBManager::get()->prepare("DELETE FROM `oc_config_precise` WHERE id = ?");
            $result = $stmt->execute([$this->database_ids[$name]]);
            if ($result) {
                unset($this->database_ids[$name]);
            }
        }
        unset($this->values[$name]);
        unset($this->descriptions[$name]);

        return $result;
    }

    public function get($name, $default = 'NO VALUE')
    {
        if ($this->has($name)) {
            return $this->values[$name];
        }
        if ($this->config_id != OC_GLOBAL_CONFIG_ID) {
            return Configuration::instance(OC_GLOBAL_CONFIG_ID)->get($name, $default);
        }

        return $default;
    }

    public function get_description_for($name)
    {
        if (isset($this->descriptions[$name])) {
            return $this->descriptions[$name];
        }

        return '';
    }

    public function load()
    {
        $stmt = DBManager::get()->prepare("SELECT `id`,`name`,`description`,`value` FROM `oc_config_precise` WHERE for_config = ?");
        $result = $stmt->execute([$this->config_id]);
        if ($result) {
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $entry) {
                $this->set($entry['name'], $entry['value'], $entry['description'], $entry['id']);
            }
        }
    }

    public function define_constants()
    {
        foreach ($this->values as $name => $value) {
            define('OC_' . strtoupper($name), $value);
        }
    }

    public function get_entries_for_display()
    {
        $entries = [];
        foreach ($this->values as $name => $value) {
            $entries[$name] = ['value' => $value, 'type' => $this->determine_value_type($value), 'description' => ($this->descriptions[$name] ? $this->descriptions[$name] : 'Keine Beschreibung...')];
        }

        return $entries;
    }

    public function get_names()
    {
        $names_in_current_config = array_keys($this->values);
        if ($this->config_id == OC_GLOBAL_CONFIG_ID) {
            return $names_in_current_config;
        }

        return array_unique(array_merge(Configuration::instance(OC_GLOBAL_CONFIG_ID)->get_names(), $names_in_current_config));
    }

    private function determine_value_type($value)
    {
        if (is_numeric($value)) {
            return 'number';
        }

        return 'text';
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

    public static function registered_base_config_ids()
    {
        $stmt = DBManager::get()->prepare('SELECT `config_id` FROM `oc_config`');
        if ($stmt->execute()) {
            $to_return = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $entry) {
                $to_return[] = $entry['config_id'];
            }

            return $to_return;
        }

        return [];
    }

    public static function overall_used_config_ids()
    {
        $tables_to_look_at = [
            //Tabellenname            //Spaltenname
            'oc_config_precise'    => 'for_config',
            'oc_endpoints'         => 'config_id',
            'oc_resources'         => 'config_id',
            'oc_seminar_series'    => 'config_id',
            'oc_seminar_workflows' => 'config_id'
        ];
        $found_ids = [];
        foreach ($tables_to_look_at as $table => $column) {
            $stmt = DBManager::get()->prepare('SELECT ' . $column . ' FROM ' . $table);
            if ($stmt->execute()) {
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $entry) {
                    $id = $entry[$column];
                    if ($id == OC_GLOBAL_CONFIG_ID) {
                        continue;
                    }
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

    private function determine_change_type($old_value, $new_value)
    {
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