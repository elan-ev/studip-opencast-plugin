<?php

/**
 * Created by PhpStorm.
 * User: jayjay
 * Date: 02.02.17
 * Time: 15:06
 */
class PluginConfiguration extends Migration
{

    /**
     * set migration description
     *
     * @return string description
     */
    function description() {
        return "Add config options to database";
    }

    function up() {
        $config = Config::get();

        $config->create('OPENCAST_STREAM_SECURITY', array("type" => "boolean", "value" => false, "description" => "Nutzung des Stream Security Features von OpenCast", "section" => "opencast"));
        $config->create('OPENCAST_EXTENDED_PLAYER_BUTTON', array("type" => "boolean", "value" => true, "description" => "Zeige den \"Extended Player\"-Button", "section" => "opencast"));
    }
}