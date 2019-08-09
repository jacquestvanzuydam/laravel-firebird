<?php namespace Firebird\Schema;

use Closure;
use Illuminate\Database\Schema\Builder as BaseBuilder;

class Builder extends BaseBuilder
{

    /**
     * Create a new command set with a Closure.
     *
     * @param string $table
     * @param \Closure|null $callback
     * @return \Firebird\Schema\Blueprint
     */
    protected function createBlueprint($table, Closure $callback = null)
    {
        if (isset($this->resolver)) {
            return call_user_func($this->resolver, $table, $callback);
        }

        return new Blueprint($table, $callback);
    }

    /**
     * Create a new command for Sequence set with a Closure.
     *
     * @param string $sequence
     * @param \Closure|null $callback
     * @return \Firebird\Schema\SequenceBlueprint
     */
    protected function createSequenceBlueprint($sequence, Closure $callback = null)
    {
        if (isset($this->resolver)) {
            return call_user_func($this->resolver, $sequence, $callback);
        }

        return new SequenceBlueprint($sequence, $callback);
    }

    /**
     * Execute the blueprint to build / modify the sequence.
     *
     * @param \Firebird\Schema\SequenceBlueprint $seqprint
     * @return void
     */
    protected function buildSequence(SequenceBlueprint $seqprint)
    {
        $seqprint->build($this->connection, $this->grammar);
    }

    /**
     * Determine if the given sequence exists.
     *
     * @param string $sequence
     * @return bool
     */
    public function hasSequence($sequence)
    {
        $sql = $this->grammar->compileSequenceExists();

        return count($this->connection->select($sql, [$sequence])) > 0;
    }

    /**
     * Create a new sequence on the schema
     *
     * @param string $sequence
     * @param \Closure $callback
     * @return void
     */
    public function createSequence($sequence, Closure $callback = null)
    {
        $seqprint = $this->createSequenceBlueprint($sequence);

        $seqprint->create();

        if ($callback) {
            $callback($seqprint);
        }

        $this->buildSequence($seqprint);
    }

    /**
     * Drop a sequence from the schema.
     *
     * @param string $sequence
     * @param \Closure $callback
     */
    public function dropSequence($sequence)
    {
        $seqprint = $this->createSequenceBlueprint($sequence);

        $seqprint->drop();

        $this->buildSequence($seqprint);
    }

    /**
     * Modify a sequence on the schema.
     *
     * @param string $sequence
     * @param \Closure $callback
     * @return void
     */
    public function sequence($sequence, Closure $callback)
    {
        $this->buildSequence($this->createSequenceBlueprint($sequence, $callback));
    }

    /**
     * Drop a sequence from the schema if it exists.
     *
     * @param string $sequence
     * @return void
     */
    public function dropSequenceIfExists($sequence)
    {
        $blueprint = $this->createSequenceBlueprint($sequence);

        $blueprint->dropIfExists();

        $this->buildSequence($blueprint);
    }

}
