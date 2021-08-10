<?php

namespace Opencast\Models;

class OCSeminarEpisodes extends \SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_seminar_episodes';
        parent::configure($config);
    }
}
