<?php

namespace Opencast\Models;

use Opencast\Models\Config;
use Opencast\Models\SeminarSeries;
use Opencast\Models\LTI\LtiHelper;

class OCConfig
{

    /**
     * return LTI credentials for default OC server. OC Block in courseware plugin cannot handle multiple OC instances
     *
     * @param string $course_id
     * @return void
     */
    public static function getConfigForCourse($context_id)
    {
        global $perm;

        $series = SeminarSeries::findOneBySeminar_id($context_id);

        if ($series) {

            $config = Config::find($series->config_id);
            return $config->toArray()['settings'];
        }

        return null;
    }
}