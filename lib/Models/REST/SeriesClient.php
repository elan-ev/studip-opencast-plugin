<?php

namespace Opencast\Models\REST;

use Opencast\Models\Config;

class SeriesClient extends RestClient
{
    public static $me;

    public function __construct($config_id = 1)
    {
        $this->serviceName = 'Series';

        if ($config = Config::getConfigForService('series', $config_id)) {
            parent::__construct($config);
        } else {
            throw new \Exception ($this->serviceName . ': '
                . _('Die Opencast-Konfiguration wurde nicht korrekt angegeben'));
        }
    }

    /**
     * Retrieves seriesmetadata for a given series identifier from conntected Opencast
     *
     * @param string series_id Identifier for a Series
     *
     * @return array|boolean response of a series, or false if unable to get
     */
    public function getSeries($series_id)
    {
        $response = $this->opencastApi->series->get($series_id);

        if ($response['code'] == 200) {
            return $response['body'];
        }
        return false;
    }


    /**
     * createSeriesForSeminar - creates an new Series for a given course in OC Matterhorn
     * @param string $course_id - course identifier
     * @return bool success or not
     */
    public function createSeriesForSeminar($course_id)
    {
        $acl = [
            [
                'allow'  => true,
                'role'   => 'ROLE_ADMIN',
                'action' => 'read'
            ],

            [
                'allow'  => true,
                'role'   => 'ROLE_ADMIN',
                'action' => 'write'
            ],

            [
                'allow'  => true,
                'role'   => $course_id . '_Instructor',
                'action' => 'read'
            ],

            [
                'allow'  => true,
                'role'   => $course_id . '_Instructor',
                'action' => 'write'
            ]
        ];

        $metadata = self::getSeriesDC($course_id);
        $response = $this->opencastApi->seriesApi->create($metadata, $acl);

        if ((int)$response['code'] === 201) {
            return $response['body']->identifier;
        }

        return false;
    }

    /**
     * createSeriesDC - creates an xml representation for a new OC-Series
     *
     * @param string $course_id
     * @return string xml - the xml representation of the string
     */
    private static function getSeriesDC($course_id)
    {
        $course       = new \Seminar($course_id);
        $name         = $course->getName() . ' - ' . $course->getStartSemesterName();
        $license      = "&copy; " . gmdate('Y') . " " . $GLOBALS['UNI_NAME_CLEAN'];
        $inst         = \Institute::find($course->institut_id);

        $publisher   = (string)$inst->name;
        $instructors = $course->getMembers('dozent');
        $instructor  = array_shift($instructors);
        $contributor = $GLOBALS['UNI_NAME_CLEAN'] ?: 'unbekannt';
        $creator     = $instructor['fullname'];
        $language    = 'de';

        $data = [
            'title'       => $name,
            'creator'     => [$creator],
            'contributor' => [$contributor],
            'language'    => $language,
            'license'     => $license,
            'publisher'   => [$publisher]
        ];

        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = [
                'id'    => $key,
                'value' => $value
            ];
        }

        return [[
            "label"  => "Opencast Series DublinCore",
            "flavor" => "dublincore/series",
            'fields' => $fields
        ]];
    }

    /**
     * createSeriesForUser - creates an new Series for a given user in OC Matterhorn
     * @param string $user_id - user identifier
     * @return bool success or not
     */
    public function createSeriesForUser($user_id)
    {
        $acl = [
            [
                'allow'  => true,
                'role'   => 'ROLE_ADMIN',
                'action' => 'read'
            ],

            [
                'allow'  => true,
                'role'   => 'ROLE_ADMIN',
                'action' => 'write'
            ],

            [
                'allow'  => true,
                'role'   => 'STUDIP_' . $user_id,
                'action' => 'read'
            ],

            [
                'allow'  => true,
                'role'   => 'STUDIP_' . $user_id,
                'action' => 'write'
            ]
        ];

        $metadata = self::getSeriesUserDC($user_id);

        $response = $this->opencastApi->seriesApi->create($metadata, $acl);

        if ((int)$response['code'] === 201) {
            return $response['body']->identifier;
        }

        return false;
    }

    /**
     * createSeriesDC - creates an xml representation for a new OC-Series for an user
     *
     * @param string $course_id
     * @return string xml - the xml representation of the string
     */
    private static function getSeriesUserDC($user_id)
    {
        $license      = "&copy; " . gmdate('Y') . " " . $GLOBALS['UNI_NAME_CLEAN'];
        $contributor = $GLOBALS['UNI_NAME_CLEAN'] ?: 'unbekannt';
        $language    = 'de';

        $data = [
            'title'       => 'User series ' . \get_username($user_id) . ' ' . $user_id,
            'creator'     => [\get_username($user_id)],
            'contributor' => [$contributor],
            'language'    => $language,
            'license'     => $license,
            'publisher'   => [\get_username($user_id)] // TODO Whats the correct publisher?
        ];

        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = [
                'id'    => $key,
                'value' => $value
            ];
        }

        return [[
            "label"  => "Opencast Series DublinCore",
            "flavor" => "dublincore/series",
            'fields' => $fields
        ]];
    }
}
