<?php namespace Firebird\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Support\Fluent;
use Illuminate\Database\Schema\Blueprint;
use Firebird\Connection;

class FirebirdGrammar extends Grammar {

  /**
   * The possible column modifiers.
   *
   * @var array
   */
  protected $modifiers = array('Default', 'Nullable');

  /**
   * The columns available as serials.
   *
   * @var array
   */
  protected $serials = array('integer');

  /**
   * Compile the query to determine if a table exists.
   *
   * @return string
   */
  public function compileTableExists()
  {
    return "SELECT RDB\$RELATION_NAME FROM RDB\$RELATIONS WHERE RDB\$RELATION_NAME = ?";
  }

  /**
   * Compile a create table command.
   *
   * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
   * @param  \Illuminate\Support\Fluent  $command
   * @param  \Illuminate\Database\Connection  $connection
   * @return string
   */
  public function compileCreate(Blueprint $blueprint, Fluent $command, Connection $connection)
  {
    $columns = implode(', ', $this->getColumns($blueprint));

    $sql = 'create table '.$this->wrapTable($blueprint)." ($columns)";

    return $sql;
  }

  /**
   * Compile a drop table command.
   *
   * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
   * @param  \Illuminate\Support\Fluent  $command
   * @return string
   */
  public function compileDrop(Blueprint $blueprint, Fluent $command)
  {
    return 'drop table '.$this->wrapTable($blueprint);
  }

  /**
   * Compile a primary key command.
   *
   * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
   * @param  \Illuminate\Support\Fluent  $command
   * @return string
   */
  public function compilePrimary(Blueprint $blueprint, Fluent $command)
  {
    $command->name(null);

    return $this->compileKey($blueprint, $command, 'primary key');
  }

  /**
   * Compile a unique key command.
   *
   * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
   * @param  \Illuminate\Support\Fluent  $command
   * @return string
   */
  public function compileUnique(Blueprint $blueprint, Fluent $command)
  {
    $columns = $this->columnize($command->columns);

    $table = $this->wrapTable($blueprint);

    return "CREATE UNIQUE INDEX ".strtoupper(substr($command->index, 0, 31))." ON {$table} ($columns)";
  }

  /**
   * Compile a plain index key command.
   *
   * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
   * @param  \Illuminate\Support\Fluent  $command
   * @return string
   */
  public function compileIndex(Blueprint $blueprint, Fluent $command)
  {
    $columns = $this->columnize($command->columns);

    $table = $this->wrapTable($blueprint);

    return "CREATE INDEX ".strtoupper(substr($command->index, 0, 31))." ON {$table} ($columns)";
  }

  /**
   * Compile an index creation command.
   *
   * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
   * @param  \Illuminate\Support\Fluent  $command
   * @param  string  $type
   * @return string
   */
  protected function compileKey(Blueprint $blueprint, Fluent $command, $type)
  {
    $columns = $this->columnize($command->columns);

    $table = $this->wrapTable($blueprint);

    return "alter table {$table} add {$type} ($columns)";
  }

  /**
   * Compile a foreign key command.
   *
   * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
   * @param  \Illuminate\Support\Fluent  $command
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

    $onColumns = $this->columnize((array) $command->references);

    $sql = "alter table {$table} add constraint ".strtoupper(substr($command->index, 0, 31))." ";

    $sql .= "foreign key ({$columns}) references {$on} ({$onColumns})";

    // Once we have the basic foreign key creation statement constructed we can
    // build out the syntax for what should happen on an update or delete of
    // the affected columns, which will get something like "cascade", etc.
    if ( ! is_null($command->onDelete))
    {
      $sql .= " on delete {$command->onDelete}";
    }

    if ( ! is_null($command->onUpdate))
    {
      $sql .= " on update {$command->onUpdate}";
    }

    return $sql;
  }

  /**
   * Compile a drop foreign key command.
   *
   * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
   * @param  \Illuminate\Support\Fluent  $command
   * @return string
   */
  public function compileDropForeign(Blueprint $blueprint, Fluent $command)
  {
    $table = $this->wrapTable($blueprint);

    return "alter table {$table} drop constraint {$command->index}";
  }

  /**
   * Get the SQL for a nullable column modifier.
   *
   * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
   * @param  \Illuminate\Support\Fluent  $column
   * @return string|null
   */
  protected function modifyNullable(Blueprint $blueprint, Fluent $column)
  {
    return $column->nullable ? '' : ' not null';
  }

  /**
   * Get the SQL for a default column modifier.
   *
   * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
   * @param  \Illuminate\Support\Fluent  $column
   * @return string|null
   */
  protected function modifyDefault(Blueprint $blueprint, Fluent $column)
  {
    if ( ! is_null($column->default))
    {
      return " default ".$this->getDefaultValue($column->default);
    }
  }

