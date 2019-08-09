<?php

namespace Firebird\Schema\Grammars;

use Firebird\Schema\SequenceBlueprint;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Support\Fluent;

class FirebirdGrammar extends Grammar
{

    /**
     * The possible column modifiers.
     *
     * @var array
     */
    protected $modifiers = ['Charset', 'Collate', 'Increment', 'Nullable', 'Default'];

    /**
     * The columns available as serials.
     *
     * @var array
     */
    protected $serials = ['bigInteger', 'integer', 'mediumInteger', 'smallInteger', 'tinyInteger'];

    /**
     * Compile the query to determine if a table exists.
     *
     * @return string
     */
    public function compileTableExists()
    {
        return 'SELECT * FROM RDB$RELATIONS WHERE RDB$RELATION_NAME = ?';
    }

    /**
     * Compile the query to determine the list of columns.
     *
     * @param string $table
     * @return string
     */
    public function compileColumnExists($table)
    {
        return 'SELECT TRIM(RDB$FIELD_NAME) AS "column_name" '
            . "FROM RDB\$RELATION_FIELDS WHERE RDB\$RELATION_NAME = '$table'";
    }

    /**
     * Compile the query to determine if a sequence exists.
     *
     * @return string
     */
    public function compileSequenceExists()
    {
        return 'SELECT * FROM RDB$GENERATORS WHERE RDB$GENERATOR_NAME = ?';
    }

    /**
     * Compile a create table command.
     *
     * @param \Illuminate\Database\Schema\Blueprint $blueprint
     * @param \Illuminate\Support\Fluent $command
     * @return string
     */
    public function compileCreate(Blueprint $blueprint, Fluent $command)
    {
        $columns = implode(', ', $this->getColumns($blueprint));

        $sql = $blueprint->temporary ? 'CREATE TEMPORARY' : 'CREATE';

        $sql .= ' TABLE ' . $this->wrapTable($blueprint) . " ($columns)";

        if ($blueprint->temporary) {
            if ($blueprint->preserve) {
                $sql .= ' ON COMMIT DELETE ROWS';
            } else {
                $sql .= ' ON COMMIT PRESERVE ROWS';
            }
        }

        return $sql;
    }

    /**
     * Compile a drop table command.
     *
     * @param \Illuminate\Database\Schema\Blueprint $blueprint
     * @param \Illuminate\Support\Fluent $command
     * @return string
     */
    public function compileDrop(Blueprint $blueprint, Fluent $command)
    {
        return 'DROP TABLE ' . $this->wrapTable($blueprint);
    }

    /**
     * Compile a column addition command.
     *
     * @param \Illuminate\Database\Schema\Blueprint $blueprint
     * @param \Illuminate\Support\Fluent $command
     * @return string
     */
    public function compileAdd(Blueprint $blueprint, Fluent $command)
    {
        $table = $this->wrapTable($blueprint);

        $columns = $this->prefixArray('ADD', $this->getColumns($blueprint));

        return 'ALTER TABLE ' . $table . ' ' . implode(', ', $columns);
    }

    /**
     * Compile a primary key command.
     *
     * @param \Illuminate\Database\Schema\Blueprint $blueprint
     * @param \Illuminate\Support\Fluent $command
     * @return string
     */
    public function compilePrimary(Blueprint $blueprint, Fluent $command)
    {
        $columns = $this->columnize($command->columns);

        return 'ALTER TABLE ' . $this->wrapTable($blueprint) . " ADD PRIMARY KEY ({$columns})";
    }

    /**
     * Compile a unique key command.
     *
     * @param \Illuminate\Database\Schema\Blueprint $blueprint
     * @param \Illuminate\Support\Fluent $command
     * @return string
     */
    public function compileUnique(Blueprint $blueprint, Fluent $command)
    {
        $table = $this->wrapTable($blueprint);

        $index = $this->wrap(substr($command->index, 0, 31));

        $columns = $this->columnize($command->columns);

        return "ALTER TABLE {$table} ADD CONSTRAINT {$index} UNIQUE ({$columns})";
    }

    /**
     * Compile a plain index key command.
     *
     * @param \Illuminate\Database\Schema\Blueprint $blueprint
     * @param \Illuminate\Support\Fluent $command
     * @return string
     */
    public function compileIndex(Blueprint $blueprint, Fluent $command)
    {
        $columns = $this->columnize($command->columns);

        $index = $this->wrap(substr($command->index, 0, 31));

        $table = $this->wrapTable($blueprint);

        return "CREATE INDEX {$index} ON {$table} ($columns)";
    }

