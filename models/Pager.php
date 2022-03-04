<?php

namespace Opencast\Models;

class Pager
{
    static private
        $page   = -1,
        $length = 0;

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
        $sort_str = '';
        $cid      = \Context::getId();

        if ($_SESSION['opencast']['sort_order']) {
            $sort_str = $_SESSION['opencast']['sort_order'];
        }
        else if (\CourseConfig::get($cid)->COURSE_SORT_ORDER) {
            $sort_str = \CourseConfig::get($cid)->COURSE_SORT_ORDER;
        }
        else {
            $sort_str = 'date:DESC';
        }

        if (strpos($sort_str, ':') === false) {
            return 'date:DESC';
        }

        return $sort_str;
    }
}
