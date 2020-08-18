<?php

namespace Firebird\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;

class Firebird15Grammar extends Grammar
{

    /**
     * The components that make up a select clause.
     *
     * @var array
     */
    protected $selectComponents = array(
        'aggregate',
        'limit',
        'offset',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders'
    );

    /**
     * Compile the "select *" portion of the query.
     * As Firebird adds the "limit" and "offset" after the "select", this must not work this way.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $columns
     * @return string
     */
    protected function compileColumns(Builder $query, $columns)
    {
        // If the query is actually performing an aggregating select, we will let that
        // compiler handle the building of the select clauses, as it will need some
        // more syntax that is best handled by that function to keep things neat.
        if ( ! is_null($query->aggregate)) return;
        $select = '';
        if (count($columns) > 0 && $query->limit == null && $query->aggregate == null)
        {
            $select = $query->distinct ? 'select distinct ' : 'select ';
        }

        return $select.$this->columnize($columns);
    }

    /**
     * Compile a select query into SQL.
     *
     * @param  Illuminate\Database\Query\Builder
     *
     * @return string
     */
    public function compileSelect(Builder $query)
    {
        if (is_null($query->columns)) $query->columns = array('*');

        return trim($this->concatenate($this->compileComponents($query)));
    }

    /**
     * Compile an aggregated select clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $aggregate
     * @return string
     */
    protected function compileAggregate(Builder $query, $aggregate)
    {
        $column = $this->columnize($aggregate['columns']);

        // If the query has a "distinct" constraint and we're not asking for all columns
        // we need to prepend "distinct" onto the column name so that the query takes
        // it into account when it performs the aggregating operations on the data.
        if ($query->distinct && $column !== '*')
        {
            $column = 'distinct '.$column;
        }

        return 'select '.$aggregate['function'].'('.$column.') as aggregate';
    }

    /**
     * Compile first instead of limit
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  int  $limit
     * @return string
     */
    protected function compileLimit(Builder $query, $limit)
    {
        return 'select first '.(int) $limit;
    }

    /**
     * Compile skip instead of offset
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  int  $limit
     * @return string
     */
    protected function compileOffset(Builder $query, $limit)
    {
        return 'skip '.(int) $limit;
    }

}
