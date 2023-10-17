<?php

use Opencast\Models\OCSeminarEpisodes;
use Opencast\Models\OCSeminarSeries;
use Opencast\Models\OCEndpoints;

class RedirectController extends OpencastController
{
    public function perform_action($action, $episode_id)
    {
        $video = OCSeminarEpisodes::findOneByEpisode_id($episode_id);

        if ($action !== 'video' || empty($video)) {
            // return from this route and show template containing the default oc preview image
            $this->set_layout(null);
            return;
        }

        $series = OCSeminarSeries::findOneBySeries_id($video->series_id);
        $endpoint = OCEndpoints::findOneBySQL("config_id = ? AND service_type = 'search'", [$series->config_id]);

        $url = parse_url($endpoint['service_url']);

        $play_url = $url['scheme'] . '://'. $url['host']
            . ($url['port'] ? ':' . $url['port'] : '') . "/play/{$episode_id}";

        $this->redirect($play_url);
    }
}