<?php

namespace Opencast\Models;

use Psr\Http\Message\ServerRequestInterface as Request;

class Filter
{
    private
        $offset,
        $limit,
        $filters = [];

    private static $ALLOWED_FILTERS = [
        'text', 'playlist', 'tag'
    ];

    public function __construct(Request $request)
    {
        $params = $request->getQueryParams();

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

        if (!empty($params['filters'])) {
            $filters = \json_decode($params['filters'], true);
            foreach($filters as $filter) {
                if (in_array($filter['type'], self::$ALLOWED_FILTERS)) {
                    $this->filters[] = [
                        'type'  => $filter['type'],
                        'value' => preg_replace('/[^0-9a-zA-Z\w\ ]/', '', $filter['value'])
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

    public function getFilters()
    {
        return $this->filters;
    }
}