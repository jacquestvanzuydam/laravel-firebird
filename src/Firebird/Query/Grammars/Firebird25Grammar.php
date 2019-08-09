<?php

namespace Firebird\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;

class Firebird25Grammar extends Grammar
{

    /**
     * All of the available clause operators.
     *
     * @var array
     */
    protected $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like', 'between', 'containing', 'starting with',
        'similar to', 'not similar to',
    ];

    /**
     * Compile an aggregated select clause.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $aggregate
     * @return string
     */
    protected function compileAggregate(Builder $query, $aggregate)
    {
        $column = $this->columnize($aggregate['columns']);

        // If the query has a "distinct" constraint and we're not asking for all columns
        // we need to prepend "distinct" onto the column name so that the query takes
        // it into account when it performs the aggregating operations on the data.
        if ($query->distinct && $column !== '*') {
            $column = 'distinct ' . $column;
        }

        return 'select ' . $aggregate['function'] . '(' . $column . ') as "aggregate"';
    }

    /**
     * Compile SQL statement for get context variable value
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string $namespace
     * @param string $name
     * @return string
     */
    public function compileGetContext(Builder $query, $namespace, $name)
    {
        return "SELECT RDB\$GET_CONTEXT('{$namespace}', '{$name}' AS VAL FROM RDB\$DATABASE";
    }

    /**
     * Compile SQL statement for execute function
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string $function
     * @param array $values
     * @return string
     */
    public function compileExecFunction(Builder $query, $function, array $values = null)
    {
        $function = $this->wrap($function);

        return "SELECT  {$function} (" . $this->parameterize($values) . ") AS VAL FROM RDB\$DATABASE";
    }

    /**
     * Compile SQL statement for execute procedure
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string $procedure
     * @param array $values
     * @return string
     */
    public function compileExecProcedure(Builder $query, $procedure, array $values = null)
    {
        $procedure = $this->wrap($procedure);

        return "EXECUTE PROCEDURE {$$procedure} (" . $this->parameterize($values) . ')';
    }

    /**
     * Compile an insert and get ID statement into SQL.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $values
     * @param string $sequence
     * @return string
     */
    public function compileInsertGetId(Builder $query, $values, $sequence)
    {
        if (is_null($sequence)) {
            $sequence = 'ID';
        }

        return $this->compileInsert($query, $values) . ' RETURNING ' . $this->wrap($sequence);
    }

    /**
     * Compile the "limit" portions of the query.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param int $limit
     * @return string
     */
    protected function compileLimit(Builder $query, $limit)
    {
        if ($query->offset) {
            $first = (int)$query->offset + 1;
            return 'ROWS ' . (int)$first;
        } else {
            return 'ROWS ' . (int)$limit;
        }
    }

    /**
     * Compile the lock into SQL.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param bool|string $value
     * @return string
     */
    protected function compileLock(Builder $query, $value)
    {
        if (is_string($value)) {
            return $value;
        }

        return $value ? 'FOR UPDATE' : '';
    }

    /**
     * Compile SQL statement for get next sequence value
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string $sequence
     * @param int $increment
     * @return string
     */
    public function compileNextSequenceValue(Builder $query, $sequence = null, $increment = null)
    {
        if (!$sequence) {
            $sequence = $this->wrap(substr('seq_' . $query->from, 0, 31));
        }
        if ($increment) {
            return "SELECT GEN_ID({$sequence}, {$increment}) AS ID FROM RDB\$DATABASE";
        }
        return "SELECT NEXT VALUE FOR {$sequence} AS ID FROM RDB\$DATABASE";
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
        if ($query->limit) {
            if ($offset) {
                $end = (int)$query->limit + (int)$offset;
                return 'TO ' . $end;
            } else {
                return '';
            }
        } else {
            $begin = (int)$offset + 1;
            return 'ROWS ' . $begin . ' TO 2147483647';
        }
    }

    /**
     * Compile an update statement into SQL.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $values
     * @return string
     */
    public function compileUpdate(Builder $query, $values)
    {
        $table = $this->wrapTable($query->from);

        // Each one of the columns in the update statements needs to be wrapped in the
        // keyword identifiers, also a place-holder needs to be created for each of
        // the values in the list of bindings so we can make the sets statements.
        $columns = $this->compileUpdateColumns($values);


        $where = $this->compileUpdateWheres($query);

        return trim("UPDATE {$table} SET {$columns} $where");
    }

    /**
     * Compile the columns for the update statement.
     *
     * @param array $values
     * @return string
     */
    protected function compileUpdateColumns($values)
    {
        $columns = [];

        // When gathering the columns for an update statement, we'll wrap each of the
        // columns and convert it to a parameter value. Then we will concatenate a
        // list of the columns that can be added into this update query clauses.
        foreach ($values as $key => $value) {
            $columns[] = $this->wrap($key) . ' = ' . $this->parameter($value);
        }

        return implode(', ', $columns);
    }

    /**
     * Compile the additional where clauses for updates with joins.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return string
     */
    protected function compileUpdateWheres(Builder $query)
    {
        $baseWhere = $this->compileWheres($query);

        return $baseWhere;
    }

    /**
     * Compile a date based where clause.
     *
     * @param string $type
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $where
     * @return string
     */
    protected function dateBasedWhere($type, Builder $query, $where)
    {
        $value = $this->parameter($where['value']);

        return 'EXTRACT(' . $type . ' FROM ' . $this->wrap($where['column']) . ') ' . $where['operator'] . ' ' . $value;
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param string $value
     * @return string
     */
    protected function wrapValue($value)
    {
        if ($value === '*') {
            return $value;
        }

        return '"' . str_replace('"', '""', $value) . '"';
    }

}
