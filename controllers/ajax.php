<?php

use Opencast\Models\OCConfig;
use Opencast\Models\OCSeminarEpisodes;
use Opencast\Models\OCSeminarSeries;

use Opencast\LTI\OpencastLTI;
use Opencast\LTI\LtiLink;

class AjaxController extends OpencastController
{
    public function index_action()
    {
        $this->render_text($this->_('Ups..'));
    }

    public function logupload_action()
    {
        StudipLog::log('OC_UPLOAD_MEDIA', Request::get('workflow_id'), Request::get('course_id'), Request::get('episode_id'));
        $this->render_text('ok');
    }

    public function getseries_action()
    {
        $user_id = $GLOBALS['user']->id;

        $cache = StudipCacheFactory::getCache();
        $cache_key = 'sop/series/' . $user_id . '/' . Context::getId();
        $results = unserialize($cache->read($cache_key));

        if ($results === false) {
            $series = OCSeriesModel::getSeriesForUser($user_id, Context::getId());
            $results = [];

            foreach ($series as $series_id => $seminar_id) {
                $course   = Course::find($seminar_id);

                if (!$course || !$oc_items = OCSeriesModel::getSeriesFromOpencast($series_id, $seminar_id)) {
                    continue;
                }

                $item = [
                    'seminar_id' => $seminar_id,
                    'series_id'  => $series_id
                ];

                $item['name']    = $course->getFullname('number-name-semester');
                $item['endtime'] = $course->getEnd_Time();
                $item            = array_merge($item, $oc_items);

                $results[] = $item;
            }

            uasort($results, function ($a, $b) {
                return $a['endtime'] == $b['endtime'] ? 0
                    : ($a['endtime'] < $b['endtime'] ? -1 : 1);
            });

            $cache->write($cache_key, serialize($results), 3600);
        }

        $this->render_json(array_values($results));
    }

    public function course_episodes_action($course_id)
    {
        if (OCPerm::editAllowed($course_id)) {
            $result = [];

            foreach (OCSeminarSeries::findBySeminar_id($course_id) as $series) {
                $api_client    = ApiEventsClient::getInstance($series['config_id']);
                $search_client = SearchClient::getInstance($series['config_id']);

                $episodes = $api_client->getBySeries($series['series_id'], $course_id);

                foreach ($episodes as $episode) {
                    $studip_episode = OCSeminarEpisodes::findOneBySQL(
                        'series_id = ? AND episode_id = ? AND seminar_id = ?',
                        [$series['series_id'], $episode['id'], $course_id]
                    );

                    $result[] = [
                        'series_id'  => $series['series_id'],
                        'id'         => $episode['id'],
                        'name'       => $episode['title'],
                        'date'       => $episode['start'],
                        'url'        => $search_client->getBaseURL() . "/paella/ui/watch.html?id=" . $episode['id'],
                        'visible'    => $studip_episode->visible
                    ];
                }
            }

            $this->render_json(array_values($result));
            return;
        }

        $this->render_json([]);
    }

    public function getepisodes_action($course_id, $series_id, $simple = false)
    {
        $api_client    = ApiEventsClient::getInstance(OCConfig::getConfigIdForSeries($series_id));
        $search_client = SearchClient::getInstance(OCConfig::getConfigIdForSeries($series_id));

        $check = !empty(OCSeminarSeries::findBySql('seminar_id = ? AND series_id = ?', [$course_id, $series_id]));
        $result = $simple_result = [];

        if (
            !$check || !OCPerm::editAllowed($course_id)
            || !$GLOBALS['perm']->have_studip_perm('autor', $course_id)
        ) {
            // do not list episodes if user has no permissions in the connected course
            $this->render_json(array_values([]));
            return;
        }

        $episodes = $api_client->getBySeries($series_id, $course_id);

        if (!is_array($episodes)) {
            $episodes = [$episodes];
        }

        foreach ($episodes as $episode) {
            $studip_episode = OCSeminarEpisodes::findOneBySQL(
                'series_id = ? AND episode_id = ? AND seminar_id = ?',
                [$series_id, $episode['id'], $course_id]
            );

            if (
                $studip_episode && (
                    (Context::getId() == $course_id && $studip_episode->visible != 'invisible'))
                || $studip_episode->visible == 'free'
            ) {
                $result[] = $episode;
            }

            $simple_result[] = [
                'id'         => $episode['id'],
                'name'       => $episode['title'],
                'date'       => $episode['start'],
                'url'        => $search_client->getBaseURL() . "/paella/ui/watch.html?id=" . $episode['id'],
                'visible'    => $studip_episode->visible
            ];
        }

        if ($simple) {
            $result = $simple_result;
        }

        $this->render_json(array_values($result));
    }

    public function getltidata_action($series_id)
    {
        $course_id = Context::getId();

        if (!$GLOBALS['perm']->have_studip_perm('user', $course_id)) {
            throw new AccessDeniedException();
        }

        $config_id = OCConfig::getConfigIdForSeries($series_id);
        $config    = OCConfig::getBaseServerConf($config_id);

        $current_user_id = $GLOBALS['auth']->auth['uid'];
        $lti_link        = new LtiLink(
            OpencastLTI::getSearchUrlForConfig($config_id),
            $config['settings']['lti_consumerkey'],
            $config['settings']['lti_consumersecret']
        );

        if (OCPerm::editAllowed($course_id, $current_user_id)) {
            $role = 'Instructor';
        } else {
            $role = 'Learner';
        }

        $lti_link->setUser($current_user_id, $role, true);
        $lti_link->setCourse($course_id);

        $launch_data = $lti_link->getBasicLaunchData();
        $signature   = $lti_link->getLaunchSignature($launch_data);

        $launch_data['oauth_signature'] = $signature;

        $lti_data = json_encode($launch_data);
        $lti_url  = $lti_link->getLaunchURL();

        $result = [
            'lti_url' => $lti_url,
            'lti_data' => $lti_data
        ];
        $this->render_json($result);
    }

    public function search_series_action()
    {
        $configs     = OCConfig::getBaseServerConf();
        $user_series = OCSeminarSeries::getSeriesByUserMemberStatus($GLOBALS['user']->id, 'dozent');
        $all_series  = [];
        $is_admin    = $GLOBALS['perm']->have_studip_perm('admin', Context::getId());

        // search on every oc-server for the series with this name
        foreach ($configs as $id => $config) {
            $apiseries_client = ApiSeriesClient::getInstance($id);
            $series = $apiseries_client->search(Request::option('search_term'));

            if (!empty($series)) {
                if (!$is_admin) {
                    $filtered_series = [];
                    foreach ($series as $series_id => $title) {
                        if (in_array($series_id, $user_series)) {
                            $filtered_series[$series_id] = $title;
                        }
                    }

                    $all_series[$id] = $filtered_series;
                } else {
                    $all_series[$id] = $series;
                }
            }
        }

        $this->render_json($all_series);
    }
}
