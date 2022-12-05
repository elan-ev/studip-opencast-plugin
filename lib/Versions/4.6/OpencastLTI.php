<?php

/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (12:33)
 */


namespace Opencast\LTI;

use Opencast\Models\Endpoints;
use Opencast\Models\SeminarSeries;

class OpencastLTI
{
    public static function getSearchUrl($context_id)
    {
        if (!$context_id) {
            return '';
        }

        $series = SeminarSeries::findOneBySeminar_id($context_id);

        if ($series) {
            $ep = Endpoints::findOneBySQL("service_type ='search' AND config_id = ?", [$series->config_id]);

            if ($ep) {
                $url = parse_url($ep['service_url']);

                return $url['scheme'] . '://' . $url['host']
                    . ($url['port'] ? ':' . $url['port'] : '') . '/lti';
            }
        }

        return '';
    }
}