  /**
   * Create the column definition for a char type.
   *
   * @param  \Illuminate\Support\Fluent  $column
   * @return string
   */
  protected function typeChar(Fluent $column)
  {
    return 'VARCHAR';
  }

  /**
   * Create the column definition for a string type.
   *
   * @param  \Illuminate\Support\Fluent  $column
   * @return string
   */
  protected function typeString(Fluent $column)
  {
    return 'VARCHAR ('.$column->length.')';
  }

  /**
   * Create the column definition for a text type.
   *
   * @param  \Illuminate\Support\Fluent  $column
   * @return string
   */
  protected function typeText(Fluent $column)
  {
    return 'BLOB SUB_TYPE TEXT';
  }

  /**
   * Create the column definition for a medium text type.
   *
   * @param  \Illuminate\Support\Fluent  $column
   * @return string
   */
  protected function typeMediumText(Fluent $column)
  {
    return 'BLOB SUB_TYPE TEXT';
  }

  /**
   * Create the column definition for a long text type.
   *
   * @param  \Illuminate\Support\Fluent  $column
   * @return string
   */
  protected function typeLongText(Fluent $column)
  {
    return 'BLOB SUB_TYPE TEXT';
  }

  /**
   * Create the column definition for a integer type.
   *
   * @param  \Illuminate\Support\Fluent  $column
   * @return string
   */
  protected function typeInteger(Fluent $column)
  {
    return 'INTEGER';
  }

  /**
   * Create the column definition for a big integer type.
   *
   * @param  \Illuminate\Support\Fluent  $column
   * @return string
   */
  protected function typeBigInteger(Fluent $column)
  {
    return 'INTEGER';
  }

  /**
   * Create the column definition for a medium integer type.
   *
   * @param  \Illuminate\Support\Fluent  $column
   * @return string
   */
  protected function typeMediumInteger(Fluent $column)
  {
    return 'INTEGER';
  }

  /**
   * Create the column definition for a tiny integer type.
   *
   * @param  \Illuminate\Support\Fluent  $column
   * @return string
   */
  protected function typeTinyInteger(Fluent $column)
  {
    return 'SMALLINT';
  }

  /**
   * Create the column definition for a small integer type.
   *
   * @param  \Illuminate\Support\Fluent  $column
   * @return string
   */
  protected function typeSmallInteger(Fluent $column)
  {
    return 'SMALLINT';
  }

  /**
   * Create the column definition for a float type.
   *
   * @param  \Illuminate\Support\Fluent  $column
   * @return string
   */
  protected function typeFloat(Fluent $column)
  {
    return 'FLOAT';
  }

  /**
   * Create the column definition for a double type.
   *
   * @param  \Illuminate\Support\Fluent  $column
   * @return string
   */
  protected function typeDouble(Fluent $column)
  {
    return 'DOUBLE';
  }

  /**
   * Create the column definition for a decimal type.
   *
   * @param  \Illuminate\Support\Fluent  $column
   * @return string
   */
  protected function typeDecimal(Fluent $column)
  {
    return 'DECIMAL';
  }

  /**
   * Create the column definition for a boolean type.
   *
   * @param  \Illuminate\Support\Fluent  $column
   * @return string
   */
  protected function typeBoolean(Fluent $column)
  {
    return 'CHAR(1)';
  }

  /**
   * Create the column definition for an enum type.
   *
   * @param  \Illuminate\Support\Fluent  $column
   * @return string
   */
  protected function typeEnum(Fluent $column)
  {
    return 'VARCHAR';
  }

  /**
   * Create the column definition for a json type.
   *
   * @param  \Illuminate\Support\Fluent  $column
   * @return string
   */
  protected function typeJson(Fluent $column)
  {
    return 'BLOB SUB_TYPE 0';
  }

  /**
   * Create the column definition for a date type.
   *
   * @param  \Illuminate\Support\Fluent  $column
   * @return string
   */
  protected function typeDate(Fluent $column)
  {
    return 'TIMESTAMP';
  }

  /**
   * Create the column definition for a date-time type.
   *
   * @param  \Illuminate\Support\Fluent  $column
   * @return string
   */
  protected function typeDateTime(Fluent $column)
  {
    return 'TIMESTAMP';
  }

  /**
   * Create the column definition for a time type.
   *
   * @param  \Illuminate\Support\Fluent  $column
   * @return string
   */
  protected function typeTime(Fluent $column)
  {
    return 'TIMESTAMP';
  }

  /**
   * Create the column definition for a timestamp type.
   *
   * @param  \Illuminate\Support\Fluent  $column
   * @return string
   */
  protected function typeTimestamp(Fluent $column)
  {
    return 'TIMESTAMP';
  }

  /**
   * Create the column definition for a binary type.
   *
   * @param  \Illuminate\Support\Fluent  $column
   * @return string
   */
  protected function typeBinary(Fluent $column)
  {
    return 'BLOB SUB_TYPE 0';
  }
}
