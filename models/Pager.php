<?php

namespace Opencast\Models;

use \ApiEventsClient;
class Pager
{
    static private
        $page   = -1,
        $length = 0,
        $search = null;

    public static function setPage($page)
    {
        self::$page = $page;
    }

    public static function getPage()
    {
        return self::$page;
    }

    public static function getOffset()
    {
        return (max(self::getPage() - 1, 0)) * self::getLimit();
    }

    public static function getLength()
    {
        return self::$length;
    }

    public static function setLength($length)
    {
        self::$length = $length;
    }

    public static function setSearch($search)
    {
        // only allow text and numbers in search term
        self::$search = preg_replace('/[^0-9a-zA-Z]/', '', $search);
    }

    public static function getSearch()
    {
        return self::$search;
    }

    /**
     * Get the number of items for each page, for now this value is static.
     * It will be 10 if paging ist active and 9999 if its not
     *
     * @return int
     */
    public static function getLimit()
    {
        if (self::getPage() == -1) {
            return 9999;
        }

        return 5;
    }

    public static function getSortOrder()
    {
        $default_sort_order = 'modified asc';

        $api_events = ApiEventsClient::getInstance();

        if (version_compare($api_events->getOcVersion(), '16', '<'))
        {
            $default_sort_order = 'DATE_CREATED_DESC';
        }

        $sort_str = '';
        $cid      = \Context::getId();

        if (!empty($_SESSION['opencast']['sort_order'])) {
            $sort_str = $_SESSION['opencast']['sort_order'];
        }
        else if (!empty(\CourseConfig::get($cid)->COURSE_SORT_ORDER)) {
            $sort_str = \CourseConfig::get($cid)->COURSE_SORT_ORDER;
        }
        else {
            $sort_str = $default_sort_order ;
        }

        $sort_options = self::getSortOptions();

        // check, if selected sort options is available
        if (in_array($sort_str, array_keys($sort_options)) === false) {
            return $default_sort_order ;
        }

        return $sort_str;
    }

    public static function getSortOptions()
    {
        $api_events = ApiEventsClient::getInstance();

        if (version_compare($api_events->getOcVersion(), '16', '<'))
        {
            return [
                'DATE_CREATED_DESC' => self::_('Datum hochgeladen: Neueste zuerst'),
                'DATE_CREATED'      => self::_('Datum hochgeladen: Älteste zuerst'),
                'TITLE'             => self::_('Titel: Alphabetisch'),
                'TITLE_DESC'        => self::_('Titel: Umgekehrt Alphabetisch')
            ];
        } else {
            return [
                'modified desc' => self::_('Datum hochgeladen: Neueste zuerst'),
                'modified asc'  => self::_('Datum hochgeladen: Älteste zuerst'),
                'title asc'    => self::_('Titel: Alphabetisch'),
                'title desc'   => self::_('Titel: Umgekehrt Alphabetisch')
            ];
        }
    }

    /**
     * Plugin localization for a single string.
     * This method supports sprintf()-like execution if you pass additional
     * parameters.
     *
     * @param String $string String to translate
     * @return translated string
     */
    public static function _($string)
    {
        $result = \OpenCast::GETTEXT_DOMAIN === null
            ? $string
            : dcgettext(\OpenCast::GETTEXT_DOMAIN, $string, LC_MESSAGES);
        if ($result === $string) {
            $result = _($string);
        }

        return $result;
    }
}
