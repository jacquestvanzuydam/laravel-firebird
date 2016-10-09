<?php namespace Firebird\Query\Grammars;

use Firebird\Query\Grammars\FirebirdGrammar;
use Illuminate\Database\Query\Builder;

class FirebirdGrammar30 extends FirebirdGrammar
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
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  int  $limit
     * @return string
     */
    protected function compileLimit(Builder $query, $limit)
    {
        if ($limit)
            return 'fetch first ' . (int) $limit . ' rows only';
        else
            return null;
    }

    /**
     * Compile the "offset" portions of the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  int  $offset
     * @return string
     */
    protected function compileOffset(Builder $query, $offset)
    {
        if ($offset)
            return 'offset ' . (int) $offset . ' rows';
        else
            return null;
    }

    /**
     * Fix PDO driver bug for 'INSERT ... RETURNING'
     * See https://bugs.php.net/bug.php?id=72931
     * Reproduced in Firebird 3.0 only
     * Remove when the bug is fixed!
     * 
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $values
     * @param string $sequence
     * @param string $sql
     */
    private function fixInsertReturningBug(Builder $query, $values, $sequence, $sql)
    {
        /*
         * Since the PDO Firebird driver bug because of which is not executed 
         * sql query 'INSERT ... RETURNING', then we wrap the statement in 
         * the block and execute it. PDO may not recognize the colon (:) within 
         * a block properly, so we will not use it. The only way I found 
         * buyout perform a query via EXECUTE STATEMENT.
         */
        if (!is_array(reset($values))) {
            $values = [$values];
        }
        $table = $this->wrapTable($query->from);
        $columns = array_map([$this, 'wrap'], array_keys(reset($values)));
        $columnsWithTypeOf = [];
        foreach ($columns as $column) {
            $columnsWithTypeOf[] = "  {$column} TYPE OF COLUMN {$table}.{$column} = ?";
        }
        $ret_column = $this->wrap($sequence);

        $columns_str = $this->columnize(array_keys(reset($values)));

        $new_sql = "EXECUTE BLOCK (\n";
        $new_sql .= implode(",\n", $columnsWithTypeOf);
        $new_sql .= ")\n";
        $new_sql .= "RETURNS ({$ret_column} TYPE OF COLUMN {$table}.{$ret_column})\n";
        $new_sql .= "AS\n";
        $new_sql .= "  DECLARE STMT VARCHAR(8191);\n";
        $new_sql .= "BEGIN\n";
        $new_sql .= "  STMT = '{$sql}';\n";
        $new_sql .= "  EXECUTE STATEMENT (STMT) ({$columns_str})\n";

        if (!$query->getConnection()->getPdo()->inTransaction()) {
            // For some unknown reason, there is a ROLLBACK. Probably due to the COMMIT RETAINING.
            $new_sql .= "  WITH AUTONOMOUS TRANSACTION\n";
        }
        $new_sql .= "  INTO {$ret_column};\n";
        $new_sql .= "  SUSPEND;\n";
        $new_sql .= "END";

        return $new_sql;
    }

    /**
     * Compile an insert and get ID statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array   $values
     * @param  string  $sequence
     * @return string
     */
    public function compileInsertGetId(Builder $query, $values, $sequence)
    {
        $sql = parent::compileInsertGetId($query, $values, $sequence);
        // Fix PDO driver bug for 'INSERT ... RETURNING'
        // See https://bugs.php.net/bug.php?id=72931
        $sql = $this->fixInsertReturningBug($query, $values, $sequence, $sql);

        return $sql;
    }

}
