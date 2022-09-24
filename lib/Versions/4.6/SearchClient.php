<?php

use Opencast\Models\Endpoints;
use Opencast\Models\SeminarSeries;

class SearchClient
{
    private
        $context_id;

    public static function create($context_id)
    {
        $sc = new self($context_id);
        return $sc;
    }

    public function __construct($context_id)
    {
        $this->context_id = $context_id;
    }

    public function getBaseURL()
    {
        if (!$this->context_id) {
            return '';
        }

        $series = SeminarSeries::findOneBySeminar_id($this->context_id);

        if ($series) {
            $ep = Endpoints::findOneBySQL("service_type ='search' AND config_id = ?", [$series->config_id]);

            if ($ep) {
                $url = parse_url($ep['service_url']);

                return $url['scheme'] . '://' . $url['host']
                    . ($url['port'] ? ':' . $url['port'] : '');
            }
        }

        return '';
    }
}