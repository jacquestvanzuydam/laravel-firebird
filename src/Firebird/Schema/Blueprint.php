<?php namespace Firebird\Schema;

use Illuminate\Database\Schema\Blueprint as BaseBlueprint;
use \Illuminate\Database\Schema\Grammars\Grammar;

class Blueprint extends BaseBlueprint
{

    /**
     * Whether a temporary table such as ON COMMIT PRESERVE ROWS
     *
     * @var bool
     */
    public $preserve = false;

    /**
     * Use identity modifier for increment columns
     *
     * @var bool
     */
    public $use_identity = false;

    /**
     * Use native boolean type
     *
     * @var bool
     */
    public $use_native_boolean = false;

    /**
     * Indicate that the temporary table as ON COMMIT PRESERVE ROWS.
     *
     * @return void
     */
    public function preserveRows()
    {
        $this->preserve = true;
    }

    /**
     * Indicate that it is necessary to use a identity modifier for increment columns
     *
     * @return void
     */
    public function useIdentity()
    {
        $this->use_identity = true;
    }

    /**
     * Indicate that it is necessary to use native boolean type
     * Reserved for future versions. Now Firebird PDO driver
     * does not support the type BOOLEAN
     *
     * @return void
     */
    public function nativeBoolean()
    {
        $this->use_native_boolean = true;
    }

    /**
     * Determine if the blueprint has a create command.
     *
     * @return bool
     */
    protected function droping()
    {
        foreach ($this->commands as $command) {
            if (($command->name == 'drop') || ($command->name == 'dropIfExists')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add the commands that are implied by the blueprint.
     *
     * @param  \Illuminate\Database\Schema\Grammars\Grammar  $grammar
     * @return void
     */
    protected function addImpliedCommands(Grammar $grammar)
    {
        parent::addImpliedCommands($grammar);

        if (!$this->use_identity) {
            $this->addSequence();
            $this->addAutoIncrementTrigger();
        }

        if ($this->droping() && !$this->use_identity) {
            $this->dropSequence();
        }
    }

    /**
     * Add the command for create sequence for table
     *
     * @return void
     */
    protected function addSequence()
    {
        foreach ($this->columns as $column) {
            if ($column->autoIncrement) {
                array_push($this->commands, $this->createCommand('sequenceForTable'));
                break;
            }
        }
    }

    /**
     * Add the command for drop sequence for table
     *
     * @return void
     */
    protected function dropSequence()
    {
        array_push($this->commands, $this->createCommand('dropSequenceForTable'));
    }

    /**
     * Add the command for create trigger
     *
     * @return void
     */
    protected function addAutoIncrementTrigger()
    {
        foreach ($this->columns as $column) {
            if ($column->autoIncrement) {
                array_push($this->commands, $this->createCommand('triggerForAutoincrement', ['columnname' => $column->name]));
                break;
            }
        }
    }

}
