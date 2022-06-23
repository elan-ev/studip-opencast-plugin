<?php

namespace Opencast\Models\REST;

use Opencast\Models\Config;

class SearchClient extends RestClient
{
    public static $me;

    public function __construct($config_id = 1)
    {
        $this->serviceName = 'Search';

        if ($config = Config::getConfigForService('search', $config_id)) {
            parent::__construct($config);
        } else {
            throw new \Exception ($this->serviceName . ': '
                . _('Die Opencast-Konfiguration wurde nicht korrekt angegeben'));
        }
    }

    /**
     *  Retrieves episodes matching the query parameters from connected Opencast
     *
     * @param array $params list of query params to look for
     * @param string $format the output format
     *
     * @return array|string response of episodes
     */
    public function getEpisodes($params = [], $format = '')
    {
        $response = $this->opencastApi->search->getEpisodes($params, $format);
        if ($response['code'] == 200) {
            return $response['body'];
        }

        return false;
    }

    /**
     *  Retrieves series matching the query parameters from connected Opencast
     *
     * @param array $params list of query params to look for
     * @param string $format the output format
     *
     * @return array|string response of series
     */
    public function getSeries($params = [], $format = '')
    {
        $response = $this->opencastApi->search->getSeries($params, $format);
        if ($response['code'] == 200) {
            return $response['body'];
        }

        return false;
    }

    /**
     * Performs a Lucene search matching the query parameters
     *
     * @param array $params list of query params to look for
     * @param string $format the output format
     *
     * @return array|string lucene search results
     */
    public function getLucene($params = [], $format = '')
    {
        $response = $this->opencastApi->search->getLucene($params, $format);
        if ($response['code'] == 200) {
            return $response['body'];
        }

        return false;
    }

    /**
     * Retrieves the base URL
     * 
     * @return string base url
     */
    public function getBaseURL()
    {
        $base = $this->base_url;
        $url = preg_replace('/\/search/', '', $base);

        return $url;
    }
}