    /**
     * Compile a foreign key command.
     *
     * @param \Illuminate\Database\Schema\Blueprint $blueprint
     * @param \Illuminate\Support\Fluent $command
     * @return string
     */
    public function compileForeign(Blueprint $blueprint, Fluent $command)
    {
        $table = $this->wrapTable($blueprint);

        $on = $this->wrapTable($command->on);

        // We need to prepare several of the elements of the foreign key definition
        // before we can create the SQL, such as wrapping the tables and convert
        // an array of columns to comma-delimited strings for the SQL queries.
        $columns = $this->columnize($command->columns);

        $onColumns = $this->columnize((array)$command->references);

        $fkName = substr($command->index, 0, 31);

        $sql = "ALTER TABLE {$table} ADD CONSTRAINT {$fkName} ";

        $sql .= "FOREIGN KEY ({$columns}) REFERENCES {$on} ({$onColumns})";

        // Once we have the basic foreign key creation statement constructed we can
        // build out the syntax for what should happen on an update or delete of
        // the affected columns, which will get something like "cascade", etc.
        if (!is_null($command->onDelete)) {
            $sql .= " ON DELETE {$command->onDelete}";
        }

        if (!is_null($command->onUpdate)) {
            $sql .= " ON UPDATE {$command->onUpdate}";
        }

        return $sql;
    }

    /**
     * Compile a drop foreign key command.
     *
     * @param \Illuminate\Database\Schema\Blueprint $blueprint
     * @param \Illuminate\Support\Fluent $command
     * @return string
     */
    public function compileDropForeign(Blueprint $blueprint, Fluent $command)
    {
        $table = $this->wrapTable($blueprint);

        return "ALTER TABLE {$table} DROP CONSTRAINT {$command->index}";
    }

    /**
     * Get the SQL for a character set column modifier.
     *
     * @param \Illuminate\Database\Schema\Blueprint $blueprint
     * @param \Illuminate\Support\Fluent $column
     * @return string|null
     */
    protected function modifyCharset(Blueprint $blueprint, Fluent $column)
    {
        if (!is_null($column->charset)) {
            return ' CHARACTER SET ' . $column->charset;
        }
    }

    /**
     * Get the SQL for a collation column modifier.
     *
     * @param \Illuminate\Database\Schema\Blueprint $blueprint
     * @param \Illuminate\Support\Fluent $column
     * @return string|null
     */
    protected function modifyCollate(Blueprint $blueprint, Fluent $column)
    {
        if (!is_null($column->collation)) {
            return ' COLLATE ' . $column->collation;
        }
    }

    /**
     * Get the SQL for a nullable column modifier.
     *
     * @param \Illuminate\Database\Schema\Blueprint $blueprint
     * @param \Illuminate\Support\Fluent $column
     * @return string|null
     */
    protected function modifyNullable(Blueprint $blueprint, Fluent $column)
    {
        return $column->nullable ? '' : ' NOT NULL';
    }

    /**
     * Get the SQL for a default column modifier.
     *
     * @param \Illuminate\Database\Schema\Blueprint $blueprint
     * @param \Illuminate\Support\Fluent $column
     * @return string|null
     */
    protected function modifyDefault(Blueprint $blueprint, Fluent $column)
    {
        if (!is_null($column->default)) {
            return ' DEFAULT ' . $this->getDefaultValue($column->default);
        }
    }

    /**
     * Get the SQL for an auto-increment column modifier.
     *
     * @param \Illuminate\Database\Schema\Blueprint $blueprint
     * @param \Illuminate\Support\Fluent $column
     * @return string|null
     */
    protected function modifyIncrement(Blueprint $blueprint, Fluent $column)
    {
        if (in_array($column->type, $this->serials) && $column->autoIncrement) {
            // identity columns support beginning Firebird 3.0 and above
            return $blueprint->use_identity ? ' GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY' : ' PRIMARY KEY';
        }
    }

