<?php

namespace Firebird\Query\Grammars;

use Illuminate\Database\Query\Builder;

class Firebird30Grammar extends Firebird25Grammar
{

    /**
     * The components that make up a select clause.
     *
     * @var array
     */
    protected $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'offset',
        'limit',
        'unions',
        'lock',
    ];

    /**
     * Compile the "limit" portions of the query.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param int $limit
     * @return string
     */
    protected function compileLimit(Builder $query, $limit)
    {
        if ($limit)
            return 'fetch first ' . (int)$limit . ' rows only';
        else
            return null;
    }

    /**
     * Compile the "offset" portions of the query.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param int $offset
     * @return string
     */
    protected function compileOffset(Builder $query, $offset)
    {
        if ($offset) {
            return 'offset ' . (int)$offset . ' rows';
        } else {
            return null;
        }
    }

}
