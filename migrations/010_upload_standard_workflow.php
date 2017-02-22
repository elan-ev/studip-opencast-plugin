<?php

/**
 * Created by PhpStorm.
 * User: jayjay
 * Date: 02.02.17
 * Time: 15:06
 */
class UploadStandardWorkflow extends Migration
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

        $config->create('OPENCAST_WORKFLOW_ID', array("type" => "string", "value" => "ng-schedule-and-upload", "description" => "Standart OpenCast Workflow bei manuellem Upload", "section" => "opencast"));
    }
}