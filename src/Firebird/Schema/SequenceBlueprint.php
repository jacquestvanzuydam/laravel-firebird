<?php

namespace Firebird\Schema;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Support\Fluent;

class SequenceBlueprint
{

    /**
     * The sequence the blueprint describes.
     *
     * @var string
     */
    protected $sequence;

    /**
     * The commands that should be run for the sequence.
     *
     * @var array
     */
    protected $commands = [];

    /**
     * Initial sequence value
     *
     * @var int
     */
    protected $start_with = 0;

    /**
     * Increment for sequence
     *
     * @var int
     */
    protected $increment = 1;

    /**
     * Restart flag that indicates that the sequence should be reset
     *
     * @var bool
     */
    protected $restart = false;

    /**
     * Create a new schema blueprint.
     *
     * @param string $sequence
     * @param \Closure|null $callback
     * @return void
     */
    public function __construct($sequence, Closure $callback = null)
    {
        $this->sequence = $sequence;

        if (!is_null($callback)) {
            $callback($this);
        }
    }

    /**
     * Execute the blueprint against the database.
     *
     * @param \Illuminate\Database\Connection $connection
     * @param \Illuminate\Database\Schema\Grammars\Grammar $grammar
     * @return void
     */
    public function build(Connection $connection, Grammar $grammar)
    {
        foreach ($this->toSql($connection, $grammar) as $statement) {
            $connection->statement($statement);
        }
    }

    /**
     * Indicate that the table needs to be created.
     *
     * @return \Illuminate\Support\Fluent
     */
    public function create()
    {
        return $this->addCommand('createSequence');
    }

    /**
     * Determine if the blueprint has a create command.
     *
     * @return bool
     */
    protected function creating()
    {
        foreach ($this->commands as $command) {
            if ($command->name == 'createSequence') {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the blueprint has a drop command.
     *
     * @return bool
     */
    protected function dropping()
    {
        foreach ($this->commands as $command) {
            if ($command->name == 'dropSequence') {
                return true;
            }
            if ($command->name == 'dropSequenceIfExists') {
                return true;
            }
        }

        return false;
    }

    /**
     * Indicate that the table should be dropped.
     *
     * @return \Illuminate\Support\Fluent
     */
    public function drop()
    {
        return $this->addCommand('dropSequence');
    }

    /**
     * Indicate that the table should be dropped if it exists.
     *
     * @return \Illuminate\Support\Fluent
     */
    public function dropIfExists()
    {
        return $this->addCommand('dropSequenceIfExists');
    }

    /**
     * Add a new command to the blueprint.
     *
     * @param string $name
     * @param array $parameters
     * @return \Illuminate\Support\Fluent
     */
    protected function addCommand($name, array $parameters = [])
    {
        $this->commands[] = $command = $this->createCommand($name, $parameters);

        return $command;
    }

    /**
     * Create a new Fluent command.
     *
     * @param string $name
     * @param array $parameters
     * @return \Illuminate\Support\Fluent
     */
    protected function createCommand($name, array $parameters = [])
    {
        return new Fluent(array_merge(compact('name'), $parameters));
    }

    /**
     * Get the commands on the blueprint.
     *
     * @return array
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * Get increment for the sequence
     *
     * @return int
     */
    public function getIncrement()
    {
        return $this->increment;
    }

    /**
     * Get initial value for the sequence
     *
     * @return int
     */
    public function getInitialValue()
    {
        return $this->start_with;
    }

    /**
     * Get the sequence the blueprint describes.
     *
     * @return string
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * Set the sequence increment
     *
     * @param int $increment
     */
    public function increment($increment)
    {
        $this->increment = $increment;
    }

    /**
     * Get the sequence restart flag
     *
     * @return bool
     */
    public function isRestart()
    {
        return $this->restart;
    }

    /**
     * Set initial value for the sequence
     *
     * @param int $startWith
     */
    public function startWith($startWith)
    {
        $this->start_with = $startWith;
    }

    /**
     * Restart sequence and set initial value
     *
     * @param int $startWith
     */
    public function restart($startWith = null)
    {
        $this->restart = true;
        $this->start_with = $startWith;
    }

    /**
     * Add the commands that are implied by the blueprint.
     *
     * @return void
     */
    protected function addImpliedCommands()
    {
        if (($this->restart || ($this->increment !== 1)) &&
            !$this->creating() &&
            !$this->dropping()) {
            array_unshift($this->commands, $this->createCommand('alterSequence'));
        }
    }

    /**
     * Get the raw SQL statements for the blueprint.
     *
     * @param \Illuminate\Database\Connection $connection
     * @param \Illuminate\Database\Schema\Grammars\Grammar $grammar
     * @return array
     */
    public function toSql(Connection $connection, Grammar $grammar)
    {
        $this->addImpliedCommands();

        $statements = [];

        // Each type of command has a corresponding compiler function on the schema
        // grammar which is used to build the necessary SQL statements to build
        // the sequence blueprint element, so we'll just call that compilers function.
        foreach ($this->commands as $command) {
            $method = 'compile' . ucfirst($command->name);

            if (method_exists($grammar, $method)) {
                if (!is_null($sql = $grammar->$method($this, $command, $connection))) {
                    $statements = array_merge($statements, (array)$sql);
                }
            }
        }

        return $statements;
    }

}