    /**
     * Create the column definition for a char type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeChar(Fluent $column)
    {
        return "CHAR({$column->length})";
    }

    /**
     * Create the column definition for a string type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeString(Fluent $column)
    {
        return "VARCHAR({$column->length})";
    }

    /**
     * Create the column definition for a text type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeText(Fluent $column)
    {
        return 'BLOB SUB_TYPE TEXT';
    }

    /**
     * Create the column definition for a medium text type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeMediumText(Fluent $column)
    {
        return 'BLOB SUB_TYPE TEXT';
    }

    /**
     * Create the column definition for a long text type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeLongText(Fluent $column)
    {
        return 'BLOB SUB_TYPE TEXT';
    }

    /**
     * Create the column definition for a integer type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeInteger(Fluent $column)
    {
        return 'INTEGER';
    }

    /**
     * Create the column definition for a big integer type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeBigInteger(Fluent $column)
    {
        return 'BIGINT';
    }

    /**
     * Create the column definition for a medium integer type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeMediumInteger(Fluent $column)
    {
        return 'INTEGER';
    }

    /**
     * Create the column definition for a tiny integer type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeTinyInteger(Fluent $column)
    {
        return 'SMALLINT';
    }

    /**
     * Create the column definition for a small integer type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeSmallInteger(Fluent $column)
    {
        return 'SMALLINT';
    }

    /**
     * Create the column definition for a float type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeFloat(Fluent $column)
    {
        return 'FLOAT';
    }

    /**
     * Create the column definition for a double type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeDouble(Fluent $column)
    {
        return 'DOUBLE PRECISION';
    }

    /**
     * Create the column definition for a decimal type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeDecimal(Fluent $column)
    {
        return "DECIMAL({$column->total}, {$column->places})";
    }

    /**
     * Create the column definition for a boolean type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeBoolean(Fluent $column)
    {
        // Firebird 3.0 support native type BOOLEAN, but
        // PDO dosn't support
        return 'CHAR(1)';
    }

    /**
     * Create the column definition for an enum type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeEnum(Fluent $column)
    {
        $allowed = array_map(function ($a) {
            return "'" . $a . "'";
        }, $column->allowed);

        return "VARCHAR(255) CHECK (\"{$column->name}\" IN (" . implode(', ', $allowed) . '))';
    }

    /**
     * Create the column definition for a json type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeJson(Fluent $column)
    {
        return 'VARCHAR(8191)';
    }

    /**
     * Create the column definition for a jsonb type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeJsonb(Fluent $column)
    {
        return 'VARCHAR(8191) CHARACTER SET OCTETS';
    }

    /**
     * Create the column definition for a date type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeDate(Fluent $column)
    {
        return 'DATE';
    }

    /**
     * Create the column definition for a date-time type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeDateTime(Fluent $column)
    {
        return 'TIMESTAMP';
    }

    /**
     * Create the column definition for a date-time type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeDateTimeTz(Fluent $column)
    {
        // Firebird don't support timezones
        return 'TIMESTAMP';
    }

    /**
     * Create the column definition for a time type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeTime(Fluent $column)
    {
        return 'TIME';
    }

    /**
     * Create the column definition for a time type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeTimeTz(Fluent $column)
    {
        // Firebird don't support timezones
        return 'TIME';
    }

    /**
     * Create the column definition for a timestamp type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeTimestamp(Fluent $column)
    {
        if ($column->useCurrent) {
            return 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
        }

        return 'TIMESTAMP';
    }

    /**
     * Create the column definition for a timestamp type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeTimestampTz(Fluent $column)
    {
        if ($column->useCurrent) {
            return 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
        }

        return 'TIMESTAMP';
    }

    /**
     * Create the column definition for a binary type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeBinary(Fluent $column)
    {
        return 'BLOB SUB_TYPE BINARY';
    }

    /**
     * Create the column definition for a uuid type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeUuid(Fluent $column)
    {
        return 'CHAR(36)';
    }

    /**
     * Create the column definition for an IP address type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeIpAddress(Fluent $column)
    {
        return 'VARCHAR(45)';
    }

    /**
     * Create the column definition for a MAC address type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeMacAddress(Fluent $column)
    {
        return 'VARCHAR(17)';
    }

    /**
     * Compile a create sequence command.
     *
     * @param \Firebird\Schema\SequenceBlueprint $blueprint
     * @param \Illuminate\Support\Fluent $command
     * @return string
     */
    public function compileCreateSequence(SequenceBlueprint $blueprint, Fluent $command)
    {
        $sql = 'CREATE SEQUENCE ';
        $sql .= $this->wrapSequence($blueprint);
        if ($blueprint->getInitialValue() !== 0) {
            $sql .= ' START WITH ' . $blueprint->getInitialValue();
        }
        if ($blueprint->getIncrement() !== 1) {
            $sql .= ' INCREMENT BY ' . $blueprint->getIncrement();
        }
        return $sql;
    }

    /**
     * Compile a create sequence command for table.
     *
     * @param \Illuminate\Database\Schema\Blueprint $blueprint
     * @param \Illuminate\Support\Fluent $command
     * @return string
     */
    public function compileSequenceForTable(Blueprint $blueprint, Fluent $command)
    {

        $sequence = $this->wrap(substr('seq_' . $blueprint->getTable(), 0, 31));

        return "CREATE SEQUENCE {$sequence}";
    }

