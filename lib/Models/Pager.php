<?php

namespace Opencast\Models;

class Pager
{
    static private
        $page   = -1,
        $limit  = 5,
        $length = 0,
        $search = null,
        $sort_order = 'DATE_CREATED_DESC';

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
        return (max(self::getPage(), 0)) * self::getLimit();
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

    public static function setLimit($limit)
    {
        self::$limit = $limit;
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

        return self::$limit;
    }

    public static function setSordOrder($sort_order)
    {
        $sort_options = self::getSortOptions();

        // check, if selected sort options is available
        if (in_array($sort_order, array_keys($sort_options))) {
            self::$sort_order = $sort_order;
        }
    }

    public static function getSortOrder()
    {
        $sort_options = self::getSortOptions();

        // check, if selected sort options is available
        if (in_array(self::$sort_order, array_keys($sort_options)) === false) {
            return 'DATE_CREATED_DESC';
        }

        return self::$sort_order;
    }

    /*
    public static function getSortOrder()
    {
        $sort_str = '';
        $cid      = \Context::getId();

        if ($_SESSION['opencast']['sort_order']) {
            $sort_str = $_SESSION['opencast']['sort_order'];
        }
        else if (\CourseConfig::get($cid)->COURSE_SORT_ORDER) {
            $sort_str = \CourseConfig::get($cid)->COURSE_SORT_ORDER;
        }
        else {
            $sort_str = 'DATE_CREATED_DESC';
        }

        $sort_options = self::getSortOptions();

        // check, if selected sort options is available
        if (in_array($sort_str, array_keys($sort_options)) === false) {
            return 'DATE_CREATED_DESC';
        }

        return $sort_str;
    }
    */

    public static function getSortOptions()
    {
        return [
            'DATE_CREATED_DESC' => self::_('Datum hochgeladen: Neueste zuerst'),
            'DATE_CREATED'      => self::_('Datum hochgeladen: Älteste zuerst'),
            'TITLE'             => self::_('Titel: Alphabetisch'),
            'TITLE_DESC'        => self::_('Titel: Umgekehrt Alphabetisch')
        ];
    }


    /**
     * Plugin localization for a single string.
     * This method supports sprintf()-like execution if you pass additional
     * parameters.
     *
     * @param String $string String to translate
     * @return translated string
     */
    public function _($string)
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
