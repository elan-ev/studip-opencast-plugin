<?php

namespace Opencast\Models;

use Psr\Http\Message\ServerRequestInterface as Request;

class Filter
{
    private
        $offset,
        $limit,
        $order,
        $filters = [],
        $course_id,
        $playlist,
        $trashed;

    private static $ALLOWED_ORDERS = [
        'created_desc', 'created_asc', 'title_desc', 'title_asc', 'presenters_desc', 'presenters_asc', 'order_desc', 'order_asc', 'mkdate_desc', 'mkdate_asc'
    ];

    private static $ALLOWED_FILTERS = [
        'text', 'playlist', 'tag', 'course', 'lecturer',
    ];

    public function __construct($params)
    {
        if (!empty($params['offset']) && $params['offset'] > 0) {
            $this->offset = $params['offset'];
        } else {
            $this->offset = 0;
        }

        if (!empty($params['limit']) && $params['limit'] <= 100) {
            $this->limit = $params['limit'];
        } else {
            $this->limit = 20;
        }

        if (!empty($params['order']) && in_array($params['order'], self::$ALLOWED_ORDERS)) {
            $this->order = $params['order'];
        } else {
            $this->order = self::$ALLOWED_ORDERS[0];
        }

        if (isset($params['cid']) && !empty($params['cid'])) {
            $this->course_id = $params['cid'];
        }

        if (isset($params['token']) && !empty($params['token'])) {
            $this->playlist = $params['token'];
        }

        if (isset($params['trashed']) && !empty($params['trashed'])) {
            $this->trashed = $params['trashed'];
        } else {
            $this->trashed = 'false';
        }

        if (!empty($params['filters'])) {
            $filters = \json_decode($params['filters'], true);
            foreach($filters as $filter) {
                if (in_array($filter['type'], self::$ALLOWED_FILTERS)) {
                    $compare = $filter['compare'] ?? '!=';
                    $this->filters[] = [
                        'type'    => $filter['type'],
                        'value'   => preg_replace('/[^0-9a-zA-Z\w\ ]/', '', $filter['value']),
                        'compare'  => $compare == '=' ? '=' : '!='
                    ];
                }
            }
        }
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function getCourseId()
    {
        return $this->course_id;
    }

    public function getPlaylist()
    {
        return $this->playlist;
    }

    public function getTrashed()
    {
        return $this->trashed;
    }
}