    /**
     * Compile a drop sequence command for table.
     *
     * @param \Illuminate\Database\Schema\Blueprint $blueprint
     * @param \Illuminate\Support\Fluent $command
     * @return string
     */
    public function compileDropSequenceForTable(Blueprint $blueprint, Fluent $command)
    {
        $sequenceName = substr('seq_' . $blueprint->getTable(), 0, 31);
        $sequence = $this->wrap($sequenceName);

        $sql = 'EXECUTE BLOCK' . "\n";
        $sql .= 'AS' . "\n";
        $sql .= 'BEGIN' . "\n";
        $sql .= "  IF (EXISTS(SELECT * FROM RDB\$GENERATORS WHERE RDB\$GENERATOR_NAME = '{$sequenceName}')) THEN" . "\n";
        $sql .= "    EXECUTE STATEMENT 'DROP SEQUENCE {$sequence}';" . "\n";
        $sql .= 'END';
        return $sql;
    }

    /**
     * Compile a create trigger for support autoincrement.
     *
     * @param \Illuminate\Database\Schema\Blueprint $blueprint
     * @param \Illuminate\Support\Fluent $command
     * @return string
     */
    public function compileTriggerForAutoincrement(Blueprint $blueprint, Fluent $command)
    {
        $table = $this->wrapTable($blueprint);
        $trigger = $this->wrap(substr('tr_' . $blueprint->getTable() . '_bi', 0, 31));
        $column = $this->wrap($command->columnname);
        $sequence = $this->wrap(substr('seq_' . $blueprint->getTable(), 0, 31));

        $sql = "CREATE OR ALTER TRIGGER {$trigger} FOR {$table}\n";
        $sql .= "ACTIVE BEFORE INSERT\n";
        $sql .= "AS\n";
        $sql .= "BEGIN\n";
        $sql .= "  IF (NEW.{$column} IS NULL) THEN \n";
        $sql .= "    NEW.{$column} = NEXT VALUE FOR {$sequence};\n";
        $sql .= 'END';

        return $sql;
    }

    /**
     * Compile a alter sequence command.
     *
     * @param \Firebird\Schema\SequenceBlueprint $blueprint
     * @param \Illuminate\Support\Fluent $command
     * @return string
     */
    public function compileAlterSequence(SequenceBlueprint $blueprint, Fluent $command)
    {
        $sql = 'ALTER SEQUENCE ';
        $sql .= $this->wrapSequence($blueprint);
        if ($blueprint->isRestart()) {
            $sql .= ' RESTART';
            if ($blueprint->getInitialValue() !== null) {
                $sql .= ' WITH ' . $blueprint->getInitialValue();
            }
        }
        if ($blueprint->getIncrement() !== 1) {
            $sql .= ' INCREMENT BY ' . $blueprint->getIncrement();
        }
        return $sql;
    }

    /**
     * Compile a drop sequence command.
     *
     * @param \Firebird\Schema\SequenceBlueprint $blueprint
     * @param \Illuminate\Support\Fluent $command
     * @return string
     */
    public function compileDropSequence(SequenceBlueprint $blueprint, Fluent $command)
    {
        return 'DROP SEQUENCE ' . $this->wrapSequence($blueprint);
    }

    /**
     * Compile a drop sequence command.
     *
     * @param \Firebird\Schema\SequenceBlueprint $blueprint
     * @param \Illuminate\Support\Fluent $command
     * @return string
     */
    public function compileDropSequenceIfExists(SequenceBlueprint $blueprint, Fluent $command)
    {
        $sql = 'EXECUTE BLOCK' . "\n";
        $sql .= 'AS' . "\n";
        $sql .= 'BEGIN' . "\n";
        $sql .= "  IF (EXISTS(SELECT * FROM RDB\$GENERATORS WHERE RDB\$GENERATOR_NAME = '" . $blueprint->getSequence() . "')) THEN" . "\n";
        $sql .= "    EXECUTE STATEMENT 'DROP SEQUENCE " . $this->wrapSequence($blueprint) . "';" . "\n";
        $sql .= 'END';
        return $sql;
    }

    /**
     * Wrap a sequence in keyword identifiers.
     *
     * @param mixed $sequence
     * @return string
     */
    public function wrapSequence($sequence)
    {
        if ($sequence instanceof SequenceBlueprint) {
            $sequence = $sequence->getSequence();
        }

        if ($this->isExpression($sequence)) {
            return $this->getValue($sequence);
        }

        return $this->wrap($this->tablePrefix . $sequence, true);
    }

}
