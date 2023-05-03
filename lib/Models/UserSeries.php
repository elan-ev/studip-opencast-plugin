<?php

namespace Opencast\Models;

use Opencast\Models\REST\SeriesClient;

class UserSeries extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_user_series';
        parent::configure($config);
    }

    private static function checkSeries($series_id)
    {
        $config_id = \Config::get()->OPENCAST_DEFAULT_SERVER;
        $series_client = new SeriesClient($config_id);

        return $series_client->getSeries($series_id) ? true : false;
    }

    public static function getSeries($user_id)
    {
        $return = [];

        foreach (self::findByUser_id($user_id) as $series) {
            if (self::checkSeries($series['series_id'])) {
                $return[] = $series;
            }
        }

        return $return;
    }

    public static function createSeries($user_id) {
        $config_id = \Config::get()->OPENCAST_DEFAULT_SERVER;
        $series_client = new SeriesClient($config_id);
        $series_id = $series_client->createSeriesForUser($user_id);

        if ($series_id) {
            $series = self::create([
                'config_id'  => $config_id,
                'user_id'    => $user_id,
                'series_id'  => $series_id,
                'visibility' => 'visible'
            ]);
            $series->store();

            return $series;
        }
        else {
            // Throw error
        }
    }
}
